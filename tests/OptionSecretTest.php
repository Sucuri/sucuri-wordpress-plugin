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

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $this->settingsFile = SucuriScanOption::optionsFilePath();
        $this->originalSettingsContent = file_exists($this->settingsFile)
            ? (string) file_get_contents($this->settingsFile)
            : '';

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

    public function testSecretOptionTakesPrecedenceOverFile()
    {
        $secret = str_repeat('c', 32) . '/' . str_repeat('d', 32);
        $fileKey = str_repeat('e', 32) . '/' . str_repeat('f', 32);

        $payload = $this->encryptPayload($secret);
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
        $payload = $this->encryptPayload('secret');

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

    private function encryptPayload($plaintext)
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
}
