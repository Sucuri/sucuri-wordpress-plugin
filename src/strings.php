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
__('This tool allows you to whitleist and blacklist one or more IP addresses from accessing your website. You can also configure the plugin to automatically blacklist any IP address involved in a password guessing brute-force attack. If a legitimate user fails to submit the correct credentials of their account they will have to log into the Firewall dashboard in order to delete their IP address from the blacklist, or try to login once again through a VPN.', 'sucuri-scanner');
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
__('The Unix Diff Utility is enabled. You can click the files in the table to see the differences detected by the scanner. If you consider the differences to be harmless you can mark the file as fixed, otherwise it is adviced to restore the original content immediately.', 'sucuri-scanner');
__('Select All', 'sucuri-scanner');
__('File Size', 'sucuri-scanner');
__('Modified At', 'sucuri-scanner');
__('File Path', 'sucuri-scanner');
__('I understand that this operation can not be reverted.', 'sucuri-scanner');
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

// settings.html.tpl
__('General', 'sucuri-scanner');
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
__('If our free scanner did not detect any issue, you may have a more complicated and hidden problem. You can <a href="https://sucuri.net/website-security-platform/signup" target="_blank" rel="noopener">sign up with Sucuri</a> for a complete and in-depth scan + cleanup (not included in the free checks).', 'sucuri-scanner');

// sitecheck-malware.snippet.tpl
__('Hover to see the Payload', 'sucuri-scanner');

// sitecheck-recommendations.html.tpl
__('Recomendations', 'sucuri-scanner');

// sitecheck-target.html.tpl
__('Malware Scan Target', 'sucuri-scanner');
__('The remote malware scanner provided by the plugin is powered by <a href="https://sitecheck.sucuri.net/" target="_blank" rel="noopener">Sucuri SiteCheck</a>, a service that takes a publicly accessible URL and scans it for malicious code. If your website is not visible to the Internet, for example, if it is hosted in a local development environment or a restricted network, the scanner will not be able to work on it. Additionally, if the website was installed in a non-standard directory the scanner will report a "404 Not Found" error. You can use this option to change the URL that will be scanned.', 'sucuri-scanner');
__('Malware Scan Target', 'sucuri-scanner');
__('Malware Scan Target:', 'sucuri-scanner');
__('Submit', 'sucuri-scanner');
