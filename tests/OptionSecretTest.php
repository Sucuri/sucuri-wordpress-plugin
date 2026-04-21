<?php declare(strict_types=1);

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class OptionSecretTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $options = array();
    private $autoload = array();
    private $cache = array();
    private $settingsFile = '';
    private $originalSettingsContent = '';
    /** @var string Temporary wp-config.php used to verify writePluginSaltToConfig(). */
    private $tempWpConfig = '';
    /** @var bool When true the update_option mock returns false (simulates DB failure). */
    private $updateOptionFails = false;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $this->settingsFile = SucuriScanOption::optionsFilePath();
        $this->originalSettingsContent = file_exists($this->settingsFile)
            ? (string) file_get_contents($this->settingsFile)
            : '';

        // Create a minimal wp-config.php in the ABSPATH directory so that
        // writePluginSaltToConfig() can write to it during tests.
        $this->tempWpConfig = rtrim(ABSPATH, '/') . '/wp-config.php';
        file_put_contents(
            $this->tempWpConfig,
            "<?php\n/* That's all, stop editing! Happy publishing. */\n"
        );

        $self = $this;

        Functions\when('__')->returnArg();
        Functions\when('wp_salt')->justReturn('test-salt');
        Functions\when('wp_strip_all_tags')->alias(function ($text) {
            return $text;
        });
        Functions\when('sucuriscan_lastlogins_datastore_exists')->justReturn(true);
        Functions\when('get_home_path')->justReturn('/');

        Functions\when('wp_cache_get')->alias(function ($key, $group = '') use ($self) {
            $cacheKey = $group . ':' . $key;
            return array_key_exists($cacheKey, $self->cache) ? $self->cache[$cacheKey] : false;
        });

        Functions\when('wp_cache_set')->alias(function ($key, $value, $group = '') use ($self) {
            $cacheKey = $group . ':' . $key;
            $self->cache[$cacheKey] = $value;
            return true;
        });

        Functions\when('wp_cache_delete')->alias(function ($key, $group = '') use ($self) {
            $cacheKey = $group . ':' . $key;
            unset($self->cache[$cacheKey]);
            return true;
        });

        Functions\when('get_option')->alias(function ($option, $default = false) use ($self) {
            return array_key_exists($option, $self->options) ? $self->options[$option] : $default;
        });

        Functions\when('update_option')->alias(function ($option, $value, $autoload = null) use ($self) {
            if ($self->updateOptionFails) {
                return false;
            }
            $self->options[$option] = $value;
            $self->autoload[$option] = $autoload;
            return true;
        });

        Functions\when('delete_option')->alias(function ($option) use ($self) {
            unset($self->options[$option]);
            unset($self->autoload[$option]);
            return true;
        });
    }

    protected function tearDown(): void
    {
        if ($this->settingsFile) {
            file_put_contents($this->settingsFile, $this->originalSettingsContent);
        }

        if ($this->tempWpConfig && file_exists($this->tempWpConfig)) {
            unlink($this->tempWpConfig);
        }

        Monkey\tearDown();
        parent::tearDown();
    }

    public function testMigratesWafKeyFromFileToSecretStorage()
    {
        $key = str_repeat('a', 32) . '/' . str_repeat('b', 32);
        $this->writeSettingsFile(array('sucuriscan_cloudproxy_apikey' => $key));

        $value = SucuriScanOption::getOption(':cloudproxy_apikey');

        $this->assertSame($key, $value);
        $this->assertArrayHasKey('sucuriscan_secret_cloudproxy_apikey_enc', $this->options);
        $this->assertArrayNotHasKey('sucuriscan_secret_cloudproxy_apikey', $this->options);

        $data = $this->readSettingsFile();
        $this->assertArrayNotHasKey('sucuriscan_cloudproxy_apikey', $data);
    }

    public function testMigrationKeepsFileKeyIfWriteFails()
    {
        $key = str_repeat('a', 32) . '/' . str_repeat('b', 32);
        $this->writeSettingsFile(array('sucuriscan_cloudproxy_apikey' => $key));

        // Simulate a complete DB failure so update_option() always returns false.
        $this->updateOptionFails = true;
        $value = SucuriScanOption::getOption(':cloudproxy_apikey');
        $this->updateOptionFails = false;

        // The value is still returned for the current request.
        $this->assertSame($key, $value);

        // The key must NOT have been deleted from the settings file.
        $data = $this->readSettingsFile();
        $this->assertArrayHasKey('sucuriscan_cloudproxy_apikey', $data);

        // Nothing must have been written to wp_options.
        $this->assertArrayNotHasKey('sucuriscan_secret_cloudproxy_apikey_enc', $this->options);
        $this->assertArrayNotHasKey('sucuriscan_secret_cloudproxy_apikey', $this->options);
    }

    public function testSecretOptionTakesPrecedenceOverFile()
    {
        $secret = str_repeat('c', 32) . '/' . str_repeat('d', 32);
        $fileKey = str_repeat('e', 32) . '/' . str_repeat('f', 32);

        $payload = $this->encryptV1Payload($secret);
        $this->options['sucuriscan_secret_cloudproxy_apikey_enc'] = $payload;
        $this->writeSettingsFile(array('sucuriscan_cloudproxy_apikey' => $fileKey));

        $value = SucuriScanOption::getOption(':cloudproxy_apikey');

        $this->assertSame($secret, $value);
        $data = $this->readSettingsFile();
        $this->assertSame($fileKey, $data['sucuriscan_cloudproxy_apikey']);
    }

    public function testDecryptFailureSetsNoticeFlag()
    {
        $payload = array(
            'v' => 1,
            'alg' => 'aes-256-gcm',
            'iv' => 'YmFk',
            'tag' => 'YmFk',
            'ct' => 'YmFk',
        );

        $this->options['sucuriscan_secret_cloudproxy_apikey_enc'] = $payload;
        $this->options['sucuriscan_secret_cloudproxy_apikey'] = 'legacy-secret';

        $value = SucuriScanOption::getOption(':cloudproxy_apikey');

        $this->assertFalse($value);
        $this->assertArrayHasKey('sucuriscan_waf_key_decrypt_error', $this->options);
        $this->assertArrayHasKey('ts', $this->options['sucuriscan_waf_key_decrypt_error']);
    }

    public function testDecryptSuccessClearsNoticeFlag()
    {
        $payload = $this->encryptV1Payload('secret');

        $this->options['sucuriscan_waf_key_decrypt_error'] = array('ts' => time());
        $this->options['sucuriscan_secret_cloudproxy_apikey_enc'] = $payload;

        $value = SucuriScanOption::getOption(':cloudproxy_apikey');

        $this->assertSame('secret', $value);
        $this->assertArrayNotHasKey('sucuriscan_waf_key_decrypt_error', $this->options);
    }

    public function testUpdateOptionClearsNoticeFlag()
    {
        $this->options['sucuriscan_waf_key_decrypt_error'] = array('ts' => time());

        $value = str_repeat('g', 32) . '/' . str_repeat('h', 32);
        SucuriScanOption::updateOption(':cloudproxy_apikey', $value);

        $this->assertArrayNotHasKey('sucuriscan_waf_key_decrypt_error', $this->options);
    }

    public function testPlaintextMigrationUsesPlugSalt()
    {
        // Plaintext stored in the intermediate secret option (not yet encrypted).
        $key = str_repeat('p', 32) . '/' . str_repeat('q', 32);
        $this->options['sucuriscan_secret_cloudproxy_apikey'] = $key;

        $value = SucuriScanOption::getOption(':cloudproxy_apikey');

        $this->assertSame($key, $value);

        // Must be stored as an encrypted v:2 payload (SUCURI_PLUG_* scheme).
        $this->assertArrayHasKey('sucuriscan_secret_cloudproxy_apikey_enc', $this->options);
        $payload = $this->options['sucuriscan_secret_cloudproxy_apikey_enc'];
        $this->assertIsArray($payload);
        $this->assertSame(2, $payload['v']);
    }

    public function testPlugSaltWrittenToWpConfig()
    {
        // Trigger a first-run initialisation by encrypting a key.
        $key = str_repeat('r', 32) . '/' . str_repeat('s', 32);
        SucuriScanOption::updateOption(':cloudproxy_apikey', $key);

        // The temp wp-config.php must now contain define() statements for both constants.
        $config = file_get_contents($this->tempWpConfig);
        $this->assertStringContainsString("define('SUCURI_PLUG_KEY'", $config);
        $this->assertStringContainsString("define('SUCURI_PLUG_SALT'", $config);

        // Both constant values must be 64-char hex strings.
        preg_match("/define\('SUCURI_PLUG_KEY',\s*'([0-9a-f]{64})'\)/", $config, $keyMatch);
        preg_match("/define\('SUCURI_PLUG_SALT',\s*'([0-9a-f]{64})'\)/", $config, $saltMatch);
        $this->assertNotEmpty($keyMatch[1] ?? '');
        $this->assertNotEmpty($saltMatch[1] ?? '');
    }

    public function testPlugSaltInsertedBeforeStopMarker()
    {
        $key = str_repeat('r', 32) . '/' . str_repeat('s', 32);
        SucuriScanOption::updateOption(':cloudproxy_apikey', $key);

        $config = file_get_contents($this->tempWpConfig);
        $plugPos = strpos($config, 'SUCURI_PLUG_KEY');
        $stopPos = strpos($config, "/* That's all");

        $this->assertNotFalse($plugPos);
        $this->assertNotFalse($stopPos);
        $this->assertLessThan($stopPos, $plugPos, 'SUCURI_PLUG_KEY must appear before the stop-editing marker');
    }

    /**
     * wp-config.php files for non-English WordPress installations use a
     * translated stop-editing comment that does not match the English string.
     * The plugin must fall back to the ABSPATH guard and still insert the
     * constants inside PHP context (i.e. before the ABSPATH line, not at the
     * end of the file where a closing PHP tag could be lurking).
     */
    public function testPlugSaltInsertedBeforeAbspathGuardWhenNoEnglishMarker()
    {
        // Italian-style wp-config.php: no English "That's all" comment.
        $italianConfig = "<?php\n"
            . "define('DB_NAME', 'mydb');\n"
            . "/** Non modificare oltre questo punto. */\n"
            . "if ( ! defined( 'ABSPATH' ) ) {\n"
            . "    define( 'ABSPATH', __DIR__ . '/' );\n"
            . "}\n"
            . "require_once ABSPATH . 'wp-settings.php';\n";

        file_put_contents($this->tempWpConfig, $italianConfig);

        $key = str_repeat('x', 32) . '/' . str_repeat('y', 32);
        SucuriScanOption::updateOption(':cloudproxy_apikey', $key);

        $config = file_get_contents($this->tempWpConfig);

        $plugPos   = strpos($config, 'SUCURI_PLUG_KEY');
        $abspathPos = strpos($config, "if ( ! defined( 'ABSPATH' )");

        $this->assertNotFalse($plugPos, 'SUCURI_PLUG_KEY must be written');
        $this->assertNotFalse($abspathPos);
        $this->assertLessThan(
            $abspathPos,
            $plugPos,
            'SUCURI_PLUG_KEY must appear before the ABSPATH guard'
        );

        // The defines must be inside PHP context: no PHP-close tag before the block
        // unless a PHP-open tag appears between that close tag and the block.
        $phpCloseTag = '?' . '>'; // split to avoid triggering PHP close-tag parsing
        $beforeBlock = substr($config, 0, $plugPos);
        $phpClosePos = strrpos($beforeBlock, $phpCloseTag);
        $phpOpenAfterClose = $phpClosePos !== false
            ? strpos($config, '<' . '?php', $phpClosePos)
            : false;
        $this->assertTrue(
            $phpClosePos === false || ($phpOpenAfterClose !== false && $phpOpenAfterClose < $plugPos),
            'SUCURI_PLUG_KEY block must be inside a PHP open tag'
        );
    }

    /**
     * Some wp-config.php files (especially those generated by hosting-panel
     * installers) end with a PHP close tag.  The plugin must not append the
     * constant block after that tag, because PHP would then emit the define()
     * lines as literal HTML text on every page load.
     */
    public function testPlugSaltNotInsertedAfterClosingPhpTag()
    {
        $phpClose = '?' . '>'; // split so this file's own PHP mode is not closed

        // Minimal wp-config.php that ends with a PHP close tag.
        $configWithCloseTag = '<' . "?php\n"
            . "define('DB_NAME', 'mydb');\n"
            . "if ( ! defined( 'ABSPATH' ) ) {\n"
            . "    define( 'ABSPATH', __DIR__ . '/' );\n"
            . "}\n"
            . "require_once ABSPATH . 'wp-settings.php';\n"
            . $phpClose . "\n";

        file_put_contents($this->tempWpConfig, $configWithCloseTag);

        $key = str_repeat('m', 32) . '/' . str_repeat('n', 32);
        SucuriScanOption::updateOption(':cloudproxy_apikey', $key);

        $config = file_get_contents($this->tempWpConfig);

        $plugPos     = strpos($config, 'SUCURI_PLUG_KEY');
        $closeTagPos = strrpos($config, $phpClose);

        $this->assertNotFalse($plugPos, 'SUCURI_PLUG_KEY must be written');
        // The block must appear BEFORE the PHP close tag.
        if ($closeTagPos !== false) {
            $this->assertLessThan(
                $closeTagPos,
                $plugPos,
                'SUCURI_PLUG_KEY must not be placed after the PHP close tag'
            );
        }
    }

    /**
     * Simulates a site already broken by the old bug: SUCURI_PLUG_KEY and
     * SUCURI_PLUG_SALT exist in wp-config.php but after a PHP close tag, so
     * they were never executed by PHP and were emitted as plain HTML text.
     *
     * maybeHealMisplacedPluginSalt() must detect this state, remove the
     * misplaced lines via regeneratePluginSaltRaw(), and rewrite them inside
     * a valid PHP block.
     */
    public function testMaybeHealMisplacedPluginSaltFixesMisplacedConstants()
    {
        if (defined('SUCURI_PLUG_KEY') || defined('SUCURI_PLUG_SALT')) {
            $this->markTestSkipped('SUCURI_PLUG_* constants are already defined in this PHP process');
        }

        $phpClose = '?' . '>'; // split to avoid closing PHP mode in this file
        $fakeKey  = str_repeat('a', 64);
        $fakeSalt = str_repeat('b', 64);

        // Build a broken wp-config.php: constants sit after the PHP close tag.
        $brokenConfig = '<' . "?php\n"
            . "define('DB_NAME', 'test');\n"
            . "require_once ABSPATH . 'wp-settings.php';\n"
            . $phpClose . "\n"
            . "define('SUCURI_PLUG_KEY',  '" . $fakeKey  . "');\n"
            . "define('SUCURI_PLUG_SALT', '" . $fakeSalt . "');\n";

        file_put_contents($this->tempWpConfig, $brokenConfig);

        // Confirm precondition: constants exist in the file but are NOT defined.
        $before = file_get_contents($this->tempWpConfig);
        $this->assertStringContainsString('SUCURI_PLUG_KEY', $before);
        $this->assertFalse(defined('SUCURI_PLUG_KEY'));

        SucuriScanOption::maybeHealMisplacedPluginSalt();

        $config = file_get_contents($this->tempWpConfig);

        // The one-time flag must be set after a successful fix.
        $this->assertTrue(
            (bool) ($this->options['sucuriscan_plug_salt_position_healed'] ?? false),
            'Healed flag must be set after successful fix'
        );

        // Constants must still be present (rewritten by regeneratePluginSaltRaw).
        $this->assertStringContainsString("define('SUCURI_PLUG_KEY'", $config);
        $this->assertStringContainsString("define('SUCURI_PLUG_SALT'", $config);

        // The fake values from the broken config must be gone (regenerated).
        $this->assertStringNotContainsString($fakeKey, $config);

        // Constants must appear BEFORE the PHP close tag.
        $plugPos     = strpos($config, 'SUCURI_PLUG_KEY');
        $closeTagPos = strrpos($config, $phpClose);
        if ($closeTagPos !== false) {
            $this->assertLessThan(
                $closeTagPos,
                $plugPos,
                'Constants must be inside PHP context (before close tag) after healing'
            );
        }
    }

    /**
     * When the one-time flag is already set, maybeHealMisplacedPluginSalt()
     * must return without touching the file (idempotent fast path).
     */
    public function testMaybeHealMisplacedPluginSaltSkipsWhenAlreadyHealed()
    {
        $this->options['sucuriscan_plug_salt_position_healed'] = true;

        // Write a wp-config.php with no SUCURI_PLUG lines at all.
        $originalContent = '<' . "?php\ndefine('DB_NAME', 'test');\n";
        file_put_contents($this->tempWpConfig, $originalContent);

        SucuriScanOption::maybeHealMisplacedPluginSalt();

        // File must be untouched.
        $this->assertSame($originalContent, file_get_contents($this->tempWpConfig));
    }

    public function testPlugSaltReplacedNotDuplicatedOnReSave()
    {
        // Each save regenerates: old lines removed, new lines written.
        // After two saves there must still be exactly one occurrence of each constant.
        $key = str_repeat('r', 32) . '/' . str_repeat('s', 32);
        SucuriScanOption::updateOption(':cloudproxy_apikey', $key);
        SucuriScanOption::updateOption(':cloudproxy_apikey', $key);

        $config = file_get_contents($this->tempWpConfig);
        $this->assertSame(1, substr_count($config, 'SUCURI_PLUG_KEY'));
        $this->assertSame(1, substr_count($config, 'SUCURI_PLUG_SALT'));
    }

    public function testReSaveReEncryptsWithNewSalt()
    {
        $key = str_repeat('r', 32) . '/' . str_repeat('s', 32);

        // First save.
        SucuriScanOption::updateOption(':cloudproxy_apikey', $key);
        $config1 = file_get_contents($this->tempWpConfig);
        $payload1 = $this->options['sucuriscan_secret_cloudproxy_apikey_enc'];

        // Extract constant values from the config file after first save.
        preg_match("/define\('SUCURI_PLUG_KEY',\s*'([0-9a-f]{64})'\)/", $config1, $km1);
        preg_match("/define\('SUCURI_PLUG_SALT',\s*'([0-9a-f]{64})'\)/", $config1, $sm1);

        // Second save — regenerates salt.
        SucuriScanOption::updateOption(':cloudproxy_apikey', $key);
        $config2 = file_get_contents($this->tempWpConfig);
        $payload2 = $this->options['sucuriscan_secret_cloudproxy_apikey_enc'];

        preg_match("/define\('SUCURI_PLUG_KEY',\s*'([0-9a-f]{64})'\)/", $config2, $km2);
        preg_match("/define\('SUCURI_PLUG_SALT',\s*'([0-9a-f]{64})'\)/", $config2, $sm2);

        // Both saves produced valid v:2 payloads.
        $this->assertSame(2, $payload1['v']);
        $this->assertSame(2, $payload2['v']);

        // The ciphertext must differ between saves (different IVs and keys).
        $this->assertNotSame($payload1['ct'], $payload2['ct']);

        // Since both saves derive from the same wp_salt('auth') = 'test-salt',
        // the written constant values must be identical across saves.
        $this->assertSame($km1[1] ?? 'a', $km2[1] ?? 'b');
        $this->assertSame($sm1[1] ?? 'a', $sm2[1] ?? 'b');
    }

    public function testReSavedPayloadCanBeDecryptedAfterSaltRegeneration()
    {
        $key = str_repeat('r', 32) . '/' . str_repeat('s', 32);

        // Simulate a corrupt/stale payload by placing a bad one in the store first.
        $bad_payload = array(
            'v' => 2,
            'alg' => 'aes-256-gcm',
            'iv' => base64_encode(str_repeat("\x00", 12)),
            'tag' => base64_encode(str_repeat("\x00", 16)),
            'ct' => base64_encode('garbage'),
        );
        $this->options['sucuriscan_secret_cloudproxy_apikey_enc'] = $bad_payload;

        // Re-save the key — should regenerate salt and store a fresh payload.
        SucuriScanOption::updateOption(':cloudproxy_apikey', $key);

        // The freshly stored payload must be readable via the normal read path.
        // Because constants are still set to old values in this PHP process, we
        // decrypt manually using the values now in wp-config.php.
        $config = file_get_contents($this->tempWpConfig);
        preg_match("/define\('SUCURI_PLUG_KEY',\s*'([0-9a-f]{64})'\)/", $config, $km);
        preg_match("/define\('SUCURI_PLUG_SALT',\s*'([0-9a-f]{64})'\)/", $config, $sm);

        $plug_raw = ($km[1] ?? '') . ($sm[1] ?? '');
        $enc_key = substr(hash_hmac('sha256', 'sucuriscan_waf_key_v1', $plug_raw, true), 0, 32);
        $stored = $this->options['sucuriscan_secret_cloudproxy_apikey_enc'];

        $iv = base64_decode($stored['iv']);
        $tag = base64_decode($stored['tag']);
        $ct = base64_decode($stored['ct']);
        $decrypted = openssl_decrypt($ct, 'aes-256-gcm', $enc_key, OPENSSL_RAW_DATA, $iv, $tag);

        $this->assertSame($key, $decrypted);
    }

    public function testV1PayloadMigratedToV2OnRead()
    {
        // Store a v:1 payload (AUTH_SALT scheme).
        $secret = str_repeat('t', 32) . '/' . str_repeat('u', 32);
        $payload = $this->encryptV1Payload($secret);
        $this->options['sucuriscan_secret_cloudproxy_apikey_enc'] = $payload;

        $value = SucuriScanOption::getOption(':cloudproxy_apikey');

        $this->assertSame($secret, $value);

        // The stored payload must now be v:2.
        $stored = $this->options['sucuriscan_secret_cloudproxy_apikey_enc'];
        $this->assertIsArray($stored);
        $this->assertSame(2, $stored['v']);
    }

    public function testV2PayloadDecryptedWithPlugSalt()
    {
        // Store a v:2 payload encrypted with SUCURI_PLUG_* (derived from 'test-salt').
        $secret = str_repeat('v', 32) . '/' . str_repeat('w', 32);
        $payload = $this->encryptV2Payload($secret);
        $this->options['sucuriscan_secret_cloudproxy_apikey_enc'] = $payload;

        $value = SucuriScanOption::getOption(':cloudproxy_apikey');

        $this->assertSame($secret, $value);
        // Payload stays as v:2 (no migration needed).
        $this->assertSame(2, $this->options['sucuriscan_secret_cloudproxy_apikey_enc']['v']);
    }

    public function testV1DecryptionUsesAuthSaltNotPlugSalt()
    {
        // A v:1 payload must always decrypt with the AUTH_SALT key, even though
        // new encryptions use the SUCURI_PLUG_* key (which is different because
        // it goes through an extra HMAC round before the final key derivation).
        $secret = str_repeat('z', 32) . '/' . str_repeat('a', 32);
        $payload = $this->encryptV1Payload($secret);
        $this->options['sucuriscan_secret_cloudproxy_apikey_enc'] = $payload;

        $value = SucuriScanOption::getOption(':cloudproxy_apikey');

        $this->assertSame($secret, $value);

        // Confirm AUTH_SALT key and SUCURI_PLUG_* key are indeed different,
        // so this test is non-trivial.
        $auth_key = substr(hash_hmac('sha256', 'sucuriscan_waf_key_v1', 'test-salt', true), 0, 32);
        $plug_raw = hash_hmac('sha256', 'sucuri_plug_key_v1', 'test-salt')
            . hash_hmac('sha256', 'sucuri_plug_salt_v1', 'test-salt');
        $plug_key = substr(hash_hmac('sha256', 'sucuriscan_waf_key_v1', $plug_raw, true), 0, 32);
        $this->assertNotSame($auth_key, $plug_key);
    }

    /**
     * Test A: stop marker present but outside PHP context — defines must still
     * land inside a valid PHP block.
     */
    public function testStopMarkerOutsidePhpInsertsDefinesInsidePhp()
    {
        $phpClose = '?' . '>';
        // Config where the stop marker itself sits outside PHP (after a close tag).
        $config = '<' . "?php\ndefine('DB_NAME', 'test');\n"
            . $phpClose . "\n"
            . "/* That's all, stop editing! Happy publishing. */\n";

        file_put_contents($this->tempWpConfig, $config);

        $key = str_repeat('c', 32) . '/' . str_repeat('d', 32);
        SucuriScanOption::updateOption(':cloudproxy_apikey', $key);

        $result = file_get_contents($this->tempWpConfig);
        $plugPos = strpos($result, 'SUCURI_PLUG_KEY');
        $this->assertNotFalse($plugPos, 'SUCURI_PLUG_KEY must be written');

        // The define must be inside PHP context — verify by tokenizing.
        $tokens = token_get_all($result);
        $pos = 0;
        $inPhp = false;
        $definePos = null;
        foreach ($tokens as $t) {
            $text = is_array($t) ? $t[1] : $t;
            $len  = strlen($text);
            if (is_array($t)) {
                if ($t[0] === T_OPEN_TAG || $t[0] === T_OPEN_TAG_WITH_ECHO) {
                    $inPhp = true;
                } elseif ($t[0] === T_CLOSE_TAG) {
                    $inPhp = false;
                }
            }
            if ($definePos === null && $pos + $len > $plugPos) {
                $definePos = $inPhp;
            }
            $pos += $len;
        }
        $this->assertTrue($definePos, 'SUCURI_PLUG_KEY define must be inside PHP context');
    }

    /**
     * Test B: when wp-config.php is not writable, the healed flag must NOT be set.
     */
    public function testHealingFailureDoesNotSetHealedFlag()
    {
        if (defined('SUCURI_PLUG_KEY') || defined('SUCURI_PLUG_SALT')) {
            $this->markTestSkipped('SUCURI_PLUG_* constants are already defined');
        }
        if (posix_getuid() === 0) {
            $this->markTestSkipped('Running as root — chmod restriction is ineffective');
        }

        $phpClose = '?' . '>';
        $fakeKey  = str_repeat('e', 64);
        $fakeSalt = str_repeat('f', 64);

        // Broken config: defines outside PHP.
        $brokenConfig = '<' . "?php\ndefine('DB_NAME', 'test');\n"
            . $phpClose . "\n"
            . "define('SUCURI_PLUG_KEY',  '" . $fakeKey  . "');\n"
            . "define('SUCURI_PLUG_SALT', '" . $fakeSalt . "');\n";

        file_put_contents($this->tempWpConfig, $brokenConfig);
        chmod($this->tempWpConfig, 0444); // read-only

        try {
            SucuriScanOption::maybeHealMisplacedPluginSalt();
        } finally {
            chmod($this->tempWpConfig, 0644); // restore for tearDown
        }

        $this->assertArrayNotHasKey(
            'sucuriscan_plug_salt_position_healed',
            $this->options,
            'Healed flag must NOT be set when the file write fails'
        );
    }

    /**
     * Test C: file ends with a PHP close tag and trailing text — fallback path
     * must insert defines inside PHP context.
     */
    public function testEofFallbackWithTrailingCloseTagInsertsDefinesInsidePhp()
    {
        $phpClose = '?' . '>';
        // No stop marker, no ABSPATH guard, no wp-settings.php — pure fallback.
        $config = '<' . "?php\ndefine('DB_NAME', 'test');\n"
            . $phpClose . "\n"
            . "Some trailing plain text.\n";

        file_put_contents($this->tempWpConfig, $config);

        $key = str_repeat('g', 32) . '/' . str_repeat('h', 32);
        SucuriScanOption::updateOption(':cloudproxy_apikey', $key);

        $result = file_get_contents($this->tempWpConfig);
        $plugPos = strpos($result, 'SUCURI_PLUG_KEY');
        $this->assertNotFalse($plugPos, 'SUCURI_PLUG_KEY must be written');

        $tokens = token_get_all($result);
        $pos = 0;
        $inPhp = false;
        $definePos = null;
        foreach ($tokens as $t) {
            $text = is_array($t) ? $t[1] : $t;
            $len  = strlen($text);
            if (is_array($t)) {
                if ($t[0] === T_OPEN_TAG || $t[0] === T_OPEN_TAG_WITH_ECHO) {
                    $inPhp = true;
                } elseif ($t[0] === T_CLOSE_TAG) {
                    $inPhp = false;
                }
            }
            if ($definePos === null && $pos + $len > $plugPos) {
                $definePos = $inPhp;
            }
            $pos += $len;
        }
        $this->assertTrue($definePos, 'SUCURI_PLUG_KEY define must be inside PHP context');
    }

    /**
     * Test D: mixed state — one define inside PHP, one outside. Healing must
     * produce a fully corrected config and set the healed flag.
     */
    public function testMixedStateOneInsideOneOutsideIsFullyHealed()
    {
        if (defined('SUCURI_PLUG_KEY') || defined('SUCURI_PLUG_SALT')) {
            $this->markTestSkipped('SUCURI_PLUG_* constants are already defined');
        }

        $phpClose = '?' . '>';
        $fakeKey  = str_repeat('i', 64);
        $fakeSalt = str_repeat('j', 64);

        // SUCURI_PLUG_KEY inside PHP, SUCURI_PLUG_SALT outside PHP.
        $mixedConfig = '<' . "?php\n"
            . "define('SUCURI_PLUG_KEY',  '" . $fakeKey  . "');\n"
            . "define('DB_NAME', 'test');\n"
            . $phpClose . "\n"
            . "define('SUCURI_PLUG_SALT', '" . $fakeSalt . "');\n";

        file_put_contents($this->tempWpConfig, $mixedConfig);

        SucuriScanOption::maybeHealMisplacedPluginSalt();

        $this->assertTrue(
            (bool) ($this->options['sucuriscan_plug_salt_position_healed'] ?? false),
            'Healed flag must be set after mixed-state fix'
        );

        $config = file_get_contents($this->tempWpConfig);
        $this->assertStringContainsString("define('SUCURI_PLUG_KEY'", $config);
        $this->assertStringContainsString("define('SUCURI_PLUG_SALT'", $config);
        $this->assertSame(1, substr_count($config, 'SUCURI_PLUG_KEY'), 'Exactly one KEY define expected');
        $this->assertSame(1, substr_count($config, 'SUCURI_PLUG_SALT'), 'Exactly one SALT define expected');
    }

    private function writeSettingsFile(array $options)
    {
        $content = "<?php exit(0); ?>\n";
        $content .= json_encode($options) . "\n";
        file_put_contents($this->settingsFile, $content);

        $this->cache = array();
    }

    private function readSettingsFile(): array
    {
        $content = (string) file_get_contents($this->settingsFile);
        $lines = explode("\n", $content, 2);
        if (count($lines) < 2) {
            return array();
        }

        $data = json_decode($lines[1], true);
        return is_array($data) ? $data : array();
    }

    /**
     * v:1 — AUTH_SALT scheme: key derived from wp_salt('auth') = 'test-salt'.
     */
    private function encryptV1Payload($plaintext)
    {
        $key = substr(hash_hmac('sha256', 'sucuriscan_waf_key_v1', 'test-salt', true), 0, 32);
        $iv = str_repeat("\x01", 12);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

        return array(
            'v' => 1,
            'alg' => 'aes-256-gcm',
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'ct' => base64_encode($ciphertext),
        );
    }

    /**
     * v:2 — SUCURI_PLUG_* scheme: key derived from plug_key + plug_salt, which are
     * themselves HMAC-derived from wp_salt('auth') = 'test-salt'.
     */
    private function encryptV2Payload($plaintext)
    {
        $auth_raw = 'test-salt';
        $plug_key = hash_hmac('sha256', 'sucuri_plug_key_v1', $auth_raw);
        $plug_salt = hash_hmac('sha256', 'sucuri_plug_salt_v1', $auth_raw);
        $plug_raw = $plug_key . $plug_salt;

        $key = substr(hash_hmac('sha256', 'sucuriscan_waf_key_v1', $plug_raw, true), 0, 32);
        $iv = str_repeat("\x02", 12);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

        return array(
            'v' => 2,
            'alg' => 'aes-256-gcm',
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'ct' => base64_encode($ciphertext),
        );
    }
}
