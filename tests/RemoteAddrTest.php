<?php declare(strict_types=1);

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Tests for IP-spoofing fixes:
 *   - SucuriScan::isIPInCIDR()
 *   - SucuriScan::isTrustedProxy()
 *   - SucuriScan::getRemoteAddr() trusted-proxy enforcement
 *   - SucuriScanInterface::initialize() no longer triggered by isBehindFirewall()
 */
final class RemoteAddrTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var array Saved $_SERVER state, restored after each test. */
    private $savedServer = array();

    /** @var string Path to the settings file used by SucuriScanOption. */
    private $settingsFile = '';

    /** @var string Original settings file content, restored in tearDown. */
    private $originalSettingsContent = '';

    /** @var array WP object cache store for test isolation. */
    private $cache = array();

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $this->savedServer = $_SERVER;

        $this->settingsFile = SucuriScanOption::optionsFilePath();
        $this->originalSettingsContent = file_exists($this->settingsFile)
            ? (string) file_get_contents($this->settingsFile)
            : '';

        $self = $this;

        Functions\when('sanitize_text_field')->alias(fn($v) => is_string($v) ? $v : '');
        Functions\when('wp_unslash')->alias(fn($v) => $v);
        Functions\when('__')->returnArg();
        Functions\when('wp_strip_all_tags')->alias(fn($v) => $v);

        Functions\when('wp_cache_get')->alias(function ($key, $group = '') use ($self) {
            $k = $group . ':' . $key;
            return array_key_exists($k, $self->cache) ? $self->cache[$k] : false;
        });
        Functions\when('wp_cache_set')->alias(function ($key, $value, $group = '') use ($self) {
            $self->cache[$group . ':' . $key] = $value;
            return true;
        });
        Functions\when('wp_cache_delete')->alias(function ($key, $group = '') use ($self) {
            unset($self->cache[$group . ':' . $key]);
            return true;
        });

        /* get_option must honour its $default argument so that the encrypted WAF
         * key path (which calls get_option($key, null)) gets null back and does
         * not enter the decryption-failure branch that calls createStorageFolder. */
        Functions\when('get_option')->alias(function ($option, $default = false) {
            return $default;
        });
        Functions\when('update_option')->justReturn(true);
        Functions\when('delete_option')->justReturn(true);

        /* Stubs needed by the event/interface layer when error paths are reached. */
        Functions\when('sucuriscan_lastlogins_datastore_exists')->justReturn(true);
        Functions\when('wp_next_scheduled')->justReturn(true);
        Functions\when('wp_schedule_event')->justReturn(null);
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->savedServer;

        if ($this->settingsFile) {
            file_put_contents($this->settingsFile, $this->originalSettingsContent);
        }

        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Write $options to the flat settings file and invalidate the in-process
     * object cache so the next getAllOptions() call re-reads from disk.
     */
    private function writeSettings(array $options): void
    {
        $content = "<?php exit(0); ?>\n" . json_encode($options) . "\n";
        file_put_contents($this->settingsFile, $content);
        $this->cache = array(); // flush the in-process object cache
    }

    // -------------------------------------------------------------------------
    // isIPInCIDR
    // -------------------------------------------------------------------------

    public function testIPInCIDRExactMatch()
    {
        $this->assertTrue(SucuriScan::isIPInCIDR('192.88.134.1', '192.88.134.0/24'));
    }

    public function testIPInCIDRSubnetBoundary()
    {
        // 192.88.134.0/23 covers 192.88.134.0 – 192.88.135.255
        $this->assertTrue(SucuriScan::isIPInCIDR('192.88.135.255', '192.88.134.0/23'));
        $this->assertFalse(SucuriScan::isIPInCIDR('192.88.136.0', '192.88.134.0/23'));
    }

    public function testIPInCIDR22Prefix()
    {
        // 185.93.228.0/22 covers 185.93.228.0 – 185.93.231.255
        $this->assertTrue(SucuriScan::isIPInCIDR('185.93.229.100', '185.93.228.0/22'));
        $this->assertTrue(SucuriScan::isIPInCIDR('185.93.231.255', '185.93.228.0/22'));
        $this->assertFalse(SucuriScan::isIPInCIDR('185.93.232.0', '185.93.228.0/22'));
    }

    public function testIPInCIDRSlash32()
    {
        $this->assertTrue(SucuriScan::isIPInCIDR('1.2.3.4', '1.2.3.4/32'));
        $this->assertFalse(SucuriScan::isIPInCIDR('1.2.3.5', '1.2.3.4/32'));
    }

    public function testIPInCIDRInvalidInputs()
    {
        $this->assertFalse(SucuriScan::isIPInCIDR('not-an-ip', '192.168.0.0/24'));
        $this->assertFalse(SucuriScan::isIPInCIDR('1.2.3.4', 'bad-cidr'));
        $this->assertFalse(SucuriScan::isIPInCIDR('1.2.3.4', '1.2.3.4/33'));
    }

    // -------------------------------------------------------------------------
    // isTrustedProxy
    // -------------------------------------------------------------------------

    public function testTrustedProxyEmptyListTrustsAll()
    {
        $this->writeSettings(array('sucuriscan_trusted_proxy_ips' => ''));
        $this->assertTrue(SucuriScan::isTrustedProxy('9.10.11.12'));
    }

    public function testTrustedProxyExactIPMatch()
    {
        $this->writeSettings(array('sucuriscan_trusted_proxy_ips' => "1.2.3.4\n5.6.7.8"));
        $this->assertTrue(SucuriScan::isTrustedProxy('1.2.3.4'));
        $this->assertFalse(SucuriScan::isTrustedProxy('9.9.9.9'));
    }

    public function testTrustedProxyCIDRMatch()
    {
        $this->writeSettings(array('sucuriscan_trusted_proxy_ips' => '185.93.228.0/22'));
        $this->assertTrue(SucuriScan::isTrustedProxy('185.93.229.100'));
        $this->assertFalse(SucuriScan::isTrustedProxy('1.2.3.4'));
    }

    // -------------------------------------------------------------------------
    // getRemoteAddr — spoofing scenarios
    // -------------------------------------------------------------------------

    /**
     * Attacker (9.10.11.12) sends X-Sucuri-ClientIP: 5.6.7.8 to a site that
     * has revproxy enabled and Sucuri's IP ranges as trusted proxies.
     * Expected: returns the real TCP peer (9.10.11.12), not the spoofed value.
     */
    public function testGetRemoteAddrBlocksSpoofedHeaderFromUntrustedPeer()
    {
        $this->writeSettings(array(
            'sucuriscan_addr_header'       => 'HTTP_X_SUCURI_CLIENTIP',
            'sucuriscan_trusted_proxy_ips' => implode("\n", array(
                '192.88.134.0/23',
                '185.93.228.0/22',
                '66.248.200.0/22',
                '208.109.0.0/22',
            )),
        ));

        $_SERVER['REMOTE_ADDR']            = '9.10.11.12'; // attacker's real IP
        $_SERVER['HTTP_X_SUCURI_CLIENTIP'] = '5.6.7.8';    // spoofed

        $this->assertSame('9.10.11.12', SucuriScan::getRemoteAddr());
    }

    /**
     * Legitimate Sucuri CDN node (185.93.229.100) forwards the real client IP
     * (1.2.3.4) via X-Sucuri-ClientIP.  Expected: returns 1.2.3.4.
     */
    public function testGetRemoteAddrTrustedProxyHeaderIsAccepted()
    {
        $this->writeSettings(array(
            'sucuriscan_addr_header'       => 'HTTP_X_SUCURI_CLIENTIP',
            'sucuriscan_trusted_proxy_ips' => implode("\n", array(
                '192.88.134.0/23',
                '185.93.228.0/22',
                '66.248.200.0/22',
                '208.109.0.0/22',
            )),
        ));

        /* Simulate state after initialize() has run: the CDN node opened the TCP
         * connection, so SUCURIREAL_REMOTE_ADDR holds the CDN IP. */
        $_SERVER['SUCURIREAL_REMOTE_ADDR'] = '185.93.229.100'; // Sucuri CDN (trusted)
        $_SERVER['REMOTE_ADDR']            = '1.2.3.4';        // already overwritten
        $_SERVER['HTTP_X_SUCURI_CLIENTIP'] = '1.2.3.4';        // real client IP

        $this->assertSame('1.2.3.4', SucuriScan::getRemoteAddr());
    }

    /**
     * When trusted_proxy_ips is empty (unconfigured), the old behaviour is
     * preserved: proxy headers are trusted regardless of TCP peer.
     */
    public function testGetRemoteAddrBackwardCompatNoTrustedList()
    {
        $this->writeSettings(array(
            'sucuriscan_addr_header'       => 'HTTP_X_SUCURI_CLIENTIP',
            'sucuriscan_trusted_proxy_ips' => '',
        ));

        $_SERVER['REMOTE_ADDR']            = '9.10.11.12';
        $_SERVER['HTTP_X_SUCURI_CLIENTIP'] = '5.6.7.8';

        $this->assertSame('5.6.7.8', SucuriScan::getRemoteAddr());
    }

    /**
     * When addr_header is REMOTE_ADDR (no proxy configured), the TCP peer is
     * always used and proxy headers are irrelevant.
     */
    public function testGetRemoteAddrUsesDirectIpWhenNoProxy()
    {
        $this->writeSettings(array(
            'sucuriscan_addr_header'       => 'REMOTE_ADDR',
            'sucuriscan_trusted_proxy_ips' => '',
        ));

        $_SERVER['REMOTE_ADDR']            = '203.0.113.5';
        $_SERVER['HTTP_X_SUCURI_CLIENTIP'] = '1.1.1.1'; // must be ignored

        $this->assertSame('203.0.113.5', SucuriScan::getRemoteAddr());
    }

    /**
     * After initialize() overwrites REMOTE_ADDR, a later call to getRemoteAddr()
     * must still use SUCURIREAL_REMOTE_ADDR (the original TCP peer) for the
     * trusted-proxy check — not the already-overwritten REMOTE_ADDR value.
     */
    public function testGetRemoteAddrUsesSucurirealAfterInitialize()
    {
        $this->writeSettings(array(
            'sucuriscan_addr_header'       => 'HTTP_X_SUCURI_CLIENTIP',
            'sucuriscan_trusted_proxy_ips' => '185.93.228.0/22',
        ));

        /* State after initialize() ran for a legitimate request. */
        $_SERVER['SUCURIREAL_REMOTE_ADDR'] = '185.93.229.5'; // CDN node (trusted)
        $_SERVER['REMOTE_ADDR']            = '10.0.0.1';     // overwritten by initialize()
        $_SERVER['HTTP_X_SUCURI_CLIENTIP'] = '10.0.0.1';     // real client IP

        $this->assertSame('10.0.0.1', SucuriScan::getRemoteAddr());
    }

    // -------------------------------------------------------------------------
    // Fix 1: isBehindFirewall() must NOT trigger initialize()
    // -------------------------------------------------------------------------

    /**
     * When revproxy is DISABLED, initialize() must not overwrite REMOTE_ADDR
     * even if the attacker sends the X-Sucuri-ClientIP header (which previously
     * made isBehindFirewall() return true and trigger the override).
     */
    public function testInitializeDoesNotRunWhenRevproxyDisabled()
    {
        $this->writeSettings(array(
            'sucuriscan_revproxy'    => 'disabled',
            'sucuriscan_addr_header' => 'HTTP_X_SUCURI_CLIENTIP',
        ));

        $_SERVER['REMOTE_ADDR']            = '9.10.11.12';
        $_SERVER['HTTP_X_SUCURI_CLIENTIP'] = '5.6.7.8'; // attacker-supplied
        unset($_SERVER['SUCURIREAL_REMOTE_ADDR']);

        SucuriScanInterface::initialize();

        $this->assertSame('9.10.11.12', $_SERVER['REMOTE_ADDR']);
        $this->assertArrayNotHasKey('SUCURIREAL_REMOTE_ADDR', $_SERVER);
    }

    // -------------------------------------------------------------------------
    // refreshProxyIPCache + isTrustedProxy with fetched list
    // -------------------------------------------------------------------------

    /**
     * When the WAF API is unreachable (no API key in tests) and the cache is
     * empty, refreshProxyIPCache() must seed it with the hardcoded fallback.
     */
    public function testRefreshProxyIPCacheSeedsDefaultsWhenAPIUnavailable()
    {
        $this->writeSettings(array(
            'sucuriscan_revproxy'               => 'enabled',
            'sucuriscan_cloudproxy_apikey'      => '',
            'sucuriscan_trusted_proxy_ips_fetched' => '',
        ));

        SucuriScanFirewall::refreshProxyIPCache();

        $fetched = SucuriScanOption::getOption(':trusted_proxy_ips_fetched');

        $this->assertNotEmpty($fetched, 'Cache must be seeded when API is unavailable');

        foreach (SucuriScanFirewall::defaultProxyIPs() as $cidr) {
            $this->assertStringContainsString($cidr, $fetched);
        }
    }

    /**
     * When the cache already has a valid list and the API is unavailable,
     * refreshProxyIPCache() must leave the existing cache untouched.
     */
    public function testRefreshProxyIPCachePreservesExistingCacheOnAPIFailure()
    {
        $existing = '10.0.0.0/8';
        $this->writeSettings(array(
            'sucuriscan_revproxy'                  => 'enabled',
            'sucuriscan_cloudproxy_apikey'         => '',
            'sucuriscan_trusted_proxy_ips_fetched' => $existing,
        ));

        SucuriScanFirewall::refreshProxyIPCache();

        $this->assertSame($existing, SucuriScanOption::getOption(':trusted_proxy_ips_fetched'));
    }

    /**
     * When revproxy is disabled, refreshProxyIPCache() must be a no-op.
     */
    public function testRefreshProxyIPCacheSkipsWhenRevproxyDisabled()
    {
        $this->writeSettings(array(
            'sucuriscan_revproxy'                  => 'disabled',
            'sucuriscan_trusted_proxy_ips_fetched' => '',
        ));

        SucuriScanFirewall::refreshProxyIPCache();

        $this->assertSame('', SucuriScanOption::getOption(':trusted_proxy_ips_fetched'));
    }

    /**
     * isTrustedProxy() must use the fetched cache when the admin list is empty.
     */
    public function testIsTrustedProxyUsesFetchedCacheWhenAdminListEmpty()
    {
        $this->writeSettings(array(
            'sucuriscan_trusted_proxy_ips'         => '',
            'sucuriscan_trusted_proxy_ips_fetched' => '185.93.228.0/22',
        ));

        $this->assertTrue(SucuriScan::isTrustedProxy('185.93.229.100'));
        $this->assertFalse(SucuriScan::isTrustedProxy('1.2.3.4'));
    }

    /**
     * Admin list must override the fetched cache when both are set.
     */
    public function testIsTrustedProxyAdminListOverridesFetchedCache()
    {
        $this->writeSettings(array(
            'sucuriscan_trusted_proxy_ips'         => '203.0.113.0/24',
            'sucuriscan_trusted_proxy_ips_fetched' => '185.93.228.0/22',
        ));

        /* Admin list is active — Sucuri CDN range no longer trusted */
        $this->assertFalse(SucuriScan::isTrustedProxy('185.93.229.100'));
        /* But the admin-configured range is trusted */
        $this->assertTrue(SucuriScan::isTrustedProxy('203.0.113.5'));
    }

    /**
     * When both lists are empty, backward-compat trust-all remains in effect.
     */
    public function testIsTrustedProxyTrustsAllWhenBothListsEmpty()
    {
        $this->writeSettings(array(
            'sucuriscan_trusted_proxy_ips'         => '',
            'sucuriscan_trusted_proxy_ips_fetched' => '',
        ));

        $this->assertTrue(SucuriScan::isTrustedProxy('9.10.11.12'));
    }
}
