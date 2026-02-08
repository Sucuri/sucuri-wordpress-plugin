<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CacheTest extends TestCase
{
    public function testDatastoreInfo()
    {
        $cache = new SucuriScanCache('auditqueue', false);

        $info = $cache->getDatastoreInfo();

        $this->assertSame('auditqueue', $info['datastore']);
        $this->assertSame('1514314767', $info['created_on']);
        $this->assertSame('1625858003', $info['updated_on']);
        $this->assertStringEndsWith('sucuri-auditqueue.php', $info['fpath']);
    }

    public function testDatastoreGetAll()
    {
        $cache = new SucuriScanCache('auditqueue', false);
        $entries = $cache->getAll();
        $this->assertSame(24, count($entries));
    }

    public function testDatastoreAddToMissingFile()
    {
        $cache = new SucuriScanCache('test-datastore', false);
        $ok = $cache->add('1234567890_0000', 'value');
        $this->assertFalse($ok);
    }

    public function testDatastoreAddToReadOnlyFile()
    {
        $datastore = 'test-datastore';
        $datastoreFullpath = SUCURI_DATA_STORAGE . "/sucuri-$datastore.php";
        $cache = new SucuriScanCache($datastore, true);
        chmod($datastoreFullpath, 000); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod

        $ok = $cache->add('1234567890_0000', 'value');
        unlink($datastoreFullpath);

        $this->assertFalse($ok);
    }
}
