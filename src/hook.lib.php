<?php

/**
 * Code related to the hook.lib.php interface.
 *
 * PHP version 5
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Daniel Cid <dcid@sucuri.net>
 * @copyright  2010-2018 Sucuri Inc.
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
 * @link       https://wordpress.org/plugins/sucuri-scanner
 */

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

/**
 * Function call interceptors.
 *
 * The term hooking covers a range of techniques used to alter or augment the
 * behavior of an operating system, of applications, or of other software
 * components by intercepting method calls or messages or events passed
 * between software components. Code that handles such intercepted methods,
 * events or messages is called a "hook".
 *
 * Hooking is used for many purposes, including debugging and extending
 * functionality. Examples might include intercepting keyboard or mouse event
 * messages before they reach an application, or intercepting operating system
 * calls in order to monitor behavior or modify the method of an application
 * or other component; it is also widely used in benchmarking programs.
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Daniel Cid <dcid@sucuri.net>
 * @copyright  2010-2018 Sucuri Inc.
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
 * @link       https://wordpress.org/plugins/sucuri-scanner
 */
class SucuriScanHook extends SucuriScanEvent
{
    /**
     * Send to Sucuri servers an alert notifying that an attachment was added to a post.
     *
     * @param  int $id The post identifier.
     * @return void
     */
    public static function hookAttachmentAdd($id = 0)
    {
        $title = 'unknown';
        $mime_type = 'unknown';
        $data = get_post($id);

        if ($data) {
            $id = $data->ID;
            $title = $data->post_title;
            $mime_type = $data->post_mime_type;
        }

        $message = sprintf('Media file added; ID: %s; name: %s; type: %s', $id, $title, $mime_type);
        self::reportNoticeEvent($message);
        self::notifyEvent('post_publication', $message);
    }
    
    /**
     * Send and alert notifying that a user was added to a blog.
     *
     * @param int    $user_id User ID.
     * @param string $role    User role.
     * @param int    $blog_id Blog ID.
     */
    public static function hookAddUserToBlog($user_id, $role, $blog_id)
    {
        $title = 'unknown';
        $email = 'user@domain.com';
        $data = get_userdata($user_id);

        if ($data) {
            $title = $data->user_login;
            $email = $data->user_email;
        }

        $message = sprintf('User added to website; user_id: %s; role: %s; blog_id: %s; name: %s; email: %s',
            $user_id, 
            $role, 
            $blog_id,
            $title,
            $email
        );
        self::reportWarningEvent($message);
    }

    /**
     * Send and alert notifying that a user was removed from a blog.
     *
     * @param int    $user_id User ID.
     * @param int    $blog_id Blog ID.
     */
    public static function hookRemoveUserFromBlog($user_id, $blog_id) {
        $title = 'unknown';
        $email = 'user@domain.com';
        $data = get_userdata($user_id);

        if ($data) {
            $title = $data->user_login;
            $email = $data->user_email;
        }
        
        $message = sprintf('User removed from website; user_id: %s; blog_id: %s; name: %s; email: %s',
            $user_id, 
            $blog_id,
            $title,
            $email
        );
        self::reportWarningEvent($message);
    }

    /**
     * Send an alert notifying that a category was created.
     *
     * @param  int $id The identifier of the category created.
     * @return void
     */
    public static function hookCategoryCreate($id = 0)
    {
        $title = ( is_int($id) ? get_cat_name($id) : 'Unknown' );

        $message = sprintf('Category created; ID: %s; name: %s', $id, $title);
        self::reportNoticeEvent($message);
        self::notifyEvent('post_publication', $message);
    }

    /**
     * Detects when the core files are updated.
     *
     * @return void
     */
    public static function hookCoreUpdate()
    {
        // WordPress update request.
        if (current_user_can('update_core')
            && SucuriScanRequest::get('action', '(do-core-upgrade|do-core-reinstall)')
            && SucuriScanRequest::post('upgrade')
        ) {
            $message = 'WordPress updated to version: ' . SucuriScanRequest::post('version');
            self::reportCriticalEvent($message);
            self::notifyEvent('website_updated', $message);
        }
    }

    /**
     * Send an alert notifying that a new link was added to the bookmarks.
     *
     * @param  int $id Identifier of the new link created;
     * @return void
     */
    public static function hookLinkAdd($id = 0)
    {
        $title = 'unknown';
        $target = '_none';
        $url = 'undefined/url';
        $data = get_bookmark($id);

        if ($data) {
            $title = $data->link_name;
            $target = $data->link_target;
            $url = $data->link_url;
        }

        $message = sprintf(
            'Bookmark link added; ID: %s; name: %s; url: %s; target: %s',
            $id,
            $title,
            $url,
            $target
        );
        self::reportWarningEvent($message);
        self::notifyEvent('post_publication', $message);
    }

    /**
     * Send an alert notifying that a new link was added to the bookmarks.
     *
     * @param  int $id Identifier of the new link created;
     * @return void
     */
    public static function hookLinkEdit($id = 0)
    {
        $title = 'unknown';
        $target = '_none';
        $url = 'undefined/url';
        $data = get_bookmark($id);

        if ($data) {
            $title = $data->link_name;
            $target = $data->link_target;
            $url = $data->link_url;
        }

        $message = sprintf(
            'Bookmark link edited; ID: %s; name: %s; url: %s; target: %s',
            $id,
            $title,
            $url,
            $target
        );
        self::reportWarningEvent($message);
        self::notifyEvent('post_publication', $message);
    }

    /**
     * Send an alert notifying that an attempt to login into the
     * administration panel failed.
     *
     * @param  string $title The name of the user account involved in the transaction.
     * @return void
     */
    public static function hookLoginFailure($title = '')
    {
        $password = SucuriScanRequest::post('pwd');
        $title = empty($title) ? 'Unknown' : sanitize_user($title, true);
        $message = 'User authentication failed: ' . $title;

        sucuriscan_log_failed_login($title);

        self::reportErrorEvent($message);

        self::notifyEvent('failed_login', $message);

        /* report brute-force attack if necessary */
        $logins = sucuriscan_get_failed_logins();

        if (is_array($logins) && !empty($logins)) {
            $max_time = 3600; /* report logins in the last hour */
            $maximum = SucuriScanOption::getOption(':maximum_failed_logins');

            if ($logins['diff_time'] <= $max_time && $logins['count'] >= $maximum) {
                /**
                 * Report brute-force attack with latest failed logins.
                 *
                 * If the time passed is within the hour, and the quantity
                 * of failed logins registered in the datastore file is
                 * bigger than the maximum quantity of failed logins allowed
                 * per hour (value configured by the administrator in the
                 * settings page), then send an email notification reporting
                 * the event and specifying that it may be a brute-force
                 * attack against the login page.
                 */
                sucuriscan_report_failed_logins($logins);
            } elseif ($logins['diff_time'] > $max_time) {
                /**
                 * Reset old failed login logs.
                 *
                 * If there time passed is superior to the hour, then reset the
                 * content of the datastore file containing the failed logins so
                 * far, any entry in that file will not be considered as part of
                 * a brute-force attack (if it exists) because the time passed
                 * between the first and last login attempt is big enough to
                 * mitigate the attack.
                 */
                sucuriscan_reset_failed_logins();
            }
        }
    }

    /**
     * Detects usage of the password reset form.
     *
     * @return void
     */
    public static function hookLoginFormResetpass()
    {
        // Detecting WordPress 2.8.3 vulnerability - $key is array.
        if (isset($_GET['key']) && is_array($_GET['key'])) {
            self::reportCriticalEvent('Attempt to reset password by attacking WP/2.8.3 bug');
        }
    }

    /**
     * Send an alert notifying that an attempt to login into the
     * administration panel was successful.
     *
     * @param  string $title User account involved in the transaction.
     * @return void
     */
    public static function hookLoginSuccess($title = '')
    {
        $title = empty($title) ? 'Unknown' : $title;
        $message = 'User authentication succeeded: ' . $title;
        self::reportNoticeEvent($message);
        self::notifyEvent('success_login', $message);
    }

    /**
     * Detects changes in the website settings.
     *
     * The plugin will monitor all the requests in the general settings page. It
     * will compare the value sent with the form with the value in the database
     * and if there are differences will send an email alert notifying the admin
     * about the changes.
     *
     * @return void
     */
    public static function hookOptionsManagement()
    {
        /* detect any Wordpress settings modification */
        if (current_user_can('manage_options') && SucuriScanOption::checkOptionsNonce()) {
            /* compare settings in the database with the modified ones */
            $options_changed = SucuriScanOption::whatOptionsWereChanged($_POST);
            $options_changed_str = '';
            $options_changed_simple = '';
            $options_changed_count = 0;

            /* determine which options were modified */
            foreach ($options_changed['original'] as $option_name => $option_value) {
                $options_changed_count += 1;
                $options_changed_str .= sprintf(
                    "The value of the option <b>%s</b> was changed from <b>'%s'</b> to <b>'%s'</b>.<br>\n",
                    self::escape($option_name),
                    self::escape($option_value),
                    self::escape($options_changed['changed'][ $option_name ])
                );
                $options_changed_simple .= sprintf(
                    "%s: from '%s' to '%s',",
                    self::escape($option_name),
                    self::escape($option_value),
                    self::escape($options_changed['changed'][ $option_name ])
                );
            }

            /* identify the origin of the request */
            $option_page = isset($_POST['option_page']) ? $_POST['option_page'] : 'options';
            $page_referer = 'Common';

            switch ($option_page) {
                case 'options':
                    $page_referer = 'Global';
                    break;

                case 'discussion': /* no_break */
                case 'general':    /* no_break */
                case 'media':      /* no_break */
                case 'permalink':  /* no_break */
                case 'reading':    /* no_break */
                case 'writing':    /* no_break */
                    $page_referer = ucwords($option_page);
                    break;
            }

            if ($options_changed_count) {
                $message = $page_referer . ' settings changed';
                self::reportErrorEvent(
                    sprintf(
                        '%s: (multiple entries): %s',
                        $message,
                        rtrim($options_changed_simple, ',')
                    )
                );
                self::notifyEvent('settings_updated', $message . "<br>\n" . $options_changed_str);
            }
        }
    }

    /**
     * Sends an alert with information about a plugin that has been activated.
     *
     * @param  string $plugin             Name of the plugin.
     * @param  string $network_activation Whether the activation was global or not.
     * @return void
     */
    public static function hookPluginActivate($plugin = '', $network_activation = '')
    {
        self::hookPluginChanges('activated', $plugin, $network_activation);
    }

    /**
     * Detects whether a plugin has been activated or deactivated.
     *
     * This method will send an email alert notifying the website owners about
     * activations or deactivations of the plugins. Notice that this only works
     * if the plugin was affected by a programmatic task. The method will not be
     * able to detect a deactivation if the plugin has been deleted via FTP or
     * SSH or any file manager available in the hosting panel.
     *
     * @param  string $action  Activated or deactivated.
     * @param  string $plugin  Short name of the plugin file.
     * @param  string $network Whether the action is global or not.
     * @return void
     */
    private static function hookPluginChanges($action, $plugin = '', $network = '')
    {
        $filename = WP_PLUGIN_DIR . '/' . $plugin;

        /* ignore non-existing plugins */
        if (!file_exists($filename)) {
            return;
        }

        $info = get_plugin_data($filename);
        $name = 'Unknown';
        $version = '0.0.0';

        if (!empty($info['Name'])) {
            $name = $info['Name'];
        }

        if (!empty($info['Version'])) {
            $version = $info['Version'];
        }

        $message = sprintf(
            'Plugin %s: %s (v%s; %s%s)',
            $action, /* activated or deactivated */
            self::escape($info['Name']),
            self::escape($info['Version']),
            self::escape($plugin),
            ($network ? '; network' : '')
        );
        self::reportWarningEvent($message);
        self::notifyEvent('plugin_' . $action, $message);
    }

    /**
     * Sends an alert with information about a plugin that has been deactivated.
     *
     * @param  string $plugin             Name of the plugin.
     * @param  string $network_activation Whether the deactivation was global or not.
     * @return void
     */
    public static function hookPluginDeactivate($plugin = '', $network_activation = '')
    {
        self::hookPluginChanges('deactivated', $plugin, $network_activation);
    }

    /**
     * Detects when a plugin is deleted.
     *
     * @return void
     */
    public static function hookPluginDelete()
    {
        // Plugin deletion request.
        if (current_user_can('delete_plugins')
            && SucuriScanRequest::post('action', 'delete-selected')
            && SucuriScanRequest::post('verify-delete', '1')
        ) {
            $plugin_list = SucuriScanRequest::post('checked', '_array');
            $items_affected = array();

            foreach ((array) $plugin_list as $plugin) {
                $filename = WP_PLUGIN_DIR . '/' . $plugin;

                if (!file_exists($filename)) {
                    continue;
                }

                $plugin_info = get_plugin_data($filename);

                if (!empty($plugin_info['Name'])
                    && !empty($plugin_info['Version'])
                ) {
                    $items_affected[] = sprintf(
                        '%s (v%s; %s)',
                        self::escape($plugin_info['Name']),
                        self::escape($plugin_info['Version']),
                        self::escape($plugin)
                    );
                }
            }

            // Report deleted plugins at once.
            if (!empty($items_affected)) {
                if (count($items_affected) > 1) {
                    $message = 'Plugins deleted: (multiple entries):';
                } else {
                    $message = 'Plugin deleted:';
                }

                $message .= "\x20" . @implode(',', $items_affected);

                self::reportWarningEvent($message);
                self::notifyEvent('plugin_deleted', $message);
            }
        }
    }

    /**
     * Detects when the plugin editor is used.
     *
     * @return void
     */
    public static function hookPluginEditor()
    {
        // Plugin editor request.
        if (current_user_can('edit_plugins')
            && SucuriScanRequest::post('action', 'update')
            && SucuriScanRequest::post('plugin', '.+')
            && SucuriScanRequest::post('file', '.+')
            && strpos($_SERVER['SCRIPT_NAME'], 'plugin-editor.php') !== false
        ) {
            $filename = SucuriScanRequest::post('file');
            $message = 'Plugin editor used in: ' . SucuriScan::escape($filename);
            self::reportErrorEvent($message);
            self::notifyEvent('theme_editor', $message);
        }
    }

    /**
     * Detects when a plugin is uploaded or installed.
     *
     * @return void
     */
    public static function hookPluginInstall()
    {
        // Plugin installation request.
        if (current_user_can('install_plugins')
            && SucuriScanRequest::get('action', '(install|upload)-plugin')
        ) {
            $plugin = SucuriScanRequest::get('plugin', '.+');

            if (isset($_FILES['pluginzip'])) {
                $plugin = $_FILES['pluginzip']['name'];
            }

            $plugin = $plugin ? $plugin : 'Unknown';
            $message = 'Plugin installed: ' . self::escape($plugin);
            self::reportWarningEvent($message);
            self::notifyEvent('plugin_installed', $message);
        }
    }

    /**
     * Detects when a plugin is updated or upgraded.
     *
     * @return void
     */
    public static function hookPluginUpdate()
    {
        // Plugin update request.
        $plugin_update_actions = '(upgrade-plugin|do-plugin-upgrade|update-selected)';

        if (!current_user_can('update_plugins')) {
            return;
        }

        if (SucuriScanRequest::getOrPost('action', $plugin_update_actions)
            || SucuriScanRequest::getOrPost('action2', $plugin_update_actions)
        ) {
            $plugin_list = array();
            $items_affected = array();

            if (SucuriScanRequest::get('plugin', '.+')
                && strpos($_SERVER['SCRIPT_NAME'], 'wp-admin/update.php') !== false
            ) {
                $plugin_list[] = SucuriScanRequest::get('plugin', '.+');
            } elseif (isset($_POST['checked'])
                && is_array($_POST['checked'])
                && !empty($_POST['checked'])
            ) {
                $plugin_list = SucuriScanRequest::post('checked', '_array');
            }

            foreach ($plugin_list as $plugin) {
                $plugin_info = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);

                if (!empty($plugin_info['Name'])
                    && !empty($plugin_info['Version'])
                ) {
                    $items_affected[] = sprintf(
                        '%s (v%s; %s)',
                        self::escape($plugin_info['Name']),
                        self::escape($plugin_info['Version']),
                        self::escape($plugin)
                    );
                }
            }

            // Report updated plugins at once.
            if (!empty($items_affected)) {
                if (count($items_affected) > 1) {
                    $message = 'Plugins updated: (multiple entries):';
                } else {
                    $message = 'Plugin updated:';
                }

                $message .= "\x20" . @implode(',', $items_affected);

                self::reportWarningEvent($message);
                self::notifyEvent('plugin_updated', $message);
            }
        }
    }

    /**
     * Every time a post or page is deleted WordPress triggers an action called
     * delete_post, this action is caught by the event monitor. However, at this
     * point the page or post has already been deleted, which means we cannot
     * send enough information about the event to the API. To fix this, we will
     * also monitor the before_delete_post action which is triggered before the
     * post is deleted but after the user has executed the action.
     *
     * We will store some information related to the post in a temporary data
     * structure. Then, when the delete_post action is triggered we will extract
     * this informaiton to send it to the API. We will delete the temporary data
     * after the operation has succeeded.
     *
     * @param  int $id The identifier of the post deleted.
     * @return void
     */
    public static function hookPostBeforeDelete($id = 0)
    {
        $data = get_post($id);

        if (!$data) {
            return;
        }

        $out = array(); /* data to cache */
        $cache = new SucuriScanCache('hookdata');

        $out['id'] = $data->ID;
        $out['author'] = $data->post_author;
        $out['type'] = $data->post_type;
        $out['status'] = $data->post_status;
        $out['inserted'] = $data->post_date;
        $out['modified'] = $data->post_modified;
        $out['guid'] = $data->guid;
        $out['title'] = empty($data->post_title) ? '(empty)' : $data->post_title;

        $cache->add('post_' . $id, $out);
    }

    /**
     * Send an alert notifying that a post was deleted.
     *
     * @param  int $id The identifier of the post deleted.
     * @return void
     */
    public static function hookPostDelete($id = 0)
    {
        $pieces = array();
        $cache = new SucuriScanCache('hookdata');
        $data = $cache->get('post_' . $id);
        $data = $data ? $data : array('id' => $id);

        foreach ($data as $keyname => $value) {
            $pieces[] = sprintf('Post %s: %s', $keyname, $value);
        }

        $cache->delete('post_' . $id);
        $entries = implode(',', $pieces); /* merge all entries together */
        self::reportWarningEvent('Post deleted: (multiple entries): ' . $entries);
    }

    /**
     * Sends an alert for transitions between post statuses.
     *
     * @param  string $new  New post status.
     * @param  string $old  Old post status.
     * @param  mixed  $post Post data.
     * @return void
     */
    public static function hookPostStatus($new = '', $old = '', $post = null)
    {
        if (!property_exists($post, 'ID')) {
            return self::throwException('Ignore corrupted post data');
        }

        /* ignore; the same */
        if ($old === $new) {
            return self::throwException('Skip events for equal transitions');
        }

        $post_type = 'post'; /* either post or page */

        if (property_exists($post, 'post_type')) {
            $post_type = $post->post_type;
        }

        if ($post_type === 'postman_sent_mail') {
            /**
             * Stop infinite loop sending the email alerts.
             *
             * The plugin detects changes in the posts, there are some other
             * plugins that intercept PHPMailer and create a post object that is
             * later used to send the real message to the users. This object is
             * also detected by our plugin and is considered an additional event
             * that must be reported, so after the first execution the operation
             * falls into an infinite loop.
             *
             * @date 30 June, 2017
             * @see https://wordpress.org/plugins/postman-smtp/
             * @see https://wordpress.org/support/topic/unable-to-access-wordpress-dashboard-after-update-to-1-8-7/
             */
            return self::throwException('Skip events for postman-smtp alerts');
        }

        /* check if email alerts are disabled for this type */
        if (SucuriScanOption::isIgnoredEvent($post_type)) {
            return self::throwException('Skip events for ignored post-types');
        }

        /* check if email alerts are disabled for this transition */
        $custom_type = sprintf('from_%s_to_%s', $old, $new);
        if (SucuriScanOption::isIgnoredEvent($custom_type)) {
            return self::throwException('Skip events for ignored post transitions');
        }

        $pieces = array();
        $post_type = ucwords($post_type);

        $pieces[] = 'ID: ' . self::escape($post->ID);
        $pieces[] = 'Old status: ' . self::escape($old);
        $pieces[] = 'New status: ' . self::escape($new);

        if (property_exists($post, 'post_title')) {
            $pieces[] = 'Title: ' . self::escape($post->post_title);
        }

        $message = self::escape($post_type) . ' status has been changed';
        $message .= "; details:\x20";
        $message .= implode(',', $pieces);

        self::reportNoticeEvent($message);
        self::notifyEvent('post_publication', $message);
    }

    /**
     * Send an alert notifying that a post was moved to the trash.
     *
     * @param  int $id The identifier of the trashed post.
     * @return void
     */
    public static function hookPostTrash($id = 0)
    {
        $title = 'Unknown';
        $status = 'none';
        $data = get_post($id);

        if ($data) {
            $title = $data->post_title;
            $status = $data->post_status;
        }

        $message = sprintf(
            'Post moved to trash; ID: %s; name: %s; status: %s',
            $id,
            $title,
            $status
        );
        self::reportWarningEvent($message);
    }

    /**
     * Send an alert notifying that a post or page is created or updated.
     *
     * @param  int $id The identifier of the post or page published.
     * @return void
     */
    private static function hookPublish($id = 0)
    {
        $title = 'Unknown';
        $p_type = 'Publication';
        $action = 'published';
        $data = get_post($id);

        if ($data) {
            $title = $data->post_title;
            $p_type = ucwords($data->post_type);
            $action = 'updated';

            /* new posts have the same creation and modification dates */
            if ($data->post_date === $data->post_modified) {
                $action = 'created';
            }

            SucuriScanFirewall::clearCacheHook();
        }

        $message = sprintf(
            '%s was %s; ID: %s; name: %s',
            self::escape($p_type),
            self::escape($action),
            intval($id),
            self::escape($title)
        );
        self::reportNoticeEvent($message);
        self::notifyEvent('post_publication', $message);
    }

    /**
     * Detects when a page is created or updated.
     *
     * @param  int $id The identifier of the post or page published.
     * @return void
     */
    public static function hookPublishPage($id = 0)
    {
        self::hookPublish($id);
    }

    /**
     * Detects when a post is created or updated via email.
     *
     * @param  int $id The identifier of the post or page published.
     * @return void
     */
    public static function hookPublishPhone($id = 0)
    {
        self::hookPublish($id);
    }

    /**
     * Detects when a post is created or updated.
     *
     * @param  int $id The identifier of the post or page published.
     * @return void
     */
    public static function hookPublishPost($id = 0)
    {
        self::hookPublish($id);
    }

    /**
     * Detects when a post is created or updated via XML-RPC.
     *
     * @param  int $id The identifier of the post or page published.
     * @return void
     */
    public static function hookPublishPostXMLRPC($id = 0)
    {
        self::hookPublish($id);
    }

    /**
     * Send an alert notifying that an attempt to retrieve the password
     * of an user account was tried.
     *
     * @param  string $title The name of the user account involved in the trasaction.
     * @return void
     */
    public static function hookRetrievePassword($title = '')
    {
        $title = empty($title) ? 'unknown' : $title;

        self::reportErrorEvent('Password retrieval attempt: ' . $title);
    }

    /**
     * Detects when a theme is deleted.
     *
     * @return void
     */
    public static function hookThemeDelete()
    {
        // Theme deletion request.
        if (current_user_can('delete_themes')
            && SucuriScanRequest::getOrPost('action', 'delete')
            && SucuriScanRequest::getOrPost('stylesheet', '.+')
        ) {
            $theme = SucuriScanRequest::getOrPost('stylesheet', '.+');
            $theme = $theme ? $theme : 'Unknown';

            $message = 'Theme deleted: ' . self::escape($theme);
            self::reportWarningEvent($message);
            self::notifyEvent('theme_deleted', $message);
        }
    }

    /**
     * Detects when the theme editor is used.
     *
     * @return void
     */
    public static function hookThemeEditor()
    {
        // Theme editor request.
        if (current_user_can('edit_themes')
            && SucuriScanRequest::post('action', 'update')
            && SucuriScanRequest::post('theme', '.+')
            && SucuriScanRequest::post('file', '.+')
            && strpos($_SERVER['SCRIPT_NAME'], 'theme-editor.php') !== false
        ) {
            $theme_name = SucuriScanRequest::post('theme');
            $filename = SucuriScanRequest::post('file');
            $message = 'Theme editor used in: ' . SucuriScan::escape($theme_name) . '/' . SucuriScan::escape($filename);
            self::reportErrorEvent($message);
            self::notifyEvent('theme_editor', $message);
        }
    }

    /**
     * Detects when a theme is installed.
     *
     * @return void
     */
    public static function hookThemeInstall()
    {
        // Theme installation request.
        if (current_user_can('install_themes')
            && SucuriScanRequest::get('action', 'install-theme')
        ) {
            $theme = SucuriScanRequest::get('theme', '.+');
            $theme = $theme ? $theme : 'Unknown';

            $message = 'Theme installed: ' . self::escape($theme);
            self::reportWarningEvent($message);
            self::notifyEvent('theme_installed', $message);
        }
    }

    /**
     * Send an alert notifying that the theme of the site was changed.
     *
     * @param  string $title The name of the new theme selected to used through out the site.
     * @return void
     */
    public static function hookThemeSwitch($title = '')
    {
        $title = empty($title) ? 'unknown' : $title;
        $message = 'Theme activated: ' . $title;
        self::reportWarningEvent($message);
        self::notifyEvent('theme_activated', $message);
    }

    /**
     * Detects when a theme is automatically or manually updated.
     *
     * @return void
     */
    public static function hookThemeUpdate()
    {
        // Theme update request.
        if (current_user_can('update_themes')
            && SucuriScanRequest::get('action', '(upgrade-theme|do-theme-upgrade)')
            && SucuriScanRequest::post('checked', '_array')
        ) {
            $themes = SucuriScanRequest::post('checked', '_array');
            $items_affected = array();

            foreach ((array) $themes as $theme) {
                $theme_info = wp_get_theme($theme);
                $theme_name = ucwords($theme);
                $theme_version = '0.0';

                if ($theme_info->exists()) {
                    $theme_name = $theme_info->get('Name');
                    $theme_version = $theme_info->get('Version');
                }

                $items_affected[] = sprintf(
                    '%s (v%s; %s)',
                    self::escape($theme_name),
                    self::escape($theme_version),
                    self::escape($theme)
                );
            }

            // Report updated themes at once.
            if (is_array($items_affected) && !empty($items_affected)) {
                if (count($items_affected) > 1) {
                    $message = 'Themes updated: (multiple entries):';
                } else {
                    $message = 'Theme updated:';
                }

                $message .= "\x20" . implode(',', $items_affected);

                self::reportWarningEvent($message);
                self::notifyEvent('theme_updated', $message);
            }
        }
    }

    /**
     * Send an alert notifying that a user account was deleted.
     *
     * @param  int $id The identifier of the user account deleted.
     * @return void
     */
    public static function hookUserDelete($id = 0)
    {
        self::reportWarningEvent('User account deleted; ID: ' . $id);
    }

    /**
     * Send an alert notifying that a user was edited.
     * @param int $id The identifier of the edited user account
     * @param object $old_user_data Object containing user's data prior to update.
     */
    public static function hookProfileUpdate($id = 0, $old_user_data)
    {
        $title = 'unknown';
        $email = 'user@domain.com';
        $roles = 'none';
        $data = get_userdata($id);

        if ($data) {
            $title = $data->user_login;
            $email = $data->user_email;
            $roles = @implode(', ', $data->roles);
        }

        $old_title = 'unknown';
        $old_email = 'user@domain.com';
        $old_roles = 'none';

        if($old_user_data) {
            $old_title = $old_user_data->user_login;
            $old_email = $old_user_data->user_email;
            $old_roles = @implode(', ', $old_user_data->roles);
        }

        $message = sprintf('User account edited; ID: %s; name: %s; old_name: %s; email: %s; old_email: %s; roles: %s; old_roles: %s',
            $id,
            $title,
            $old_title,
            $email,
            $old_email,
            $roles,
            $old_roles
        );
        self::reportWarningEvent($message);
    }

    /**
     * Send an alert notifying that a new user account was created.
     *
     * @param  int $id The identifier of the new user account created.
     * @return void
     */
    public static function hookUserRegister($id = 0)
    {
        $title = 'unknown';
        $email = 'user@domain.com';
        $roles = 'none';
        $data = get_userdata($id);

        if ($data) {
            $title = $data->user_login;
            $email = $data->user_email;
            $roles = @implode(', ', $data->roles);
        }

        $message = sprintf(
            'User account created; ID: %s; name: %s; email: %s; roles: %s',
            $id,
            $title,
            $email,
            $roles
        );
        self::reportWarningEvent($message);
        self::notifyEvent('user_registration', $message);
    }

    /**
     * Detects when a widget is added.
     *
     * @return void
     */
    public static function hookWidgetAdd()
    {
        self::hookWidgetChanges();
    }

    /**
     * Detects when a widget is added.
     *
     * @return void
     */
    private static function hookWidgetChanges()
    {
        // Widget addition or deletion.
        if (current_user_can('edit_theme_options')
            && SucuriScanRequest::post('action', 'save-widget')
            && SucuriScanRequest::post('id_base') !== false
            && SucuriScanRequest::post('sidebar') !== false
        ) {
            if (SucuriScanRequest::post('delete_widget', '1')) {
                $action_d = 'deleted';
                $action_text = 'deleted from';
            } else {
                $action_d = 'added';
                $action_text = 'added to';
            }

            $message = sprintf(
                'Widget %s (%s) %s %s (#%d; size %dx%d)',
                SucuriScanRequest::post('id_base'),
                SucuriScanRequest::post('widget-id'),
                $action_text,
                SucuriScanRequest::post('sidebar'),
                SucuriScanRequest::post('widget_number'),
                SucuriScanRequest::post('widget-width'),
                SucuriScanRequest::post('widget-height')
            );

            self::reportWarningEvent($message);
            self::notifyEvent('widget_' . $action_d, $message);
        }
    }

    /**
     * Detects when a widget is deleted.
     *
     * @return void
     */
    public static function hookWidgetDelete()
    {
        self::hookWidgetChanges();
    }
}
