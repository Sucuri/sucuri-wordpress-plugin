<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/autoload.php';
require_once BASE_DIR . '/src/totp.core.php';

final class TotpCalcTest extends TestCase
{
    /**
     * Helper: RFC 6238 test seeds are ASCII (not Base32). We must Base32-encode
     * the ASCII seed before passing it into calc_totp(), which expects Base32.
     */
    private static function base32KeyFromAscii(string $ascii): string
    {
        return SucuriScanTOTP::base32_encode($ascii);
    }

    /**
     * RFC 6238 Appendix B known vectors adapted to class defaults:
     *  - RFC publishes 8-digit OTPs with SHA-1, X=30, T0=0 at specific timestamps.
     *  - Implementation uses DEFAULT_DIGIT_COUNT (typically 6), so we compare
     *    the last 6 digits of each published 8-digit result.
     *
     * Times from Appendix B: 59, 1111111109, 1111111111, 1234567890, 2000000000, 20000000000.
     * See: RFC 6238 Appendix B test vectors. 
     */
    public function testCalcTotp_Rfc6238_Sha1_6Digits_Defaults(): void
    {
        // RFC 6238 SHA-1 seed (ASCII, 20 bytes)
        $seedSha1Ascii = "12345678901234567890";
        $keySha1 = self::base32KeyFromAscii($seedSha1Ascii);

        // Published 8-digit SHA-1 OTPs from RFC 6238 Appendix B
        $publishedSha1_8Digit = [
            59 => '94287082',
            1111111109 => '07081804',
            1111111111 => '14050471',
            1234567890 => '89005924',
            2000000000 => '69279037',
            20000000000 => '65353130',
        ];

        foreach ($publishedSha1_8Digit as $ts => $otp8) {
            // Compare last 6 digits (equivalent to modulo 10^6)
            $expected6 = substr($otp8, -SucuriScanTOTP::DEFAULT_DIGIT_COUNT);

            // With T0=0 and X=30s (RFC), step = floor(t / X)
            $step = (int) floor($ts / SucuriScanTOTP::DEFAULT_TIME_STEP_SEC);

            $actual = SucuriScanTOTP::calc_totp($keySha1, $step);
            $this->assertSame(
                $expected6,
                $actual,
                "Mismatch at t={$ts} (expected {$expected6}, got {$actual})"
            );
        }
    }

    /**
     * Sanity: with defaults (digits=DEFAULT_DIGIT_COUNT, sha1, step=now) we should get
     * a numeric string of the expected length.
     */
    public function testCalcTotp_Defaults_AreWellFormed(): void
    {
        $key = SucuriScanTOTP::base32_encode("dummysecrethere!!");
        $code = SucuriScanTOTP::calc_totp($key, false);

        $this->assertSame(SucuriScanTOTP::DEFAULT_DIGIT_COUNT, strlen($code));
        $this->assertMatchesRegularExpression('/^\d+$/', $code);
    }

    /**
     * Truncation check: HOTP/TOTP uses dynamic truncation (RFC 4226 §5.3).
     * Your implementation uses the low 4 bits of the *last HMAC byte* to choose
     * the 31-bit code window. For SHA-1 (your default), this yields the same
     * results as older "byte 19" wording; for longer digests (SHA-256/512),
     * "last byte" removes ambiguity (see RFC 6238 errata).
     *
     * We assert against one RFC timestamp using SHA-1 and compare the last 6 digits.
     */
    public function testCalcTotp_TruncationBehavior_Sha1_Defaults(): void
    {
        $seedSha1Ascii = "12345678901234567890";
        $keySha1 = self::base32KeyFromAscii($seedSha1Ascii);

        $ts = 1234567890; // in RFC 6238 Appendix B
        $step = (int) floor($ts / SucuriScanTOTP::DEFAULT_TIME_STEP_SEC);

        // Published 8-digit SHA-1 at t=1234567890 is 89005924 (RFC 6238, App. B)
        $expected6 = substr('89005924', -SucuriScanTOTP::DEFAULT_DIGIT_COUNT);

        $actual = SucuriScanTOTP::calc_totp($keySha1, $step);
        $this->assertSame($expected6, $actual);
    }

    /**
     * Smoke test: very large timestamp to exercise pack64() and ensure we still
     * match RFC vectors modulo 10^DEFAULT_DIGIT_COUNT.
     */
    public function testCalcTotp_FarFutureTimestamp(): void
    {
        $seedSha1Ascii = "12345678901234567890";
        $keySha1 = self::base32KeyFromAscii($seedSha1Ascii);

        $ts = 20000000000; // present in RFC vectors
        $step = (int) floor($ts / SucuriScanTOTP::DEFAULT_TIME_STEP_SEC);

        // RFC 8-digit SHA-1 at this time is 65353130 -> last 6 = 353130
        $expected6 = substr('65353130', -SucuriScanTOTP::DEFAULT_DIGIT_COUNT);

        $actual = SucuriScanTOTP::calc_totp($keySha1, $step);
        $this->assertSame($expected6, $actual);
    }

    /**
     * pack64 must return an 8-byte big-endian buffer. Validate a few canonical values.
     * Uses 'N2' to form the expected big-endian 64-bit buffer in a platform-neutral way.
     */
    public function testPack64_BigEndianLayout_KnownValues(): void
    {
        // value => expected hex
        $cases = [
            0 => '0000000000000000',
            1 => '0000000000000001',
            0x7fffffff => '000000007fffffff',
            0x80000000 => '0000000080000000', // requires 64-bit PHP to represent as int
        ];

        foreach ($cases as $value => $expectedHex) {
            if ($value === 0x80000000 && PHP_INT_SIZE < 8) {
                // On 32-bit PHP this literal may not be representable as int; skip safely.
                $this->markTestSkipped('0x80000000 not representable as int on 32-bit PHP.');
                continue;
            }

            $packed = SucuriScanTOTP::pack64($value);

            // Compute expected bytes without relying on 64-bit shifts
            $hi = (int) (($value >> 32) & 0xFFFFFFFF); // safe if PHP_INT_SIZE >= 8, else zero for smaller values
            if (PHP_INT_SIZE < 8) {
                // For 32-bit and small values in $cases, hi should be 0.
                $hi = 0;
            }
            $lo = $value & 0xFFFFFFFF;
            $expected = pack('N2', $hi, $lo);

            $this->assertSame(bin2hex($expected), bin2hex($packed));
            $this->assertSame(8, strlen($packed));
        }
    }

    /**
     * If PHP provides pack('J', ...), our pack64 should match it exactly.
     * 'J' is unsigned 64-bit big-endian as of PHP 5.6.3+. 
     */
    public function testPack64_MatchesPackJ_WhenAvailable(): void
    {
        if (version_compare(PHP_VERSION, '5.6.3', '<')) {
            $this->markTestSkipped("pack('J', ...) not available before PHP 5.6.3.");
            return;
        }

        // Choose a few values that are safe on both 64-bit and 32-bit (<= 0xFFFFFFFF on 32-bit)
        $values = [0, 1, 0x12345678, 0xFFFFFFFF];

        foreach ($values as $v) {
            if (PHP_INT_SIZE < 8 && $v > 0x7FFFFFFF) {
                // On 32-bit PHP, ints are signed; 0xFFFFFFFF won't be representable as positive.
                // Skip to avoid undefined behavior on that platform.
                $this->markTestSkipped('Value not representable as positive 32-bit integer.');
                continue;
            }

            $expected = @pack('J', $v); // silence in case of platform oddities
            if ($expected === false) {
                $this->markTestSkipped("pack('J', ...) not supported on this build.");
                continue;
            }

            $actual = SucuriScanTOTP::pack64($v);
            $this->assertSame(bin2hex($expected), bin2hex($actual));
        }
    }

    /**
     * Negative counters are invalid and should be rejected.
     */
    public function testPack64_RejectsNegative(): void
    {
        $this->expectException(\Exception::class);
        SucuriScanTOTP::pack64(-1);
    }
}
