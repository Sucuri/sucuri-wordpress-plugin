<?php

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

/**
 * Sucuri one-click hardening page.
 *
 * It loads all the functions defined in /lib/hardening.php and shows the forms
 * that the administrator can use to harden multiple parts of the site.
 *
 * @return void
 */
function sucuriscan_hardening_page()
{
    SucuriScanInterface::checkPageVisibility();

    $template_variables = array(
        'Hardening.Panel' => sucuriscan_hardening_panel(),
        'Hardening.Whitelist' => sucuriscan_hardening_whitelist(),
    );

    echo SucuriScanTemplate::getTemplate('hardening', $template_variables);
}

function sucuriscan_hardening_panel()
{
    if (SucuriScanRequest::post(':run_hardening')
        && !SucuriScanInterface::checkNonce()
    ) {
        unset($_POST['sucuriscan_run_hardening']);
    }

    $template_variables = array(
        'PageTitle' => 'Hardening',
        'Hardening.Version' => sucuriscan_harden_version(),
        'Hardening.CloudProxy' => sucuriscan_cloudproxy_enabled(),
        'Hardening.RemoveGenerator' => sucuriscan_harden_removegenerator(),
        'Hardening.NginxPhpFpm' => '',
        'Hardening.Upload' => '',
        'Hardening.WpContent' => '',
        'Hardening.WpIncludes' => '',
        'Hardening.PhpVersion' => sucuriscan_harden_phpversion(),
        'Hardening.SecretKeys' => sucuriscan_harden_secretkeys(),
        'Hardening.Readme' => sucuriscan_harden_readme(),
        'Hardening.AdminUser' => sucuriscan_harden_adminuser(),
        'Hardening.FileEditor' => sucuriscan_harden_fileeditor(),
        'Hardening.DBTables' => sucuriscan_harden_dbtables(),
    );

    if (SucuriScan::isNginxServer() === true) {
        $template_variables['Hardening.NginxPhpFpm'] = sucuriscan_harden_nginx_phpfpm();
    } elseif (SucuriScan::isIISServer() === true) {
        /* TODO: Include IIS (Internet Information Services) hardening options. */
    } else {
        $template_variables['Hardening.Upload'] = sucuriscan_harden_upload();
        $template_variables['Hardening.WpContent'] = sucuriscan_harden_wpcontent();
        $template_variables['Hardening.WpIncludes'] = sucuriscan_harden_wpincludes();
    }

    return SucuriScanTemplate::getSection('hardening-panel', $template_variables);
}

function sucuriscan_hardening_whitelist()
{
    $template_variables = array(
        'HardeningWhitelist.List' => '',
        'HardeningWhitelist.NoItemsVisibility' => 'visible',
    );
    $allowed_folders = array(
        'wp-includes',
        'wp-content',
        'wp-content/uploads',
    );

    if (SucuriScanInterface::checkNonce()) {
        // Add a new file to the hardening whitelist.
        if ($fwhite = SucuriScanRequest::post(':hardening_whitelist')) {
            $folder = SucuriScanRequest::post(':hardening_folder');

            if (in_array($folder, $allowed_folders)) {
                try {
                    SucuriScanHardening::whitelist($fwhite, $folder);
                    SucuriScanInterface::info('File was whitelisted from the hardening');
                } catch (Exception $e) {
                    SucuriScanInterface::error($e->getMessage());
                }
            } else {
                SucuriScanInterface::error('Specified folder is not hardened by this plugin');
            }
        }

        // Remove a file from the hardening whitelist.
        if ($rmfwhite = SucuriScanRequest::post(':hardening_rmfwhite', '_array')) {
            foreach ($rmfwhite as $fpath) {
                $fpath = str_replace('/.*/', '|', $fpath);
                $parts = explode('|', $fpath, 2);
                SucuriScanHardening::dewhitelist($parts[1], $parts[0]);
            }

            SucuriScanInterface::info('Selected files were processed successfully');
        }
    }

    // Read the access control file and retrieve the whitelisted files.
    $counter = 0;
    foreach ($allowed_folders as $folder) {
        $files = SucuriScanHardening::getWhitelisted($folder);

        if ($files !== false) {
            $template_variables['HardeningWhitelist.NoItemsVisibility'] = 'hidden';

            foreach ($files as $file) {
                $css_class = ($counter % 2 === 0) ? '' : 'alternate';
                $fregexp = sprintf('%s/.*/%s', $folder, $file);
                $html = SucuriScanTemplate::getSnippet(
                    'hardening-whitelist',
                    array(
                        'HardeningWhitelist.CssClass' => $css_class,
                        'HardeningWhitelist.Regexp' => $fregexp,
                        'HardeningWhitelist.Folder' => $folder,
                        'HardeningWhitelist.File' => $file,
                    )
                );
                $template_variables['HardeningWhitelist.List'] .= $html;
                $counter++;
            }
        }
    }

    return SucuriScanTemplate::getSection('hardening-whitelist', $template_variables);
}

/**
 * Generate the HTML code necessary to show a form with the options to harden
 * a specific part of the WordPress installation, if the Status variable is
 * set as a positive integer the button is shown as "unharden".
 *
 * @param  string  $title       Title of the panel.
 * @param  integer $status      Either one or zero representing the state of the hardening, one for secure, zero for insecure.
 * @param  string  $type        Name of the hardening option, this will be used through out the form generation.
 * @param  string  $messageok   Message that will be shown if the hardening was executed.
 * @param  string  $messagewarn Message that will be shown if the hardening is not executed.
 * @param  string  $desc        Optional description of the hardening.
 * @param  string  $updatemsg   Optional explanation of the hardening after the submission of the form.
 * @return void
 */
function sucuriscan_harden_status($title = '', $status = 0, $type = '', $messageok = '', $messagewarn = '', $desc = null, $updatemsg = null)
{
    $template_variables = array(
        'Hardening.Title' => $title,
        'Hardening.Description' => '',
        'Hardening.Status' => 'unknown',
        'Hardening.StatusVisibility' => 'visible',
        'Hardening.FieldName' => '',
        'Hardening.FieldValue' => '',
        'Hardening.FieldAttributes' => '',
        'Hardening.Information' => '',
        'Hardening.UpdateMessage' => '',
    );

    if (is_null($type)) {
        $type = 'unknown';
        $template_variables['Hardening.FieldAttributes'] = 'disabled="disabled"';
    }

    $template_variables['Hardening.Status'] = (string) $status;

    if ($status === 1) {
        $template_variables['Hardening.FieldName'] = $type . '_unharden';
        $template_variables['Hardening.FieldValue'] = 'Revert hardening';
        $template_variables['Hardening.Information'] = $messageok;
    } elseif ($status === 0) {
        $template_variables['Hardening.FieldName'] = $type;
        $template_variables['Hardening.FieldValue'] = 'Harden';
        $template_variables['Hardening.Information'] = $messagewarn;
    } else {
        $template_variables['Hardening.FieldName'] = '';
        $template_variables['Hardening.FieldValue'] = 'Unavailable';
        $template_variables['Hardening.Information'] = 'Can not be determined.';
        $template_variables['Hardening.FieldAttributes'] = 'disabled="disabled"';
    }

    if (!is_null($desc)) {
        $template_variables['Hardening.Description'] = '<p>' . $desc . '</p>';
    }

    if (!is_null($updatemsg)) {
        $template_variables['Hardening.UpdateMessage'] = '<p>' . $updatemsg . '</p>';
    }

    if ($status === 999) {
        $template_variables['Hardening.StatusVisibility'] = 'hidden';
    }

    return SucuriScanTemplate::getSnippet('hardening', $template_variables);
}

/**
 * Check whether the version number of the WordPress installed is the latest
 * version available officially.
 *
 * @return void
 */
function sucuriscan_harden_version()
{
    $site_version = SucuriScan::siteVersion();
    $updates = get_core_updates();
    $cp = (!is_array($updates) || empty($updates) ? 1 : 0);

    if (isset($updates[0]) && $updates[0] instanceof stdClass) {
        if ($updates[0]->response == 'latest'
            || $updates[0]->response == 'development'
        ) {
            $cp = 1;
        }
    }

    if (strcmp($site_version, '3.7') < 0) {
        $cp = 0;
    }

    $initial_msg = 'Why keep your site updated? WordPress is an open-source
        project which means that with every update the details of the changes made
        to the source code are made public, if there were security fixes then
        someone with malicious intent can use this information to attack any site
        that has not been upgraded.';
    $messageok = sprintf('Your WordPress installation (%s) is current.', $site_version);
    $messagewarn = sprintf(
        'Your current version (%s) is not current.<br>
        <a href="update-core.php" class="button-primary">Update now!</a>',
        $site_version
    );

    return sucuriscan_harden_status('Verify WordPress version', $cp, null, $messageok, $messagewarn, $initial_msg);
}

/**
 * Notify the state of the hardening for the removal of the Generator tag in
 * HTML code printed by WordPress to show the current version number of the
 * installation.
 *
 * @return void
 */
function sucuriscan_harden_removegenerator()
{
    return sucuriscan_harden_status(
        'Remove WordPress version',
        1,
        null,
        'WordPress version properly hidden',
        null,
        'It checks if your WordPress version is being hidden from being displayed '
        .'in the generator tag (enabled by default with this plugin).'
    );
}

function sucuriscan_harden_nginx_phpfpm()
{
    $description = 'It seems that you are using the Nginx web server, if that is
        the case then you will need to add the following code into the global
        <code>nginx.conf</code> file or the virtualhost associated with this
        website. Choose the correct rules for the directories that you want to
        protect. If you encounter errors after restart the web server then revert
        the changes and contact the support team of your hosting company, or read
        the official article about <a href="https://codex.wordpress.org/Nginx">
        WordPress on Nginx</a>.</p>';

    $description .= "<pre class='code'># Block PHP files in uploads directory.\nlocation ~* /(?:uploads|files)/.*\.php$ {\n\x20\x20deny all;\n}</pre>";
    $description .= "<pre class='code'># Block PHP files in content directory.\nlocation ~* /wp-content/.*\.php$ {\n\x20\x20deny all;\n}</pre>";
    $description .= "<pre class='code'># Block PHP files in includes directory.\nlocation ~* /wp-includes/.*\.php$ {\n\x20\x20deny all;\n}</pre>";

    $description .= "<pre class='code'>";
    $description .= "# Block PHP files in uploads, content, and includes directory.\n";
    $description .= "location ~* /(?:uploads|files|wp-content|wp-includes)/.*\.php$ {\n";
    $description .= "\x20\x20deny all;\n";
    $description .= '}</pre>';

    $description .= '<p>';
    $description .= 'If you need to unblock individual files like the one required
        to keep the TinyMCE plugin working which is located <em>(in the current
        version)</em> at <em>"/wp-includes/js/tinymce/wp-tinymce.php"</em> you may
        want to include a rule like this one, changing <em>"/path/to/file.php"</em>
        with the file path that you want to allow access relative to the document
        root.';
    $description .= '</p>';

    $description .= "<pre class='code'>";
    $description .= "location = /path/to/file.php {\n";
    $description .= "\x20\x20allow all;\n";
    $description .= '}</pre>';

    $description .= '<p class="sucuriscan-hidden">';

    return sucuriscan_harden_status(
        'Block PHP files',
        999,
        null,
        null,
        null,
        $description
    );
}

/**
 * Check whether the WordPress upload folder is protected or not.
 *
 * A htaccess file is placed in the upload folder denying the access to any php
 * file that could be uploaded through a vulnerability in a Plugin, Theme or
 * WordPress itself.
 *
 * @return void
 */
function sucuriscan_harden_upload()
{
    $dpath = WP_CONTENT_DIR . '/uploads';

    if (SucuriScanRequest::post(':run_hardening')) {
        if (SucuriScanRequest::post(':harden_upload')) {
            $result = SucuriScanHardening::hardenDirectory($dpath);

            if ($result === true) {
                $message = 'Hardening applied to the uploads directory';
                SucuriScanEvent::reportNoticeEvent($message);
                SucuriScanInterface::info($message);
            } else {
                SucuriScanInterface::error('Error hardening directory, check the permissions.');
            }
        } elseif (SucuriScanRequest::post(':harden_upload_unharden')) {
            $result = SucuriScanHardening::unhardenDirectory($dpath);

            if ($result === true) {
                $message = 'Hardening reverted in the uploads directory';
                SucuriScanEvent::reportErrorEvent($message);
                SucuriScanInterface::info($message);
            } else {
                SucuriScanInterface::info('Access file is not writable, check the permissions.');
            }
        }
    }

    // Check whether the directory is already hardened or not.
    $is_hardened = SucuriScanHardening::isHardened($dpath);
    $cp = ( $is_hardened === true ) ? 1 : 0;

    $description = 'It checks if the uploads directory of this site allows the direct execution'
        . ' of PHP files. It is recommendable to prevent this because someone may try to exploit'
        . ' a vulnerability of a plugin, theme, and/or other PHP-based code located in this'
        . ' directory sending requests directory to these files.</p><p><b>Note:</b> Many plugins'
        . ' and themes in the WordPress marketplace put <em>(insecure)</em> PHP files in this'
        . ' folder for <em>"X"</em> or <em>"Y"</em> reasons, they may not want to change their'
        . ' code to prevent security issues, so you will have to keep this option un-hardened'
        . ' or else you will end up breaking their functionality.';

    return sucuriscan_harden_status(
        'Protect uploads directory',
        $cp,
        'sucuriscan_harden_upload',
        'Upload directory properly hardened',
        'Upload directory not hardened',
        $description,
        null
    );
}

/**
 * Check whether the WordPress content folder is protected or not.
 *
 * A htaccess file is placed in the content folder denying the access to any php
 * file that could be uploaded through a vulnerability in a Plugin, Theme or
 * WordPress itself.
 *
 * @return void
 */
function sucuriscan_harden_wpcontent()
{
    if (SucuriScanRequest::post(':run_hardening')) {
        if (SucuriScanRequest::post(':harden_wpcontent')) {
            $result = SucuriScanHardening::hardenDirectory(WP_CONTENT_DIR);

            if ($result === true) {
                $message = 'Hardening applied to the content directory';
                SucuriScanEvent::reportNoticeEvent($message);
                SucuriScanInterface::info($message);
            } else {
                SucuriScanInterface::error('Error hardening directory, check the permissions.');
            }
        } elseif (SucuriScanRequest::post(':harden_wpcontent_unharden')) {
            $result = SucuriScanHardening::unhardenDirectory(WP_CONTENT_DIR);

            if ($result === true) {
                $message = 'Hardening reverted in the content directory';
                SucuriScanEvent::reportErrorEvent($message);
                SucuriScanInterface::info($message);
            } else {
                SucuriScanInterface::info('Access file is not writable, check the permissions.');
            }
        }
    }

    // Check whether the directory is already hardened or not.
    $is_hardened = SucuriScanHardening::isHardened(WP_CONTENT_DIR);
    $cp = ( $is_hardened === true ) ? 1 : 0;

    $description = 'This option blocks direct access to any PHP file located under the content'
        . ' directory of this site. The note under the <em>"Protect uploads directory"</em>'
        . ' section also applies to this option so you may want to read that part too. If you'
        . ' experience any kind of issues in your site after you apply this hardening go to the'
        . ' content directory using a FTP client or a file manager <em>(generally available in'
        . ' your hosting panel)</em> and rename a file named <code>.htaccess</code>.';

    return sucuriscan_harden_status(
        'Restrict wp-content access',
        $cp,
        'sucuriscan_harden_wpcontent',
        'WP-content directory properly hardened',
        'WP-content directory not hardened',
        $description,
        null
    );
}

/**
 * Check whether the WordPress includes folder is protected or not.
 *
 * A htaccess file is placed in the includes folder denying the access to any php
 * file that could be uploaded through a vulnerability in a Plugin, Theme or
 * WordPress itself, there are some exceptions for some specific files that must
 * be available publicly.
 *
 * @return void
 */
function sucuriscan_harden_wpincludes()
{
    $dpath = ABSPATH . '/wp-includes';

    if (SucuriScanRequest::post(':run_hardening')) {
        if (SucuriScanRequest::post(':harden_wpincludes')) {
            $result = SucuriScanHardening::hardenDirectory($dpath);

            if ($result === true) {
                $message = 'Hardening applied to the library directory';
                SucuriScanHardening::whitelist('wp-tinymce.php', 'wp-includes');
                SucuriScanHardening::whitelist('ms-files.php', 'wp-includes');
                SucuriScanEvent::reportNoticeEvent($message);
                SucuriScanInterface::info($message);
            } else {
                SucuriScanInterface::error('Error hardening directory, check the permissions.');
            }
        } elseif (SucuriScanRequest::post(':harden_wpincludes_unharden')) {
            $result = SucuriScanHardening::unhardenDirectory($dpath);

            if ($result === true) {
                $message = 'Hardening reverted in the library directory';
                SucuriScanHardening::dewhitelist('wp-tinymce.php', 'wp-includes');
                SucuriScanHardening::dewhitelist('ms-files.php', 'wp-includes');
                SucuriScanEvent::reportErrorEvent($message);
                SucuriScanInterface::info($message);
            } else {
                SucuriScanInterface::info('Access file is not writable, check the permissions.');
            }
        }
    }

    // Check whether the directory is already hardened or not.
    $is_hardened = SucuriScanHardening::isHardened($dpath);
    $cp = ( $is_hardened === true ) ? 1 : 0;

    return sucuriscan_harden_status(
        'Restrict wp-includes access',
        $cp,
        'sucuriscan_harden_wpincludes',
        'WP-Includes directory properly hardened',
        'WP-Includes directory not hardened',
        'This option blocks direct PHP access to any file inside <code>wp-includes</code>.',
        null
    );
}

/**
 * Check the version number of the PHP interpreter set to work with the site,
 * is considered that old versions of the PHP interpreter are insecure.
 *
 * @return void
 */
function sucuriscan_harden_phpversion()
{
    return sucuriscan_harden_status(
        'Verify PHP version',
        intval(version_compare(PHP_VERSION, '5.6.0') >= 0),
        null,
        'Using an updated version of PHP &mdash; <code>' . PHP_VERSION . '</code>',
        'You are using an outdated version of PHP &mdash; <code>' . PHP_VERSION . '</code>'
        . '<a href="http://php.net/supported-versions.php" target="_blank" class="button-primary">Update Now</a>',
        'This checks if you have the latest version of PHP installed.',
        null
    );
}

/**
 * Check whether the site is behind a secure proxy server or not.
 *
 * @return void
 */
function sucuriscan_cloudproxy_enabled()
{
    $btn_string = '';
    $proxy_info = SucuriScan::isBehindCloudproxy();
    $status = 1;

    $description = 'A WAF is a protection layer for your web site, blocking all sort of attacks (brute force attempts, '
        . 'DDoS, SQL injections, etc) and helping it remain malware and blacklist free. This test checks if your site is '
        . 'using <a href="https://cloudproxy.sucuri.net/" target="_blank">Sucuri\'s CloudProxy WAF</a> to protect your site.';

    if ($proxy_info === false) {
        $status = 0;
        $btn_string = '<a href="https://goo.gl/qfNkMq" target="_blank" class="button button-primary">Harden</a>';
    }

    return sucuriscan_harden_status(
        'Website Firewall protection',
        $status,
        null,
        'Your website is protected by a Website Firewall (WAF)',
        $btn_string . 'Your website is not protected by a Website Firewall (WAF)',
        $description,
        null
    );
}

/**
 * Check whether the Wordpress configuration file has the security keys recommended
 * to avoid any unauthorized access to the interface.
 *
 * WordPress Security Keys is a set of random variables that improve encryption of
 * information stored in the user’s cookies. There are a total of four security
 * keys: AUTH_KEY, SECURE_AUTH_KEY, LOGGED_IN_KEY, and NONCE_KEY.
 *
 * @return void
 */
function sucuriscan_harden_secretkeys()
{
    $wp_config_path = SucuriScan::getWPConfigPath();
    $current_keys = SucuriScanOption::getSecurityKeys();

    if ($wp_config_path) {
        $cp = 1;
        $wp_config_path = SucuriScan::escape($wp_config_path);
        $message = 'The main configuration file was found at: <code>' . $wp_config_path . '</code><br>';

        if (!empty($current_keys['bad']) || !empty($current_keys['missing'])) {
            $cp = 0;
        }
    } else {
        $cp = 0;
        $message = 'The <code>wp-config.php</code> file was not found.<br>';
    }

    $message .= '<br>It checks whether you have proper random keys/salts created for WordPress. A
        <a href="https://codex.wordpress.org/Editing_wp-config.php#Security_Keys" target="_blank">
        secret key</a> makes your site harder to hack and access harder to crack by adding
        random elements to the password. In simple terms, a secret key is a password with
        elements that make it harder to generate enough options to break through your
        security barriers.';
    $messageok = 'Security keys and salts not set, we recommend to create them for security reasons'
        . '<a href="' . SucuriScanTemplate::getUrl('posthack') . '" class="button button-primary">'
        . 'Harden</a>';

    return sucuriscan_harden_status(
        'Security keys',
        $cp,
        null,
        'Security keys and salts properly created',
        $messageok,
        $message,
        null
    );
}

/**
 * Check whether the "readme.html" file is still available in the root of the
 * site or not, which can lead to an attacker to know which version number of
 * Wordpress is being used and search for possible vulnerabilities.
 *
 * @return void
 */
function sucuriscan_harden_readme()
{
    $upmsg = null;
    $cp = is_readable(ABSPATH.'/readme.html') ? 0 : 1;

    // TODO: After hardening create an option to automatically remove this after WP upgrade.
    if (SucuriScanRequest::post(':run_hardening')) {
        if (SucuriScanRequest::post(':harden_readme') && $cp == 0) {
            if (@unlink(ABSPATH.'/readme.html') === false) {
                $upmsg = SucuriScanInterface::error('Unable to remove <code>readme.html</code> file.');
            } else {
                $cp = 1;
                $message = 'Hardening applied to the <code>readme.html</code> file';
                SucuriScanEvent::reportNoticeEvent($message);
                SucuriScanInterface::info($message);
            }
        } elseif (SucuriScanRequest::post(':harden_readme_unharden')) {
            SucuriScanInterface::error('We can not revert this action, you must create the <code>readme.html</code> manually.');
        }
    }

    return sucuriscan_harden_status(
        'Information leakage (readme.html)',
        $cp,
        ( $cp == 0 ? 'sucuriscan_harden_readme' : null ),
        '<code>readme.html</code> file properly deleted',
        '<code>readme.html</code> not deleted and leaking the WordPress version',
        'It checks whether you have the <code>readme.html</code> file available that leaks your WordPress version',
        $upmsg
    );
}

/**
 * Check whether the main administrator user still has the default name "admin"
 * or not, which can lead to an attacker to perform a brute force attack.
 *
 * @return void
 */
function sucuriscan_harden_adminuser()
{
    $upmsg = null;
    $user_query = new WP_User_Query(array(
        'search' => 'admin',
        'fields' => array( 'ID', 'user_login' ),
        'search_columns' => array( 'user_login' ),
    ));
    $results = $user_query->get_results();
    $account_removed = ( count($results) === 0 ? 1 : 0 );

    if ($account_removed === 0) {
        $upmsg = '<i><strong>Notice.</strong> We do not offer an option to automatically change the user name.
        Go to the <a href="'.SucuriScan::adminURL('users.php').'" target="_blank">user list</a> and create
        a new administrator user. Once created, log in as that user and remove the default <code>admin</code>
        (make sure to assign all the admin posts to the new user too).</i>';
    }

    return sucuriscan_harden_status(
        'Default admin account',
        $account_removed,
        null,
        'Default admin user account (admin) not being used',
        'Default admin user account (admin) being used. Not recommended',
        'It checks whether you have the default <code>admin</code> account enabled, security guidelines recommend creating a new admin user name.',
        $upmsg
    );
}

/**
 * Enable or disable the user of the built-in Wordpress file editor.
 *
 * @return void
 */
function sucuriscan_harden_fileeditor()
{
    $file_editor_disabled = defined('DISALLOW_FILE_EDIT') ? DISALLOW_FILE_EDIT : false;

    if (SucuriScanRequest::post(':run_hardening')) {
        $current_time = date('r');
        $wp_config_path = SucuriScan::getWPConfigPath();

        $wp_config_writable = ( file_exists($wp_config_path) && is_writable($wp_config_path) ) ? true : false;
        $new_wpconfig = $wp_config_writable ? @file_get_contents($wp_config_path) : '';

        if (SucuriScanRequest::post(':harden_fileeditor')) {
            if ($wp_config_writable) {
                if (preg_match('/(.*define\(.DB_COLLATE..*)/', $new_wpconfig, $match)) {
                    $disallow_fileedit_definition = "\n\ndefine('DISALLOW_FILE_EDIT', TRUE); // Sucuri Security: {$current_time}\n";
                    $new_wpconfig = str_replace($match[0], $match[0].$disallow_fileedit_definition, $new_wpconfig);
                }

                $file_editor_disabled = true;
                @file_put_contents($wp_config_path, $new_wpconfig, LOCK_EX);
                $message = 'Hardening applied to the plugin and theme editor';
                SucuriScanEvent::reportNoticeEvent($message);
                SucuriScanInterface::info($message);
            } else {
                SucuriScanInterface::error('The <code>wp-config.php</code> file is not in the default location
                    or is not writable, you will need to put the following code manually there:
                    <code>define("DISALLOW_FILE_EDIT", TRUE);</code>');
            }
        } elseif (SucuriScanRequest::post(':harden_fileeditor_unharden')) {
            if (preg_match("/(.*define\('DISALLOW_FILE_EDIT', TRUE\);.*)/", $new_wpconfig, $match)) {
                if ($wp_config_writable) {
                    $new_wpconfig = str_replace("\n{$match[1]}", '', $new_wpconfig);
                    file_put_contents($wp_config_path, $new_wpconfig, LOCK_EX);
                    $file_editor_disabled = false;
                    $message = 'Hardening reverted in the plugin and theme editor';
                    SucuriScanEvent::reportErrorEvent($message);
                    SucuriScanInterface::info($message);
                } else {
                    SucuriScanInterface::error('The <code>wp-config.php</code> file is not in the default location
                        or is not writable, you will need to remove the following code manually from there:
                        <code>define("DISALLOW_FILE_EDIT", TRUE);</code>');
                }
            } else {
                SucuriScanInterface::error('The theme and plugin editor are not disabled from the configuration file.');
            }
        }
    }

    $message = 'Occasionally you may wish to disable the plugin or theme editor to prevent overzealous
        users from being able to edit sensitive files and potentially crash the site. Disabling these
        also provides an additional layer of security if a hacker gains access to a well-privileged
        user account.';

    return sucuriscan_harden_status(
        'Plugin &amp; Theme editor',
        ( $file_editor_disabled === false ? 0 : 1 ),
        'sucuriscan_harden_fileeditor',
        'File editor for Plugins and Themes is disabled',
        'File editor for Plugins and Themes is enabled',
        $message,
        null
    );
}

/**
 * Check whether the prefix of each table in the database designated for the site
 * is the same as the default prefix defined by Wordpress "_wp", in that case the
 * "harden" button will generate randomly a new prefix and rename all those tables.
 *
 * @return void
 */
function sucuriscan_harden_dbtables()
{
    global $table_prefix;

    $hardened = ( $table_prefix == 'wp_' ? 0 : 1 );

    return sucuriscan_harden_status(
        'Database table prefix',
        $hardened,
        null,
        'Database table prefix properly modified',
        'Database table set to the default value <code>wp_</code>.',
        'It checks whether your database table prefix has been changed from the default <code>wp_</code>',
        '<strong>Be aware that this hardening procedure can cause your site to go down</strong>'
    );
}
