<?php

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

require_once __DIR__ . '/autoload.php';

if (!class_exists('SucuriScanBackupCodes')) {
    require BASE_DIR . '/src/backupcodes.lib.php';
}

/**
 * Tests focused on SucuriScanBackupCodes generation, storage, normalization,
 * validation/consumption and one-time reveal semantics.
 */
final class BackupCodesTest extends TestCase
{
    /** @var array<int, array<string, mixed>> */
    private $userMeta = [];

    /** @var array<string, mixed> */
    private $transients = [];

    /** @var string|false */
    private $auditQueueSnapshot = false;

    /** @var bool */
    private $failConditionalUpdate = false;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // validate_and_consume() triggers real SucuriScanEvent logging,
        // which appends to this fixture file on disk; snapshot it so
        // tearDown() can restore it and these tests don't leave the
        // checked-in fixture dirty.
        $auditQueuePath = BASE_DIR . '/tests/fixtures/sucuri-auditqueue.php';

        if (file_exists($auditQueuePath)) {
            $this->auditQueueSnapshot = file_get_contents($auditQueuePath);
        }

        $this->userMeta = [];
        $this->transients = [];
        $this->failConditionalUpdate = false;

        // Deterministic stand-ins for WP's real hashing so tests don't
        // depend on bcrypt/phpass being available in this environment.
        Functions\when('wp_hash_password')->alias(function ($password) {
            return 'HASH:' . $password;
        });

        Functions\when('wp_check_password')->alias(function ($password, $hash, $user_id = '') {
            return $hash === 'HASH:' . $password;
        });

        Functions\when('get_user_meta')->alias(function ($user_id, $key, $single = false) {
            return isset($this->userMeta[$user_id][$key]) ? $this->userMeta[$user_id][$key] : '';
        });

        Functions\when('update_user_meta')->alias(function ($user_id, $key, $value, $previous = null) {
            if (func_num_args() === 4) {
                if ($this->failConditionalUpdate || !isset($this->userMeta[$user_id][$key]) || $this->userMeta[$user_id][$key] !== $previous) {
                    return false;
                }
            }

            $this->userMeta[$user_id][$key] = $value;
            return true;
        });

        Functions\when('delete_user_meta')->alias(function ($user_id, $key) {
            unset($this->userMeta[$user_id][$key]);
            return true;
        });

        Functions\when('set_transient')->alias(function ($key, $value, $ttl = 0) {
            $this->transients[$key] = $value;
            return true;
        });

        Functions\when('get_transient')->alias(function ($key) {
            return isset($this->transients[$key]) ? $this->transients[$key] : false;
        });

        Functions\when('delete_transient')->alias(function ($key) {
            unset($this->transients[$key]);
            return true;
        });

        // validate_and_consume() logs via SucuriScanEvent, which cascades
        // into SucuriScanOption's cache-backed option reads and i18n calls.
        Functions\when('wp_cache_get')->justReturn(false);
        Functions\when('wp_cache_set')->justReturn(true);
        Functions\when('wp_cache_delete')->justReturn(true);
        Functions\when('__')->returnArg(1);
        Functions\when('wp_strip_all_tags')->alias(function ($text) {
            return trim(strip_tags((string) $text));
        });

        // validate_and_consume() logging cascades into the plugin's local
        // audit-log queue (SucuriScanEvent::sendLogToQueue()); neutralize
        // its filesystem/hardening side effects for these unit tests.
        Functions\when('sucuriscan_lastlogins_datastore_exists')->justReturn(true);
        Functions\when('get_home_path')->justReturn('/');
    }

    protected function tearDown(): void
    {
        if ($this->auditQueueSnapshot !== false) {
            file_put_contents(BASE_DIR . '/tests/fixtures/sucuri-auditqueue.php', $this->auditQueueSnapshot);
        }

        Monkey\tearDown();
        parent::tearDown();
    }

    public function testGenerateForUserReturnsTenUniqueFormattedCodes(): void
    {
        $codes = SucuriScanBackupCodes::generate_for_user(101);

        $this->assertCount(10, $codes);
        $this->assertCount(10, array_unique($codes), 'codes should be unique');

        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^[23456789A-HJ-KM-NP-Z]{4}-[23456789A-HJ-KM-NP-Z]{4}$/', $code);
        }
    }

    public function testGenerateForUserStoresHashesNotPlaintext(): void
    {
        $codes = SucuriScanBackupCodes::generate_for_user(101);

        $stored = $this->userMeta[101][SucuriScanBackupCodes::META_KEY];

        $this->assertCount(10, $stored);

        foreach ($codes as $index => $code) {
            $this->assertNotSame($code, $stored[$index]);
            $this->assertStringStartsWith('HASH:', $stored[$index]);
        }
    }

    public function testMaybeGenerateForUserIsIdempotent(): void
    {
        $first = SucuriScanBackupCodes::maybe_generate_for_user(202);
        $this->assertCount(10, $first);

        $second = SucuriScanBackupCodes::maybe_generate_for_user(202);
        $this->assertNull($second, 'must not regenerate when a set already exists');

        // Confirm the originally stored hashes were not overwritten.
        $stored = $this->userMeta[202][SucuriScanBackupCodes::META_KEY];
        $this->assertTrue(SucuriScanBackupCodes::validate_and_consume(202, $first[0]));
        unset($stored);
    }

    public function testHasCodesReflectsGenerationState(): void
    {
        $this->assertFalse(SucuriScanBackupCodes::has_codes(303));

        SucuriScanBackupCodes::generate_for_user(303);

        $this->assertTrue(SucuriScanBackupCodes::has_codes(303));
    }

    public function testDeleteForUserClearsStoredCodes(): void
    {
        SucuriScanBackupCodes::generate_for_user(404);
        $this->assertTrue(SucuriScanBackupCodes::has_codes(404));

        SucuriScanBackupCodes::delete_for_user(404);

        $this->assertFalse(SucuriScanBackupCodes::has_codes(404));
    }

    public function testRemainingCountDecrementsOnConsumption(): void
    {
        $codes = SucuriScanBackupCodes::generate_for_user(505);
        $this->assertSame(10, SucuriScanBackupCodes::remaining_count(505));

        SucuriScanBackupCodes::validate_and_consume(505, $codes[0]);

        $this->assertSame(9, SucuriScanBackupCodes::remaining_count(505));
    }

    public function testValidateAndConsumeAcceptsCodeOnlyOnce(): void
    {
        $codes = SucuriScanBackupCodes::generate_for_user(606);

        $this->assertTrue(SucuriScanBackupCodes::validate_and_consume(606, $codes[3]));
        $this->assertFalse(SucuriScanBackupCodes::validate_and_consume(606, $codes[3]), 'a code must be single-use');
    }

    public function testValidateAndConsumeRejectsCodeWhenConditionalUpdateFails(): void
    {
        $codes = SucuriScanBackupCodes::generate_for_user(607);
        $this->failConditionalUpdate = true;

        $this->assertFalse(SucuriScanBackupCodes::validate_and_consume(607, $codes[0]));
        $this->assertSame(10, SucuriScanBackupCodes::remaining_count(607));
    }

    public function testValidateAndConsumeNormalizesCaseSpacingAndDashes(): void
    {
        $codes = SucuriScanBackupCodes::generate_for_user(707);
        $raw = $codes[0]; // formatted as XXXX-XXXX

        $messy = ' ' . strtolower(str_replace('-', ' ', $raw)) . ' ';

        $this->assertTrue(SucuriScanBackupCodes::validate_and_consume(707, $messy));
    }

    public function testValidateAndConsumeRejectsUnknownOrMalformedCode(): void
    {
        SucuriScanBackupCodes::generate_for_user(808);

        $this->assertFalse(SucuriScanBackupCodes::validate_and_consume(808, 'ZZZZ-ZZZZ'));
        $this->assertFalse(SucuriScanBackupCodes::validate_and_consume(808, 'short'));
        $this->assertFalse(SucuriScanBackupCodes::validate_and_consume(808, ''));
    }

    public function testStashAndConsumeRevealIsSingleRead(): void
    {
        $codes = ['AAAA-1111', 'BBBB-2222'];

        SucuriScanBackupCodes::stash_reveal(909, $codes);

        $first = SucuriScanBackupCodes::consume_reveal(909);
        $this->assertSame($codes, $first);

        $second = SucuriScanBackupCodes::consume_reveal(909);
        $this->assertSame([], $second, 'a stashed reveal must not be readable twice');
    }

    public function testStashAndConsumeRevealRedirectIsSingleRead(): void
    {
        $redirect = 'https://example.com/after-login';

        SucuriScanBackupCodes::stash_reveal(910, array('AAAA-1111'), $redirect);

        $this->assertSame($redirect, SucuriScanBackupCodes::consume_reveal_redirect(910));
        $this->assertSame('', SucuriScanBackupCodes::consume_reveal_redirect(910));
    }

    public function testNormalizeCodeUppercasesAndStripsInvalidCharacters(): void
    {
        $this->assertSame('ABCD2345', SucuriScanBackupCodes::normalize_code(' abcd-2345 '));
        $this->assertSame('', SucuriScanBackupCodes::normalize_code('01IlOo'));
    }
}
