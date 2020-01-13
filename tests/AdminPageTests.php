<?php

use WPAcceptance\EnvironmentFactory;

class SucuriScannerTests extends \WPAcceptance\PHPUnit\TestCase {
    public function testOverviewPageLoaded() {
        $actor = $this->openBrowserPage();

        $actor->login();
        
        $actor->moveTo('/wp-admin/admin.php?page=sucuriscan#auditlogs');

        $actor->seeTextInSource('<html', '<html*> not found in page source.');
        $actor->seeTextInSource('</html>', '</html> not found in page source.');
        $actor->seeTextInSource('<body', '<body> not found in page source.');
        $actor->seeTextInSource('</body>', '</body> not found in page source.');

        $actor->seeTextInSource('Copyright &copy; 2020 Sucuri Inc. All Rights Reserved.', 'Copyright statement not found in page source.');
    }
}