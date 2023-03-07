<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

final class HardeningTest extends TestCase
{
    public function testHtaccessWindows()
    {
        define('ABSPATH', 'C:\sites\mysite\web\wp/');
        $method = new ReflectionMethod('SucuriScanHardening', 'htaccess');
        $method->setAccessible(true);

        $path = $method->invokeArgs(null, array('C:/sites/mysite/web/wp/sucuri'));

        $this->assertSame('C:/sites/mysite/web/wp/sucuri/.htaccess', $path);
    }

    public function testHtaccessNonWindows()
    {
        define('ABSPATH', '/sites/mysite/web/wp/');
        $method = new ReflectionMethod('SucuriScanHardening', 'htaccess');
        $method->setAccessible(true);

        $path = $method->invokeArgs(null, array('/sites/mysite/web/wp/sucuri'));

        $this->assertSame('/sites/mysite/web/wp/sucuri/.htaccess', $path);
    }
}
