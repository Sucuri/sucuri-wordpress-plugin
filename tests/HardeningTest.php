<?php declare(strict_types=1);

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class HardeningTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        Functions\when('get_home_path')->justReturn(__DIR__);
        Functions\when('__')->returnArg();

        $_SERVER['SERVER_SOFTWARE'] = 'apache';

        // Creating temp .php files in order to test allowing/disallowing them
        file_put_contents(SUCURI_DATA_STORAGE . '/archive.php', '<?php echo "Hello World";');
        file_put_contents(SUCURI_DATA_STORAGE . '/export.php', '<?php echo "Hello World";');
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();

        // Removing temp files
        if (file_exists($this->getHtaccessPath())) {
            unlink($this->getHtaccessPath());
        }

        if (file_exists(SUCURI_DATA_STORAGE . '/archive.php')) {
            unlink(SUCURI_DATA_STORAGE . '/archive.php');
        }

        if (file_exists(SUCURI_DATA_STORAGE . '/export.php')) {
            unlink(SUCURI_DATA_STORAGE . '/export.php');
        }
    }

    public function testHardeningDirectory()
    {
        $hardening = new SucuriScanHardening();

        $hardening->hardenDirectory(SUCURI_DATA_STORAGE);

        $this->assertFileExists($this->getHtaccessPath());

        $this->assertEquals(
            trim(file_get_contents($this->getHtaccessPath())),
            $this->getHtaccessContentWithBasicHardening()
        );
    }

    public function testUnhardeningDirectory()
    {
        $hardening = new SucuriScanHardening();

        $hardening->hardenDirectory(SUCURI_DATA_STORAGE);

        $this->assertFileExists($this->getHtaccessPath());

        $this->assertEquals(
            trim(file_get_contents($this->getHtaccessPath())),
            $this->getHtaccessContentWithBasicHardening()
        );

        $hardening->unhardenDirectory(SUCURI_DATA_STORAGE);

        $this->assertFileDoesNotExist($this->getHtaccessPath());
    }

    public function testRemoveFromAllowlist()
    {
        $hardening = new SucuriScanHardening();

        $hardening->hardenDirectory(SUCURI_DATA_STORAGE);

        try {
            $hardening->allow('archive.php', SUCURI_DATA_STORAGE);
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }

        $this->assertFileExists($this->getHtaccessPath());

        $this->assertEquals(
            trim(file_get_contents($this->getHtaccessPath())),
            $this->getHtaccessContentWithAllowedFiles('archive.php')
        );

        $hardening->removeFromAllowlist('archive.php', SUCURI_DATA_STORAGE);

        $this->assertEquals(
            trim(file_get_contents($this->getHtaccessPath())),
            $this->getHtaccessContentWithBasicHardening()
        );
    }

    public function testRemoveMultipleFilesFromAllowlist()
    {
        $hardening = new SucuriScanHardening();

        $hardening->hardenDirectory(SUCURI_DATA_STORAGE);

        try {
            $hardening->allow('archive.php', SUCURI_DATA_STORAGE);
            $hardening->allow('export.php', SUCURI_DATA_STORAGE);
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }

        $this->assertFileExists($this->getHtaccessPath());

        $this->assertEquals(
            trim(file_get_contents($this->getHtaccessPath())),
            $this->getHtaccessContentWithAllowedFiles(['archive.php', 'export.php'])
        );

        $hardening->removeFromAllowlist('archive.php', SUCURI_DATA_STORAGE);
        $hardening->removeFromAllowlist('export.php', SUCURI_DATA_STORAGE);

        $this->assertEquals(
            trim(file_get_contents($this->getHtaccessPath())),
            $this->getHtaccessContentWithBasicHardening()
        );
    }

    public function testRemovingLegacyHardeningRules()
    {
        $hardening = new SucuriScanHardening();

        $hardening->hardenDirectory(SUCURI_DATA_STORAGE);

        $legacyRules = sprintf(
            "<Files %s>\n"
            . "  <IfModule !mod_authz_core.c>\n"
            . "    Allow from all\n"
            . "  </IfModule>\n"
            . "  <IfModule mod_authz_core.c>\n"
            . "    Require all granted\n"
            . "  </IfModule>\n"
            . "</Files>",
            'archive.php'
        );

        // Manually adding legacy rules to the .htaccess file
        file_put_contents($this->getHtaccessPath(), $legacyRules, FILE_APPEND);

        $hardening->removeFromAllowlist('archive.php', SUCURI_DATA_STORAGE, true);

        $this->assertFileExists($this->getHtaccessPath());

        $this->assertEquals(
            trim(file_get_contents($this->getHtaccessPath())),
            $this->getHtaccessContentWithBasicHardening()
        );
    }

    public function testAllowBlockedPHPFiles()
    {
        $hardening = new SucuriScanHardening();

        $hardening->hardenDirectory(SUCURI_DATA_STORAGE);

        try {
            $hardening->allow('archive.php', SUCURI_DATA_STORAGE);
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }

        $this->assertFileExists($this->getHtaccessPath());

        $this->assertEquals(
            trim(file_get_contents($this->getHtaccessPath())),
            $this->getHtaccessContentWithAllowedFiles('archive.php')
        );
    }

    public function testAllowMultipleBlockedPHPFiles()
    {
        $hardening = new SucuriScanHardening();

        $hardening->hardenDirectory(SUCURI_DATA_STORAGE);

        try {
            $hardening->allow('archive.php', SUCURI_DATA_STORAGE);
            $hardening->allow('export.php', SUCURI_DATA_STORAGE);
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }

        $this->assertFileExists($this->getHtaccessPath());

        $this->assertEquals(
            trim(file_get_contents($this->getHtaccessPath())),
            $this->getHtaccessContentWithAllowedFiles(['archive.php', 'export.php'])
        );
    }

    private function getHtaccessPath(): string
    {
        return SUCURI_DATA_STORAGE . '/.htaccess';
    }

    private function getHtaccessContentWithBasicHardening(): string
    {

        $baseRules = "<FilesMatch \"\.(?i:php)$\">\n";
        $baseRules .= "  <IfModule !mod_authz_core.c>\n";
        $baseRules .= "    Order allow,deny\n";
        $baseRules .= "    Deny from all\n";
        $baseRules .= "  </IfModule>\n";
        $baseRules .= "  <IfModule mod_authz_core.c>\n";
        $baseRules .= "    Require all denied\n";
        $baseRules .= "  </IfModule>\n";
        $baseRules .= "</FilesMatch>\n";

        return trim($baseRules);
    }

    private function getHtaccessContentWithAllowedFiles($files): string
    {
        $baseContent = $this->getHtaccessContentWithBasicHardening();

        if (is_string($files)) {
            $files = [$files];
        }

        foreach ($files as $filepath) {
            $filepath = str_replace(['<', '>', '..'], '', $filepath);
            $relative_folder = str_replace(ABSPATH, '/', SUCURI_DATA_STORAGE);
            $relative_folder = '/' . ltrim($relative_folder, '/');

            $baseContent .= sprintf(
                "\n\n"
                . "<Files %s>\n"
                . "  <If \"%%{REQUEST_URI} =~ m#^%s/%s$#\">\n"
                . "    <IfModule !mod_authz_core.c>\n"
                . "      Allow from all\n"
                . "    </IfModule>\n"
                . "    <IfModule mod_authz_core.c>\n"
                . "      Require all granted\n"
                . "    </IfModule>\n"
                . "  </If>\n"
                . "</Files>",
                basename($filepath),
                rtrim($relative_folder, '/'),
                $filepath
            );
        }

        return trim($baseContent);
    }
}
