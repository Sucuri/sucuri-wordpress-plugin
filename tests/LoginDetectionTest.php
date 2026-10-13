<?php declare(strict_types=1);

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

if (!class_exists('WP_User')) {
    class WP_User
    {
        public $ID;
        public $user_login = '';
        public function __construct($id)
        {
            $this->ID = (int) $id;
        }
    }
}

if (!class_exists('WP_Session_Tokens')) {
    /**
     * Stand-in for core's session manager: a token is valid while listed in $live.
     */
    class WP_Session_Tokens
    {
        /** @var array<int, string> user ID => live session token */
        public static $live = array();

        private $user_id;

        public static function get_instance($user_id)
        {
            $manager = new self();
            $manager->user_id = (int) $user_id;
            return $manager;
        }

        public function verify($token)
        {
            return isset(self::$live[$this->user_id]) && self::$live[$this->user_id] === $token;
        }
    }
}

/**
 * Logins are reported when a request ends with a live session for a user the
 * request did not arrive as, whether or not wp_login fired.
 *
 * @see https://wordpress.org/support/topic/notification-emails-have-stopped-arriving-email-test-send-ok-shield-conflict/
 */
final class LoginDetectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // User 99 does not exist.
        Functions\when('get_user_by')->alias(function ($field, $id) {
            if ((int) $id === 99) {
                return false;
            }

            $user = new WP_User($id);
            $user->user_login = 'u' . $id;
            return $user;
        });

        // Reset the detector's per-request state.
        Functions\when('get_current_user_id')->justReturn(0);
        SucuriScanHook::loginWatchStart();
    }

    protected function tearDown(): void
    {
        WP_Session_Tokens::$live = array();
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testPasswordLoginIsReported()
    {
        $this->assertSame(array('u2'), $this->detect(0, array(2 => 'tok'), array(2 => 'tok')));
    }

    public function testLoginInterruptedBySecondFactorIsNotReported()
    {
        // Two-factor plugins destroy the session before showing their challenge.
        $this->assertSame(array(), $this->detect(0, array(2 => 'tok'), array()));
    }

    public function testCookieRenewalForCurrentUserIsNotReported()
    {
        // e.g. the user changes their own password.
        $this->assertSame(array(), $this->detect(2, array(2 => 'tok'), array(2 => 'tok')));
    }

    public function testSwitchingToAnotherUserIsReported()
    {
        $this->assertSame(array('u3'), $this->detect(2, array(3 => 'tok'), array(3 => 'tok')));
    }

    public function testDeletedUserIsNotReported()
    {
        $this->assertSame(array(), $this->detect(0, array(99 => 'tok'), array(99 => 'tok')));
    }

    public function testRepeatedCookiesInOneRequestAreReportedOnce()
    {
        do_action('init');
        SucuriScanHook::loginWatchCookieSet('', 0, 0, 2, 'auth', 'tok');

        $this->assertSame(array('u2'), $this->detect(0, array(2 => 'tok'), array(2 => 'tok')));
    }

    public function testCookieRenewedBeforeInitIsNotReported()
    {
        Functions\when('wp_validate_auth_cookie')->justReturn(2);
        SucuriScanHook::loginWatchCookieSet('', 0, 0, 2, 'auth', 'tok');
        WP_Session_Tokens::$live = array(2 => 'tok');

        $reported = array();
        Monkey\Actions\expectDone('sucuriscan_login')->zeroOrMoreTimes()->whenHappen(function ($login) use (&$reported) {
            $reported[] = $login;
        });
        SucuriScanHook::loginWatchFinish();

        $this->assertSame(array(), $reported);
    }

    /**
     * Simulate one request and return the logins it reported.
     *
     * @param int                $arrivedAs User the request arrived logged in as.
     * @param array<int, string> $cookies   Sessions issued during the request.
     * @param array<int, string> $live      Sessions still valid when it ends.
     */
    private function detect(int $arrivedAs, array $cookies, array $live): array
    {
        $reported = array();

        Monkey\Actions\expectDone('sucuriscan_login')->zeroOrMoreTimes()->whenHappen(function ($login) use (&$reported) {
            $reported[] = $login;
        });
        Functions\when('get_current_user_id')->justReturn($arrivedAs);

        do_action('init');
        SucuriScanHook::loginWatchStart();

        foreach ($cookies as $user_id => $token) {
            SucuriScanHook::loginWatchCookieSet('', 0, 0, $user_id, 'auth', $token);
        }

        WP_Session_Tokens::$live = $live;
        SucuriScanHook::loginWatchFinish();

        return $reported;
    }
}
