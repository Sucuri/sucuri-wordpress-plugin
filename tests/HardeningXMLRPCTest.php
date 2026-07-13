<?php declare(strict_types=1);

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

if (!function_exists('is_plugin_active')) {
    function is_plugin_active($plugin)
    {
        return in_array($plugin, $GLOBALS['__test_active_plugins'] ?? array(), true);
    }
}

if (!function_exists('is_plugin_active_for_network')) {
    function is_plugin_active_for_network($plugin)
    {
        return in_array($plugin, $GLOBALS['__test_network_active_plugins'] ?? array(), true);
    }
}

if (!function_exists('is_multisite')) {
    function is_multisite()
    {
        return (bool) ($GLOBALS['__test_is_multisite'] ?? false);
    }
}

final class HardeningXMLRPCTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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

        $GLOBALS['__test_active_plugins'] = array();
        $GLOBALS['__test_network_active_plugins'] = array();
        $GLOBALS['__test_is_multisite'] = false;

        Functions\when('__')->returnArg();
        Functions\when('wp_cache_get')->justReturn(false);
        Functions\when('wp_cache_set')->justReturn(true);
        Functions\when('wp_cache_delete')->justReturn(true);
        Functions\when('get_option')->justReturn(false);
        Functions\when('update_option')->justReturn(true);
        Functions\when('delete_option')->justReturn(true);
    }

    protected function tearDown(): void
    {
        if ($this->settingsFile) {
            file_put_contents($this->settingsFile, $this->originalSettingsContent);
        }

        unset($GLOBALS['__test_active_plugins']);
        unset($GLOBALS['__test_network_active_plugins']);
        unset($GLOBALS['__test_is_multisite']);

        Monkey\tearDown();
        parent::tearDown();
    }

    public function testXmlrpcEnabledReturnsFalseWhenHardeningIsApplied()
    {
        SucuriScanOption::updateOption(':hardening_xmlrpc', 'enabled');

        $this->assertFalse(SucuriScanHardening::xmlrpcEnabled(true));
    }

    public function testXmlrpcEnabledPassesThroughValueWhenHardeningIsNotApplied()
    {
        SucuriScanOption::updateOption(':hardening_xmlrpc', 'disabled');

        $this->assertTrue(SucuriScanHardening::xmlrpcEnabled(true));
        $this->assertFalse(SucuriScanHardening::xmlrpcEnabled(false));
    }

    public function testXmlrpcEnabledDefaultsToNotHardened()
    {
        $this->assertTrue(SucuriScanHardening::xmlrpcEnabled(true));
    }

    public function testActiveXMLRPCDependentPluginsReturnsEmptyByDefault()
    {
        $this->assertSame(array(), SucuriScanHardening::activeXMLRPCDependentPlugins());
    }

    public function testActiveXMLRPCDependentPluginsDetectsJetpack()
    {
        $GLOBALS['__test_active_plugins'] = array('jetpack/jetpack.php');

        $this->assertSame(array('Jetpack'), SucuriScanHardening::activeXMLRPCDependentPlugins());
    }

    public function testActiveXMLRPCDependentPluginsDetectsNetworkActivatedPluginOnMultisite()
    {
        $GLOBALS['__test_is_multisite'] = true;
        $GLOBALS['__test_network_active_plugins'] = array('jetpack/jetpack.php');

        $this->assertSame(array('Jetpack'), SucuriScanHardening::activeXMLRPCDependentPlugins());
    }

    public function testActiveXMLRPCDependentPluginsIgnoresNetworkActivationOutsideMultisite()
    {
        $GLOBALS['__test_is_multisite'] = false;
        $GLOBALS['__test_network_active_plugins'] = array('jetpack/jetpack.php');

        $this->assertSame(array(), SucuriScanHardening::activeXMLRPCDependentPlugins());
    }
}
