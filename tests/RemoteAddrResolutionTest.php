<?php declare(strict_types=1);

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Tests for how SucuriScan::getRemoteAddr() resolves the client IP.
 *
 * getRemoteAddr() is what lastlogins.php, event.lib.php (audit log) and mail.lib.php use
 * to record the client IP. Proxy headers (the shipped default :addr_header is
 * HTTP_X_SUCURI_CLIENTIP) are only consulted when an administrator has explicitly enabled
 * reverse proxy mode; otherwise the IP comes solely from REMOTE_ADDR.
 */
final class RemoteAddrResolutionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var array Plugin options exposed through the stubbed alloptions cache. */
    private $cache = array();

    /** @var array Snapshot of $_SERVER restored on tearDown. */
    private $serverBackup = array();

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $self = $this;
        $this->serverBackup = $_SERVER;

        Functions\when('__')->returnArg();
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('wp_unslash')->returnArg();

        // getAllOptions() reads wp_cache_get('alloptions', SUCURISCAN) first and returns
        // it when it is a non-empty array, so seeding the cache fully controls the option
        // store without touching the committed settings fixture on disk.
        Functions\when('wp_cache_get')->alias(function ($key, $group = '') use ($self) {
            $cacheKey = $group . ':' . $key;
            return array_key_exists($cacheKey, $self->cache) ? $self->cache[$cacheKey] : false;
        });
        Functions\when('wp_cache_set')->alias(function ($key, $value, $group = '') use ($self) {
            $self->cache[$group . ':' . $key] = $value;
            return true;
        });
        Functions\when('get_option')->alias(function ($option, $default = false) {
            return $default;
        });
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Seed the plugin option store.
     *
     * @param string $revproxy 'enabled' or 'disabled'.
     * @return void
     */
    private function seedOptions($revproxy)
    {
        $this->cache['sucuriscan:alloptions'] = array(
            'sucuriscan_addr_header' => 'HTTP_X_SUCURI_CLIENTIP', // shipped default
            'sucuriscan_revproxy' => $revproxy,
        );
    }

    /**
     * Build a request with a real TCP peer plus a value in the given header.
     *
     * @param string $header $_SERVER key carrying the header value.
     * @return void
     */
    private function buildRequest($header = 'HTTP_X_SUCURI_CLIENTIP')
    {
        $_SERVER = array();
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4'; // genuine client
        $_SERVER[$header] = '6.6.6.6'; // value carried in the proxy header
    }

    public function testIgnoresUntrustedSucuriHeaderWhenReverseProxyDisabled()
    {
        $this->seedOptions('disabled');
        $this->buildRequest('HTTP_X_SUCURI_CLIENTIP');

        $this->assertSame('1.2.3.4', SucuriScan::getRemoteAddr());
        $this->assertSame('REMOTE_ADDR', SucuriScan::getRemoteAddrHeader());
    }

    public function testIgnoresUntrustedForwardedForHeaderWhenReverseProxyDisabled()
    {
        $this->seedOptions('disabled');
        $this->buildRequest('HTTP_X_FORWARDED_FOR');

        $this->assertSame('1.2.3.4', SucuriScan::getRemoteAddr());
    }

    public function testHonorsConfiguredHeaderWhenReverseProxyEnabled()
    {
        // Legitimate firewall/proxy deployment: the header must still be trusted.
        $this->seedOptions('enabled');
        $this->buildRequest('HTTP_X_SUCURI_CLIENTIP');

        $this->assertSame('6.6.6.6', SucuriScan::getRemoteAddr());
        $this->assertSame('HTTP_X_SUCURI_CLIENTIP', SucuriScan::getRemoteAddrHeader());
    }

    public function testDoesNotFallThroughToHeaderWhenRealPeerMissing()
    {
        // Even if REMOTE_ADDR is absent, a proxy-disabled site must not adopt the header.
        $this->seedOptions('disabled');
        $_SERVER = array();
        $_SERVER['HTTP_X_SUCURI_CLIENTIP'] = '6.6.6.6';

        $this->assertSame('127.0.0.1', SucuriScan::getRemoteAddr());
    }
}
