<?php

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

require_once __DIR__ . '/autoload.php';

if (!class_exists('SucuriScanTOTP')) {
    require BASE_DIR . '/src/totp.core.php';
}

if (!class_exists('WP_User')) {
    class WP_User
    {
        public $ID;
        public function __construct($id)
        {
            $this->ID = (int) $id;
        }
    }
}

/**
 * Tests focused on SucuriScanTwoFactor enforcement logic and authenticate interception.
 */
final class TwoFactorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        Functions\when('wp_validate_redirect')->returnArg(1);
        Functions\when('wp_login_url')->justReturn('https://example.com/wp-login.php');
        Functions\when('add_query_arg')->alias(function ($args, $url) {
            if (is_array($args)) {
                $qs = http_build_query($args);
                return $url . (strpos($url, '?') === false ? '?' : '&') . $qs;
            }
            return $url;
        });
        Functions\when('trailingslashit')->alias(fn($p) => rtrim($p, '/') . '/');
        Functions\when('esc_html__')->returnArg(1);
        Functions\when('__')->returnArg(1);
        Functions\when('esc_html')->returnArg(1);
        Functions\when('wp_nonce_field')->justReturn('<input type="hidden" name="_wpnonce" value="nonce" />');
        Functions\when('sanitize_text_field')->alias(fn($v) => is_string($v) ? $v : '');

        // Auth / redirect related
        Functions\when('wp_safe_redirect')->justReturn(true);
        Functions\when('wp_set_current_user')->justReturn(true);
        Functions\when('wp_set_auth_cookie')->justReturn(true);
        Functions\when('check_admin_referer')->justReturn(true);
        Functions\when('login_header')->justReturn(true);
        Functions\when('login_footer')->justReturn(true);

        // Script enqueue related
        Functions\when('wp_script_is')->justReturn(false);
        Functions\when('wp_register_script')->justReturn(true);
        Functions\when('wp_enqueue_script')->justReturn(true);
        Functions\when('is_admin')->justReturn(true);
        Functions\when('get_current_user_id')->justReturn(1);

        // Transients & cache
        Functions\when('set_transient')->justReturn(true);
        Functions\when('get_transient')->justReturn(false);
        Functions\when('delete_transient')->justReturn(true);
        Functions\when('wp_cache_get')->justReturn(false);
        Functions\when('wp_cache_set')->justReturn(true);
        Functions\when('wp_cache_delete')->justReturn(true);

        // User / meta
        Functions\when('wp_generate_password')->justReturn('TESTTOKENSTRINGWITHLENGTHEXPECTED0123456789ABCDEF');
        Functions\when('get_user_meta')->justReturn(''); // ensure no secret => setup path
        Functions\when('update_user_meta')->justReturn(true);
        Functions\when('add_user_meta')->justReturn(true);
        Functions\when('get_user_by')->alias(fn($field, $val) => (object) ['ID' => (int) $val, 'user_login' => 'u' . $val, 'user_email' => 'u' . $val . '@example.com']);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testEnforcementDisabledMode(): void
    {
        SucuriScanOption::updateOption(':twofactor_mode', 'disabled');
        $ref = new ReflectionClass(SucuriScanTwoFactor::class);
        $method = $ref->getMethod('is_enforced_for_user');
        $method->setAccessible(true);
        $this->assertFalse($method->invoke(null, 5));
    }

    public function testEnforcementAllUsersMode(): void
    {
        SucuriScanOption::updateOption(':twofactor_mode', 'all_users');
        $ref = new ReflectionClass(SucuriScanTwoFactor::class);
        $method = $ref->getMethod('is_enforced_for_user');
        $method->setAccessible(true);
        $this->assertTrue($method->invoke(null, 5));
    }

    public function testEnforcementSelectedUsersPositive(): void
    {
        SucuriScanOption::updateOption(':twofactor_mode', 'selected_users');
        SucuriScanOption::updateOption(':twofactor_users', [10, 20, 30]);
        $ref = new ReflectionClass(SucuriScanTwoFactor::class);
        $method = $ref->getMethod('is_enforced_for_user');
        $method->setAccessible(true);
        $this->assertTrue($method->invoke(null, 20));
    }

    public function testEnforcementSelectedUsersNegative(): void
    {
        SucuriScanOption::updateOption(':twofactor_mode', 'selected_users');
        SucuriScanOption::updateOption(':twofactor_users', [10, 20, 30]);
        $ref = new ReflectionClass(SucuriScanTwoFactor::class);
        $method = $ref->getMethod('is_enforced_for_user');
        $method->setAccessible(true);
        $this->assertFalse($method->invoke(null, 999));
    }

    public function testEnforcementSelectedUsersTooLargeListFailsClosed(): void
    {
        SucuriScanOption::updateOption(':twofactor_mode', 'selected_users');
        // Build list > 25000 unique ids; suspicious! ;)
        $big = range(1, 25050);
        SucuriScanOption::updateOption(':twofactor_users', $big);
        $ref = new ReflectionClass(SucuriScanTwoFactor::class);
        $method = $ref->getMethod('is_enforced_for_user');
        $method->setAccessible(true);
        $this->assertFalse($method->invoke(null, 123));
    }

    public function testAuthenticateBypassesWhenNotEnforced(): void
    {
        SucuriScanOption::updateOption(':twofactor_mode', 'disabled');
        $wpUser = (object) ['ID' => 42];
        $result = SucuriScanTwoFactor::authenticate($wpUser, 'user42', 'pass');
        $this->assertSame($wpUser, $result);
    }

    public function testAuthenticateRedirectsForSetupWhenEnforcedWithoutSecret(): void
    {
        SucuriScanOption::updateOption(':twofactor_mode', 'selected_users');
        SucuriScanOption::updateOption(':twofactor_users', [55]);

        $wpUser = new WP_User(55);

        Functions\when('wp_safe_redirect')->alias(function ($url) {
            throw new RuntimeException('REDIRECT:' . esc_url($url));
        });

        try {
            SucuriScanTwoFactor::authenticate($wpUser, 'user55', 'pass');
            $this->fail('Expected redirect did not occur');
        } catch (RuntimeException $e) {
            $msg = $e->getMessage();
            $this->assertStringStartsWith('REDIRECT:', $msg);
            $this->assertStringContainsString('action=sucuri-2fa-setup', $msg);
        }
    }
}
