<?php

/**
 * Minimal permission wrappers to centralize capability checks.
 *
 * PHP version 5
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Sucuri Inc.
 * @copyright  2010-2025 Sucuri Inc.
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
 * @link       https://wordpress.org/plugins/sucuri-scanner
 */

// Abort if the file is loaded out of context.
if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

/**
 * Minimal, explicit capability wrappers used by the plugin.xw
 */
class SucuriScanPermissions extends SucuriScan
{
    /**
     * Whether the current user can manage plugin settings (manage_options).
     *
     * @return bool
     */
    public static function canManagePlugin()
    {
        return function_exists('current_user_can') && current_user_can('manage_options');
    }

    /**
     * Alias for managing Two-Factor policy.
     * Currently equivalent to canManagePlugin().
     *
     * @return bool
     */
    public static function canManageTwoFactorPolicy()
    {
        return self::canManagePlugin();
    }

    /**
     * Whether the current user can edit other users (edit_users).
     *
     * @return bool
     */
    public static function canEditUsers()
    {
        return function_exists('current_user_can') && current_user_can('edit_users');
    }

    /**
     * Whether the current user can list users (list_users).
     *
     * @return bool
     */
    public static function canListUsers()
    {
        return function_exists('current_user_can') && current_user_can('list_users');
    }

    /**
     * Whether the current user can reset Two-Factor for a target user.
     * Allows self-reset; otherwise requires edit_users.
     *
     * @param int $target_user_id Target user ID.
     * @return bool
     */
    public static function canResetTwoFactorFor($target_user_id)
    {
        $target_user_id = (int) $target_user_id;
        $current_id = function_exists('get_current_user_id') ? (int) get_current_user_id() : 0;

        if ($current_id > 0 && $current_id === $target_user_id) {
            return true;
        }

        return self::canEditUsers();
    }

    /**
     * Plugin capabilities wrappers.
     * The following mirror core caps used throughout the plugin hooks.
     */

    /** @return bool */
    public static function canDeletePlugins()
    {
        return function_exists('current_user_can') && current_user_can('delete_plugins');
    }

    /** @return bool */
    public static function canEditPlugins()
    {
        return function_exists('current_user_can') && current_user_can('edit_plugins');
    }

    /** @return bool */
    public static function canInstallPlugins()
    {
        return function_exists('current_user_can') && current_user_can('install_plugins');
    }

    /** @return bool */
    public static function canUpdatePlugins()
    {
        return function_exists('current_user_can') && current_user_can('update_plugins');
    }

    /** @return bool */
    public static function canDeleteThemes()
    {
        return function_exists('current_user_can') && current_user_can('delete_themes');
    }

    /** @return bool */
    public static function canEditThemes()
    {
        return function_exists('current_user_can') && current_user_can('edit_themes');
    }

    /** @return bool */
    public static function canInstallThemes()
    {
        return function_exists('current_user_can') && current_user_can('install_themes');
    }

    /** @return bool */
    public static function canUpdateThemes()
    {
        return function_exists('current_user_can') && current_user_can('update_themes');
    }

    /** @return bool */
    public static function canEditThemeOptions()
    {
        return function_exists('current_user_can') && current_user_can('edit_theme_options');
    }
}