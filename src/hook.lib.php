<?php

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
 * between software components. Code that handles such intercepted functionmethods, events or messages is called a "hook".
 *
 * Hooking is used for many purposes, including debugging and extending
 * functionality. Examples might include intercepting keyboard or mouse event
 * messages before they reach an application, or intercepting operating system
 * calls in order to monitor behavior or modify the method of an application
 * or other component; it is also widely used in benchmarking programs.
 */
class SucuriScanHook extends SucuriScanEvent
{
    /**
     * Detects whether a plugin has been activated or deactivated.
     *
     * @param  string $plugin             Name of the plugin.
     * @param  string $network_activation Whether the activation was global or not.
     * @return void
     */
    private static function detectPluginChanges($action, $plugin = '', $network_activation = '')
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
            'Plugin %s: %s (v%s; %s)',
            $action, /* activated or deactivated */
            self::escape($info['Name']),
            self::escape($info['Version']),
            self::escape($plugin)
        );
        self::reportWarningEvent($message);
        self::notifyEvent('plugin_' . $action, $message);
    }

    /**
     * Sends an alert with information about a plugin that has been activated.
     *
     * @param  string $plugin             Name of the plugin.
     * @param  string $network_activation Whether the activation was global or not.
     * @return void
     */
    public static function hookDetectPluginActivation($plugin = '', $network_activation = '')
    {
        self::detectPluginChanges('activated', $plugin, $network_activation);
    }

    /**
     * Sends an alert with information about a plugin that has been deactivated.
     *
     * @param  string $plugin             Name of the plugin.
     * @param  string $network_activation Whether the deactivation was global or not.
     * @return void
     */
    public static function hookDetectPluginDeactivation($plugin = '', $network_activation = '')
    {
        self::detectPluginChanges('deactivated', $plugin, $network_activation);
    }

    /**
     * Send to Sucuri servers an alert notifying that an attachment was added to a post.
     *
     * @param  integer $id The post identifier.
     * @return void
     */
    public static function hookAddAttachment($id = 0)
    {
        if ($data = get_post($id)) {
            $id = $data->ID;
            $title = $data->post_title;
            $mime_type = $data->post_mime_type;
        } else {
            $title = 'unknown';
            $mime_type = 'unknown';
        }

        $message = sprintf('Media file added; identifier: %s; name: %s; type: %s', $id, $title, $mime_type);
        self::reportNoticeEvent($message);
        self::notifyEvent('post_publication', $message);
    }

    /**
     * Send an alert notifying that a new link was added to the bookmarks.
     *
     * @param  integer $id Identifier of the new link created;
     * @return void
     */
    public static function hookAddLink($id = 0)
    {
        $title = 'unknown';
        $target = '_none';
        $url = 'undefined/url';

        if ($data = get_bookmark($id)) {
            $title = $data->link_name;
            $target = $data->link_target;
            $url = $data->link_url;
        }

        $message = sprintf(
            'Bookmark link added; identifier: %s; name: %s; url: %s; target: %s',
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
     * @param  integer $id Identifier of the new link created;
     * @return void
     */
    public static function hookEditLink($id = 0)
    {
        $title = 'unknown';
        $target = '_none';
        $url = 'undefined/url';

        if ($data = get_bookmark($id)) {
            $title = $data->link_name;
            $target = $data->link_target;
            $url = $data->link_url;
        }

        $message = sprintf(
            'Bookmark link edited; identifier: %s; name: %s; url: %s; target: %s',
            $id,
            $title,
            $url,
            $target
        );
        self::reportWarningEvent($message);
        self::notifyEvent('post_publication', $message);
    }

    /**
     * Send an alert notifying that a category was created.
     *
     * @param  integer $id The identifier of the category created.
     * @return void
     */
    public static function hookCreateCategory($id = 0)
    {
        $title = ( is_int($id) ? get_cat_name($id) : 'Unknown' );

        $message = sprintf('Category created; identifier: %s; name: %s', $id, $title);
        self::reportNoticeEvent($message);
        self::notifyEvent('post_publication', $message);
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
     * @param  integer $id The identifier of the post deleted.
     * @return void
     */
    public static function hookBeforeDeletePost($id = 0)
    {
        if ($data = get_post($id)) {
            $out = array(); /* data to cache */
            $cache = new SucuriScanCache('hookdata');

            $out['id'] = $data->ID;
            $out['author'] = $data->post_author;
            $out['type'] = $data->post_type;
            $out['status'] = $data->post_status;
            $out['inserted'] = $data->post_date;
            $out['modified'] = $data->post_modified;
            $out['guid'] = $data->guid;
            $out['title'] = $data->post_title;

            if (empty($data->post_title)) {
                $out['title'] = '(empty)';
            }

            $cache->add('post_' . $id, $out);
        }
    }

    /**
     * Send an alert notifying that a post was deleted.
     *
     * @param  integer $id The identifier of the post deleted.
     * @return void
     */
    public static function hookDeletePost($id = 0)
    {
        $pieces = array();
        $cache = new SucuriScanCache('hookdata');
        $data = $cache->get('post_' . $id);

        if (!$data) {
            $data = array('id' => $id);
        }

        foreach ($data as $keyname => $value) {
            $pieces[] = sprintf('Post %s: %s', $keyname, $value);
        }

        $data = $cache->delete('post_' . $id);
        $entries = implode(',', $pieces); /* merge all entries together */
        self::reportWarningEvent('Post deleted: (multiple entries): ' . $entries);
    }

    /**
     * Send an alert notifying that a post was moved to the trash.
     *
     * @param  integer $id The identifier of the trashed post.
     * @return void
     */
    public static function hookWPTrashPost($id = 0)
    {
        if ($data = get_post($id)) {
            $title = $data->post_title;
            $status = $data->post_status;
        } else {
            $title = 'Unknown';
            $status = 'none';
        }

        $message = sprintf(
            'Post moved to trash; identifier: %s; name: %s; status: %s',
            $id,
            $title,
            $status
        );
        self::reportWarningEvent($message);
    }

    /**
     * Send an alert notifying that a user account was deleted.
     *
     * @param  integer $id The identifier of the user account deleted.
     * @return void
     */
    public static function hookDeleteUser($id = 0)
    {
        self::reportWarningEvent('User account deleted; identifier: ' . $id);
    }

    /**
     * Send an alert notifying that an attempt to reset the password
     * of an user account was executed.
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
     * Sends an alert for transitions between post statuses.
     *
     * @param  string $new  New post status.
     * @param  string $old  Old post status.
     * @param  object $post Post data.
     * @return void
     */
    public static function hookTransitionPostStatus($new = '', $old = '', $post)
    {
        if (!property_exists($post, 'ID')) {
            return;
        }

        $post_type = 'post'; /* either post or page */

        if (property_exists($post, 'post_type')) {
            $post_type = $post->post_type;
        }

        /* check if email alerts are disabled for this type */
        if (SucuriScanOption::isIgnoredEvent($post_type)) {
            return;
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
     * Send an alert notifying that a post was published.
     *
     * @param  integer $id The identifier of the post or page published.
     * @return void
     */
    private static function hookPublish($id = 0)
    {
        $title = 'Unknown';
        $p_type = 'Publication';
        $action = 'published';

        if ($data = get_post($id)) {
            $title = $data->post_title;
            $p_type = ucwords($data->post_type);
            $action = 'updated';

            /* new posts have the same creation and modification dates */
            if ($data->post_date === $data->post_modified) {
                $action = 'created';
            }
        }

        $message = sprintf(
            '%s was %s; identifier: %s; name: %s',
            self::escape($p_type),
            self::escape($action),
            intval($id),
            self::escape($title)
        );
        self::reportNoticeEvent($message);
        self::notifyEvent('post_publication', $message);
    }

    /**
     * Alias method for hookPublish()
     *
     * @param  integer $id The identifier of the post or page published.
     * @return void
     */
    public static function hookPublishPage($id = 0)
    {
        self::hookPublish($id);
    }

    /**
     * Alias method for hookPublish()
     *
     * @param  integer $id The identifier of the post or page published.
     * @return void
     */
    public static function hookPublishPost($id = 0)
    {
        self::hookPublish($id);
    }

    /**
     * Alias method for hookPublish()
     *
     * @param  integer $id The identifier of the post or page published.
     * @return void
     */
    public static function hookPublishPhone($id = 0)
    {
        self::hookPublish($id);
    }

    /**
     * Alias method for hookPublish()
     *
     * @param  integer $id The identifier of the post or page published.
     * @return void
     */
    public static function hookXMLRPCPublishPost($id = 0)
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
        if (empty($title)) {
            $title = 'unknown';
        }

        self::reportErrorEvent('Password retrieval attempt: ' . $title);
    }

    /**
     * Send an alert notifying that the theme of the site was changed.
     *
     * @param  string $title The name of the new theme selected to used through out the site.
     * @return void
     */
    public static function hookSwitchTheme($title = '')
    {
        if (empty($title)) {
            $title = 'unknown';
        }

        $message = 'Theme activated: ' . $title;
        self::reportWarningEvent($message);
        self::notifyEvent('theme_activated', $message);
    }

    /**
     * Send an alert notifying that a new user account was created.
     *
     * @param  integer $id The identifier of the new user account created.
     * @return void
     */
    public static function hookUserRegister($id = 0)
    {
        if ($data = get_userdata($id)) {
            $title = $data->user_login;
            $email = $data->user_email;
            $roles = @implode(', ', $data->roles);
        } else {
            $title = 'unknown';
            $email = 'user@domain.com';
            $roles = 'none';
        }

        $message = sprintf(
            'User account created; identifier: %s; name: %s; email: %s; roles: %s',
            $id,
            $title,
            $email,
            $roles
        );
        self::reportWarningEvent($message);
        self::notifyEvent('user_registration', $message);
    }

    /**
     * Send an alert notifying that an attempt to login into the
     * administration panel was successful.
     *
     * @param  string $title The name of the user account involved in the transaction.
     * @return void
     */
    public static function hookWPLogin($title = '')
    {
        if (empty($title)) {
            $title = 'Unknown';
        }

        $message = 'User authentication succeeded: ' . $title;
        self::reportNoticeEvent($message);
        self::notifyEvent('success_login', $message);
    }

    /**
     * Send an alert notifying that an attempt to login into the
     * administration panel failed.
     *
     * @param  string $title The name of the user account involved in the transaction.
     * @return void
     */
    public static function hookWPLoginFailed($title = '')
    {
        if (empty($title)) {
            $title = 'Unknown';
        }

        $title = sanitize_user($title, true);
        $password = SucuriScanRequest::post('pwd');
        $message = 'User authentication failed: ' . $title
        . '; password: ' . $password;

        self::reportErrorEvent($message);

        self::notifyEvent('failed_login', $message);

        // Log the failed login in the internal datastore for future reports.
        $logged = sucuriscan_log_failed_login($title, $password);

        // Check if the quantity of failed logins will be considered as a brute-force attack.
        if ($logged) {
            $failed_logins = sucuriscan_get_failed_logins();

            if ($failed_logins) {
                $max_time = 3600;
                $maximum_failed_logins = SucuriScanOption::getOption('sucuriscan_maximum_failed_logins');

                /**
                 * If the time passed is within the hour, and the quantity of failed logins
                 * registered in the datastore file is bigger than the maximum quantity of
                 * failed logins allowed per hour (value configured by the administrator in the
                 * settings page), then send an email notification reporting the event and
                 * specifying that it may be a brute-force attack against the login page.
                 */
                if ($failed_logins['diff_time'] <= $max_time
                    && $failed_logins['count'] >= $maximum_failed_logins
                ) {
                    sucuriscan_report_failed_logins($failed_logins);
                } /**
                 * If there time passed is superior to the hour, then reset the content of the
                 * datastore file containing the failed logins so far, any entry in that file
                 * will not be considered as part of a brute-force attack (if it exists) because
                 * the time passed between the first and last login attempt is big enough to
                 * mitigate the attack. We will consider the current failed login event as the
                 * first entry of that file in case of future attempts during the next sixty
                 * minutes.
                 */
                elseif ($failed_logins['diff_time'] > $max_time) {
                    sucuriscan_reset_failed_logins();
                    sucuriscan_log_failed_login($title);
                }
            }
        }
    }

    /**
     * Fires immediately after a comment is inserted into the database.
     *
     * The action comment-post can also be used to track the insertion of data in
     * the comments table, but this only returns the identifier of the new entry in
     * the database and the status (approved, not approved, spam). The WP-Insert-
     * Comment action returns the same identifier and additionally the full data set
     * with the comment information.
     *
     * @see https://codex.wordpress.org/Plugin_API/Action_Reference/wp_insert_comment
     * @see https://codex.wordpress.org/Plugin_API/Action_Reference/comment_post
     *
     * @param  integer $id      The comment identifier.
     * @param  object  $comment The comment object.
     * @return void
     */
    public static function hookWPInsertComment($id = 0, $comment = false)
    {
        if ($comment instanceof stdClass
            && property_exists($comment, 'comment_ID')
            && property_exists($comment, 'comment_agent')
            && property_exists($comment, 'comment_author_IP')
            && SucuriScanOption::isEnabled(':comment_monitor')
        ) {
            $data_set = array(
                'id' => $comment->comment_ID,
                'post_id' => $comment->comment_post_ID,
                'user_id' => $comment->user_id,
                'parent' => $comment->comment_parent,
                'approved' => $comment->comment_approved,
                'remote_addr' => $comment->comment_author_IP,
                'author_email' => $comment->comment_author_email,
                'date' => $comment->comment_date,
                'content' => $comment->comment_content,
                'user_agent' => $comment->comment_agent,
            );
            $message = base64_encode(json_encode($data_set));
            self::reportNoticeEvent('Base64:' . $message, true);
        }
    }

    // TODO: Log when the comment status is modified: wp_set_comment_status
    // TODO: Log when the comment data is modified: edit_comment
    // TODO: Log when the comment is going to be deleted: delete_comment, trash_comment
    // TODO: Log when the comment is finally deleted: deleted_comment, trashed_comment
    // TODO: Log when the comment is closed: comment_closed
    // TODO: Detect auto updates in core, themes, and plugin files.

    /**
     * Send a alerts to the administrator of some specific events that are
     * not triggered through an hooked action, but through a simple request in the
     * admin interface.
     *
     * @return integer Either one or zero representing the success or fail of the operation.
     */
    public static function hookUndefinedActions()
    {
        self::hookUndefinedActionsPluginUpdate();
        self::hookUndefinedActionsPluginInstallation();
        self::hookUndefinedActionsPluginDeletion();
        self::hookUndefinedActionsPluginEditor();
        self::hookUndefinedActionsThemeEditor();
        self::hookUndefinedActionsThemeInstallation();
        self::hookUndefinedActionsThemeDeletion();
        self::hookUndefinedActionsThemeUpdate();
        self::hookUndefinedActionsCoreUpdate();
        self::hookUndefinedActionsWidgetAddition();
        self::hookUndefinedActionsOptionsManagement();
    }

    private static function hookUndefinedActionsPluginUpdate()
    {
        // Plugin update request.
        $plugin_update_actions = '(upgrade-plugin|do-plugin-upgrade|update-selected)';

        if (current_user_can('update_plugins')
            && (
                SucuriScanRequest::getOrPost('action', $plugin_update_actions)
                || SucuriScanRequest::getOrPost('action2', $plugin_update_actions)
            )
        ) {
            $plugin_list = array();
            $items_affected = array();

            if (SucuriScanRequest::get('plugin', '.+')
                && strpos($_SERVER['REQUEST_URI'], 'wp-admin/update.php') !== false
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
                $message_tpl = ( count($items_affected) > 1 )
                    ? 'Plugins updated: (multiple entries): %s'
                    : 'Plugin updated: %s';
                $message = sprintf(
                    $message_tpl,
                    @implode(',', $items_affected)
                );
                self::reportWarningEvent($message);
                self::notifyEvent('plugin_updated', $message);
            }
        }
    }

    private static function hookUndefinedActionsPluginInstallation()
    {
        // Plugin installation request.
        if (current_user_can('install_plugins')
            && SucuriScanRequest::get('action', '(install|upload)-plugin')
        ) {
            if (isset($_FILES['pluginzip'])) {
                $plugin = self::escape($_FILES['pluginzip']['name']);
            } else {
                $plugin = SucuriScanRequest::get('plugin', '.+');

                if (!$plugin) {
                    $plugin = 'Unknown';
                }
            }

            $message = 'Plugin installed: ' . self::escape($plugin);
            SucuriScanEvent::reportWarningEvent($message);
            self::notifyEvent('plugin_installed', $message);
        }
    }

    private static function hookUndefinedActionsPluginDeletion()
    {
        // Plugin deletion request.
        if (current_user_can('delete_plugins')
            && SucuriScanRequest::post('action', 'delete-selected')
            && SucuriScanRequest::post('verify-delete', '1')
        ) {
            $plugin_list = SucuriScanRequest::post('checked', '_array');
            $items_affected = array();

            foreach ((array) $plugin_list as $plugin) {
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

            // Report deleted plugins at once.
            if (!empty($items_affected)) {
                $message_tpl = ( count($items_affected) > 1 )
                    ? 'Plugins deleted: (multiple entries): %s'
                    : 'Plugin deleted: %s';
                $message = sprintf(
                    $message_tpl,
                    @implode(',', $items_affected)
                );
                self::reportWarningEvent($message);
                self::notifyEvent('plugin_deleted', $message);
            }
        }
    }

    private static function hookUndefinedActionsPluginEditor()
    {
        // Plugin editor request.
        if (current_user_can('edit_plugins')
            && SucuriScanRequest::post('action', 'update')
            && SucuriScanRequest::post('plugin', '.+')
            && SucuriScanRequest::post('file', '.+')
            && strpos($_SERVER['REQUEST_URI'], 'plugin-editor.php') !== false
        ) {
            $filename = SucuriScanRequest::post('file');
            $message = 'Plugin editor used in: ' . SucuriScan::escape($filename);
            self::reportErrorEvent($message);
            self::notifyEvent('theme_editor', $message);
        }
    }

    private static function hookUndefinedActionsThemeEditor()
    {
        // Theme editor request.
        if (current_user_can('edit_themes')
            && SucuriScanRequest::post('action', 'update')
            && SucuriScanRequest::post('theme', '.+')
            && SucuriScanRequest::post('file', '.+')
            && strpos($_SERVER['REQUEST_URI'], 'theme-editor.php') !== false
        ) {
            $theme_name = SucuriScanRequest::post('theme');
            $filename = SucuriScanRequest::post('file');
            $message = 'Theme editor used in: ' . SucuriScan::escape($theme_name) . '/' . SucuriScan::escape($filename);
            self::reportErrorEvent($message);
            self::notifyEvent('theme_editor', $message);
        }
    }

    private static function hookUndefinedActionsThemeInstallation()
    {
        // Theme installation request.
        if (current_user_can('install_themes')
            && SucuriScanRequest::get('action', 'install-theme')
        ) {
            $theme = SucuriScanRequest::get('theme', '.+');

            if (!$theme) {
                $theme = 'Unknown';
            }

            $message = 'Theme installed: ' . self::escape($theme);
            SucuriScanEvent::reportWarningEvent($message);
            self::notifyEvent('theme_installed', $message);
        }
    }

    private static function hookUndefinedActionsThemeDeletion()
    {
        // Theme deletion request.
        if (current_user_can('delete_themes')
            && SucuriScanRequest::getOrPost('action', 'delete')
            && SucuriScanRequest::getOrPost('stylesheet', '.+')
        ) {
            $theme = SucuriScanRequest::get('stylesheet', '.+');

            if (!$theme) {
                $theme = 'Unknown';
            }

            $message = 'Theme deleted: ' . self::escape($theme);
            SucuriScanEvent::reportWarningEvent($message);
            self::notifyEvent('theme_deleted', $message);
        }
    }

    private static function hookUndefinedActionsThemeUpdate()
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
            if (!empty($items_affected)) {
                $message_tpl = ( count($items_affected) > 1 )
                    ? 'Themes updated: (multiple entries): %s'
                    : 'Theme updated: %s';
                $message = sprintf(
                    $message_tpl,
                    @implode(',', $items_affected)
                );
                self::reportWarningEvent($message);
                self::notifyEvent('theme_updated', $message);
            }
        }
    }

    private static function hookUndefinedActionsCoreUpdate()
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

    private static function hookUndefinedActionsWidgetAddition()
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

    private static function hookUndefinedActionsOptionsManagement()
    {
        // Detect any Wordpress settings modification.
        if (current_user_can('manage_options')
            && SucuriScanOption::checkOptionsNonce()
        ) {
            // Get the settings available in the database and compare them with the submission.
            $options_changed = SucuriScanOption::whatOptionsWereChanged($_POST);
            $options_changed_str = '';
            $options_changed_simple = '';
            $options_changed_count = 0;

            // Generate the list of options changed.
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

            // Get the option group (name of the page where the request was originated).
            $option_page = isset($_POST['option_page']) ? $_POST['option_page'] : 'options';
            $page_referer = false;

            // Check which of these option groups where modified.
            switch ($option_page) {
                case 'options':
                    $page_referer = 'Global';
                    break;
                case 'general':    /* no_break */
                case 'writing':    /* no_break */
                case 'reading':    /* no_break */
                case 'discussion': /* no_break */
                case 'media':      /* no_break */
                case 'permalink':
                    $page_referer = ucwords($option_page);
                    break;
                default:
                    $page_referer = 'Common';
                    break;
            }

            if ($page_referer && $options_changed_count > 0) {
                $message = $page_referer . ' settings changed';
                SucuriScanEvent::reportErrorEvent(sprintf(
                    '%s: (multiple entries): %s',
                    $message,
                    rtrim($options_changed_simple, ',')
                ));
                self::notifyEvent('settings_updated', $message . "<br>\n" . $options_changed_str);
            }
        }
    }
}
