<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class IntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SucuriScanOption::updateOption(':notify_to', 'alerts@example.com');
        SucuriScanOption::updateOption(':use_wpmail', 'enabled');
        SucuriScanOption::updateOption(':email_subject', ':event');
        $GLOBALS['__sucuri_test_mails'] = [];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['__sucuri_test_mails']);
        parent::tearDown();
    }

    private function callIgnoreIntegrityFilepath(string $path): bool
    {
        $ref = new ReflectionClass('SucuriScanIntegrity');
        $method = $ref->getMethod('ignoreIntegrityFilepath');
        $method->setAccessible(true);
        return (bool) $method->invoke(null, $path);
    }

    public function testIgnoresSSSDownloaderFile()
    {
        $this->assertTrue(
            $this->callIgnoreIntegrityFilepath('sucuri-sss-downloader_123e4567-e89b-12d3-a456-426614174000.php')
        );
    }

    public function testIgnoresSSSUploaderFile()
    {
        $this->assertTrue(
            $this->callIgnoreIntegrityFilepath('sucuri-sss-uploader_abc12345-def6-7890-abcd-ef1234567890.php')
        );
    }

    public function testDoesNotIgnoreRandomPhpAtRoot()
    {
        $this->assertFalse(
            $this->callIgnoreIntegrityFilepath('random-file-name.php')
        );
    }

    public function testNotifyEventScanChecksumsDisabled()
    {
        SucuriScanOption::updateOption(':notify_scan_checksums', 'disabled');
        $sent = SucuriScanEvent::notifyEvent('scan_checksums', 'Body');
        $this->assertFalse($sent);
        $this->assertCount(0, $GLOBALS['__sucuri_test_mails']);
    }

    public function testNotifyEventScanChecksumsEnabledSendsMail()
    {
        SucuriScanOption::updateOption(':notify_scan_checksums', 'enabled');
        $sent = SucuriScanEvent::notifyEvent('scan_checksums', '<p>Body</p>');
        $this->assertTrue($sent);
        $this->assertGreaterThanOrEqual(1, count($GLOBALS['__sucuri_test_mails']));
        $last = end($GLOBALS['__sucuri_test_mails']);
        $this->assertSame('alerts@example.com', $last['to']);
        $this->assertStringContainsString('core integrity checks', strtolower((string) $last['subject']));
    }
}
