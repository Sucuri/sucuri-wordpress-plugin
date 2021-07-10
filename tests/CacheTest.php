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
        $this->assertSame(20, count($entries));
    }
}
