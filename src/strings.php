<?php

/**
 * Code related to translation strings.
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

// auditlogs.html.tpl
__('Loading...', 'sucuri-scanner');
__('Total logs in the queue:', 'sucuri-scanner');
__('Maximum execution time:', 'sucuri-scanner');
__('Successfully sent to the API:', 'sucuri-scanner');
__('Total request timeouts (failures):', 'sucuri-scanner');
__('Total execution time:', 'sucuri-scanner');
__('Send Logs', 'sucuri-scanner');

// base.html.tpl
__('Sucuri Security', 'sucuri-scanner');
__('WP Plugin', 'sucuri-scanner');
__('Review', 'sucuri-scanner');
__('Generate API Key', 'sucuri-scanner');
__('Dashboard', 'sucuri-scanner');
__('Firewall (WAF)', 'sucuri-scanner');
__('Settings', 'sucuri-scanner');
__('Copyright', 'sucuri-scanner');
__('Sucuri Inc. All Rights Reserved.', 'sucuri-scanner');

// dashboard.html.tpl
__('no data available', 'sucuri-scanner');
__('Audit Logs', 'sucuri-scanner');

// firewall-auditlogs.html.tpl
__('Firewall Audit Logs', 'sucuri-scanner');
__('The firewall logs every request involved in an attack and separates them from the legitimate requests. You can analyze the data from the latest entries in the logs using this tool and take action either enabling the advanced features of the IDS <em>(Intrusion Detection System)</em> from the <a href="https://waf.sucuri.net/?settings" target="_blank" rel="noopener">Firewall Dashboard</a> and/or blocking IP addresses and URL paths directly from the <a href="https://waf.sucuri.net/?audit" target="_blank" rel="noopener">Firewall Audit Trails</a> page.', 'sucuri-scanner');
__('Non-blocked requests are hidden from the logs, this is intentional.', 'sucuri-scanner');
__('Loading...', 'sucuri-scanner');
__('Submit', 'sucuri-scanner');

// firewall-auditlogs.snippet.tpl
__('Date/Time:', 'sucuri-scanner');
__('Signature:', 'sucuri-scanner');
__('Request:', 'sucuri-scanner');
__('U-Agent:', 'sucuri-scanner');
__('Target:', 'sucuri-scanner');
__('Referer:', 'sucuri-scanner');

// firewall-clearcache.html.tpl
__('Loading...', 'sucuri-scanner');
__('Clear cache when a post or page is updated (Loading...)', 'sucuri-scanner');
__('Clear cache when a post or page is updated', 'sucuri-scanner');
__('Clear Cache', 'sucuri-scanner');
__('The firewall offers multiple options to configure the cache level applied to your website. You can either enable the full cache which is the recommended setting, or you can set the cache level to minimal which will keep the pages static for a couple of minutes, or force the usage of the website headers <em>(only for advanced users)</em>, or in extreme cases where you do not need the cache you can simply disable it. Find more information about it in the <a href="https://kb.sucuri.net/firewall/Performance/caching-options" target="_blank" rel="noopener">Sucuri Knowledge Base</a> website.', 'sucuri-scanner');
__('Note that the firewall has <a href="https://kb.sucuri.net/firewall/Performance/cache-exceptions" target="_blank" rel="noopener">special caching rules</a> for Images, CSS, PDF, TXT, JavaScript, media files and a few more extensions that are stored on our <a href="https://en.wikipedia.org/wiki/Edge_device" target="_blank" rel="noopener">edge</a>. The only way to flush the cache for these files is by clearing the firewall’s cache completely <em>(for the whole website)</em>. Due to our caching of JavaScript and CSS files, often, as is best practice, the use of versioning during development will ensure updates going live as expected. This is done by adding a query string such as <code>?ver=1.2.3</code> and incrementing on each update.', 'sucuri-scanner');
__('A web cache (or HTTP cache) is an information technology for the temporary storage (caching) of web documents, such as HTML pages and images, to reduce bandwidth usage, server load, and perceived lag. A web cache system stores copies of documents passing through it; subsequent requests may be satisfied from the cache if certain conditions are met. A web cache system can refer either to an appliance, or to a computer program. &mdash; <a href="https://en.wikipedia.org/wiki/Web_cache" target="_blank" rel="noopener">WikiPedia - Web Cache</a>', 'sucuri-scanner');

// firewall-ipaccess.html.tpl
__('Delete', 'sucuri-scanner');
__('Submit', 'sucuri-scanner');
__('Loading...', 'sucuri-scanner');
__('IP Address Access', 'sucuri-scanner');
__('This tool allows you to whitelist and blacklist one or more IP addresses from accessing your website. You can also configure the plugin to automatically blacklist any IP address involved in a password guessing brute-force attack. If a legitimate user fails to submit the correct credentials of their account they will have to log into the Firewall dashboard in order to delete their IP address from the blacklist, or try to login once again through a VPN.', 'sucuri-scanner');
__('Blacklist IP:', 'sucuri-scanner');
__('e.g. 192.168.1.54', 'sucuri-scanner');
__('IP Address', 'sucuri-scanner');

// firewall-settings.html.tpl
__('Firewall Settings', 'sucuri-scanner');
__('A powerful Web Application Firewall and <b>Intrusion Detection System</b> for any WordPress user and many other platforms. This page will help you to configure and monitor your site through the <b>Sucuri Firewall</b>. Once enabled, our firewall will act as a shield, protecting your site from attacks and preventing malware infections and reinfections. It will block SQL injection attempts, brute force attacks, XSS, RFI, backdoors and many other threats against your site.', 'sucuri-scanner');
__('Add your <a href="https://waf.sucuri.net/?settings&panel=api" target="_blank" rel="noopener">Firewall API key</a> in the form below to start communicating with the firewall API service.', 'sucuri-scanner');
__('Firewall API Key:', 'sucuri-scanner');
__('Delete', 'sucuri-scanner');
__('Save', 'sucuri-scanner');
__('Name', 'sucuri-scanner');
__('Value', 'sucuri-scanner');
__('<em>[1]</em> More information about the <a href="https://sucuri.net/website-firewall/" target="_blank" rel="noopener">Sucuri Firewall</a>, features and pricing.<br><em>[2]</em> Instructions and videos in the official <a href="https://kb.sucuri.net/firewall" target="_blank" rel="noopener">Knowledge Base</a> site.<br><em>[3]</em> <a href="https://login.sucuri.net/signup2/create?CloudProxy" target="_blank" rel="noopener">Sign up</a> for a new account and start protecting your site.', 'sucuri-scanner');

// firewall.html.tpl
__('Settings', 'sucuri-scanner');
__('Audit Logs', 'sucuri-scanner');
__('IP Access', 'sucuri-scanner');
__('Clear Cache', 'sucuri-scanner');

// integrity-correct.html.tpl
__('WordPress Integrity', 'sucuri-scanner');
__('We inspect your WordPress installation and look for modifications on the core files as provided by WordPress.org. Files located in the root directory, wp-admin and wp-includes will be compared against the files distributed with v%%SUCURI.WordPressVersion%%; all files with inconsistencies will be listed here. Any changes might indicate a hack.', 'sucuri-scanner');
__('All Core WordPress Files Are Correct', 'sucuri-scanner');
__('We have not identified additional files, deleted files, or relevant changes to the core files in your WordPress installation. If you are experiencing other malware issues, please use a <a href="https://sucuri.net/website-security/malware-removal" target="_blank" rel="noopener">Server Side Scanner</a>.', 'sucuri-scanner');
__('Review False Positives', 'sucuri-scanner');

// integrity-diff-utility.html.tpl
__('Loading...', 'sucuri-scanner');
__('Lines with a <b>minus</b> sign as the prefix <em>(here in red)</em> show the original code. Lines with a <b>plus</b> sign as the prefix <em>(here in green)</em> show the modified code. You can read more about the DIFF format from the WikiPedia article about the <a target="_blank" href="https://en.wikipedia.org/wiki/Diff_utility" rel="noopener">Unix Diff Utility</a>.', 'sucuri-scanner');

// integrity-incorrect.html.tpl
__('WordPress Integrity', 'sucuri-scanner');
__('We inspect your WordPress installation and look for modifications on the core files as provided by WordPress.org. Files located in the root directory, wp-admin and wp-includes will be compared against the files distributed with v%%SUCURI.WordPressVersion%%; all files with inconsistencies will be listed here. Any changes might indicate a hack.', 'sucuri-scanner');
__('Core WordPress Files Were Modified', 'sucuri-scanner');
__('We identified that some of your WordPress core files were modified. That might indicate a hack or a broken file on your installation. If you are experiencing other malware issues, please use a <a href="https://sucuri.net/website-security/malware-removal" target="_blank" rel="noopener">Server Side Scanner</a>.', 'sucuri-scanner');
__('Review False Positives', 'sucuri-scanner');
__('WordPress Integrity (%%SUCURI.Integrity.ListCount%%)', 'sucuri-scanner');
__('The Unix Diff Utility is enabled. You can click the files in the table to see the differences detected by the scanner. If you consider the differences to be harmless you can mark the file as fixed, otherwise it is advised to restore the original content immediately.', 'sucuri-scanner');
__('Select All', 'sucuri-scanner');
__('File Size', 'sucuri-scanner');
__('Modified At', 'sucuri-scanner');
__('File Path', 'sucuri-scanner');
__('I understand that this operation cannot be reverted.', 'sucuri-scanner');
__('Action:', 'sucuri-scanner');
__('Mark as Fixed', 'sucuri-scanner');
__('Restore File', 'sucuri-scanner');
__('Delete File', 'sucuri-scanner');
__('Submit', 'sucuri-scanner');
__('Marking one or more files as fixed will force the plugin to ignore them during the next scan, very useful when you find false positives. Additionally you can restore the original content of the core files that appear as modified or deleted, this will tell the plugin to download a copy of the original files from the official WordPress repository. Deleting a file is an irreversible action, be careful.', 'sucuri-scanner');

// integrity-notification.html.tpl
__('We identified that some of your WordPress core files were modified. That might indicate a hack or a broken file on your installation. If you are experiencing other malware issues, please use a <a href="https://sucuri.net/website-security/malware-removal" target="_blank" rel="noopener">Server Side Scanner</a>.', 'sucuri-scanner');
__('WordPress Integrity (%%SUCURI.Integrity.ListCount%%)', 'sucuri-scanner');
__('Status', 'sucuri-scanner');
__('File Size', 'sucuri-scanner');
__('Modified At', 'sucuri-scanner');
__('File Path', 'sucuri-scanner');
__('Marking one or more files as fixed will force the plugin to ignore them during the next scan, very useful when you find false positives. Additionally you can restore the original content of the core files that appear as modified or deleted, this will tell the plugin to download a copy of the original files from the official WordPress repository. Deleting a file is an irreversible action, be careful.', 'sucuri-scanner');

// integrity.html.tpl
__('WordPress Integrity', 'sucuri-scanner');
__('We inspect your WordPress installation and look for modifications on the core files as provided by WordPress.org. Files located in the root directory, wp-admin and wp-includes will be compared against the files distributed with v%%SUCURI.WordPressVersion%%; all files with inconsistencies will be listed here. Any changes might indicate a hack.', 'sucuri-scanner');
__('Loading...', 'sucuri-scanner');

// lastlogins.html.tpl
__('All Users', 'sucuri-scanner');
__('Admins', 'sucuri-scanner');
__('Logged-in Users', 'sucuri-scanner');
__('Failed logins', 'sucuri-scanner');

// lastlogins-admins.html.tpl
__('Successful Logins (admins)', 'sucuri-scanner');
__('Here you can see a list of all the successful logins of accounts with admin privileges.', 'sucuri-scanner');
__('Username', 'sucuri-scanner');
__('Registration', 'sucuri-scanner');
__('Newest To Oldest', 'sucuri-scanner');

// lastlogins-admins.snippet.tpl
__('no data available', 'sucuri-scanner');
__('IP Address', 'sucuri-scanner');
__('Date/Time', 'sucuri-scanner');
__('Edit', 'sucuri-scanner');

// lastlogins-all.html.tpl
__('Successful Logins (all)', 'sucuri-scanner');
__('Here you can see a list of all the successful user logins.', 'sucuri-scanner');
__('Username', 'sucuri-scanner');
__('IP Address', 'sucuri-scanner');
__('Hostname', 'sucuri-scanner');
__('Date/Time', 'sucuri-scanner');
__('no data available', 'sucuri-scanner');

// lastlogins-all.snippet.tpl
__('Edit', 'sucuri-scanner');

// lastlogins-failedlogins.html.tpl
__('Failed logins', 'sucuri-scanner');
__('This information will be used to determine if your site is being victim of <a href="https://kb.sucuri.net/definitions/attacks/brute-force/password-guessing" target="_blank" rel="noopener">Password Guessing Brute Force Attacks</a>. These logs will be accumulated and the plugin will send a report via email if there are more than <code>%%SUCURI.FailedLogins.MaxFailedLogins%%</code> failed login attempts during the same hour, you can change this number from <a href="%%SUCURI.URL.Settings%%#alerts">here</a>. <b>NOTE:</b> Some <em>"Two-Factor Authentication"</em> plugins do not follow the same rules that WordPress have to report failed login attempts, so you may not see all the attempts in this panel if you have one of these plugins installed.', 'sucuri-scanner');
__('Username', 'sucuri-scanner');
__('IP Address', 'sucuri-scanner');
__('Date/Time', 'sucuri-scanner');
__('Web Browser', 'sucuri-scanner');
__('no data available', 'sucuri-scanner');
__('Block', 'sucuri-scanner');

// lastlogins-loggedin.html.tpl
__('Logged-in Users}', 'sucuri-scanner');
__('Here you can see a list of the users that are currently logged-in.', 'sucuri-scanner');
__('ID', 'sucuri-scanner');
__('Username', 'sucuri-scanner');
__('Last Activity', 'sucuri-scanner');
__('Registered', 'sucuri-scanner');
__('IP Address', 'sucuri-scanner');

// lastlogins-loggedin.snippet.tpl
__('Edit', 'sucuri-scanner');
__('Website:', 'sucuri-scanner');
__('IP Address:', 'sucuri-scanner');
__('Reverse IP:', 'sucuri-scanner');
__('Date/Time:', 'sucuri-scanner');
__('Message:', 'sucuri-scanner');

// register-site.html.tpl
__('An API key is required to activate some additional tools available in this plugin. The keys are free and you can virtually generate an unlimited number of them as long as the domain name and email address are unique. The key is used to authenticate the HTTP requests sent by the plugin to an API service managed by Sucuri Inc.', 'sucuri-scanner');
__('If you experience issues generating the API key you can request one by sending the domain name and email address that you want to use to <a href="mailto:info@sucuri.net">info@sucuri.net</a>. Note that generating a key for a website that is not facing the Internet is not possible because the API service needs to validate that the domain name exists.', 'sucuri-scanner');
__('Website:', 'sucuri-scanner');
__('E-mail:', 'sucuri-scanner');
__('DNS Lookups', 'sucuri-scanner');
__('Check the box if your website is behind a known firewall service, this guarantees that the IP address of your visitors will be detected correctly for the security logs. You can change this later from the settings.', 'sucuri-scanner');
__('Enable DNS Lookups On Startup', 'sucuri-scanner');
__('I agree to the <a target="_blank" href="https://sucuri.net/terms">Terms of Service</a>.', 'sucuri-scanner');
__('I have read and understand the <a target="_blank" href="https://sucuri.net/privacy">Privacy Policy</a>.', 'sucuri-scanner');
__('Submit', 'sucuri-scanner');

// settings-alerts-bruteforce.html.tpl
__('Password Guessing Brute Force Attacks', 'sucuri-scanner');
__('<a href="https://kb.sucuri.net/definitions/attacks/brute-force/password-guessing" target="_blank" rel="noopener">Password guessing brute force attacks</a> are very common against web sites and web servers. They are one of the most common vectors used to compromise web sites. The process is very simple and the attackers basically try multiple combinations of usernames and passwords until they find one that works. Once they get in, they can compromise the web site with malware, spam , phishing or anything else they want.', 'sucuri-scanner');
__('Consider Brute-Force Attack After:', 'sucuri-scanner');
__('Submit', 'sucuri-scanner');

// settings-alerts-events.html.tpl
__('Security Alerts', 'sucuri-scanner');
__('You have installed a plugin or theme that is not fully compatible with our plugin, some of the security alerts (like the successful and failed logins) will not be sent to you. To prevent an infinite loop while detecting these changes in the website and sending the email alerts via a custom SMTP plugin, we have decided to stop any attempt to send the emails to prevent fatal errors.', 'sucuri-scanner');
__('Select All', 'sucuri-scanner');
__('Event', 'sucuri-scanner');
__('Submit', 'sucuri-scanner');

// settings-alerts-ignore-posts.html.tpl
__('Post-Type Alerts', 'sucuri-scanner');
__('It seems that you disabled the email alerts for <b>new site content</b>, this panel is intended to provide a way to ignore specific events in your site and with that the alerts reported to your email. Since you have deactivated the <b>new site content</b> alerts, this panel will be disabled too.', 'sucuri-scanner');
__('This is a list of registered <a href="https://codex.wordpress.org/Post_Types" target="_blank" rel="noopener">Post Types</a>. You will receive an email alert when a custom page or post associated to any of these types is created or updated. If you don’t want to receive one or more of these alerts, feel free to uncheck the boxes in the table below. If you are receiving alerts for post types that are not listed in this table, it may be because there is an add-on that that is generating a custom post-type on runtime, you will have to find out by yourself what is the unique ID of that post-type and type it in the form below. The plugin will do its best to ignore these alerts as long as the unique ID is valid.', 'sucuri-scanner');
__('Stop Alerts For This Post-Type:', 'sucuri-scanner');
__('e.g. unique_post_type_id', 'sucuri-scanner');
__('Submit', 'sucuri-scanner');
__('Show Post-Types Table', 'sucuri-scanner');
__('Hide Post-Types Table', 'sucuri-scanner');
__('Select All', 'sucuri-scanner');
__('Post Type', 'sucuri-scanner');
__('Post Type ID', 'sucuri-scanner');
__('Ignored At (optional)', 'sucuri-scanner');

// settings-alerts-perhour.html.tpl
__('Alerts Per Hour', 'sucuri-scanner');
__('Configure the maximum number of email alerts per hour. If the number is exceeded and the plugin detects more events during the same hour, it will still log the events into the audit logs but will not send the email alerts. Be careful with this as you will miss important information.', 'sucuri-scanner');
__('Maximum Alerts Per Hour:', 'sucuri-scanner');
__('Submit', 'sucuri-scanner');

// settings-alerts-recipients.html.tpl
__('Alerts Recipient', 'sucuri-scanner');
__('By default, the plugin will send the email alerts to the primary admin account, the same account created during the installation of WordPress in your web server. You can add more people to the list, they will receive a copy of the same security alerts.', 'sucuri-scanner');
__('E-mail:', 'sucuri-scanner');
__('e.g. user@example.com', 'sucuri-scanner');
__('Submit', 'sucuri-scanner');
__('Select All', 'sucuri-scanner');
__('E-mail', 'sucuri-scanner');
__('Delete', 'sucuri-scanner');
__('Test Alerts', 'sucuri-scanner');

// settings-alerts-subject.html.tpl
__('Alert Subject', 'sucuri-scanner');
__('Format of the subject for the email alerts, by default the plugin will use the website name and the event identifier that is being reported, you can use this panel to include the IP address of the user that triggered the event and some additional data. You can create filters in your email client creating a custom email subject using the pseudo-tags shown below.', 'sucuri-scanner');
__('Custom Format', 'sucuri-scanner');
__('Submit', 'sucuri-scanner');

// settings-alerts-trustedips.html.tpl
__('Trusted IP Addresses', 'sucuri-scanner');
__('If you are working in a LAN <em>(Local Area Network)</em> you may want to include the IP addresses of all the nodes in the subnet, this will force the plugin to stop sending email alerts about actions executed from trusted IP addresses. Use the CIDR <em>(Classless Inter Domain Routing)</em> format to specify ranges of IP addresses <em>(only 8, 16, and 24)</em>.', 'sucuri-scanner');
__('IP Address:', 'sucuri-scanner');
__('e.g. 182.120.56.0/24', 'sucuri-scanner');
__('Submit', 'sucuri-scanner');
__('Select All', 'sucuri-scanner');
__('IP Address', 'sucuri-scanner');
__('CIDR Format', 'sucuri-scanner');
__('IP Added At', 'sucuri-scanner');
__('no data available', 'sucuri-scanner');
__('Delete', 'sucuri-scanner');

// settings-apirecovery.html.tpl
__('If this operation was successful you will receive a message in the email used during the registration of the API key <em>(usually the email of the main admin user)</em>. This message contains the key in plain text, copy and paste the key in the form field below. The plugin will verify the authenticity of the key sending an initial HTTP request to the API service, if this fails the key will be removed automatically and you will have to start the process all over again.', 'sucuri-scanner');
__('There are cases where this operation may fail, an example would be when the email address is not associated with the domain anymore, this happens when the base URL changes <em>(from www to none or viceversa)</em>. If you are having issues recovering the key please send an email explaining the situation to <a href="mailto:info@sucuri.net">info@sucuri.net</a>', 'sucuri-scanner');
__('API Key:', 'sucuri-scanner');
__('Submit', 'sucuri-scanner');

// settings-apiregistered.html.tpl
__('Congratulations! The rest of the features available in the plugin have been enabled. This product is designed to supplement existing security products. It’s not a silver bullet for your security needs, but it’ll give you greater security awareness and better posture, all with the intent of reducing risk.', 'sucuri-scanner');
__('Your website has been granted a new API key and it was associated to the email address that you chose during the registration process. You can use the same email to recover the key if you happen to lose it sometime. We encourage you to check the rest of the settings page and configure the plugin to your own needs.', 'sucuri-scanner');
__('Dashboard', 'sucuri-scanner');
__('Settings', 'sucuri-scanner');

// settings-apiservice-checksums.html.tpl
__('WordPress Checksums API', 'sucuri-scanner');
__('The WordPress integrity tool uses a remote API service maintained by the WordPress organization to determine which files in the installation were added, removed or modified. The API returns a list of files with their respective checksums, this information guarantees that the installation is not corrupt. You can, however, point the integrity tool to a GitHub repository in case that you are using a custom version of WordPress like the <a href="https://github.com/WordPress/WordPress" target="_blank" rel="noopener">development version of the code</a>.', 'sucuri-scanner');
__('WordPress Checksums API', 'sucuri-scanner');
__('e.g. URL — or — user/repo', 'sucuri-scanner');
__('Submit', 'sucuri-scanner');

// settings-apiservice-proxy.html.tpl
__('API Communication via Proxy', 'sucuri-scanner');
__('All the HTTP requests used to communicate with the API service are being sent using the WordPress built-in functions, so (almost) all its official features are inherited, this is useful if you need to pass these HTTP requests through a proxy. According to the <a href="https://developer.wordpress.org/reference/classes/wp_http_proxy/" target="_blank" rel="noopener">official documentation</a> you have to add some constants to the main configuration file: <em>WP_PROXY_HOST, WP_PROXY_PORT, WP_PROXY_USERNAME, WP_PROXY_PASSWORD</em>.', 'sucuri-scanner');
__('HTTP Proxy Hostname', 'sucuri-scanner');
__('HTTP Proxy Port num', 'sucuri-scanner');
__('HTTP Proxy Username', 'sucuri-scanner');
__('HTTP Proxy Password', 'sucuri-scanner');

// settings-apiservice-status.html.tpl
__('API Service Communication', 'sucuri-scanner');
__('Once the API key is generate the plugin will communicate with a remote API service that will act as a safe data storage for the audit logs generated when the website triggers certain events that the plugin monitors. If the website is hacked the attacker will not have access to these logs and that way you can investigate what was modified <em>(for malware infaction)</em> and/or how the malicious person was able to gain access to the website.', 'sucuri-scanner');
__('Disabling the API service communication will stop the event monitoring, consider to enable the <a href="%%SUCURI.URL.Settings%%#general">Log Exporter</a> to keep the monitoring working while the HTTP requests are ignored, otherwise an attacker may execute an action that will not be registered in the security logs and you will not have a way to investigate the attack in the future.', 'sucuri-scanner');
__('<strong>Are you a developer?</strong> You may be interested in our API. Feel free to use the URL shown below to access the latest 50 entries in your security log, change the value for the parameter <code>l=N</code> if you need more. Be aware that the API doesn’t provides an offset parameter, so if you have the intention to query specific sections of the log you will need to wrap the HTTP request around your own cache mechanism. We <strong>DO NOT</strong> take feature requests for the API, this is a semi-private service tailored for the specific needs of the plugin and not intended to be used by 3rd-party apps, we may change the behavior of each API endpoint without previous notice, use it at your own risk.', 'sucuri-scanner');

// settings-general-apikey.html.tpl
__('API Key', 'sucuri-scanner');
__('An API key is required to prevent attackers from deleting audit logs that can help you investigate and recover after a hack, and allows the plugin to display statistics. By generating an API key, you agree that Sucuri will collect and store anonymous data about your website. We take your privacy seriously.', 'sucuri-scanner');
__('Your domain <code>%%SUCURI.CleanDomain%%</code> does not seems to have a DNS <code>A</code> record so it will be considered as <em>invalid</em> by the API interface when you request the generation of a new key. Adding <code>www</code> at the beginning of the domain name may fix this issue. If you do not understand what is this then send an email to our support team requesting the key.', 'sucuri-scanner');
__('Recover Via E-mail', 'sucuri-scanner');
__('Manual Activation', 'sucuri-scanner');
__('If you do not have access to the administrator email, you can reinstall the plugin. The API key is generated using an administrator email and the domain of the website. Click the "Manual Activation" button if you already have a valid API key to authenticate this website with the remote API web service.', 'sucuri-scanner');
__('Delete', 'sucuri-scanner');
__('API Key:', 'sucuri-scanner');

// settings-general-datastorage.html.tpl
__('Data Storage', 'sucuri-scanner');
__('This is the directory where the plugin will store the security logs, the list of files marked as fixed in the core integrity tool, the cache for the malware scanner and 3rd-party plugin metadata. The plugin requires write permissions in this directory as well as the files contained in it. If you prefer to keep these files in a non-public directory <em>(one level up the document root)</em> please define a constant in the <em>"wp-config.php"</em> file named <em>"SUCURI_DATA_STORAGE"</em> with the absolute path to the new directory.', 'sucuri-scanner');
__('Select All', 'sucuri-scanner');
__('File Path', 'sucuri-scanner');
__('File Size', 'sucuri-scanner');
__('Status', 'sucuri-scanner');
__('Writable', 'sucuri-scanner');
__('Delete', 'sucuri-scanner');

// settings-general-importexport.html.tpl
__('Import &amp; Export Settings', 'sucuri-scanner');
__('Copy the JSON-encoded data from the box below, go to your other websites and click the <em>"Import"</em> button in the settings page. The plugin will start using the same settings from this website. Notice that some options are omitted as they contain values specific to this website. To import the settings from another website into this one, replace the JSON-encoded data in the box below with the JSON-encoded data exported from the other website, then click the button <em>"Import"</em>. Notice that some options will not be imported to reduce the security risk of writing arbitrary data into the disk.', 'sucuri-scanner');
__('I understand that this operation cannot be reverted.', 'sucuri-scanner');
__('Submit', 'sucuri-scanner');

// settings-general-ipdiscoverer.html.tpl
__('IP Address Discoverer', 'sucuri-scanner');
__('IP address discoverer will use DNS lookups to automatically detect if the website is behind the <a href="https://sucuri.net/website-firewall/" target="_blank" rel="noopener">Sucuri Firewall</a>, in which case it will modify the global server variable <em>Remote-Addr</em> to set the real IP of the website’s visitors. This check runs on every WordPress init action and that is why it may slow down your website as some hosting providers rely on slow DNS servers which makes the operation take more time than it should.', 'sucuri-scanner');
__('HTTP Header:', 'sucuri-scanner');
__('Proceed', 'sucuri-scanner');
__('Sucuri Firewall', 'sucuri-scanner');
__('Website:', 'sucuri-scanner');
__('Top Level Domain:', 'sucuri-scanner');
__('Hostname:', 'sucuri-scanner');
__('IP Address (Hostname):', 'sucuri-scanner');
__('IP Address (Username):', 'sucuri-scanner');

// settings-general-resetoptions.html.tpl
__('Reset Security Logs, Hardening and Settings', 'sucuri-scanner');
__('This action will trigger the deactivation / uninstallation process of the plugin. All local security logs, hardening and settings will be deleted. Notice that the security logs stored in the API service will not be deleted, this is to prevent tampering from a malicious user. You can request a new API key if you want to start from scratch.', 'sucuri-scanner');
__('I understand that this operation cannot be reverted.', 'sucuri-scanner');
__('Submit', 'sucuri-scanner');

// settings-general-reverseproxy.html.tpl
__('Reverse Proxy', 'sucuri-scanner');
__('The event monitor uses the API address of the origin of the request to track the actions. The plugin uses two methods to retrieve this: the main method uses the global server variable <em>Remote-Addr</em> available in most modern web servers, and an alternative method uses custom HTTP headers <em>(which are unsafe by default)</em>. You should not worry about this option unless you know what a reverse proxy is. Services like the <a href="https://sucuri.net/website-firewall/" target="_blank" rel="noopener">Sucuri Firewall</a> &mdash; once active &mdash; force the network traffic to pass through them to filter any security threat that may affect the original server. A side effect of this is that the real IP address is no longer available in the global server variable <em>Remote-Addr</em> but in a custom HTTP header with a name provided by the service.', 'sucuri-scanner');

// settings-general-selfhosting.html.tpl
__('Log Exporter', 'sucuri-scanner');
__('This option allows you to export the WordPress audit logs to a local log file that can be read by a SIEM or any log analysis software <em>(we recommend OSSEC)</em>. That will give visibility from within WordPress to complement your log monitoring infrastructure. <b>NOTE:</b> Do not use a publicly accessible file, you must use a file at least one level up the document root to prevent leaks of information.', 'sucuri-scanner');
__('File Path:', 'sucuri-scanner');
__('Submit', 'sucuri-scanner');

// settings-general-timezone.html.tpl
__('Timezone Override', 'sucuri-scanner');
__('This option defines the timezone that will be used through out the entire plugin to print the dates and times whenever is necessary. This option also affects the date and time of the logs visible in the audit logs panel which is data that comes from a remote server configured to use Eastern Daylight Time (EDT). WordPress offers an option in the general settings page to allow you to configure the timezone for the entire website, however, if you are experiencing problems with the time in the audit logs, this option will help you fix them.', 'sucuri-scanner');
__('Timezone:', 'sucuri-scanner');
__('Submit', 'sucuri-scanner');

// settings-hardening-whitelist-phpfiles.html.tpl
__('Whitelist Blocked PHP Files', 'sucuri-scanner');
__('After you apply the hardening in either the includes, content, and/or uploads directories, the plugin will add a rule in the access control file to deny access to any PHP file located in these folders. This is a good precaution in case an attacker is able to upload a shell script. With a few exceptions the <em>"index.php"</em> file is the only one that should be publicly accessible, however many theme/plugin developers decide to use these folders to process some operations. In this case applying the hardening <strong>may break</strong> their functionality.', 'sucuri-scanner');
__('File Path:', 'sucuri-scanner');
__('Submit', 'sucuri-scanner');
__('Select All', 'sucuri-scanner');
__('File Path', 'sucuri-scanner');
__('Directory', 'sucuri-scanner');
__('Pattern', 'sucuri-scanner');
__('no data available', 'sucuri-scanner');
__('Delete', 'sucuri-scanner');

// settings-posthack-available-updates-alert.html.tpl
__('WordPress has a big user base in the public Internet, which brings interest to attackers to find vulnerabilities in the code, 3rd-party extensions, and themes that other companies develop. You should keep every piece of code installed in your website updated to prevent attacks as soon as disclosed vulnerabilities are patched.', 'sucuri-scanner');
__('Name', 'sucuri-scanner');
__('Version', 'sucuri-scanner');
__('Update', 'sucuri-scanner');
__('Tested With', 'sucuri-scanner');

// settings-posthack-available-updates.html.tpl
__('Available Plugin and Theme Updates', 'sucuri-scanner');
__('WordPress has a big user base in the public Internet, which brings interest to attackers to find vulnerabilities in the code, 3rd-party extensions, and themes that other companies develop. You should keep every piece of code installed in your website updated to prevent attacks as soon as disclosed vulnerabilities are patched.', 'sucuri-scanner');
__('Name', 'sucuri-scanner');
__('Version', 'sucuri-scanner');
__('Update', 'sucuri-scanner');
__('Tested With', 'sucuri-scanner');
__('Loading...', 'sucuri-scanner');

// settings-posthack-available-updates.snippet.tpl
__('Download', 'sucuri-scanner');

// settings-posthack-reset-password-alert.html.tpl
__('WordPress has generated a new (random) password for your account <b>%%SUCURI.ResetPassword.UserName%%</b> at <a target="_blank" href="http://%%SUCURI.ResetPassword.Website%%" rel="noopener">%%SUCURI.ResetPassword.Website%%</a>. The change has been requested by one of the admins in this website for security reasons. Your new password is &mdash; <span style="font-family:Menlo, Monaco, monospace, serif;font-weight:700">%%%SUCURI.ResetPassword.Password%%%</span> &mdash; please change it as soon as possible.', 'sucuri-scanner');

// settings-posthack-reset-password.html.tpl
__('Reset User Password', 'sucuri-scanner');
__('Loading...', 'sucuri-scanner');
__('You can generate a new random password for the user accounts that you select from the list. An email with the new password will be sent to the email address of each chosen user. If you choose to change the password of your own user, then your current session will expire immediately. You will need to log back into the admin panel with the new password that will be sent to your email.', 'sucuri-scanner');
__('Select All', 'sucuri-scanner');
__('Username', 'sucuri-scanner');
__('E-mail', 'sucuri-scanner');
__('Registered', 'sucuri-scanner');
__('Roles', 'sucuri-scanner');
__('Submit', 'sucuri-scanner');

// settings-posthack-reset-plugins.html.tpl
__('Reset Installed Plugins', 'sucuri-scanner');
__('Loading...', 'sucuri-scanner');
__('In case you suspect having an infection in your site, or after you got rid of a malicious code, it’s recommended to reinstall all the plugins installed in your site, including the ones you are not using. Notice that premium plugins will not be automatically reinstalled to prevent backward compatibility issues and problems with licenses.', 'sucuri-scanner');
__('The information shown here is cached for %%SUCURI.ResetPlugin.CacheLifeTime%% seconds. This is necessary to reduce the quantity of HTTP requests sent to the WordPress servers and the bandwidth of your site. Currently there is no option to recreate this cache.', 'sucuri-scanner');
__('<b>WARNING!</b> This procedure can break your website. The reset will not affect the database nor the settings of each plugin, but depending on how they were written the reset action might break them. Be sure to create a backup of the plugins directory before the execution of this tool.', 'sucuri-scanner');
__('Select All', 'sucuri-scanner');
__('Name', 'sucuri-scanner');
__('Version', 'sucuri-scanner');
__('Type', 'sucuri-scanner');
__('Status', 'sucuri-scanner');
__('Submit', 'sucuri-scanner');

// settings-posthack-security-keys.html.tpl
__('Update Secret Keys', 'sucuri-scanner');
__('The secret or security keys are a list of constants added to your site to ensure better encryption of information stored in the user’s cookies. A secret key makes your site harder to hack by adding random elements to the password. You do not have to remember the keys, just write a random, complicated, and long string in the <code>wp-config.php</code> file. You can change these keys at any point in time. Changing them will invalidate all existing cookies, forcing all logged in users to login again.', 'sucuri-scanner');
__('Your current session will expire once the form is submitted.', 'sucuri-scanner');
__('Status', 'sucuri-scanner');
__('Name', 'sucuri-scanner');
__('Value', 'sucuri-scanner');
__('I understand that this operation cannot be reverted.', 'sucuri-scanner');
__('Generate New Security Keys', 'sucuri-scanner');

// settings-scanner-cronjobs.html.tpl
__('Scheduled Tasks', 'sucuri-scanner');
__('The plugin scans your entire website looking for changes which are later reported via the API in the audit logs page. By default the scanner runs daily but you can change the frequency to meet your requirements. Notice that scanning your project files too frequently may affect the performance of your website. Be sure to have enough server resources before changing this option. The memory limit and maximum execution time are two of the PHP options that your server will set to stop your website from consuming too much resources.', 'sucuri-scanner');
__('The scanner uses the <a href="http://php.net/manual/en/class.splfileobject.php" target="_blank" rel="noopener">PHP SPL library</a> and the <a target="_blank" href="http://php.net/manual/en/class.filesystemiterator.php" rel="noopener">Filesystem Iterator</a> class to scan the directory tree where your website is located in the server. This library is only available on PHP 5 >= 5.3.0 &mdash; OR &mdash; PHP 7; if you have an older version of PHP the plugin will not work as expected. Please ask your hosting provider to advise you on this matter.', 'sucuri-scanner');
__('Scheduled tasks are rules registered in your database by a plugin, theme, or the base system itself; they are used to automatically execute actions defined in the code every certain amount of time. A good use of these rules is to generate backup files of your site, execute a security scanner, or remove unused elements like drafts. <b>Note:</b> Scheduled tasks can be re-installed by any plugin/theme automatically.', 'sucuri-scanner');
__('Select All', 'sucuri-scanner');
__('Name', 'sucuri-scanner');
__('Schedule', 'sucuri-scanner');
__('Next Due', 'sucuri-scanner');
__('Arguments', 'sucuri-scanner');
__('Loading...', 'sucuri-scanner');
__('Action:', 'sucuri-scanner');
__('Submit', 'sucuri-scanner');

// settings-scanner-ignore-folders.html.tpl
__('Ignore Files And Folders During The Scans', 'sucuri-scanner');
__('Use this tool to select the files and/or folders that are too heavy for the scanner to process. These are usually folders with images, media files like videos and audios, backups and &mdash; in general &mdash; anything that is not code-related. Ignoring these files or folders will reduce the memory consumption of the PHP script.', 'sucuri-scanner');
__('Ignore a file or directory:', 'sucuri-scanner');
__('e.g. /private/directory/', 'sucuri-scanner');
__('Submit', 'sucuri-scanner');
__('Select All', 'sucuri-scanner');
__('File Path', 'sucuri-scanner');
__('Status', 'sucuri-scanner');
__('Unignore Selected Directories', 'sucuri-scanner');

// settings-scanner-integrity-cache.html.tpl
__('WordPress Integrity (False Positives)', 'sucuri-scanner');
__('Since the scanner doesn’t read the files during the execution of the integrity check, it is possible to find false positives. Files listed here have been marked as false positives and will be ignored by the scanner in subsequent scans.', 'sucuri-scanner');
__('Select All', 'sucuri-scanner');
__('Reason', 'sucuri-scanner');
__('Ignored At', 'sucuri-scanner');
__('File Path', 'sucuri-scanner');
__('no data available', 'sucuri-scanner');
__('Stop Ignoring the Selected Files', 'sucuri-scanner');

// settings-scanner-integrity-diff-utility.html.tpl
__('WordPress Integrity Diff Utility', 'sucuri-scanner');
__('If your server allows the execution of system commands, you can configure the plugin to use the <a href="https://en.wikipedia.org/wiki/Diff_utility" target="_blank" rel="noopener">Unix Diff Utility</a> to compare the actual content of the file installed in the website and the original file provided by WordPress. This will show the differences between both files and then you can act upon the information provided.', 'sucuri-scanner');
__('WordPress Integrity Diff Utility', 'sucuri-scanner');

// settings-webinfo-details.html.tpl
__('Environment Variables', 'sucuri-scanner');

// settings-webinfo-htaccess.html.tpl
__('Access File Integrity', 'sucuri-scanner');
__('The <code>.htaccess</code> file is a distributed configuration file, and is how the Apache web server handles configuration changes on a per-directory basis. WordPress uses this file to manipulate how Apache serves files from its root directory and subdirectories thereof; most notably, it modifies this file to be able to handle pretty permalinks.', 'sucuri-scanner');
__('Htaccess file found in', 'sucuri-scanner');
__('Your website has no <code>.htaccess</code> file or it was not found in the default location.', 'sucuri-scanner');
__('The main <code>.htaccess</code> file in your site has the standard rules for a WordPress installation. You can customize it to improve the performance and change the behaviour of the redirections for pages and posts in your site. To get more information visit the official documentation at <a target="_blank" rel="noopener" href="https://codex.wordpress.org/Using_Permalinks#Creating_and_editing_.28.htaccess.29"> Codex WordPress - Creating and editing (.htaccess)</a>', 'sucuri-scanner');
__('Codex WordPress HTAccess', 'sucuri-scanner');

// settings.html.tpl
__('General Settings', 'sucuri-scanner');
__('Scanner', 'sucuri-scanner');
__('Hardening', 'sucuri-scanner');
__('Post-Hack', 'sucuri-scanner');
__('Alerts', 'sucuri-scanner');
__('API Service Communication', 'sucuri-scanner');
__('Website Info', 'sucuri-scanner');
__('Hardening Options', 'sucuri-scanner');

// sitecheck-details.html.tpl
__('This information will be updated %%SUCURI.SiteCheck.Lifetime%%', 'sucuri-scanner');
__('Refresh Malware Scan', 'sucuri-scanner');

// sitecheck-malware.html.tpl
__('No malicious JavaScript', 'sucuri-scanner');
__('No malicious iFrames', 'sucuri-scanner');
__('No suspicious redirections', 'sucuri-scanner');
__('No blackhat SEO spam', 'sucuri-scanner');
__('No anomaly detection', 'sucuri-scanner');
__('Some types of problems cannot be detected by this scanner. If this scanner did not detect any issue and you still suspect a problem exists, you can <a href="https://sucuri.net/website-security-platform/signup" target="_blank" rel="noopener">sign up with Sucuri</a> for a complete and in-depth scan + cleanup (not included in the free checks).', 'sucuri-scanner');

// sitecheck-malware.snippet.tpl
__('Hover to see the Payload', 'sucuri-scanner');

// sitecheck-recommendations.html.tpl
__('Recommendations', 'sucuri-scanner');

// sitecheck-target.html.tpl
__('Malware Scan Target', 'sucuri-scanner');
__('The remote malware scanner provided by the plugin is powered by <a href="https://sitecheck.sucuri.net/" target="_blank" rel="noopener">Sucuri SiteCheck</a>, a service that takes a publicly accessible URL and scans it for malicious code. If your website is not visible to the Internet, for example, if it is hosted in a local development environment or a restricted network, the scanner will not be able to work on it. Additionally, if the website was installed in a non-standard directory the scanner will report a "404 Not Found" error. You can use this option to change the URL that will be scanned.', 'sucuri-scanner');
__('Malware Scan Target', 'sucuri-scanner');
__('Malware Scan Target:', 'sucuri-scanner');
__('Submit', 'sucuri-scanner');

// wordpress-recommendations.html.tpl
__('WordPress Security Recommendations', 'sucuri-scanner');