<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests for the two guards used by the integrity delete/restore actions:
 *
 *  1. resolveIntegrityPath() keeps the resolved path within ABSPATH and rejects entries
 *     that point outside it. ABSPATH is the repository root via tests/constants.php.
 *  2. flaggedFilesByStatus() builds an allowlist from the integrity scan results so the
 *     handler only acts on files the scan reported with the claimed status.
 */
final class IntegrityFilePathTest extends TestCase
{
    /**
     * @param string $file_path Relative path supplied by the request.
     * @return string|false Result of SucuriScanIntegrity::resolveIntegrityPath().
     */
    private function resolve(string $file_path)
    {
        $method = (new ReflectionClass('SucuriScanIntegrity'))->getMethod('resolveIntegrityPath');
        $method->setAccessible(true);
        return $method->invoke(null, $file_path);
    }

    public function testAllowsPathInsideInstallation()
    {
        $result = $this->resolve('wp-content/uploads/file.php');

        $this->assertNotFalse($result);
        $this->assertStringEndsWith('/wp-content/uploads/file.php', $result);
        $this->assertStringNotContainsString('..', $result);
    }

    public function testAllowsBenignParentSegmentsThatStayInside()
    {
        // ".." is fine as long as the resolved path remains within ABSPATH.
        $result = $this->resolve('wp-content/themes/../plugins/akismet/akismet.php');

        $this->assertNotFalse($result);
        $this->assertStringEndsWith('/wp-content/plugins/akismet/akismet.php', $result);
        $this->assertStringNotContainsString('..', $result);
    }

    public function testRejectsParentEscapeToWpConfig()
    {
        // A leading parent segment resolves above the install and must be rejected.
        $this->assertFalse($this->resolve('../wp-config.php'));
    }

    public function testRejectsEscapeIntoSiblingDirectory()
    {
        $this->assertFalse($this->resolve('wp-content/../../sibling/evil.php'));
    }

    public function testRejectsDeepEscapeOutsideInstall()
    {
        $this->assertFalse($this->resolve('../../../../../../../../etc/passwd'));
    }

    public function testRejectsTrailingSegmentsThatClimbOut()
    {
        $this->assertFalse($this->resolve('wp-content/uploads/../../../..'));
    }

    /**
     * @param mixed $latest_hashes Synthetic checkIntegrityIntegrity() output.
     * @return array Map of status => array(filepath => true).
     */
    private function flagged($latest_hashes): array
    {
        $method = (new ReflectionClass('SucuriScanIntegrity'))->getMethod('flaggedFilesByStatus');
        $method->setAccessible(true);
        return $method->invoke(null, $latest_hashes);
    }

    public function testFlaggedMapGroupsActionableStatusesAndExcludesStable()
    {
        $flagged = $this->flagged(array(
            'added' => array(array('filepath' => 'wp-content/uploads/evil.php')),
            'modified' => array(array('filepath' => 'wp-includes/version.php')),
            'removed' => array(array('filepath' => 'wp-admin/install.php')),
            'stable' => array(array('filepath' => 'readme.html')),
        ));

        $this->assertArrayHasKey('wp-content/uploads/evil.php', $flagged['added']);
        $this->assertArrayHasKey('wp-includes/version.php', $flagged['modified']);
        $this->assertArrayHasKey('wp-admin/install.php', $flagged['removed']);
        // "stable" files are not actionable and must never enter the allowlist.
        $this->assertArrayNotHasKey('stable', $flagged);
    }

    public function testFileNotFlaggedWithStatusIsNotActionable()
    {
        // readme.html is a core file the scan reports as "stable" (or not at all), never
        // as "added", so it must not enter the "added" allowlist even though it resolves
        // inside ABSPATH.
        $flagged = $this->flagged(array(
            'added' => array(array('filepath' => 'wp-content/uploads/evil.php')),
            'removed' => array(),
            'modified' => array(),
            'stable' => array(array('filepath' => 'readme.html')),
        ));

        $this->assertFalse(isset($flagged['added']['readme.html']));
        $this->assertTrue(isset($flagged['added']['wp-content/uploads/evil.php']));
    }

    public function testFlaggedMapIsEmptyWhenScanUnavailable()
    {
        // checkIntegrityIntegrity() returns false when the checksum API is unavailable;
        // the allowlist must then be empty so every submitted entry is rejected.
        $flagged = $this->flagged(false);

        $this->assertSame(array(), $flagged['added']);
        $this->assertSame(array(), $flagged['removed']);
        $this->assertSame(array(), $flagged['modified']);
    }
}
