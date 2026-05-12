<?php

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

require_once __DIR__ . '/autoload.php';

if (!defined('SUCURISCAN_MAX_PAGINATION_BUTTONS')) {
    define('SUCURISCAN_MAX_PAGINATION_BUTTONS', 16);
}

// ── WP_User_Query stub ───────────────────────────────────────────────────────
// Tests push expected responses before calling users_admin_section().
// The class pops one entry per instantiation, matching the three queries:
//   1. Pagination query  ($dbquery)
//   2. Activated-count   ($count_query)
//   3. Per-page active   ($active_query)  — skipped when page has no users
//
// Each queue entry: ['total' => int, 'results' => array, 'args' => array]
// resetQueue() is called in setUp() so tests never bleed into each other.
//
if (!class_exists('WP_User_Query')) {
    class WP_User_Query
    {
        private static $queue = array();
        private static $recorded = array();

        private $total;
        private $results;

        public static function pushResponse($total, array $results)
        {
            self::$queue[] = array('total' => (int) $total, 'results' => $results);
        }

        public static function resetQueue()
        {
            self::$queue    = array();
            self::$recorded = array();
        }

        public static function getRecordedArgs()
        {
            return self::$recorded;
        }

        public function __construct(array $args = array())
        {
            self::$recorded[] = $args;

            if (!empty(self::$queue)) {
                $entry         = array_shift(self::$queue);
                $this->total   = (int) $entry['total'];
                $this->results = (array) $entry['results'];
            } else {
                $this->total   = 0;
                $this->results = array();
            }
        }

        public function get_total()
        {
            return $this->total;
        }

        public function get_results()
        {
            return $this->results;
        }
    }
}

// ── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Build a minimal user object as returned by a paginated WP_User_Query
 * (fields => array('ID','user_login','user_email')).
 */
function make_page_user($id)
{
    return (object) array(
        'ID'         => (int) $id,
        'user_login' => 'user' . $id,
        'user_email' => 'user' . $id . '@example.com',
    );
}

/**
 * Push the three standard WP_User_Query responses expected by users_admin_section().
 *
 * @param array $page_users  Objects returned by the pagination query.
 * @param int   $total_users Total matched by the pagination query.
 * @param int   $activated   Total users with 2FA active (count query get_total).
 * @param int[] $active_ids  IDs returned by the per-page active query.
 */
function push_standard_responses(array $page_users, $total_users, $activated, array $active_ids)
{
    WP_User_Query::pushResponse($total_users, $page_users); // 1. pagination
    WP_User_Query::pushResponse($activated, array());       // 2. count (results unused)
    WP_User_Query::pushResponse(count($active_ids), $active_ids); // 3. per-page active
}

// ── Test class ───────────────────────────────────────────────────────────────

/**
 * Tests for SucuriScanTwoFactor::users_admin_section() query refactoring.
 *
 * These tests verify that the method uses WP_User_Query (not raw $wpdb)
 * and that the query arguments are correctly shaped.
 */
final class TwoFactorUsersAdminTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        WP_User_Query::resetQueue();

        // Provide permission by default; individual tests override when needed.
        Functions\when('current_user_can')->justReturn(true);

        // Translation stubs (esc_url / esc_attr are real functions defined in autoload.php).
        Functions\when('__')->returnArg(1);
        Functions\when('esc_html__')->returnArg(1);
        Functions\when('esc_html')->returnArg(1);

        // WP URL/nonce helpers used by template calls.
        Functions\when('wp_create_nonce')->justReturn('nonce');
        Functions\when('admin_url')->alias(fn($p = '') => 'https://example.com/wp-admin/' . ltrim((string) $p, '/'));
        Functions\when('trailingslashit')->alias(fn($p) => rtrim($p, '/') . '/');

        SucuriScanOption::updateOption(':twofactor_mode', 'disabled');
        SucuriScanOption::updateOption(':twofactor_users', array());
    }

    protected function tearDown(): void
    {
        WP_User_Query::resetQueue();
        Monkey\tearDown();
        parent::tearDown();
    }

    // ── permission gate ───────────────────────────────────────────────────────

    public function testPermissionGateReturnsEmptyString(): void
    {
        Functions\when('current_user_can')->justReturn(false);

        $result = SucuriScanTwoFactor::users_admin_section();

        $this->assertSame('', $result);

        // WP_User_Query must never be touched when access is denied.
        $this->assertCount(0, WP_User_Query::getRecordedArgs());
    }

    // ── return type ───────────────────────────────────────────────────────────

    public function testAlwaysReturnsString(): void
    {
        push_standard_responses(array(), 0, 0, array());

        $result = SucuriScanTwoFactor::users_admin_section();

        $this->assertIsString($result);
    }

    // ── pagination query (query #1) shape ─────────────────────────────────────

    public function testPaginationQueryFetchesRequiredFields(): void
    {
        push_standard_responses(array(), 0, 0, array());

        SucuriScanTwoFactor::users_admin_section();

        $args = WP_User_Query::getRecordedArgs();
        $this->assertGreaterThanOrEqual(1, count($args));

        $paginationArgs = $args[0];
        $this->assertArrayHasKey('fields', $paginationArgs);
        $this->assertContains('ID', $paginationArgs['fields']);
        $this->assertContains('user_login', $paginationArgs['fields']);
        $this->assertContains('user_email', $paginationArgs['fields']);
    }

    public function testPaginationQueryHasLimitAndOffset(): void
    {
        push_standard_responses(array(), 0, 0, array());

        SucuriScanTwoFactor::users_admin_section();

        $args = WP_User_Query::getRecordedArgs()[0];
        $this->assertArrayHasKey('number', $args);
        $this->assertGreaterThan(0, $args['number']);
        $this->assertArrayHasKey('offset', $args);
        $this->assertGreaterThanOrEqual(0, $args['offset']);
    }

    // ── count query (query #2) shape ─────────────────────────────────────────

    public function testCountQueryUsesMetaQueryForSecretKey(): void
    {
        push_standard_responses(
            array(make_page_user(1), make_page_user(2)),
            2,
            1,
            array(1)
        );

        SucuriScanTwoFactor::users_admin_section();

        $args = WP_User_Query::getRecordedArgs();
        $this->assertGreaterThanOrEqual(2, count($args), 'count query (index 1) must exist');

        $countArgs = $args[1];
        $this->assertArrayHasKey('meta_query', $countArgs, 'count query must use meta_query');

        $mq = $countArgs['meta_query'];
        $this->assertIsArray($mq);
        $this->assertNotEmpty($mq);

        $clause = $mq[0];
        $this->assertSame(SucuriScanTwoFactor::SECRET_META_KEY, $clause['key']);
        $this->assertSame('', $clause['value']);
        $this->assertSame('!=', $clause['compare']);
    }

    public function testCountQueryEnablesCountTotal(): void
    {
        push_standard_responses(
            array(make_page_user(1)),
            1,
            0,
            array()
        );

        SucuriScanTwoFactor::users_admin_section();

        $countArgs = WP_User_Query::getRecordedArgs()[1];
        $this->assertArrayHasKey('count_total', $countArgs);
        $this->assertTrue($countArgs['count_total']);
    }

    public function testCountQueryLimitsResultsToOne(): void
    {
        push_standard_responses(
            array(make_page_user(1)),
            1,
            0,
            array()
        );

        SucuriScanTwoFactor::users_admin_section();

        $countArgs = WP_User_Query::getRecordedArgs()[1];
        $this->assertArrayHasKey('number', $countArgs);
        $this->assertSame(1, (int) $countArgs['number']);
    }

    public function testCountQueryRequestsOnlyIdField(): void
    {
        push_standard_responses(
            array(make_page_user(1)),
            1,
            0,
            array()
        );

        SucuriScanTwoFactor::users_admin_section();

        $countArgs = WP_User_Query::getRecordedArgs()[1];
        $this->assertArrayHasKey('fields', $countArgs);
        $this->assertSame('ID', $countArgs['fields']);
    }

    // ── per-page active query (query #3) shape ────────────────────────────────

    public function testActiveQueryUsesIncludeWithPageUserIds(): void
    {
        $page_users = array(make_page_user(10), make_page_user(20), make_page_user(30));
        push_standard_responses($page_users, 3, 2, array(10, 20));

        SucuriScanTwoFactor::users_admin_section();

        $args = WP_User_Query::getRecordedArgs();
        $this->assertGreaterThanOrEqual(3, count($args), 'active query (index 2) must exist');

        $activeArgs = $args[2];
        $this->assertArrayHasKey('include', $activeArgs);
        $this->assertEqualsCanonicalizing(array(10, 20, 30), $activeArgs['include']);
    }

    public function testActiveQueryUsesMetaQueryForSecretKey(): void
    {
        $page_users = array(make_page_user(5));
        push_standard_responses($page_users, 1, 1, array(5));

        SucuriScanTwoFactor::users_admin_section();

        $activeArgs = WP_User_Query::getRecordedArgs()[2];
        $this->assertArrayHasKey('meta_query', $activeArgs);

        $clause = $activeArgs['meta_query'][0];
        $this->assertSame(SucuriScanTwoFactor::SECRET_META_KEY, $clause['key']);
        $this->assertSame('', $clause['value']);
        $this->assertSame('!=', $clause['compare']);
    }

    // ── skip active query when page is empty ──────────────────────────────────

    public function testActiveQueryIsSkippedWhenPageHasNoUsers(): void
    {
        // Only push 2 responses: pagination (0 users) + count.
        WP_User_Query::pushResponse(0, array());  // 1. pagination
        WP_User_Query::pushResponse(0, array());  // 2. count

        SucuriScanTwoFactor::users_admin_section();

        $this->assertCount(2, WP_User_Query::getRecordedArgs(),
            'active query must not be issued when the page has no users');
    }

    // ── does not crash on edge cases ─────────────────────────────────────────

    public function testEmptySiteDoesNotCrash(): void
    {
        WP_User_Query::pushResponse(0, array());
        WP_User_Query::pushResponse(0, array());

        $result = SucuriScanTwoFactor::users_admin_section();

        $this->assertIsString($result);
    }

    public function testLargeSiteDoesNotCrash(): void
    {
        $page_users = array_map('make_page_user', range(1, 10));
        push_standard_responses($page_users, 50000, 12000, range(1, 5));

        $result = SucuriScanTwoFactor::users_admin_section();

        $this->assertIsString($result);
    }
}
