<?php

use WPAcceptance\EnvironmentFactory;

class SucuriScannerSettingsTests extends \WPAcceptance\PHPUnit\TestCase {
    public function testResetSecurityLogsHardeningAndSettings() {
        $actor = $this->openBrowserPage();

        $actor->login();
        
        $actor->moveTo('/wp-admin/admin.php?page=sucuriscan_settings#general');

        $actor->waitUntilElementVisible('body');

        $actor->checkOptions('div.sucuriscan-panel:nth-child(8) > div:nth-child(2) > form:nth-child(2) > p:nth-child(2) > label:nth-child(1) > input:nth-child(2)');

        $actor->click('div.sucuriscan-panel:nth-child(8) > div:nth-child(2) > form:nth-child(2) > button:nth-child(3)');

        $actor->waitUntilElementVisible('.sucuriscan-alert');

        $actor->seeText('Local security logs, hardening and settings were deleted');
    }

    public function testActivateAndDeactivateReverseProxy() {
        $actor = $this->openBrowserPage();

        $actor->login();
        
        $actor->moveTo('/wp-admin/admin.php?page=sucuriscan_settings#general');

        $actor->waitUntilElementVisible('body');

        $actor->click('#sucuriscan-tabs-general > div:nth-child(4) > div:nth-child(2) > div:nth-child(2) > form:nth-child(2) > button:nth-child(3)');

        $actor->waitUntilElementVisible('.sucuriscan-alert');

        $actor->seeText('Reverse proxy support was set to enabled');

        $actor->seeText('HTTP header was set to HTTP_X_SUCURI_CLIENTIP');

        $actor->click('#sucuriscan-tabs-general > div:nth-child(4) > div:nth-child(2) > div:nth-child(2) > form:nth-child(2) > button:nth-child(3)');

        $actor->waitUntilElementVisible('.sucuriscan-alert');

        $actor->seeText('Reverse proxy support was set to disabled');

        $actor->seeText('HTTP header was set to REMOTE_ADDR');
    }
}