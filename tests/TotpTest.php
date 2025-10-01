<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/autoload.php';
require_once BASE_DIR . '/src/totp.core.php';

final class TotpTest extends TestCase
{
    public function testBase32EncodeDecodeRoundTrip(): void
    {
        $raw = "Hello world";
        $encoded = SucuriScanTOTP::base32_encode($raw);
        $this->assertNotSame('', $encoded);
        $decoded = SucuriScanTOTP::base32_decode($encoded);
        $this->assertSame($raw, $decoded);
    }

    public function testBase32EncodeKnownValues(): void
    {
        $vectors = [
            '' => '',
            'f' => 'MY',
            'fo' => 'MZXQ',
            'foo' => 'MZXW6',
            'foob' => 'MZXW6YQ',
            'fooba' => 'MZXW6YTB',
            'foobar' => 'MZXW6YTBOI',
        ];

        foreach ($vectors as $plain => $expected) {
            $this->assertSame($expected, SucuriScanTOTP::base32_encode($plain), "Failed encoding vector for '{$plain}'");
            if ($expected !== '') {
                $this->assertSame($plain, SucuriScanTOTP::base32_decode($expected), "Failed decoding vector for '{$plain}'");
            }
        }
    }

    public function testBase32DecodeThrowsOnInvalidCharacters(): void
    {
        $this->expectException(\Exception::class);
        SucuriScanTOTP::base32_decode('!@#');
    }

    public function testGenerateKey(): void
    {
        $key1 = SucuriScanTOTP::generate_key();
        $key2 = SucuriScanTOTP::generate_key();

        // DEFAULT_KEY_BIT_SIZE is 160 => 20 bytes => 32 Base32 chars (no padding)
        $this->assertSame(32, strlen($key1));
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $key1);
        $this->assertNotSame($key1, $key2, 'Two consecutive keys should differ');
    }

    public function testGetAuthcodeValidTicktimeForCurrentStep(): void
    {
        $stepSec = SucuriScanTOTP::DEFAULT_TIME_STEP_SEC;

        // Avoid time boundary flakiness around TOTP window edges
        $phase = time() % $stepSec;
        if ($phase >= $stepSec - 2) {
            sleep(2);
        } elseif ($phase < 1) {
            sleep(1);
        }

        $key = SucuriScanTOTP::generate_key();
        $currentStep = (int) floor(time() / $stepSec);
        $code = SucuriScanTOTP::calc_totp($key, $currentStep);

        $tick = SucuriScanTOTP::get_authcode_valid_ticktime($key, $code);
        $this->assertIsNumeric($tick);
        $this->assertSame((int) ($currentStep * $stepSec), (int) $tick);

        // Mutate one digit to ensure invalid
        $badCode = $code;
        $idx = strlen($badCode) - 1;
        $badCode[$idx] = (string) ((((int) $badCode[$idx]) + 1) % 10);
        $this->assertFalse(SucuriScanTOTP::get_authcode_valid_ticktime($key, $badCode));
    }
}
