<?php declare(strict_types=1);

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

// Not loaded by the shared bootstrap.
require_once BASE_DIR . '/src/settings.php';
require_once BASE_DIR . '/src/settings-posthack.php';

/**
 * Tests that resetPasswordAjax() enforces an authorization check before resetting a
 * password.
 *
 * resetPasswordAjax() takes user_id from POST and calls setNewPassword(), which resolves
 * the account with get_userdata(). The handler first requires
 * current_user_can('edit_user', $id) so the action only proceeds for accounts the current
 * user is allowed to edit.
 */
final class PasswordResetAuthorizationTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $serverBackup = array();
    private $postBackup = array();
    private $sent = null;
    private $getUserdataCalls = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $this->serverBackup = $_SERVER;
        $this->postBackup = $_POST;
        $this->sent = null;
        $this->getUserdataCalls = 0;
        $self = $this;

        $_POST = array('form_action' => 'reset_user_password', 'user_id' => '5');

        // Capture the JSON response instead of emitting + die().
        Functions\when('wp_send_json')->alias(function ($data, $status = null) use ($self) {
            $self->sent = array('data' => $data, 'status' => $status);
        });

        // Track invocation; returning a non-WP_User makes setNewPassword() bail early,
        // so the authorized path is exercised without the full mail/reset chain.
        Functions\when('get_userdata')->alias(function ($id) use ($self) {
            $self->getUserdataCalls++;
            return false;
        });
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
        $_POST = $this->postBackup;
        unset($GLOBALS['__test_current_user_can']);
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testDeniedWhenUserCannotEditTarget()
    {
        $GLOBALS['__test_current_user_can'] = false;

        SucuriScanSettingsPosthack::resetPasswordAjax();

        $this->assertSame(array('data' => 'Error', 'status' => 403), $this->sent);
        // The gate must bail before setNewPassword()/get_userdata() touches the account.
        $this->assertSame(0, $this->getUserdataCalls);
    }

    public function testProceedsToResetAttemptWhenAuthorized()
    {
        $GLOBALS['__test_current_user_can'] = true;

        SucuriScanSettingsPosthack::resetPasswordAjax();

        // Passed the capability gate (not 403) and reached the reset attempt, which fails
        // here only because the mocked get_userdata() returns no user.
        $this->assertSame(array('data' => 'Error', 'status' => 200), $this->sent);
        $this->assertSame(1, $this->getUserdataCalls);
    }
}
