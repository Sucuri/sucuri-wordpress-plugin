<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests that formatChangedStatusFiles() HTML-escapes the audit-log file list.
 *
 * For "status has been changed" entries, ajaxAuditLogs() assigns the file list to
 * AuditLog.Extra, which the audit-log template renders with the raw "%%%...%%%" tag. The
 * list originates from the audit-log API response, so formatChangedStatusFiles() escapes
 * each item before it is rendered.
 */
final class AuditLogFileListEscapingTest extends TestCase
{
    /**
     * @param array $file_list File names from the audit-log entry.
     * @return string Formatted AuditLog.Extra value.
     */
    private function format(array $file_list): string
    {
        $method = (new ReflectionClass('SucuriScanAuditLogs'))->getMethod('formatChangedStatusFiles');
        $method->setAccessible(true);
        return (string) $method->invoke(null, $file_list);
    }

    public function testEscapesMarkupInFileNames()
    {
        $out = $this->format(array('<script>alert(1)</script>', 'wp-content/ok.php'));

        $this->assertStringNotContainsString('<script>', $out);
        $this->assertStringContainsString('&lt;script&gt;', $out);
        // Legitimate names are preserved and the items remain comma-separated.
        $this->assertStringContainsString('wp-content/ok.php', $out);
        $this->assertStringContainsString(', ', $out);
    }

    public function testEmptyListProducesEmptyString()
    {
        $this->assertSame('', $this->format(array()));
    }
}
