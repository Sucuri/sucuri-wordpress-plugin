<?php declare(strict_types=1);

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class AuditlogsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        Functions\when('get_home_path')->justReturn(__DIR__);
        Functions\when('__')->returnArg();
        Functions\when('wp_cache_get')->justReturn(false);
        Functions\when('wp_cache_set')->justReturn(true);
        Functions\when('wp_cache_delete')->justReturn(true);
        Functions\when('get_option')->justReturn(false);

        $_SERVER['SERVER_SOFTWARE'] = 'apache';
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testTimeAuditLogFiltering()
    {
        $filters = array(
            'time' => 'today'
        );

        $auditlogs = SucuriScanAPI::getAuditLogsFromQueue($filters);

        $this->assertEquals(0, count($auditlogs['output_data']));

        // test custom date
        $filters = array(
            'time' => 'custom',
            'startDate' => '2017-12-26',
            'endDate' => '2017-12-26',
            'plugins' => 'activated'
        );

        $auditlogs = SucuriScanAPI::getAuditLogsFromQueue($filters);

        $this->assertEquals(2, count($auditlogs['output_data']));

        foreach ($auditlogs['output_data'] as $log) {
            $this->assertStringContainsString('Plugin activated', $log['message']);
        }
    }

    public function testPostAuditLogFiltering()
    {
        $filters = array(
            'posts' => 'updated'
        );

        $auditlogs = SucuriScanAPI::getAuditLogsFromQueue($filters);

        $this->assertEquals(1, count($auditlogs['output_data']));

        foreach ($auditlogs['output_data'] as $log) {
            $this->assertStringContainsString('Post was updated', $log['message']);
        }
    }

    public function testUserAuditLogFiltering()
    {
        $filters = array(
            'users' => 'deleted'
        );

        $auditlogs = SucuriScanAPI::getAuditLogsFromQueue($filters);

        $this->assertEquals(1, count($auditlogs['output_data']));

        foreach ($auditlogs['output_data'] as $log) {
            $this->assertStringContainsString('User account deleted', $log['message']);
        }
    }
}
