<?php

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

require_once __DIR__ . '/autoload.php';

if (!class_exists('SucuriScanTOTP')) {
    require BASE_DIR . '/src/totp.core.php';
}

if (!class_exists('WP_User')) {
    class WP_User
    {
        public $ID;
        public $user_login = '';
        public function __construct($id)
        {
            $this->ID = (int) $id;
        }
    }
}

/**
 * Tests focused on SucuriScanTwoFactor enforcement logic and authenticate interception.
 */
final class TwoFactorTest extends TestCase
{
    /** @var array<string, string> */
    private $fixtureSnapshots = [];

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // A backup-code login triggers real SucuriScanOption/SucuriScanEvent
        // reads+writes, which touch these fixture files on disk; snapshot
        // them so tearDown() can restore them and tests don't leave the
        // checked-in fixtures dirty.
        foreach (['sucuri-auditqueue.php', 'sucuri-settings.php'] as $fixture) {
            $path = BASE_DIR . '/tests/fixtures/' . $fixture;

            if (file_exists($path)) {
                $this->fixtureSnapshots[$path] = file_get_contents($path);
            }
        }

        Functions\when('wp_validate_redirect')->returnArg(1);
        Functions\when('wp_login_url')->justReturn('https://example.com/wp-login.php');
        Functions\when('add_query_arg')->alias(function ($args, $url) {
            if (is_array($args)) {
                $qs = http_build_query($args);
                return $url . (strpos($url, '?') === false ? '?' : '&') . $qs;
            }
            return $url;
        });
        Functions\when('trailingslashit')->alias(fn($p) => rtrim($p, '/') . '/');
        Functions\when('esc_html__')->returnArg(1);
        Functions\when('__')->returnArg(1);
        Functions\when('esc_html')->returnArg(1);
        Functions\when('wp_nonce_field')->justReturn('<input type="hidden" name="_wpnonce" value="nonce" />');
        Functions\when('sanitize_text_field')->alias(fn($v) => is_string($v) ? $v : '');

        // Auth / redirect related
        Functions\when('wp_safe_redirect')->justReturn(true);
        Functions\when('wp_set_current_user')->justReturn(true);
        Functions\when('wp_set_auth_cookie')->justReturn(true);
        Functions\when('check_admin_referer')->justReturn(true);
        Functions\when('login_header')->justReturn(true);
        Functions\when('login_footer')->justReturn(true);

        // Script enqueue related
        Functions\when('wp_script_is')->justReturn(false);
        Functions\when('wp_register_script')->justReturn(true);
        Functions\when('wp_enqueue_script')->justReturn(true);
        Functions\when('is_admin')->justReturn(true);
        Functions\when('get_current_user_id')->justReturn(1);

        // Transients & cache
        Functions\when('set_transient')->justReturn(true);
        Functions\when('get_transient')->justReturn(false);
        Functions\when('delete_transient')->justReturn(true);
        Functions\when('wp_cache_get')->justReturn(false);
        Functions\when('wp_cache_set')->justReturn(true);
        Functions\when('wp_cache_delete')->justReturn(true);

        // User / meta
        Functions\when('wp_generate_password')->justReturn('TESTTOKENSTRINGWITHLENGTHEXPECTED0123456789ABCDEF');
        Functions\when('get_user_meta')->justReturn(''); // ensure no secret => setup path
        Functions\when('update_user_meta')->justReturn(true);
        Functions\when('add_user_meta')->justReturn(true);
        Functions\when('get_user_by')->alias(fn($field, $val) => (object) ['ID' => (int) $val, 'user_login' => 'u' . $val, 'user_email' => 'u' . $val . '@example.com']);
    }

    protected function tearDown(): void
    {
        unset($_GET['token'], $_POST['token'], $_REQUEST['token'], $_POST['sucuriscan_totp_code'], $_SERVER['REQUEST_METHOD'], $_SERVER['HTTP_USER_AGENT']);

        foreach ($this->fixtureSnapshots as $path => $contents) {
            file_put_contents($path, $contents);
        }

        Monkey\tearDown();
        parent::tearDown();
    }

    public function testEnforcementDisabledMode(): void
    {
        SucuriScanOption::updateOption(':twofactor_mode', 'disabled');
        $ref = new ReflectionClass(SucuriScanTwoFactor::class);
        $method = $ref->getMethod('is_enforced_for_user');
        $method->setAccessible(true);
        $this->assertFalse($method->invoke(null, 5));
    }

    public function testEnforcementAllUsersMode(): void
    {
        SucuriScanOption::updateOption(':twofactor_mode', 'all_users');
        $ref = new ReflectionClass(SucuriScanTwoFactor::class);
        $method = $ref->getMethod('is_enforced_for_user');
        $method->setAccessible(true);
        $this->assertTrue($method->invoke(null, 5));
    }

    public function testEnforcementSelectedUsersPositive(): void
    {
        SucuriScanOption::updateOption(':twofactor_mode', 'selected_users');
        SucuriScanOption::updateOption(':twofactor_users', [10, 20, 30]);
        $ref = new ReflectionClass(SucuriScanTwoFactor::class);
        $method = $ref->getMethod('is_enforced_for_user');
        $method->setAccessible(true);
        $this->assertTrue($method->invoke(null, 20));
    }

    public function testEnforcementSelectedUsersNegative(): void
    {
        SucuriScanOption::updateOption(':twofactor_mode', 'selected_users');
        SucuriScanOption::updateOption(':twofactor_users', [10, 20, 30]);
        $ref = new ReflectionClass(SucuriScanTwoFactor::class);
        $method = $ref->getMethod('is_enforced_for_user');
        $method->setAccessible(true);
        $this->assertFalse($method->invoke(null, 999));
    }

    public function testEnforcementSelectedUsersTooLargeListFailsClosed(): void
    {
        SucuriScanOption::updateOption(':twofactor_mode', 'selected_users');
        // Build list > 25000 unique ids; suspicious! ;)
        $big = range(1, 25050);
        SucuriScanOption::updateOption(':twofactor_users', $big);
        $ref = new ReflectionClass(SucuriScanTwoFactor::class);
        $method = $ref->getMethod('is_enforced_for_user');
        $method->setAccessible(true);
        $this->assertFalse($method->invoke(null, 123));
    }

    public function testAuthenticateBypassesWhenNotEnforced(): void
    {
        SucuriScanOption::updateOption(':twofactor_mode', 'disabled');
        $wpUser = (object) ['ID' => 42];
        $result = SucuriScanTwoFactor::authenticate($wpUser, 'user42', 'pass');
        $this->assertSame($wpUser, $result);
    }

    public function testAuthenticateRedirectsForSetupWhenEnforcedWithoutSecret(): void
    {
        SucuriScanOption::updateOption(':twofactor_mode', 'selected_users');
        SucuriScanOption::updateOption(':twofactor_users', [55]);

        $wpUser = new WP_User(55);

        Functions\when('wp_safe_redirect')->alias(function ($url) {
            throw new RuntimeException('REDIRECT:' . esc_url($url));
        });

        try {
            SucuriScanTwoFactor::authenticate($wpUser, 'user55', 'pass');
            $this->fail('Expected redirect did not occur');
        } catch (RuntimeException $e) {
            $msg = $e->getMessage();
            $this->assertStringStartsWith('REDIRECT:', $msg);
            $this->assertStringContainsString('action=sucuri-2fa-setup', $msg);
        }
    }

    /**
     * Regression test: extract_submitted_login_credential() must not run a
     * TOTP submission through SucuriScanBackupCodes::normalize_code(), since
     * that filter strips digits ("0"/"1") that are excluded from the
     * backup-code alphabet but are perfectly valid in a 6-digit TOTP code.
     */
    public function testExtractSubmittedLoginCredentialPreservesTotpDigits(): void
    {
        $_POST['sucuriscan_totp_code'] = '013456';

        $ref = new ReflectionClass(SucuriScanTwoFactor::class);
        $method = $ref->getMethod('extract_submitted_login_credential');
        $method->setAccessible(true);

        $this->assertSame('013456', $method->invoke(null, 'sucuriscan_totp_code'));
    }

    public function testExtractSubmittedLoginCredentialNormalizesBackupCode(): void
    {
        $_POST['sucuriscan_totp_code'] = ' aaaa-1111 ';

        $ref = new ReflectionClass(SucuriScanTwoFactor::class);
        $method = $ref->getMethod('extract_submitted_login_credential');
        $method->setAccessible(true);

        $this->assertSame('AAAA1111', $method->invoke(null, 'sucuriscan_totp_code'));
    }

    /**
     * The login field has to accept both credential shapes the server accepts:
     * a 6-digit TOTP code and a 9-character formatted backup code (AAAA-BBBB).
     * A numeric-only pattern here would lock every user out of backup-code
     * login, so the constraints are asserted against the parsed input element
     * rather than by matching text anywhere in the file.
     */
    public function testLoginFormAcceptsBothCredentialShapesWithoutJavaScript(): void
    {
        $template = file_get_contents(BASE_DIR . '/inc/tpl/login-2fa.html.tpl');

        $this->assertNotFalse($template);

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML((string) $template);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $input = $xpath->query('//input[@name="sucuriscan_totp_code"]')->item(0);

        $this->assertInstanceOf(DOMElement::class, $input, 'the code input must exist');
        $this->assertSame('9', $input->getAttribute('maxlength'), 'must fit a 9-char formatted backup code');
        $this->assertSame('text', $input->getAttribute('inputmode'), 'backup codes are not numeric');
        $this->assertFalse(
            $input->hasAttribute('pattern'),
            'a pattern attribute would reject one of the two accepted credential shapes'
        );

        // The 2FA challenge must survive with scripting unavailable.
        $this->assertSame(0, $xpath->query('//script')->length, 'the login step must not depend on JS');
    }

    /**
     * backup-codes.js calls window.sucuriscanDownloadTextFile(), which is
     * defined by inc/js/scripts.js (handle: sucuriscan). Without the dependency
     * the two can load in either order and Download silently does nothing, so
     * the registration is exercised instead of grepped for.
     */
    public function testBackupCodesScriptDependsOnTheSharedScript(): void
    {
        $registered = array();

        Functions\when('wp_register_script')->alias(
            function ($handle, $src, $deps = array(), $ver = false, $footer = false) use (&$registered) {
                $registered[$handle] = array('src' => $src, 'deps' => $deps);

                return true;
            }
        );

        $method = (new ReflectionClass(SucuriScanTwoFactor::class))->getMethod('ensure_backup_codes_script');
        $method->setAccessible(true);
        $method->invoke(null);

        $this->assertArrayHasKey('sucuriscan-backup-codes', $registered);
        $this->assertStringContainsString('inc/js/backup-codes.js', $registered['sucuriscan-backup-codes']['src']);
        $this->assertContains(
            'sucuriscan',
            $registered['sucuriscan-backup-codes']['deps'],
            'the download helper lives in the shared script and must load first'
        );
    }

    /**
     * The reveal element is a data-only <div> consumed by the enqueued script.
     * Keeping markup and behaviour separated is what lets the plugin ship
     * without inline script on a page that renders plaintext backup codes.
     */
    public function testRevealTemplateShipsNoInlineScript(): void
    {
        $revealTemplate = file_get_contents(BASE_DIR . '/inc/tpl/profile-2fa-backup-codes.snippet.tpl');

        $this->assertNotFalse($revealTemplate);
        $this->assertStringNotContainsString('<script', $revealTemplate);
    }

    public function testSuccessfulSetupShowsBackupCodesBeforeOriginalRedirect(): void
    {
        SucuriScanOption::updateOption(':twofactor_mode', 'all_users');

        $transients = array();

        Functions\when('wp_hash_password')->alias(function ($password) {
            return 'HASH:' . $password;
        });
        Functions\when('set_transient')->alias(function ($key, $value, $ttl) use (&$transients) {
            $transients[$key] = $value;
            return true;
        });
        Functions\when('wp_safe_redirect')->alias(function ($url) {
            throw new RuntimeException('REDIRECT:' . $url);
        });

        $method = (new ReflectionClass(SucuriScanTwoFactor::class))->getMethod('process_successful_setup');
        $method->setAccessible(true);

        try {
            $method->invoke(null, 55, 'TESTSECRET', 123, false, 'https://example.com/account', 'token1234567890');
            $this->fail('Expected redirect after successful setup');
        } catch (RuntimeException $e) {
            $this->assertSame('REDIRECT:https://example.com/wp-admin/profile.php', $e->getMessage());
        }

        $this->assertContains('https://example.com/account', $transients);
    }

    public function testRecordFailedBackupAttemptUsesStricterLimitThanTotp(): void
    {
        $ref = new ReflectionClass(SucuriScanTwoFactor::class);
        $method = $ref->getMethod('record_failed_backup_attempt');
        $method->setAccessible(true);

        Functions\when('wp_safe_redirect')->alias(function ($url) {
            throw new RuntimeException('REDIRECT:' . $url);
        });

        $session = ['attempts' => 0];
        $token = 'sometoken1234567890';

        $method->invokeArgs(null, [$token, &$session]);
        $this->assertSame(1, $session['backup_attempts']);

        $method->invokeArgs(null, [$token, &$session]);
        $this->assertSame(2, $session['backup_attempts']);

        // BACKUP_TOKEN_MAX_ATTEMPTS = 3, stricter than LOGIN_TOKEN_MAX_ATTEMPTS = 5.
        $this->expectException(RuntimeException::class);
        $method->invokeArgs(null, [$token, &$session]);
    }

    public function testLoginFormAcceptsValidBackupCodeConsumesItAndLogsIn(): void
    {
        SucuriScanOption::updateOption(':twofactor_mode', 'all_users');

        Functions\when('wp_unslash')->returnArg(1);

        // Backup-code consumption logs via SucuriScanEvent, which cascades
        // into the local audit-log queue's filesystem/hardening machinery.
        Functions\when('wp_strip_all_tags')->alias(function ($text) {
            return trim(strip_tags((string) $text));
        });
        Functions\when('sucuriscan_lastlogins_datastore_exists')->justReturn(true);
        Functions\when('get_home_path')->justReturn('/');

        $userId = 77;
        $token = 'BACKUPCODELOGINTOKEN123';

        Functions\when('get_transient')->alias(function ($key) use ($token, $userId) {
            if (strpos($key, $token) !== false) {
                return array(
                    'user_id' => $userId,
                    'remember' => false,
                    'redirect' => 'https://example.com/wp-admin/',
                    'secret' => '',
                    'created' => time(),
                    'attempts' => 0,
                    'ua' => '',
                );
            }

            return false;
        });

        Functions\when('wp_hash_password')->alias(function ($password) {
            return 'HASH:' . $password;
        });
        Functions\when('wp_check_password')->alias(function ($password, $hash, $user_id = '') {
            return $hash === 'HASH:' . $password;
        });

        $meta = array();
        Functions\when('get_user_meta')->alias(function ($uid, $key, $single = false) use (&$meta) {
            return isset($meta[$uid][$key]) ? $meta[$uid][$key] : '';
        });
        Functions\when('update_user_meta')->alias(function ($uid, $key, $value) use (&$meta) {
            $meta[$uid][$key] = $value;
            return true;
        });

        $codes = SucuriScanBackupCodes::generate_for_user($userId);
        $validCode = $codes[0];

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_REQUEST['token'] = $token;
        $_POST['sucuriscan_totp_code'] = $validCode;

        Functions\when('wp_safe_redirect')->alias(function ($url) {
            throw new RuntimeException('REDIRECT:' . $url);
        });

        try {
            SucuriScanTwoFactor::login_form_2fa();
            $this->fail('Expected redirect on successful backup-code login did not occur');
        } catch (RuntimeException $e) {
            $this->assertStringStartsWith('REDIRECT:', $e->getMessage());
        }

        $this->assertSame(9, SucuriScanBackupCodes::remaining_count($userId), 'the used code must be consumed');
        $this->assertFalse(
            SucuriScanBackupCodes::validate_and_consume($userId, $validCode),
            'a consumed backup code must not validate again'
        );
    }

    /**
     * Every way a two-factor login can succeed must finish like wp_signon():
     * auth cookie, then wp_login with the user, then the redirect.
     *
     * @see https://wordpress.org/support/topic/2fa-logins-are-never-recorded-in-last-logins-wp_login-never-fires/
     * @dataProvider twoFactorLoginExits
     */
    public function testTwoFactorLoginFiresWpLoginLikeWpSignon(string $method, array $args): void
    {
        Functions\when('get_user_by')->alias(function ($field, $val) {
            $user = new WP_User($val);
            $user->user_login = 'u' . $val;
            return $user;
        });

        $this->assertSame(array('cookie', 'wp_login:u321:WP_User', 'redirect'), $this->runLoginExit($method, $args));
    }

    public function twoFactorLoginExits(): array
    {
        $url = 'https://example.com/wp-admin/';

        return array(
            'authenticator code' => array('complete_success_login', array(321, false, $url, 'token1234567890', 123)),
            'backup code' => array('complete_success_login', array(321, true, $url, 'token1234567890', 0)),
            'first-time setup' => array('process_successful_setup', array(321, 'JBSWY3DPEHPK3PXP', 123, false, $url, 'token1234567890')),
        );
    }

    public function testTwoFactorLoginSkipsWpLoginWhenUserIsGone(): void
    {
        Functions\when('get_user_by')->justReturn(false);

        $this->assertSame(
            array('cookie', 'redirect'),
            $this->runLoginExit('complete_success_login', array(321, false, 'https://example.com/wp-admin/', 'token1234567890', 0))
        );
        $this->assertSame(0, did_action('wp_login'));
    }

    /**
     * Run a protected login exit point and return the order of the calls it made.
     */
    private function runLoginExit(string $method, array $args): array
    {
        SucuriScanOption::updateOption(':twofactor_mode', 'all_users');

        $calls = array();

        Functions\when('wp_hash_password')->alias(function ($password) {
            return 'HASH:' . $password;
        });
        Functions\when('wp_set_auth_cookie')->alias(function () use (&$calls) {
            $calls[] = 'cookie';
        });
        Monkey\Actions\expectDone('wp_login')->zeroOrMoreTimes()->whenHappen(function ($login, $user) use (&$calls) {
            $calls[] = 'wp_login:' . $login . ':' . get_class($user);
        });
        Functions\when('wp_safe_redirect')->alias(function () use (&$calls) {
            $calls[] = 'redirect';
            throw new RuntimeException('REDIRECT');
        });

        $ref = (new ReflectionClass(SucuriScanTwoFactor::class))->getMethod($method);
        $ref->setAccessible(true);

        try {
            $ref->invokeArgs(null, $args);
            $this->fail('Expected a redirect');
        } catch (RuntimeException $e) {
            $this->assertSame('REDIRECT', $e->getMessage());
        }

        return $calls;
    }
}
