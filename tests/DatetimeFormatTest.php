<?php declare(strict_types=1);

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * SucuriScan::datetime() must never render an empty date when the core
 * date_format / time_format options are missing, blank or corrupt.
 *
 * @see https://wordpress.org/support/topic/last-logins-showing-blank-date-time-column/
 */
final class DatetimeFormatTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** 2023-11-14 22:13:20 UTC */
    const TIMESTAMP = 1700000000;

    /** @var array<string, mixed> */
    private $wpOptions = array();

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // A valid cached timezone keeps datetime() away from the settings file.
        Functions\when('wp_cache_get')->justReturn(array('sucuriscan_timezone' => 'UTC+00.00'));
        Functions\when('get_option')->alias(function ($option, $default = false) {
            return array_key_exists($option, $this->wpOptions) ? $this->wpOptions[$option] : $default;
        });
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testUsesSiteFormats()
    {
        $this->wpOptions = array('date_format' => 'Y-m-d', 'time_format' => 'H:i');

        $this->assertSame('2023-11-14 22:13', SucuriScan::datetime(self::TIMESTAMP));
    }

    /**
     * @dataProvider unusableFormats
     */
    public function testUnusableFormatsFallBackToDefaults(array $options, $expected)
    {
        $this->wpOptions = $options;

        $this->assertSame($expected, SucuriScan::datetime(self::TIMESTAMP));
    }

    public function unusableFormats()
    {
        return array(
            'missing' => array(array(), 'November 14, 2023 10:13 pm'),
            'blank' => array(array('date_format' => '', 'time_format' => '   '), 'November 14, 2023 10:13 pm'),
            'non-printing' => array(array('date_format' => '\\', 'time_format' => '\\ \\'), 'November 14, 2023 10:13 pm'),
            'non-string' => array(array('date_format' => array('corrupt'), 'time_format' => 'H:i'), 'November 14, 2023 22:13'),
            'date only unusable' => array(array('date_format' => '', 'time_format' => 'H:i'), 'November 14, 2023 22:13'),
            'time only unusable' => array(array('date_format' => 'Y-m-d'), '2023-11-14 10:13 pm'),
        );
    }
}
