<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests the allowlist contract behind the settings-import :addr_header check.
 *
 * The settings import loop in sucuriscan_settings_general_importexport() accepts an
 * imported :addr_header value only when it is one of SucuriScan::allowedHttpHeaders(), the
 * same allowlist the UI enforces. The import handler itself is a free function gated on a
 * nonce and POST state, so it is not directly unit-testable; these assertions lock the
 * allowlist contract the guard depends on.
 */
final class ImportAddrHeaderValidationTest extends TestCase
{
    public function testAllowlistAcceptsTheLegitimateHeaders()
    {
        $allowed = SucuriScan::allowedHttpHeaders();

        $this->assertContains('HTTP_X_SUCURI_CLIENTIP', $allowed);
        $this->assertContains('REMOTE_ADDR', $allowed);
    }

    public function testAllowlistRejectsArbitraryServerKeys()
    {
        $allowed = SucuriScan::allowedHttpHeaders();

        // Arbitrary $_SERVER keys must not be accepted as the IP-source header.
        $this->assertNotContains('HTTP_X_EVIL_HEADER', $allowed);
        $this->assertNotContains('HTTP_HOST', $allowed);
        $this->assertNotContains('HTTP_X_FORWARDED_HOST', $allowed);
    }
}
