<?php declare(strict_types=1);

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

final class IntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // Simple in-memory cache for wp_cache_* mocks
        $GLOBALS['__wp_cache'] = [];

        // Mock WordPress functions used during these tests
        Functions\when('wp_cache_get')->alias(function ($key, $group = '') {
            $group = $group ?: 'default';
            return $GLOBALS['__wp_cache'][$group][$key] ?? false;
        });

        Functions\when('wp_cache_set')->alias(function ($key, $value, $group = '', $expire = 0) {
            $group = $group ?: 'default';
            if (!isset($GLOBALS['__wp_cache'][$group])) {
                $GLOBALS['__wp_cache'][$group] = [];
            }
            $GLOBALS['__wp_cache'][$group][$key] = $value;
            return true;
        });

        Functions\when('wp_cache_delete')->alias(function ($key, $group = '') {
            $group = $group ?: 'default';
            if (isset($GLOBALS['__wp_cache'][$group][$key])) {
                unset($GLOBALS['__wp_cache'][$group][$key]);
                return true;
            }
            return false;
        });

        // Translation and URL helpers
        Functions\when('__')->alias(function ($text, $domain = null) {
            return $text; });
        Functions\when('get_site_url')->justReturn('https://example.com');
        Functions\when('sanitize_text_field')->alias(fn($v) => is_string($v) ? $v : '');
        Functions\when('wp_strip_all_tags')->alias(function ($string) {
            return trim(strip_tags((string) $string));
        });

        // Capture emails sent via wp_mail
        Functions\when('wp_mail')->alias(function ($to, $subject, $message, $headers = array ()) {
            if (!isset($GLOBALS['__sucuri_test_mails'])) {
                $GLOBALS['__sucuri_test_mails'] = [];
            }
            $GLOBALS['__sucuri_test_mails'][] = [
                'to' => $to,
                'subject' => $subject,
                'message' => $message,
                'headers' => $headers,
            ];
            return true;
        });

        SucuriScanOption::updateOption(':notify_to', 'alerts@example.com');
        SucuriScanOption::updateOption(':use_wpmail', 'enabled');
        SucuriScanOption::updateOption(':email_subject', ':event');
        $GLOBALS['__sucuri_test_mails'] = [];
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        unset($GLOBALS['__wp_cache']);
        unset($GLOBALS['__sucuri_test_mails']);
        parent::tearDown();
    }

    private function callIsFilepathSafe(string $path): bool
    {
        $ref = new ReflectionClass('SucuriScanIntegrity');
        $method = $ref->getMethod('isFilepathSafe');
        $method->setAccessible(true);
        return (bool) $method->invoke(null, $path);
    }

    /** @dataProvider traversalPathProvider */
    public function testTraversalPathsAreBlocked(string $path): void
    {
        $this->assertFalse($this->callIsFilepathSafe($path));
    }

    public function traversalPathProvider(): array
    {
        return [
            'dot-dot-slash prefix'          => ['../../wp-config.php'],
            'dot-dot-slash in middle'        => ['wp-includes/../../../etc/passwd'],
            'single dot-dot-slash'           => ['../wp-config.php'],
            'dot-slash prefix'               => ['./wp-config.php'],
            'unix absolute path'             => ['/etc/passwd'],
            'unix absolute nested'           => ['/var/www/html/outside.php'],
            'windows backslash absolute'     => ['\\windows\\system32\\config'],
            'windows drive letter'           => ['C:\\windows\\system32\\config'],
            'empty string'                   => [''],
        ];
    }

    /** @dataProvider safePathProvider */
    public function testSafePathsAreAllowed(string $path): void
    {
        $this->assertTrue($this->callIsFilepathSafe($path));
    }

    public function safePathProvider(): array
    {
        return [
            'simple filename at root'        => ['wp-config.php'],
            'nested wp-includes path'        => ['wp-includes/post.php'],
            'deeply nested plugin path'      => ['wp-content/plugins/some-plugin/file.php'],
            'file that exists in ABSPATH'    => ['sucuri.php'],
        ];
    }

    public function testSymlinkEscapeOutsideAbspathIsBlocked(): void
    {
        $outside = tempnam(sys_get_temp_dir(), 'sucuri_test_');
        file_put_contents($outside, 'outside content');

        $linkName = 'sucuri_test_symlink_' . uniqid() . '.php';
        $linkPath = rtrim((string) realpath(ABSPATH), '/\\') . DIRECTORY_SEPARATOR . $linkName;

        if (!@symlink($outside, $linkPath)) {
            @unlink($outside);
            $this->markTestSkipped('Cannot create symlinks in this environment.');
        }

        try {
            $this->assertFalse($this->callIsFilepathSafe($linkName));
        } finally {
            @unlink($linkPath);
            @unlink($outside);
        }
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
