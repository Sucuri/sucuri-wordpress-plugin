<?php
// Abort if the file is loaded out of context.
if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

/**
 * This class implements Two-Factor Authentication (2FA) using TOTP (Time-based One-Time Password).
 */
class SucuriScanTwoFactor extends SucuriScan
{
    const OPTION_PREFIX = 'sucuriscan_totp_';
    const SECRET_META_KEY = 'sucuriscan_topt_secret_key';
    const LAST_SUCCESS_META_KEY = 'sucuriscan_topt_last_success';
    const LOGIN_TOKEN_TTL = 600;
    const LOGIN_TOKEN_MAX_ATTEMPTS = 5;
    const DEFAULT_CODE_ERROR = 'sucuriscan_profile_error';
    const LOGIN_TRANSIENT_PREFIX = 'sucuri_2fa_';
    const LOGIN_TOKEN_PATTERN = '[A-Za-z0-9]{10,128}';
    const LOGIN_TOKEN_MIN_LENGTH = 10;
    const LOGIN_TOKEN_MAX_LENGTH = 128;

    public static function add_hooks()
    {
        add_filter('authenticate', array(__CLASS__, 'authenticate'), 30, 3);
        add_action('login_form_sucuri-2fa', array(__CLASS__, 'login_form_2fa'));
        add_action('login_form_sucuri-2fa-setup', array(__CLASS__, 'login_form_2fa_setup'));
        add_action('login_head', array(__CLASS__, 'brand_login_logo'));
        add_action('show_user_profile', array(__CLASS__, 'render_user_profile_section'));
        add_action('edit_user_profile', array(__CLASS__, 'render_user_profile_section'));
        add_action('personal_options_update', array(__CLASS__, 'save_user_profile_section'));
        add_action('edit_user_profile_update', array(__CLASS__, 'save_user_profile_section'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_profile_assets'));
        add_action('wp_ajax_sucuri_profile_2fa_enable', array(__CLASS__, 'ajax_profile_enable'));
        add_action('wp_ajax_sucuri_profile_2fa_reset', array(__CLASS__, 'ajax_profile_reset'));
    }

    protected static $profile_error_queue = array();
    protected static $profile_error_hook_registered = false;

    /**
     * Adds an error to be displayed on the user profile page.
     * 
     * @param mixed $code
     * @param mixed $message
     * 
     * @return void
     */
    protected static function add_profile_error($code, $message)
    {
        $code = (string) (is_scalar($code) ? $code : self::DEFAULT_CODE_ERROR);
        $code = trim($code) !== '' ? $code : self::DEFAULT_CODE_ERROR;
        $message = (string) (is_scalar($message) ? $message : '');
        $message = trim($message);

        if ($message === '') {
            // Do not enqueue empty messages.
            return;
        }

        if (!is_array(self::$profile_error_queue)) {
            self::$profile_error_queue = array();
        }

        self::$profile_error_queue[] = array('code' => $code, 'message' => $message);

        if (!self::$profile_error_hook_registered) {
            add_action('user_profile_update_errors', array(__CLASS__, 'on_profile_update_errors'), 10, 3);
            self::$profile_error_hook_registered = true;
        }
    }

    /**
     * Flush queued profile errors into the WP_Error object provided by WordPress.
     * WordPress passes a WP_Error instance by reference as the first argument of the
     * 'user_profile_update_errors' action. If for any reason it's not a WP_Error we just drop.
     *
     * @param mixed $errors Expected WP_Error instance.
     *
     * @return void
     */
    public static function on_profile_update_errors($errors)
    {
        if (empty(self::$profile_error_queue) || !is_array(self::$profile_error_queue)) {
            self::$profile_error_queue = array();
            return;
        }

        if (!class_exists('WP_Error') || !($errors instanceof WP_Error)) {
            // Can't safely add errors; clear queue to avoid leakage into later requests.
            self::$profile_error_queue = array();
            return;
        }

        foreach (self::$profile_error_queue as $item) {
            if (!is_array($item)) {
                continue;
            }

            $code = isset($item['code']) ? (string) $item['code'] : '';
            $message = isset($item['message']) ? (string) $item['message'] : '';

            if ($code === '' || $message === '') {
                continue;
            }

            $isDuplicate = false;
            $existing = $errors->get_error_messages($code);

            if (!empty($existing) && in_array($message, $existing, true)) {
                $isDuplicate = true;
            }

            if (!$isDuplicate) {
                $errors->add($code, $message);
            }
        }

        self::$profile_error_queue = array();
    }

    /**
     * Determine whether Two-Factor is enforced for a given user.
     *
     * Modes (stored in :twofactor_mode):
     *  - disabled         => never enforce
     *  - all_users        => enforce for every valid user id > 0
     *  - selected_users   => enforce only for IDs listed in :twofactor_users (array)
     * Any other/unknown mode safely falls back to 'disabled'.
     *
     * Security / Defensive Notes:
     *  - We normalize/sanitize user lists to integers and strictly compare.
     *  - We bound extremely large lists (> 25k) to a safe failure (returns false) to avoid memory pressure from malformed options.
     *
     * @param int $user_id User ID to evaluate.
     *
     * @return bool Whether enforcement applies.
     */
    protected static function is_enforced_for_user($user_id)
    {
        $user_id = (int) $user_id;

        if ($user_id <= 0) {
            return false; // Invalid target user.
        }

        $mode = (string) SucuriScanOption::getOption(':twofactor_mode');

        if ($mode === '' || $mode === null) {
            $mode = 'disabled';
        }

        // Whitelist allowed modes; unknown values become 'disabled' to fail closed.
        static $allowed_modes = array('disabled', 'all_users', 'selected_users');

        if (!in_array($mode, $allowed_modes, true)) {
            $mode = 'disabled';
        }

        switch ($mode) {
            case 'disabled':
                return false;

            case 'all_users':
                return true;

            case 'selected_users':
                $list = SucuriScanOption::getOption(':twofactor_users');

                if (!is_array($list) || empty($list)) {
                    return false;
                }

                $normalized = array();

                foreach ($list as $id) {
                    $id = (int) $id;

                    if ($id > 0) {
                        $normalized[$id] = true;
                    }
                }

                if (empty($normalized)) {
                    return false;
                }

                if (count($normalized) > 25000) {
                    return false; // List too large / suspicious.
                }

                return isset($normalized[$user_id]);
        }

        return false; // Fallback.
    }


    /**
     * This function enqueues the necessary assets for the user profile page if 2FA is enforced.
     * 
     * @param mixed $hook
     * 
     * @return void
     */
    public static function enqueue_profile_assets($hook)
    {
        if (!is_admin()) {
            return;
        }

        $is_profile_context = ($hook === 'profile.php' || $hook === 'user-edit.php');
        $is_plugin_twofactor_page = false;

        if (is_string($hook) && strpos($hook, 'sucuriscan_2fa') !== false) {
            $is_plugin_twofactor_page = true;
        }

        if (!$is_profile_context && !$is_plugin_twofactor_page) {
            return; // Not a target page.
        }

        $target_user = 0;

        if ($is_profile_context) {
            if ($hook === 'profile.php') {
                $target_user = get_current_user_id();
            } elseif ($hook === 'user-edit.php') {
                $req_user = SucuriScanRequest::get('user_id', '[0-9]+');

                if ($req_user !== false) {
                    $target_user = (int) $req_user;
                }
            }
        } else {
            $target_user = get_current_user_id();
        }

        if (!self::is_enforced_for_user($target_user) && !$is_plugin_twofactor_page) {
            return;
        }

        self::ensure_qr_script();
    }

    protected static function create_login_token($user_id, $remember, $redirect_to, $secret_for_setup = '')
    {
        $token = wp_generate_password(64, false, false);

        $data = array(
            'user_id' => (int) $user_id,
            'remember' => (bool) $remember,
            'redirect' => (string) $redirect_to,
            'secret' => (string) $secret_for_setup,
            'created' => time(),
            'attempts' => 0,
            'ua' => isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '',
        );

        set_transient(self::transient_key($token), $data, self::LOGIN_TOKEN_TTL);

        return $token;
    }

    /**
     * This function retrieves the login session data associated with a given token.
     * 
     * @param mixed $token
     */
    protected static function get_login_session($token)
    {
        if (!$token) {
            return false;
        }

        $data = get_transient(self::transient_key($token));

        return is_array($data) ? $data : false;
    }

    /**
     * This function clears the login session associated with a given token.
     */
    protected static function clear_login_session($token)
    {
        if ($token) {
            delete_transient(self::transient_key($token));
        }
    }

    /**
     * This function updates the login session associated with a given token.
     * 
     * @param mixed $token
     * @param mixed $data
     * 
     * @return void
     */
    protected static function update_login_session($token, $data)
    {
        if (!$token || !is_array($data)) {
            return;
        }

        $created = isset($data['created']) ? (int) $data['created'] : time();
        $elapsed = max(0, time() - $created);
        $remaining = self::LOGIN_TOKEN_TTL - $elapsed;

        if ($remaining <= 0) {
            self::clear_login_session($token);
            return;
        }

        set_transient(self::transient_key($token), $data, $remaining);
    }

    /**
     * Build the transient key for a given raw token.
     *
     * @param string $token Raw random token value.
     * @return string Transient key name.
     */
    protected static function transient_key($token)
    {
        return self::LOGIN_TRANSIENT_PREFIX . $token;
    }

    /**
     * Fetch and normalize the 2FA login token from either GET or POST (param: token).
     * Performs both pattern-based filtering (through SucuriScanRequest) and an
     * explicit secondary validation for defense-in-depth.
     *
     * @return string Normalized, validated token or empty string if invalid/absent.
     */
    protected static function fetch_request_token()
    {
        $raw = SucuriScanRequest::getOrPost('token', self::LOGIN_TOKEN_PATTERN);

        if ($raw === false) {
            return '';
        }

        $token = (string) $raw;
        $len = strlen($token);

        if ($len < self::LOGIN_TOKEN_MIN_LENGTH || $len > self::LOGIN_TOKEN_MAX_LENGTH) {
            return '';
        }

        if (preg_match('/^[A-Za-z0-9]{10,128}$/', $token) !== 1) {
            return '';
        }

        return $token;
    }

    /**
     * Bootstrap a 2FA session from request token.
     * Redirects to wp_login_url() and exits on any invalid precondition.
     *
     * @param bool $require_secret If true, session must include a non-empty 'secret'.
     *
     * @return array Array with keys: token, session, user_id, redirect_to, remember, secret (may be '').
     */
    protected static function bootstrap_session($require_secret = false)
    {
        $token = self::fetch_request_token();

        if (empty($token)) {
            wp_safe_redirect(wp_login_url());
            exit;
        }

        $session = self::get_login_session($token);

        if (!$session || empty($session['user_id'])) {
            wp_safe_redirect(wp_login_url());
            exit;
        }

        if ($require_secret && empty($session['secret'])) {
            wp_safe_redirect(wp_login_url());
            exit;
        }

        $user_id = (int) $session['user_id'];
        $redirect_to = (string) (isset($session['redirect']) ? $session['redirect'] : admin_url());
        $remember = (bool) (isset($session['remember']) ? $session['remember'] : false);
        $secret = isset($session['secret']) ? (string) $session['secret'] : '';

        return compact('token', 'session', 'user_id', 'redirect_to', 'remember', 'secret');
    }

    /**
     * Enforce user-agent binding. If mismatch, clear session and redirect to login.
     *
     * @param array $session
     * @param string $token
     *
     * @return void
     */
    protected static function enforce_user_agent($session, $token)
    {
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';

        if (!empty($session['ua']) && $session['ua'] !== $ua) {
            self::clear_login_session($token);
            wp_safe_redirect(wp_login_url());
            exit;
        }
    }

    /**
     * Extract and normalize a submitted numeric TOTP code from POST.
     * Returns empty string if absent.
     * @param string $field
     * 
     * @return string
     */
    protected static function extract_submitted_code($field)
    {
        $code_raw = SucuriScanRequest::post($field, '[0-9 ]+');

        return $code_raw !== false ? preg_replace('/\D+/', '', (string) $code_raw) : '';
    }

    /**
     * Record a failed attempt. If lockout threshold reached the session is cleared and user redirected.
     * Otherwise session is updated with incremented attempts.
     * 
     * @param string $token
     * @param array  $session (by reference)
     *
     * @return void (never returns on lockout)
     */
    protected static function record_failed_attempt($token, &$session)
    {
        $session['attempts'] = isset($session['attempts']) ? ((int) $session['attempts'] + 1) : 1;

        if ($session['attempts'] >= self::LOGIN_TOKEN_MAX_ATTEMPTS) {
            self::clear_login_session($token);
            wp_safe_redirect(wp_login_url());
            exit;
        }

        self::update_login_session($token, $session);
    }

    /**
     * Complete a successful standard verification login.
     * Clears session, sets auth cookie and redirects.
     */
    protected static function complete_success_login($user_id, $remember, $redirect_to, $token, $valid_ts)
    {
        if ($valid_ts) {
            update_user_meta($user_id, self::LAST_SUCCESS_META_KEY, $valid_ts);
        }

        self::clear_login_session($token);
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, $remember);
        wp_safe_redirect($redirect_to);
        exit;
    }

    /**
     * Handle successful setup flow: persist secret, update policy (selected_users) as needed,
     * finalize authentication and redirect.
     */
    protected static function process_successful_setup($user_id, $secret_key, $valid_ts, $remember, $redirect_to, $token)
    {
        self::store_user_totp_key($user_id, $secret_key);

        if ($valid_ts) {
            update_user_meta($user_id, self::LAST_SUCCESS_META_KEY, $valid_ts);
        }

        $current_mode = SucuriScanOption::getOption(':twofactor_mode');

        if ($current_mode !== 'all_users') {
            $list = SucuriScanOption::getOption(':twofactor_users');
            $list = is_array($list) ? array_map('intval', $list) : array();

            if (!in_array((int) $user_id, $list, true)) {
                $list[] = (int) $user_id;
            }

            $list = array_values(array_unique(array_filter($list, function ($v) {
                return $v > 0;
            })));

            SucuriScanOption::updateOption(':twofactor_users', $list);
            SucuriScanOption::updateOption(':twofactor_mode', 'selected_users');
        }

        self::clear_login_session($token);
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, $remember);
        wp_safe_redirect($redirect_to);
        exit;
    }

    /*
     * WordPress calls the 'authenticate' filter multiple times during the login pipeline.
     * 
     * We only act when:
     *   - A previous authentication step has produced a concrete WP_User instance; AND
     *   - There is a non-empty username credential (protects against cookie/interim flows where username may be blank);
     *   - Two-Factor enforcement policy applies to this user.
     * 
     * @param mixed $user
     * @param mixed $username
     * @param mixed $password
     * 
     * @return mixed WP_User instance on success, WP_Error on failure, or original $user to pass through.
     */
    public static function authenticate($user, $username, $password)
    {
        if ($user instanceof WP_Error) {
            return $user;
        }

        // Security note: If username is empty we avoid triggering 2FA so we do not
        // inadvertently enforce during non-standard auth flows (e.g., XML-RPC / cookie).
        if (empty($username)) {
            return $user;
        }

        if (!$user instanceof WP_User) {
            return $user; // Not a fully authenticated user yet.
        }

        $user_id = (int) $user->ID;

        if ($user_id <= 0) {
            return $user;
        }

        if (!self::is_enforced_for_user($user_id)) {
            return $user; // 2FA not required; allow core to continue normally.
        }

        $remember = (SucuriScanRequest::post('rememberme') !== false);
        $redirect_raw = SucuriScanRequest::getOrPost('redirect_to');
        $redirect_to = $redirect_raw !== false ? (string) $redirect_raw : admin_url();
        $redirect_to = wp_validate_redirect($redirect_to, admin_url());

        $secret_key = self::get_user_totp_key($user_id);

        if (empty($secret_key)) {
            try {
                $setup_secret = SucuriScanTOTP::generate_key();
            } catch (Exception $e) {
                $setup_secret = '';
            }

            if (empty($setup_secret)) {
                // Rare failure: generation failed; surface explicit error so login flow can show feedback.
                return new WP_Error(
                    'sucuriscan_2fa_error',
                    esc_html__('Unable to initialize two-factor setup.', 'sucuri-scanner')
                );
            }

            $token = self::create_login_token($user_id, $remember, $redirect_to, $setup_secret);
            $setup_url = add_query_arg(
                array(
                    'action' => 'sucuri-2fa-setup',
                    'token' => rawurlencode($token),
                ),
                wp_login_url()
            );

            wp_safe_redirect($setup_url);
            exit;
        }

        // Proceed to verification challenge screen.
        $token = self::create_login_token($user_id, $remember, $redirect_to, '');
        $verify_url = add_query_arg(
            array(
                'action' => 'sucuri-2fa',
                'token' => rawurlencode($token),
            ),
            wp_login_url()
        );

        wp_safe_redirect($verify_url);
        exit;
    }

    public static function login_form_2fa()
    {
        $error = '';
        $boot = self::bootstrap_session(false);
        $token = $boot['token'];
        $session = $boot['session'];
        $user_id = $boot['user_id'];
        $redirect_to = $boot['redirect_to'];
        $remember = $boot['remember'];
        $nonce_action = 'sucuri_2fa_verify_' . $token;

        self::enforce_user_agent($session, $token);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_admin_referer($nonce_action);

            $submitted_code = self::extract_submitted_code('sucuriscan_totp_code');
            $invalid_message = esc_html__('Invalid two-factor authentication code.', 'sucuri-scanner');

            if (strlen($submitted_code) !== SucuriScanTOTP::DEFAULT_DIGIT_COUNT) {
                self::record_failed_attempt($token, $session);
                $error = $invalid_message;
            } else {
                $secret_key = self::get_user_totp_key($user_id);

                if (empty($secret_key)) {
                    self::clear_login_session($token);
                    wp_safe_redirect(add_query_arg(array('action' => 'sucuri-2fa-setup'), wp_login_url()));
                    exit;
                }

                $valid_ts = false;
                try {
                    $valid_ts = SucuriScanTOTP::get_authcode_valid_ticktime($secret_key, $submitted_code);
                } catch (Exception $e) {
                    $valid_ts = false;
                }

                if ($valid_ts) {
                    $last = (int) get_user_meta($user_id, self::LAST_SUCCESS_META_KEY, true);

                    if ($last && $last >= $valid_ts) {
                        $valid_ts = false;
                    }
                }

                if ($valid_ts) {
                    self::complete_success_login($user_id, $remember, $redirect_to, $token, $valid_ts);
                }

                self::record_failed_attempt($token, $session);
                $error = $invalid_message;
            }
        }

        $message_html = SucuriScanTemplate::getSnippet('login-message', array(
            'Message' => esc_html__('Enter the 6-digit code from your authenticator app to continue.', 'sucuri-scanner'),
        ));

        if (!empty($error)) {
            $message_html = SucuriScanTemplate::getSnippet('login-error', array(
                'Error' => esc_html($error),
            )) . $message_html;
        }

        login_header(esc_html__('Two-Factor Authentication', 'sucuri-scanner'), $message_html);

        $params = array(
            'ActionURL' => add_query_arg(array('action' => 'sucuri-2fa', 'token' => rawurlencode($token)), wp_login_url()),
            'NonceField' => wp_nonce_field($nonce_action, '_wpnonce', true, false),
        );

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo SucuriScanTemplate::getSection('login-2fa', $params);

        login_footer();
        exit;
    }

    /*
     * Render the 2FA setup form.
     * 
     * @return void
     */
    public static function login_form_2fa_setup()
    {
        $error = '';
        $boot = self::bootstrap_session(true);
        $token = $boot['token'];
        $session = $boot['session'];
        $user_id = $boot['user_id'];
        $redirect_to = $boot['redirect_to'];
        $remember = $boot['remember'];
        $secret_key = $boot['secret'];
        $nonce_action = 'sucuri_2fa_setup_' . $token;

        self::enforce_user_agent($session, $token);

        $user = get_user_by('id', $user_id);
        $otpauth = SucuriScanTOTP::generate_qr_code_url($user, $secret_key);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_admin_referer($nonce_action);

            $submitted_code = self::extract_submitted_code('sucuriscan_totp_code');
            $invalid_message = esc_html__('Invalid code. Make sure you scanned the QR and your device time is correct.', 'sucuri-scanner');

            if (strlen($submitted_code) !== SucuriScanTOTP::DEFAULT_DIGIT_COUNT) {
                self::record_failed_attempt($token, $session);
                $error = $invalid_message;
            } else {
                $valid_ts = false;

                try {
                    $valid_ts = SucuriScanTOTP::get_authcode_valid_ticktime($secret_key, $submitted_code);
                } catch (Exception $e) {
                    $valid_ts = false;
                }

                if ($valid_ts) {
                    self::process_successful_setup($user_id, $secret_key, $valid_ts, $remember, $redirect_to, $token);
                }

                self::record_failed_attempt($token, $session);
                $error = $invalid_message;
            }
        }

        $message_html = SucuriScanTemplate::getSnippet('login-message', array(
            'Message' => esc_html__('Set up two-factor authentication. Scan the QR code with your authenticator app, then enter the 6-digit code to continue.', 'sucuri-scanner'),
        ));

        if (!empty($error)) {
            $message_html = SucuriScanTemplate::getSnippet('login-error', array(
                'Error' => esc_html($error),
            )) . $message_html;
        }

        login_header(esc_html__('Set up Two-Factor Authentication', 'sucuri-scanner'), $message_html);

        $params = array(
            'ActionURL' => add_query_arg(array('action' => 'sucuri-2fa-setup', 'token' => rawurlencode($token)), wp_login_url()),
            'NonceField' => wp_nonce_field($nonce_action, '_wpnonce', true, false),
            'SecretManual' => $secret_key,
            'OtpauthURI' => $otpauth,
        );

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo SucuriScanTemplate::getSection('login-2fa-setup', $params);

        login_footer();
        exit;
    }

    /**
     * Brand the 2FA login and setup pages with the Sucuri logo.
     * Only applies to the 2FA-specific actions.
     *
     * @return void
     */
    public static function brand_login_logo()
    {
        $action_raw = SucuriScanRequest::getOrPost('action', '[a-z0-9\-_]+');
        $action = $action_raw !== false ? (string) $action_raw : '';

        if ($action !== 'sucuri-2fa' && $action !== 'sucuri-2fa-setup') {
            return;
        }

        self::ensure_qr_script();

        $logo = trailingslashit(SUCURISCAN_URL) . 'inc/images/pluginlogo.png';

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo SucuriScanTemplate::getSnippet('login-brand', array(
            'LogoURL' => esc_url($logo),
        ));
    }

    /**
     * Ensure the QR code script for 2FA (qr.js) is registered and enqueued once.
     *
     * @return void
     */
    protected static function ensure_qr_script()
    {
        if (!function_exists('wp_script_is') || !function_exists('wp_register_script') || !function_exists('wp_enqueue_script')) {
            return;
        }

        if (!wp_script_is('sucuriscan-qrcode', 'registered')) {
            wp_register_script(
                'sucuriscan-qrcode',
                trailingslashit(SUCURISCAN_URL) . 'inc/js/qr.js',
                array(),
                method_exists('SucuriScan', 'fileVersion') ? SucuriScan::fileVersion('inc/js/qr.js') : false,
                false
            );
        }

        if (!wp_script_is('sucuriscan-qrcode', 'enqueued')) {
            wp_enqueue_script('sucuriscan-qrcode');
        }
    }

    /**
     * Retrieve the stored TOTP secret key for a given user.
     */
    public static function get_user_totp_key($user_id)
    {
        return (string) get_user_meta($user_id, self::SECRET_META_KEY, true);
    }

    /**
     * Store or update the TOTP secret key for a given user.
     */
    public static function store_user_totp_key($user_id, $key)
    {
        $existingKey = self::get_user_totp_key($user_id);

        if (empty($existingKey)) {
            return (bool) add_user_meta($user_id, self::SECRET_META_KEY, $key);
        }

        return (bool) update_user_meta($user_id, self::SECRET_META_KEY, $key);
    }

    /**
     * Resolve and authorize the target user for profile AJAX endpoints.
     * Sends json error & exits on failure; always returns an array on success.
     *
     * @return array [current => int, target => int, is_self => bool]
     */
    protected static function resolve_ajax_target_user()
    {
        $current = get_current_user_id();

        if (!$current || $current <= 0) {
            wp_send_json_error(array('message' => 'Forbidden'), 403);
        }

        $raw_user = SucuriScanRequest::post('user_id', '[0-9]+');

        if ($raw_user === false || $raw_user === '') {
            $user_id = $current;
        } else {
            $user_id = (int) $raw_user;

            if ($user_id <= 0) {
                wp_send_json_error(array('message' => 'Invalid user.'), 400);
            }
        }

        $is_self = ($current === $user_id);

        $provided_nonce = SucuriScanRequest::post('nonce');
        $nonce_ok = false;

        if ($provided_nonce !== '') {
            if (wp_verify_nonce($provided_nonce, 'sucuri_profile_2fa_' . $user_id)) {
                $nonce_ok = true;
            } elseif ($is_self && wp_verify_nonce($provided_nonce, 'sucuri_profile_2fa')) {
                $nonce_ok = true;
            }
        }

        if (!$nonce_ok) {
            wp_send_json_error(array('message' => 'Invalid security token.'), 403);
        }

        if (!$is_self && !SucuriScanPermissions::canEditUsers()) {
            wp_send_json_error(array('message' => 'Not allowed'), 403);
        }

        if (!self::is_enforced_for_user($user_id)) {
            wp_send_json_error(array('message' => 'Two-Factor not enforced for this user'), 400);
        }

        return array('current' => $current, 'target' => $user_id, 'is_self' => $is_self);
    }

    /**
     * Generate a new setup key + otpauth URL for a user; returns array(key, otpauth) or empty array on failure.
     *
     * @param int $user_id
     *
     * @return array
     */
    protected static function generate_setup_key_and_otpauth($user_id)
    {
        $key = '';

        try {
            $key = SucuriScanTOTP::generate_key();
        } catch (Exception $e) {
            $key = '';
        }

        if ($key === '') {
            return array();
        }

        $user = get_user_by('id', $user_id);

        if (!$user) {
            return array();
        }

        $otpauth = SucuriScanTOTP::generate_qr_code_url($user, $key);

        return array($key, $otpauth);
    }

    /**
     * Build status snippet HTML (enabled state actions) for a user.
     *
     * @param int $user_id
     *
     * @return string
     */
    protected static function profile_status_snippet($user_id)
    {
        return SucuriScanTemplate::getSnippet('profile-2fa-status', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'ajax_nonce' => wp_create_nonce('sucuri_profile_2fa_' . (int) $user_id),
            'user_id' => (int) $user_id,
        ));
    }

    /**
     * Build setup snippet HTML for a user (expects pre-generated key + otpauth).
     *
     * @param int $user_id
     * @param string $key
     * @param string $otpauth
     *
     * @return string
     */
    protected static function profile_setup_snippet($user_id, $key, $otpauth)
    {
        return SucuriScanTemplate::getSnippet('profile-2fa-setup', array(
            'totp_key' => $key,
            'topt_url' => $otpauth,
            'ajax_url' => admin_url('admin-ajax.php'),
            'ajax_nonce' => wp_create_nonce('sucuri_profile_2fa_' . (int) $user_id),
            'user_id' => (int) $user_id,
        ));
    }

    /**
     * Internal generic TOTP verification utility (non-AJAX). Returns array with:
     *  [ 'valid' => bool, 'valid_ts' => int|0, 'error' => string ]
     *
     * Performs:
     *  - Code length check
     *  - Secret format validation
     *  - TOTP computation + replay prevention (LAST_SUCCESS_META_KEY)
     * 
     * Does NOT throw/exit; caller decides how to surface errors.
     *
     * @param int $user_id
     * @param string $secret
     * @param string $code
     *
     * @return array
     */
    protected static function verify_totp_code($user_id, $secret, $code)
    {
        $user_id = (int) $user_id;
        $result = array('valid' => false, 'valid_ts' => 0, 'error' => '');

        if (strlen($code) !== SucuriScanTOTP::DEFAULT_DIGIT_COUNT) {
            $result['error'] = __('Please enter the 6-digit verification code.', 'sucuri-scanner');
            return $result;
        }

        if (empty($secret) || !SucuriScanTOTP::is_valid_key($secret)) {
            $result['error'] = __('Invalid secret. Reload the page and try again.', 'sucuri-scanner');
            return $result;
        }

        $valid_ts = false;
        try {
            $valid_ts = SucuriScanTOTP::get_authcode_valid_ticktime($secret, $code);
        } catch (Exception $e) {
            $valid_ts = false;
        }

        if ($valid_ts) {
            $last = (int) get_user_meta($user_id, self::LAST_SUCCESS_META_KEY, true);
            if ($last && $last >= $valid_ts) {
                // Replay / stale timestep
                $valid_ts = false;
            }
        }

        if (!$valid_ts) {
            $result['error'] = __('Incorrect code. Check your authenticator app and device time.', 'sucuri-scanner');
            return $result;
        }

        $result['valid'] = true;
        $result['valid_ts'] = (int) $valid_ts;
        return $result;
    }

    /**
     * Validate secret & code and return valid tick timestamp or send JSON error.
     * Performs length & format validation plus replay prevention.
     *
     * @param int $user_id
     * @param string $secret
     * @param string $code
     * @return int $valid_ts
     */
    protected static function validate_secret_and_code_or_error($user_id, $secret, $code)
    {
        if (strlen($code) !== SucuriScanTOTP::DEFAULT_DIGIT_COUNT) {
            wp_send_json_error(array('message' => __('Please enter the 6-digit verification code.', 'sucuri-scanner')));
        }

        if (empty($secret) || !SucuriScanTOTP::is_valid_key($secret)) {
            wp_send_json_error(array('message' => __('Invalid secret.', 'sucuri-scanner')));
        }

        $valid_ts = false;
        try {
            $valid_ts = SucuriScanTOTP::get_authcode_valid_ticktime($secret, $code);
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Verification failed.', 'sucuri-scanner')));
        }

        if ($valid_ts) {
            $last = (int) get_user_meta($user_id, self::LAST_SUCCESS_META_KEY, true);
            if ($last && $last >= $valid_ts) {
                $valid_ts = false; // Replay within same or older window.
            }
        }

        if (!$valid_ts) {
            wp_send_json_error(array('message' => __('Incorrect code. Check your authenticator app and device time.', 'sucuri-scanner')));
        }

        return (int) $valid_ts;
    }

    /**
     * This function saves the user's 2FA settings when their profile is updated.
     * 
     * @param WP_User $user
     *
     * @return void
     */
    public static function render_user_profile_section($user)
    {
        if (!($user instanceof WP_User)) {
            return;
        }

        $current_id = get_current_user_id();
        $is_self = ((int) $user->ID === (int) $current_id);
        $can_manage_users = SucuriScanPermissions::canEditUsers();

        if (!self::is_enforced_for_user((int) $user->ID)) {
            return;
        }

        $existing = self::get_user_totp_key((int) $user->ID);
        $enabled = !empty($existing);

        $status_html = $enabled
            ? '<span class="dashicons dashicons-yes" style="color:#46b450"></span> ' . esc_html__('Enabled', 'sucuri-scanner')
            : '<span class="dashicons dashicons-dismiss" style="color:#dc3232"></span> ' . esc_html__('Disabled', 'sucuri-scanner');

        wp_nonce_field('sucuri_2fa_profile_action', 'sucuri_2fa_profile_nonce');
        $uid = (int) $user->ID;

        if ($enabled) {
            $actions_html = self::profile_status_snippet($uid);
        } else {
            $actions_html = '';

            if ($is_self) {
                list($key, $otpauth) = array('', '');

                $data = self::generate_setup_key_and_otpauth($uid);

                if (!empty($data)) {
                    list($key, $otpauth) = $data;

                    $actions_html = self::profile_setup_snippet($uid, $key, $otpauth);
                }
            } elseif ($can_manage_users) {
                $actions_html = '<p class="description">' . esc_html__('Two-Factor is not enabled for this user. Ask the user to enable it from their own Profile page.', 'sucuri-scanner') . '</p>';
            }
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo SucuriScanTemplate::getSection('profile-2fa-section', array(
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            'StatusHTML' => $status_html,
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            'ActionsHTML' => $actions_html,
        ));
    }

    /**
     * AJAX handler to enable 2FA for a user.
     * Expects POST with: user_id (int, optional), code (string, required), secret (string, required), nonce (string, required).
     * 
     * @return void (sends JSON response and exits)
     */
    public static function ajax_profile_enable()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Forbidden'), 403);
        }

        $resolved = self::resolve_ajax_target_user();
        $user_id = $resolved['target'];
        $is_self = $resolved['is_self'];

        if (!$is_self) {
            wp_send_json_error(array('message' => __('You can only enable two-factor for your own account.', 'sucuri-scanner')), 403);
        }

        $code_raw = SucuriScanRequest::post('code', '[0-9 ]+');
        $code = $code_raw !== false ? preg_replace('/\D+/', '', $code_raw) : '';
        $secret = (string) SucuriScanRequest::post('secret', '[A-Za-z0-9=]+');

        $valid_ts = self::validate_secret_and_code_or_error($user_id, $secret, $code);

        self::store_user_totp_key($user_id, $secret);
        update_user_meta($user_id, self::LAST_SUCCESS_META_KEY, $valid_ts);

        if (class_exists('SucuriScanEvent')) {
            SucuriScanEvent::reportInfoEvent('Two-factor authentication enabled for user ID ' . (int) $user_id);
        }

        $html = self::profile_status_snippet($user_id);
        wp_send_json_success(array('html' => $html));
    }


    /**
     * AJAX handler to reset (disable) 2FA for a user.
     * Expects POST with: user_id (int, optional), nonce (string, required).
     *
     * @return void (sends JSON response and exits)
     */
    public static function ajax_profile_reset()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Forbidden'), 403);
        }

        $resolved = self::resolve_ajax_target_user();
        $user_id = $resolved['target'];
        $is_self = $resolved['is_self'];

        delete_user_meta($user_id, self::SECRET_META_KEY);
        delete_user_meta($user_id, self::LAST_SUCCESS_META_KEY);

        if (class_exists('SucuriScanEvent')) {
            SucuriScanEvent::reportInfoEvent('Two-factor authentication reset for user ID ' . (int) $user_id);
        }

        $html = '';

        if ($is_self) {
            $data = self::generate_setup_key_and_otpauth($user_id);

            if (!empty($data)) {
                list($key, $otpauth) = $data;

                $html = self::profile_setup_snippet($user_id, $key, $otpauth);
            }
        }

        if ($html === '') {
            $html = SucuriScanTemplate::getSnippet('profile-2fa-disabled', array());
        }

        wp_send_json_success(array('html' => $html));
    }


    /**
     * Process and save 2FA settings when a user profile is updated.
     * 
     * @param int $user_id
     *
     * @return void
     */
    public static function save_user_profile_section($user_id)
    {
        $user_id = (int) $user_id;

        if ($user_id <= 0) {
            return;
        }

        $profile_nonce = SucuriScanRequest::post('sucuri_2fa_profile_nonce', '_nonce');

        if (!$profile_nonce || !wp_verify_nonce($profile_nonce, 'sucuri_2fa_profile_action')) {
            return;
        }

        $action_raw = SucuriScanRequest::post('sucuri_2fa_action', '[a-z_]+');
        $action = $action_raw !== false ? sanitize_text_field((string) $action_raw) : '';

        if ($action !== 'enable' && $action !== 'reset') {
            return;
        }

        $current_id = get_current_user_id();
        $is_self = ($current_id && (int) $current_id === $user_id);

        if ($action === 'enable') {
            if (!$is_self) {
                return;
            }

            if (!self::is_enforced_for_user($user_id)) {
                self::add_profile_error('sucuri_2fa_policy', esc_html__('Two-Factor is not enforced for your account.', 'sucuri-scanner'));
                return;
            }

            $existing = self::get_user_totp_key($user_id);

            if (!empty($existing)) {
                self::add_profile_error('sucuri_2fa_already', esc_html__('Two-Factor is already enabled.', 'sucuri-scanner'));
                return;
            }

            $code = SucuriScanRequest::post('sucuriscan_totp_code', '[0-9 ]+');
            $secret = SucuriScanRequest::post('sucuri_2fa_secret', '[A-Za-z0-9=]+');
            $code = $code ? preg_replace('/\D+/', '', $code) : '';
            $secret = $secret ? (string) $secret : '';
            $verified = self::verify_totp_code($user_id, $secret, $code);

            if (!$verified['valid']) {
                self::add_profile_error('sucuri_2fa_code', esc_html($verified['error']));
                return;
            }

            self::store_user_totp_key($user_id, $secret);
            update_user_meta($user_id, self::LAST_SUCCESS_META_KEY, $verified['valid_ts']);

            if (class_exists('SucuriScanEvent')) {
                SucuriScanEvent::reportInfoEvent('Two-factor authentication enabled for user ID ' . (int) $user_id);
            }

            return;
        }

        if ($action === 'reset') {
            if (!$is_self && !SucuriScanPermissions::canEditUsers()) {
                return; // Capability required to reset others.
            }
            if (!self::is_enforced_for_user($user_id)) {
                // Silently ignore: not enforced.
                return;
            }
            delete_user_meta($user_id, self::SECRET_META_KEY);
            delete_user_meta($user_id, self::LAST_SUCCESS_META_KEY);
            if (class_exists('SucuriScanEvent')) {
                SucuriScanEvent::reportInfoEvent('Two-factor authentication reset for user ID ' . (int) $user_id);
            }
            return;
        }
    }

    /**
     * Render the 2FA setup block for the current user via AJAX.
     * 
     * @return string HTML (error message if not logged in or other failure).
     */
    public static function topt()
    {
        if (!SucuriScanInterface::checkNonce()) {
            return SucuriScanInterface::error(__('Incorrect nonce.', 'sucuri-scanner'));
        }

        $user = wp_get_current_user();

        if (!$user->ID) {
            return SucuriScanInterface::error(__('Incorrect user.', 'sucuri-scanner'));
        }

        $existing = self::get_user_totp_key((int) $user->ID);

        if (!empty($existing)) {
            return SucuriScanTemplate::getSnippet('2fa-current-user-status', array(
                'Message' => __('Two-Factor Authentication is already enabled for your account.', 'sucuri-scanner'),
            ));
        }

        $data = self::generate_setup_key_and_otpauth((int) $user->ID);

        if (empty($data)) {
            return SucuriScanInterface::error(__('Unable to generate secret.', 'sucuri-scanner'));
        }

        list($key, $otpauth) = $data;

        $params = array(
            'totp_key' => $key,
            'topt_url' => $otpauth,
            '2FA.Status' => false,
            'SecretManual' => $key,
        );

        return SucuriScanTemplate::getSnippet('2fa-setup', $params);
    }


    /**
     * Render the 2FA block for the current user profile page.
     * 
     * @return string HTML (error message if not logged in or other failure).
     */
    public static function current_user_block()
    {
        $user = wp_get_current_user();

        if (!$user || !$user->ID) {
            return SucuriScanInterface::error(__('Incorrect user.', 'sucuri-scanner'));
        }

        $existing = self::get_user_totp_key((int) $user->ID);

        if (!empty($existing)) {
            return SucuriScanTemplate::getSnippet('2fa-current-user-status', array(
                'Message' => __('Two-Factor Authentication is enabled for your account.', 'sucuri-scanner'),
            ));
        }

        $data = self::generate_setup_key_and_otpauth((int) $user->ID);

        if (empty($data)) {
            return SucuriScanInterface::error(__('Unable to generate secret.', 'sucuri-scanner'));
        }

        list($key, $otpauth) = $data;

        return SucuriScanTemplate::getSnippet('2fa-setup', array(
            'totp_key' => $key,
            'topt_url' => $otpauth,
            'SecretManual' => $key
        ));
    }

    /**
     * Render the 2FA users admin section (list of users with status and bulk actions).
     * Only shown if current user can 'list_users'.
     * 
     * @return string HTML (empty if no permission)
     */
    public static function users_admin_section()
    {
        if (!SucuriScanPermissions::canListUsers()) {
            return '';
        }

        $rows = '';
        $users = get_users(array('fields' => array('ID', 'user_login', 'user_email', 'roles')));
        $total_users = is_array($users) ? count($users) : 0;
        $activated_count = 0;

        if (is_array($users)) {
            foreach ($users as $user) {
                $uid = (int) $user->ID;
                $secret = self::get_user_totp_key($uid);
                $status = empty($secret) ? __('Deactivated', 'sucuri-scanner') : __('Activated', 'sucuri-scanner');

                if (!empty($secret)) {
                    $activated_count++;
                }

                $rows .= SucuriScanTemplate::getSnippet('2fa-user-row', array(
                    'ID' => $uid,
                    'Login' => $user->user_login,
                    'Email' => $user->user_email,
                    'Status' => $status,
                ));
            }
        }

        $bulkOptions = '';

        $bulkMap = array(
            'activate_all' => __('Activate two factor for all users', 'sucuri-scanner'),
            'activate_selected' => __('Activate two factor for selected users', 'sucuri-scanner'),
            'deactivate_all' => __('Deactivate two factor for all users', 'sucuri-scanner'),
            'deactivate_selected' => __('Deactivate two factor for selected users', 'sucuri-scanner'),
            'reset_selected' => __('Reset two factor for selected users (keep enforcement)', 'sucuri-scanner'),
            'reset_all' => __('Reset two factor for all users (keep enforcement)', 'sucuri-scanner'),
            'reset_everything' => __('Delete all two-factor data and disable enforcement', 'sucuri-scanner'),
        );

        foreach ($bulkMap as $val => $label) {
            $bulkOptions .= sprintf('<option value="%s">%s</option>', esc_attr($val), esc_html($label));
        }

        $status_id = 0;
        $status_text = __('Deactivated', 'sucuri-scanner');

        if ($activated_count > 0) {
            if ($total_users > 0 && $activated_count >= $total_users) {
                $status_id = 1;
                $status_text = __('Activated for all users', 'sucuri-scanner');
            } else {
                $status_id = 2;
                $status_text = __('Activated for some users', 'sucuri-scanner');
            }
        }

        return SucuriScanTemplate::getSection('2fa-users', array(
            'Rows' => $rows,
            'BulkOptions' => $bulkOptions,
            'TwoFactor.Status' => (string) $status_id,
            'TwoFactor.StatusText' => $status_text,
        ));
    }

    /**
     * Retrieve all valid user IDs (>0).
     *
     * @return int[]
     */
    protected static function get_all_user_ids()
    {
        $users = get_users(array('fields' => array('ID')));

        if (!is_array($users)) {
            return array();
        }

        $ids = array();

        foreach ($users as $user) {
            if (is_object($user) && isset($user->ID)) {
                $id = (int) $user->ID;

                if ($id > 0) {
                    $ids[$id] = true;
                }
            } elseif (is_array($user) && isset($user['ID'])) {
                $id = (int) $user['ID'];

                if ($id > 0) {
                    $ids[$id] = true;
                }
            }
        }

        return array_keys($ids);
    }

    /**
     * Normalize an array of user IDs, optionally intersect with full set.
     * Returns a deduplicated, sorted list.
     *
     * @param array $ids
     * @param array $universe Optional array of allowed IDs; if provided we intersect.
     *
     * @return int[]
     */
    protected static function normalize_user_ids($ids, $universe = null)
    {
        if (!is_array($ids)) {
            return array();
        }

        $output = array();

        foreach ($ids as $id) {
            $id = (int) $id;

            if ($id > 0) {
                $output[$id] = true;
            }
        }

        $output = array_keys($output);

        if (is_array($universe)) {
            $allowed = array();
            $flip = array();

            foreach ($universe as $uid) {
                $flip[(int) $uid] = true;
            }

            foreach ($output as $uid) {
                if (isset($flip[$uid])) {
                    $allowed[] = $uid;
                }
            }

            $output = $allowed;
        }

        sort($output, SORT_NUMERIC);

        return $output;
    }

    /**
     * Bulk administrative action handler for Two-Factor policy management.
     *
     * Supported actions:
     *  - activate_all
     *  - deactivate_all
     *  - activate_selected
     *  - deactivate_selected
     *  - reset_selected
     *  - reset_all
     *
     * Returns structured response array for easier testing & UI handling:
     *  [ success => bool, code => string, message => string, affected => array, mode => string ]
     *
     * Capability (manage_options) is enforced here for defense-in-depth.
     *
     * @param string $action
     * @param array  $selected User IDs (untrusted input)
     *
     * @return array
     */
    public static function process_admin_bulk_action($action, $selected)
    {
        $result = array(
            'success' => false,
            'code' => 'invalid',
            'message' => '',
            'affected' => array(),
            'mode' => (string) SucuriScanOption::getOption(':twofactor_mode'),
        );

        if (!is_admin() || !SucuriScanPermissions::canManagePlugin()) {
            $result['message'] = __('You are not allowed to modify Two-Factor settings.', 'sucuri-scanner');

            return $result;
        }

        $action = is_string($action) ? trim($action) : '';
        $all_ids = self::get_all_user_ids();
        $selected = self::normalize_user_ids($selected, $all_ids);
        $current_mode = (string) SucuriScanOption::getOption(':twofactor_mode');

        switch ($action) {
            case 'activate_all':
                SucuriScanOption::updateOption(':twofactor_mode', 'all_users');
                SucuriScanOption::updateOption(':twofactor_users', array());
                $result['success'] = true;
                $result['code'] = 'activate_all';
                $result['message'] = __('Two-Factor enforced for all users.', 'sucuri-scanner');
                $result['mode'] = 'all_users';
                $result['affected'] = $all_ids;
                break;

            case 'deactivate_all':
                SucuriScanOption::updateOption(':twofactor_mode', 'disabled');
                SucuriScanOption::updateOption(':twofactor_users', array());
                $result['success'] = true;
                $result['code'] = 'deactivate_all';
                $result['message'] = __('Two-Factor deactivated for all users.', 'sucuri-scanner');
                $result['mode'] = 'disabled';
                $result['affected'] = $all_ids;
                break;

            case 'activate_selected':
                if (empty($selected)) {
                    $result['message'] = __('No users selected.', 'sucuri-scanner');
                    break;
                }
                $list = SucuriScanOption::getOption(':twofactor_users');
                $list = is_array($list) ? array_map('intval', $list) : array();
                $list = self::normalize_user_ids($list);
                $merged = self::normalize_user_ids(array_merge($list, $selected), $all_ids);
                SucuriScanOption::updateOption(':twofactor_mode', 'selected_users');
                SucuriScanOption::updateOption(':twofactor_users', $merged);
                $result['success'] = true;
                $result['code'] = 'activate_selected';
                $result['message'] = __('Two-Factor enforced for selected users.', 'sucuri-scanner');
                $result['mode'] = 'selected_users';
                $result['affected'] = $selected;
                break;

            case 'deactivate_selected':
                if (empty($selected)) {
                    $result['message'] = __('No users selected.', 'sucuri-scanner');
                    break;
                }

                if ($current_mode === 'all_users') {
                    $remaining = array_values(array_diff($all_ids, $selected));

                    if (empty($remaining)) {
                        SucuriScanOption::updateOption(':twofactor_mode', 'disabled');
                        SucuriScanOption::updateOption(':twofactor_users', array());
                        $result['mode'] = 'disabled';
                    } else {
                        SucuriScanOption::updateOption(':twofactor_mode', 'selected_users');
                        SucuriScanOption::updateOption(':twofactor_users', $remaining);
                        $result['mode'] = 'selected_users';
                    }
                } else {
                    $list = SucuriScanOption::getOption(':twofactor_users');
                    $list = is_array($list) ? array_map('intval', $list) : array();
                    $list = self::normalize_user_ids($list, $all_ids);
                    $remaining = array_values(array_diff($list, $selected));

                    if (empty($remaining)) {
                        SucuriScanOption::updateOption(':twofactor_mode', 'disabled');
                        SucuriScanOption::updateOption(':twofactor_users', array());
                        $result['mode'] = 'disabled';
                    } else {
                        SucuriScanOption::updateOption(':twofactor_mode', 'selected_users');
                        SucuriScanOption::updateOption(':twofactor_users', $remaining);
                        $result['mode'] = 'selected_users';
                    }
                }

                $result['success'] = true;
                $result['code'] = 'deactivate_selected';
                $result['message'] = __('Two-Factor deactivated for selected users.', 'sucuri-scanner');
                $result['affected'] = $selected;
                break;

            case 'reset_selected':
                if (empty($selected)) {
                    $result['message'] = __('No users selected.', 'sucuri-scanner');
                    break;
                }

                foreach ($selected as $uid) {
                    delete_user_meta($uid, self::SECRET_META_KEY);
                    delete_user_meta($uid, self::LAST_SUCCESS_META_KEY);
                }

                $result['success'] = true;
                $result['code'] = 'reset_selected';
                $result['message'] = __('Two-Factor settings reset for selected users.', 'sucuri-scanner');
                $result['affected'] = $selected;
                $result['mode'] = (string) SucuriScanOption::getOption(':twofactor_mode');
                break;

            case 'reset_all':
                foreach ($all_ids as $uid) {
                    delete_user_meta($uid, self::SECRET_META_KEY);
                    delete_user_meta($uid, self::LAST_SUCCESS_META_KEY);
                }

                $result['success'] = true;
                $result['code'] = 'reset_all';
                $result['message'] = __('Two-Factor settings reset for all users.', 'sucuri-scanner');
                $result['affected'] = $all_ids;
                $result['mode'] = (string) SucuriScanOption::getOption(':twofactor_mode');
                break;

            case 'reset_everything':
                foreach ($all_ids as $uid) {
                    delete_user_meta($uid, self::SECRET_META_KEY);
                    delete_user_meta($uid, self::LAST_SUCCESS_META_KEY);
                }
                SucuriScanOption::updateOption(':twofactor_mode', 'disabled');
                SucuriScanOption::updateOption(':twofactor_users', array());
                $result['success'] = true;
                $result['code'] = 'reset_everything';
                $result['message'] = __('All Two-Factor data deleted and enforcement disabled.', 'sucuri-scanner');
                $result['affected'] = $all_ids;
                $result['mode'] = 'disabled';
                break;

            default:
                $result['message'] = __('Invalid two-factor action selected.', 'sucuri-scanner');
                break;
        }

        return $result;
    }

    /**
     * AJAX handler to verify and enable 2FA for the current user.
     * Expects POST with: form_action=totp_verify, topt_code (string, required),
     *  topt_key (string, required if no existing key), enforce_all (0|1, optional),
     *  _wpnonce (string, required).
     * 
     * @return void (sends JSON response and exits)
     */
    public static function totp_verify()
    {
        if (SucuriScanRequest::post('form_action') !== 'totp_verify') {
            return;
        }

        if (!SucuriScanInterface::checkNonce()) {
            return SucuriScanInterface::error(__('Incorrect nonce.', 'sucuri-scanner'));
        }

        $user = wp_get_current_user();

        if (!$user || !$user->ID) {
            return SucuriScanInterface::error(__('Incorrect user.', 'sucuri-scanner'));
        }

        $user_id = (int) $user->ID;
        $existingKey = self::get_user_totp_key($user_id);
        $code_raw = SucuriScanRequest::post('topt_code', '[0-9 ]+');
        $code = $code_raw ? preg_replace('/\D+/', '', $code_raw) : '';
        $secret_input = SucuriScanRequest::post('topt_key', '[A-Za-z0-9=]+');
        $secret_input = $secret_input ? (string) $secret_input : '';
        $secret = !empty($existingKey) ? $existingKey : $secret_input;
        $verified = self::verify_totp_code($user_id, $secret, $code);

        if (!$verified['valid']) {
            wp_send_json(array('data' => '', 'error' => $verified['error']), 200);
        }

        self::store_user_totp_key($user_id, $secret);
        update_user_meta($user_id, self::LAST_SUCCESS_META_KEY, $verified['valid_ts']);

        $enforce_all = false;
        $enforce_all_raw = SucuriScanRequest::post('enforce_all', '[01]');

        if (SucuriScanPermissions::canManagePlugin()) {
            $enforce_all = ($enforce_all_raw !== false) && ((string) $enforce_all_raw === '1');
        }

        if ($enforce_all) {
            SucuriScanOption::updateOption(':twofactor_mode', 'all_users');
            SucuriScanOption::updateOption(':twofactor_users', array());
        } else {
            $list = SucuriScanOption::getOption(':twofactor_users');
            $list = is_array($list) ? array_map('intval', $list) : array();

            if (!in_array($user_id, $list, true)) {
                $list[] = $user_id;
            }

            $list = array_values(array_unique(array_filter($list, function ($v) {
                return $v > 0;
            })));

            SucuriScanOption::updateOption(':twofactor_users', $list);
            SucuriScanOption::updateOption(':twofactor_mode', 'selected_users');
        }

        wp_send_json(array('data' => 'activated', 'error' => ''), 200);
    }
}
