<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests that allowlistRule() escapes file paths interpolated into the .htaccess regex.
 *
 * allowlistRule() interpolates the file path into the PCRE of an Apache
 * <If "%{REQUEST_URI} =~ m#^.../...$#"> directive. The path values that land in the regex
 * are passed through preg_quote(..., '#') so regex metacharacters (., *, |, (, ), etc.)
 * are matched literally and the condition matches only the intended file.
 */
final class HardeningAllowlistRegexTest extends TestCase
{
    /**
     * @param string $filepath File path supplied by the admin.
     * @param string $folder   Hardened folder hosting the file.
     * @return string Generated Apache rule block.
     */
    private function rule(string $filepath, string $folder): string
    {
        $method = (new ReflectionClass('SucuriScanHardening'))->getMethod('allowlistRule');
        $method->setAccessible(true);
        return (string) $method->invoke(null, $filepath, $folder);
    }

    public function testRegexMetacharactersInFilePathAreEscaped()
    {
        $rule = $this->rule('evil.(a|b)*.php', ABSPATH . 'wp-content/uploads');

        // The REQUEST_URI pattern must contain the escaped form...
        $this->assertStringContainsString('evil\.\(a\|b\)\*\.php', $rule);
        // ...and never the raw metacharacters terminating the pattern ("...$#").
        $this->assertStringNotContainsString('evil.(a|b)*.php$#', $rule);
    }

    public function testFolderSegmentIsEscapedInRegex()
    {
        // A metacharacter in the hardened folder name must also be neutralized.
        $rule = $this->rule('plugin.php', ABSPATH . 'wp-content/up(loads)');

        $this->assertStringContainsString('up\(loads\)', $rule);
        $this->assertStringNotContainsString('up(loads)/', $rule);
    }

    public function testEscapedPathsAreDecodedWhenReadingTheAllowlist()
    {
        $rule = $this->rule('evil.(a|b)*.php', ABSPATH . 'wp-content');
        $method = (new ReflectionClass('SucuriScanHardening'))->getMethod('getFilesWithNewPattern');
        $method->setAccessible(true);

        $files = $method->invoke(null, $rule, ABSPATH . 'wp-content');

        $this->assertSame(array(array(
            'file' => 'evil.(a|b)*.php',
            'relative_path' => 'evil.(a|b)*.php',
        )), $files);
    }
}
