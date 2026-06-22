<?php

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

require_once __DIR__ . '/autoload.php';

if (!defined('SUCURISCAN_TWOFACTOR_USERS_PER_PAGE')) {
    define('SUCURISCAN_TWOFACTOR_USERS_PER_PAGE', 25);
}

if (!defined('SUCURISCAN_MAX_PAGINATION_BUTTONS')) {
    define('SUCURISCAN_MAX_PAGINATION_BUTTONS', 16);
}

/**
 * Exception used to capture wp_send_json() payloads in tests (mirrors the
 * way WordPress halts execution after emitting JSON).
 */
class TwoFactorSendJsonStop extends \RuntimeException
{
    /** @var mixed */
    public $payload;

    public function __construct($payload)
    {
        parent::__construct('wp_send_json');
        $this->payload = $payload;
    }
}

/**
 * Minimal WP_User_Query test double. It branches on the query arguments so the
 * order in which the production code instantiates queries does not matter:
 *
 *  - meta_key set ............ "activated users" count query
 *  - fields === 'ID', number 1 "total users" count query
 *  - otherwise ............... the paginated list query
 */
if (!class_exists('WP_User_Query')) {
    class WP_User_Query
    {
        /** @var array Captured argument sets for every instantiation. */
        public static $captured = array();

        /** @var array Objects returned by the paginated list query. */
        public static $listResults = array();
        /** @var int Total reported by the paginated list query. */
        public static $listTotal = 0;
        /** @var int Total reported by the "total users" count query. */
        public static $totalUsers = 0;
        /** @var int Total reported by the "activated users" count query. */
        public static $activatedUsers = 0;

        private $results = array();
        private $total = 0;

        public function __construct($args)
        {
            self::$captured[] = $args;

            if (isset($args['meta_key'])) {
                $this->total = self::$activatedUsers;
                $this->results = array();
            } elseif (
                isset($args['fields']) && $args['fields'] === 'ID'
                && isset($args['number']) && (int) $args['number'] === 1
            ) {
                $this->total = self::$totalUsers;
                $this->results = array();
            } else {
                $this->results = self::$listResults;
                $this->total = self::$listTotal;
            }
        }

        public static function reset()
        {
            self::$captured = array();
            self::$listResults = array();
            self::$listTotal = 0;
            self::$totalUsers = 0;
            self::$activatedUsers = 0;
        }

        /** Return the captured args for the paginated list query (no meta, fields array). */
        public static function listArgs()
        {
            foreach (self::$captured as $args) {
                if (!isset($args['meta_key']) && isset($args['fields']) && is_array($args['fields'])) {
                    return $args;
                }
            }

            return null;
        }

        public function get_results()
        {
            return $this->results;
        }

        public function get_total()
        {
            return $this->total;
        }
    }
}

/**
 * Tests for the paginated/searchable Two-Factor users table backend
 * (SucuriScanTwoFactor::ajaxUsersList and its helpers). These guard the
 * scalability fix that prevents the 2FA page from loading every registered
 * user at once on large sites (e.g. WooCommerce).
 */
final class TwoFactorUsersListTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        Functions\when('__')->returnArg(1);
        Functions\when('esc_html__')->returnArg(1);
        Functions\when('esc_html')->returnArg(1);
        Functions\when('sanitize_text_field')->alias(fn($v) => is_string($v) ? trim($v) : '');

        WP_User_Query::reset();

        unset($GLOBALS['__test_current_user_can']);
        $_GET = array();
        $_POST = array();
        $_REQUEST = array();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        unset($GLOBALS['__test_current_user_can']);
        $_GET = array();
        $_POST = array();
        $_REQUEST = array();
        parent::tearDown();
    }

    /**
     * @param string $name Protected/static method name.
     * @return ReflectionMethod
     */
    private function method(string $name): ReflectionMethod
    {
        $ref = new ReflectionClass(SucuriScanTwoFactor::class);
        $m = $ref->getMethod($name);
        $m->setAccessible(true);

        return $m;
    }

    public function testQueryArgsBoundsNumberAndComputesOffset(): void
    {
        $args = $this->method('build_users_query_args')->invoke(null, 3, 25, '');

        $this->assertSame(25, $args['number'], 'number must be bounded to the per-page size');
        $this->assertSame(50, $args['offset'], 'offset must be (page-1)*perPage');
        $this->assertTrue($args['count_total']);
        $this->assertSame(array('ID', 'user_login', 'user_email', 'display_name'), $args['fields']);
        $this->assertArrayNotHasKey('search', $args, 'no search key when term is empty');
    }

    public function testQueryArgsFirstPageOffsetIsZero(): void
    {
        $args = $this->method('build_users_query_args')->invoke(null, 1, 25, '');
        $this->assertSame(0, $args['offset']);
    }

    public function testQueryArgsInvalidPageFallsBackToFirst(): void
    {
        $args = $this->method('build_users_query_args')->invoke(null, 0, 25, '');
        $this->assertSame(0, $args['offset']);
        $this->assertSame(25, $args['number']);
    }

    public function testQueryArgsWrapsSearchAndLimitsColumns(): void
    {
        $args = $this->method('build_users_query_args')->invoke(null, 1, 25, 'john');

        $this->assertSame('*john*', $args['search']);
        $this->assertSame(
            array('user_login', 'user_email', 'display_name'),
            $args['search_columns']
        );
    }

    public function testSearchTermAbsentReturnsEmpty(): void
    {
        $term = $this->method('get_users_search_term')->invoke(null);
        $this->assertSame('', $term);
    }

    public function testSearchTermIsSanitizedAndLengthBounded(): void
    {
        $_GET['twofactor_search'] = str_repeat('a', 200);
        $term = $this->method('get_users_search_term')->invoke(null);

        $this->assertSame(128, strlen($term), 'search term must be capped at 128 chars');
    }

    public function testSearchTermPreservesSpecialCharacters(): void
    {
        // Emails/display names can legitimately contain & and ' — these must
        // not be HTML-encoded before being handed to WP_User_Query.
        $_POST['twofactor_search'] = "O'Brien & Co";
        $term = $this->method('get_users_search_term')->invoke(null);

        $this->assertSame("O'Brien & Co", $term);
    }

    public function testSearchTermPrefersPostOverGet(): void
    {
        $_GET['twofactor_search'] = 'from-get';
        $_POST['twofactor_search'] = 'from-post';

        $this->assertSame('from-post', $this->method('get_users_search_term')->invoke(null));
    }

    public function testRenderUserRowsReturnsEmptyForInvalidInput(): void
    {
        $m = $this->method('render_user_rows');
        $this->assertSame('', $m->invoke(null, array()));
        $this->assertSame('', $m->invoke(null, 'not-an-array'));
        // Objects without an ID are skipped without touching the template engine.
        $this->assertSame('', $m->invoke(null, array((object) array('user_login' => 'x'))));
    }

    public function testPolicyStatusAllUsersActivated(): void
    {
        WP_User_Query::$totalUsers = 5;
        WP_User_Query::$activatedUsers = 5;

        list($id, $text) = $this->method('compute_policy_status')->invoke(null);

        $this->assertSame(1, $id);
        $this->assertSame('Activated for all users', $text);
    }

    public function testPolicyStatusSomeUsersActivated(): void
    {
        WP_User_Query::$totalUsers = 5;
        WP_User_Query::$activatedUsers = 2;

        list($id, $text) = $this->method('compute_policy_status')->invoke(null);

        $this->assertSame(2, $id);
        $this->assertSame('Activated for some users', $text);
    }

    public function testPolicyStatusNoneActivated(): void
    {
        WP_User_Query::$totalUsers = 5;
        WP_User_Query::$activatedUsers = 0;

        list($id, $text) = $this->method('compute_policy_status')->invoke(null);

        $this->assertSame(0, $id);
        $this->assertSame('Deactivated', $text);
    }

    public function testActivatedCountUsesMetaExistsQuery(): void
    {
        WP_User_Query::$totalUsers = 3;
        WP_User_Query::$activatedUsers = 1;

        $this->method('compute_policy_status')->invoke(null);

        $hasMetaQuery = false;

        foreach (WP_User_Query::$captured as $args) {
            if (isset($args['meta_key']) && $args['meta_compare'] === 'EXISTS') {
                $hasMetaQuery = true;
            }
        }

        $this->assertTrue($hasMetaQuery, 'activated count must use a meta EXISTS query, not iterate users');
    }

    public function testAjaxDeniedWhenUserCannotListUsers(): void
    {
        $GLOBALS['__test_current_user_can'] = false;
        $_POST['form_action'] = 'get_twofactor_users';

        Functions\when('wp_send_json')->alias(function ($payload) {
            throw new TwoFactorSendJsonStop($payload);
        });

        try {
            SucuriScanTwoFactor::ajaxUsersList();
            $this->fail('Expected wp_send_json to halt execution');
        } catch (TwoFactorSendJsonStop $e) {
            $this->assertSame('', $e->payload['content']);
            $this->assertSame(0, $e->payload['total']);
            $this->assertStringContainsString('not allowed', $e->payload['statusText']);
        }

        // No user queries should run when permission is denied.
        $this->assertCount(0, WP_User_Query::$captured);
    }

    public function testAjaxNoopWhenFormActionMismatch(): void
    {
        $_POST['form_action'] = 'something_else';

        // wp_send_json must never be called for a mismatched action.
        Functions\when('wp_send_json')->alias(function () {
            throw new TwoFactorSendJsonStop('should-not-run');
        });

        $this->assertNull(SucuriScanTwoFactor::ajaxUsersList());
        $this->assertCount(0, WP_User_Query::$captured);
    }
}
