=== Sucuri Security - Auditing, Malware Scanner and Security Hardening ===
Contributors: wordpress@sucuri.net
Donate Link: https://sucuri.net/
Tags: malware, security, firewall, scan, spam, virus, sucuri, protection, blocklist, detection, hardening, file integrity
Requires at least: 3.6
Tested up to: 6.0
Stable tag: 1.8.36

The Sucuri WordPress Security plugin is a security toolset for security integrity monitoring, malware detection and security hardening.

== Description ==

Sucuri Inc. is a globally recognized authority in all matters related to website security, with specialization in WordPress Security.

The Sucuri Security WordPress plugin is free to all WordPress users. It is a security suite meant to complement your existing security posture. Currently the ownership of this plugin was transferred to GoDaddy.
It offers its users a set of security features for their website, each designed to have a positive effect on their security posture:

* Security Activity Auditing
* File Integrity Monitoring
* Remote Malware Scanning
* Blocklist Monitoring
* Effective Security Hardening
* Post-Hack Security Actions
* Security Notifications
* Website Firewall (premium)

== Installation  ==

The installation of the Sucuri WordPress Security plugin is very simple and straight forward. A detailed breakdown of the process is available [here](https://sucuri.net/wordpress-security-plugin-installation) (including images), however, below we outline the bare minimum steps.

To install Sucuri Security and complement your Security posture:

1. Log into your WordPress administration panel,
2. In the sidebar, choose "Plugins" and then "Add New",
3. Type "sucuri" or "sucuri-scanner" in the search box,
4. Install the option with the "By Sucuri Inc." at the foot,
5. Once activated, you will find a new icon in the sidebar with the Sucuri logo. Go to the plugin's dashboard and click the button that says "Generate API Key" to activate the event monitoring, this will generate a unique key to authenticate your website against the remote Sucuri WordPress API service,
6. Feel free to visit the plugin' settings page to configure other options including the security alerts, hardening options, file system scanner paths and API service communication.

Visit the [Support Forum](https://wordpress.org/support/plugin/sucuri-scanner) to ask questions, suggest new features, or report bugs. And recommend the plugin to your friends and colleagues if you think it can help them.

== Frequently Asked Questions ==

More information on the Sucuri Security WordPress plugin can be found in our [Knowledge Base](https://kb.sucuri.net/plugins/WordPress+Plugin).

= What is the security activity auditing? =

This is perhaps the most underutilized security function. It’s the act of monitoring all security related events within your WordPress install. The challenge is, what makes up a security event. In the eyes of Sucuri, any change that occurs within the application could be categorized as a security event, and as such we try to record it.

This is important because it gives you, the website owner, the ability to keep a good eye on the various changes occurring within your environment. Who is logging in? What changes are being made?

This feature is logging all activity to the Sucuri cloud, for safe keeping. This ensures that an attacker is not able to wipe your forensic data and prevent further security analysis after a compromise. If an attacker is able to bypass your security controls, your security logs will be kept safe within the Sucuri Security Operations Center (SOC).

This feature is particularly important to website / system administrators and security experts looking to understand what is going on with their website and when it’s happening.

= What is the file integrity monitoring =

Security File Integrity Monitoring has been fundamental to the world of security. It’s the act of comparing a known good with the current state. If the current state differs from the known good, you know you have a problem. This is the basis of a lot of host intrusion detection systems. It’s what we have built into the plugin.

It will create a known good the minute the plugin is installed. This will be of all the directories at the root of the install, this includes plugins, themes and core files.

= What is the remote malware scanning? =

This feature is powered by our scanning engine, found on our free security scanner - [SiteCheck](https://sitecheck.sucuri.net/). It’s important to take some time to [understand how this scanner works](https://blog.sucuri.net/2012/10/ask-sucuri-how-does-sitecheck-work.html). There are limitations with the way this scanner works, you can find more information in the FAQ section.

= What is the blocklist monitoring? =

Another very interesting feature of the Security Malware Scanner is that it checks various blocklist engines, including the following:

* Sucuri Labs
* Google Safe Browsing
* Norton
* AVG
* Phish Tank
* ESET
* McAfee Site Advisor
* Yandex
* SpamHaus
* Bitdefender

These are some of the largest blocklisting entities, each having the ability to directly impact your brand's online reputation. By synchronizing with their environments we’re able to tell you, upon scan, whether any of them are negatively flagging your website with a security related issue. If they do, then via our website security product, we’re able to help you get off of the security blocklist.

= What is effective security hardening =

It’s easy to get lost in the world of security hardening. At Sucuri we clean hundreds of websites a day, many with the various security hardening configurations you find in various WordPress Security presentations. In this section, we add those that we feel to be most effective, and that complement the entire Sucuri suite of products.

= What are the post-hack security actions =

Regardless of how good your security posture is, sometimes it’s impossible to prevent the inevitable. When this happens, we’ve included a section to help you walk through the three key things you should do after a compromise.

= What are the security notifications =

Having all these security features would be useless unless you were notified of the issues. This is why we have made available security alerts. We have also expanded the various security related events, to provide website owners more flexibility in regards to what they want to know about. As a website owner, you have the option to make these security alerts as quiet or noisy as you would like.

= What is the website firewall (premium) =

This is by far the coolest security feature Sucuri has to offer everyday website owners. It’s an enterprise grade Website Firewall designed to give you the best security protection any website can hope for. It protects your website from a variety of website attacks, including:

* Denial of Service (DOS / DDOS) Attacks
* Exploitation of Software Vulnerabilities
* Zero Day Disclosure Patches
* Brute Force Attacks against your Access Control Mechanisms

This is coupled with a number of features like:

* Performance Optimization
* Advanced Access Control Features
* Failover and Redundancy

This is **not included as a free** option of the plugin, but is integrated so that if purchased you are able to activate.  If you prefer to leverage the Sucuri Firewall product by itself, you have the option to operate the [Website Firewall WordPress Security](https://wordpress.org/plugins/sucuri-cloudproxy-waf/) plugin in standalone mode.

The Sucuri WordPress Security plugin is built by the team that is known for their proactive approach to security. It is built using intelligence gathered from thousands upon thousands of remediation cases, millions of unique domain scans and 10’s of millions of website security attack blocks.

= What does this plugin do that other security plugins don't do? =

A few other security plugins provide activity monitoring features, but few do them well. The activity monitoring in this plugin is second to none, tying the activity into the Sucuri Security Operations Center (SOC) ensuring its safe keeping.

This security plugin also takes a different approach to security plugins, stripping it of what we categorize as unnecessary features for a basic website end-user. We've narrowed the key features we felt were most pertinent to any website owner and integrated them into this plugin.

= If I install the Sucuri Security plugin do I get a Sucuri account? =

No, this is a free plugin that we offer at no charge. It does not mean you get a free account.

= If I have the premium plugin, do I need the free plugin? =

The premium plugin was deprecated back in 2014. All the major features were merged into the free plugin. If you are still using the (old) premium plugin please consider deleting it and installing the (new) free plugin from the WordPress plugin market. Notice that you will need to generate a new API key as the new API service does not supports the old one.

= Do I still need Sucuri’s products if I have this plugin? =

Yes. This plugin compliments your existing security toolsets. It is not designed to replace the Sucuri Website Security or Firewall products.

= Where do I get support for this plugin? =

The best place is to engage us via the [Support Forum](https://wordpress.org/support/plugin/sucuri-scanner). If you are a client, you can submit a ticket [here](https://support.sucuri.net/support/?new).

= Does your plugin conflict with WordFence? =

The plugin does not, but there might be issues with our scanners. If you get an “Unable to Properly Scan Your Site” error, it’s likely because the WordFence plugin is blocking our scanner as an invalid crawler. You would have to white list our IP address on the WordFence dashboard.

= What are the Remote Malware Scanner limitations? =

Because the security malware scanner is remote, it is unable to see things that are on the server but that are not displaying on the browser. If you are interested in this, we encourage you to subscribe to our website security product. This issues includes things like Phishing pages, Backdoors, Mailer Scripts, etc.

= Your plugin didn’t detect this malware? =

This happens, reference the remote scanner limitations above. This should not be confused with our website security product. If you have malware, and you are a client, submit a ticket so that [we can help you get clean](https://support.sucuri.net/support/?new&mremoval).

If you are not a client, and you want to share what you have found please send it to [labs@sucuri.net](mailto:labs@sucuri.net).

The plugin is not performing application level malware / security scanning so this is not uncommon.

= Is it free to enable the Website Firewall option? =

No, it is not. To enable you must subscribe to the [Website Firewall](https://sucuri.net/website-firewall-signup) service.

= Will this plugin impact the performance of my website? =

We improve the performance of the code with every release. However, due to differences between hosting providers there are cases where the plugin may affect the responsiveness of the website upon installation. Things like HTTP requests, SSL certificate verifications and DNS lookups are among the few things that, depending on how your web server is configured, will slow down your website.

= Do the logs get stored to my database? =

No, they do not.

= Are there any issues installing your plugin with any hosts? =

Not that we are aware of.

= Do I need this plugin to use the Website Firewall service? =

No, it is not required. The Website Firewall runs in the cloud without the need of anything installed. This plugin only helps see and manage the service from the WordPress dashboard.

= What information does Sucuri collect? =

We take your privacy seriously. For free plugin users without an API key, no information is collected by Sucuri. After activating an API key, Sucuri will store some information, such as logs. Please see our [Terms of Service](https://sucuri.net/terms) and [Privacy Policy](https://sucuri.net/privacy). Please email gdpr@sucuri.net if you have other questions about your privacy.

== Screenshots ==

1. WordPress Integrity Tool - Detects added, modified, and removed files.
2. Integrity Diff Utility - Shows differences in the core WordPress files.
3. Audit Logs and Malware Scanner - Reports suspicious events and malicious code.
4. Sucuri Firewall - Settings visibility, audit logs, IP blocklisting, and cache.
5. Website Hardening - Offers multiple options to increase the security of the website.
6. Failed Logins - Shows failed login attempts, successful logins and online users.
7. Post Hack Tools - Offers multiple tools to react after the suspiciousness of a hack.
8. Settings - Offers multiple settings to configure the functionality of the plugin.

== Upgrade Notice ==

= 1.8.19 =
This version adds an option to refresh the malware scan results on demand, as well as several small bug fixes and improvements.

== Contributors & Maintenance Notice ==

= 1.8.30 =
Daniel is no longer maintaining the Sucuri plugin at GoDaddy. We have transferred it to a dedicated team to maintain and improve it.

== Changelog ==
= 1.8.36 =
* Changed Branding fonts, colors and images to match the current Sucuri brand

= 1.8.35 =
* Fixed "Early referer checks on admin hooks"

= 1.8.34 =
* Added referer check on admin hooks

= 1.8.33 = 
* Fixed "Added option to clear cache by path"

= 1.8.32 = 
* Fixed "Empty wp-config file after automatic secret key updates"

= 1.8.31 =
* Fixed "Path cannot be empty" error

= 1.8.30 =
* Bump version

= 1.8.29 =
* Changed ownership

= 1.8.28 =
* Silence fopen warning

= 1.8.27 =
* Add support for PHP 8
* Reduce memory requirements when reading a log file
* Fix DISALLOW_FILE_EDIT related notice

= 1.8.26 =
* Replace the word "blacklist" with "blocklist" in the codebase
* Replace the word "whitelist" with "allowlist" in the codebase

= 1.8.25 =
* Fix notice about MONTH_IN_SECONDS in WP < 4.4
* Update reset password workflow

= 1.8.24 =
* Fix warning caused by humanTime function
* Fix fatal error caused by cron jobs with nested arguments

= 1.8.23 =
* Add Automatic Secret Keys Updater
* Improve button's and link's messaging on Last Logins sections
* Improve messaging on Hardening page
* Improve messaging on IP Access page

= 1.8.22 =
* Add "SSL existence check" to WordPress Security Recommendations
* Add "Salt & Security Keys existence check" to WordPress Security Recommendations
* Add "Salt & Security Keys age check" to WordPress Security Recommendations
* Add "Admin account check" to WordPress Security Recommendations
* Add "Single super-admin check" to WordPress Security Recommendations
* Add "Too many plugins check" to WordPress Security Recommendations
* Add "File editing check" to WordPress Security Recommendations
* Add "WordPress debug check" to WordPress Security Recommendations
* Add "Basic hardening check" to WordPress Security Recommendations
* Add a delete button on Last Logins sections
* Add register of logs removal on Audit Logs
* Fix display of Access File Integrity on NGINX/IIS servers
* Remove PHP version check from hardening page

= 1.8.21 =
* Add WordPress Security Recommendations section in the dashboard
* Add PHP version check
* Fix goo.gl links
* Fix post_type pattern match to allow numbers and max of 20 chars
* Fix Audit Logs queue timezone issue
* Fix regex in template string replacement
* Update translation file to include WordPress Security Recommendations section fields
* Make the menu icon use the menu color styling
* Remove block button from failed logins page

= 1.8.20 =
* Add dynamic core directories in the hardening allowlist options
* Modify scheduled tasks panel to load the table via Ajax
* Allow hosting details display to be filterable
* Preparation for translations

= 1.8.19 =
* Add option to refresh the SiteCheck malware scan results
* Add support for a CLI command to ignore files in the core integrity check
* Fix text

= 1.8.18 =
* Keep settings when the plugin is deactivated, unless the plugin is uninstalled

= 1.8.17 =
* Update [Terms of Service](https://sucuri.net/terms) and [Privacy Policy](https://sucuri.net/privacy)

= 1.8.15 =
* Make default plugin options filterable
* Fix missing button to manually activate the advanced features
* Remove unnecessary tags from README per WordPress guidelines
* Modify resolution of the images to respect retina display

= 1.8.14 =
* Add filter to allow automatic configuration of the settings

= 1.8.13 =
* Add new version of the GPL v2 license file
* Remove unused option to reduce number of failed logins
* Fix multiple typos in the code found after a diff parse
* Modify name of the base library file for consistency
* Modify wording of the API key panel in the settings page
* Add option to include the hostname in the alert subject
* Fix open_basedir restriction was not considered on scans
* Remove firewall API key deletion on re-authentication

= 1.8.12 =
* Fix invalid array when deselecting all security alerts
* Add language files to the list of ignored changes
* Modify internal response to the log file not found error
* Add option to force the firewall cache flush
* Fix unexpected exception when open_basedir is in place
* Add support to export and import trusted IP addresses
* Add link to the audit logs API endpoint for developers
* Add reverse ip address in all email alerts from visitor
* Remove API key from the settings that can be exported
* Modify code to make default plugin options filterable
* Add ability to store the settings in the object cache
* Add support for wp-cli and command to generate an API key
* Fix missing documentation tags in the command line library
* Fix format and coding standard in CSS and JavaScript files
* Add button to toggle the visibility of the post-types table
* Modify order of the added, modified, removed core files
* Fix relative file path when ABSPATH is point to root
* Add additional notifications for changes on users

= 1.8.11 =
* Modify Sucuri firewall detection with regular expressions
* Modify option to force scanner to ignore directories
* Modify form to monitor and ignore post-types
* Modify miscellaneous changes in some alert messages
* Modify error message displaying for invalid CSRF validations
* Fix minor issues with the version detection code
* Remove internationalization support for consistency
* Add support for the RTL reading direction
* Add API key in admin notice when it is being deleted
* Fix modification date for corrupt core files
* Fix audit log parser for incompatible JSON data
* Fix password visibility when the option is changed

= 1.8.10 =
* Version bump skipped

= 1.8.9 =
* Remove duplicated failed user authentication log
* Remove trailing forward slash from asset URL
* Fix post-type ignore tool to allow hyphens in the ID
* Fix queries to the database in the last logins page
* Remove unnecessary option queries to the database
* Fix PHP notice for a string offset cast occurred
* Remove unnecessary data from the website info page
* Modify timing for the execution of the Ajax requests

= 1.8.8 =
* Add smart limit to send logs from the queue to the API
* Add option to ignore events for post transitions
* Fix infinite loop with email alerts and SMTP plugin
* Add option to configure the malware scanner target URL
* Add option to enable the auto clear cache firewall function
* Modify status of the directory hardening using the Firewall
* Modify error message in audit logs when the API key is missing
* Modify timing for the dashboard alerts after an update
* Modify firewall clear cache button to execute via Ajax
* Modify firewall settings page to load data via Ajax
* Add option to blocklist IP addresses with the Firewall API
* Fix order of the audit logs when the queue is merged
* Add more directories to ignore during the scans
* Add option to customize the URL for the malware scans
* Fix error interception for Firewall API errors
* Add support for other English and Spanish based languages
* Modify mechanism to ignore files from integrity checks
* Add option to stop sending the failed login passwords
* Modify default value for some of the alert settings
* Remove unnecessary statistics panel for the audit logs
* Modify output for the malware results to simplify links
* Add option to override the timezone for the datetime
* Add option to configure the WordPress checksums API
* Add maximum execution time avoidance in the integrity tool
* Add support to run diff on deleted WordPress files

= 1.8.7 =
* Fix multiple issues with the API calls
* Add queue system to fix website performance
* Fix non-dismissable newsletter invitation message
* Fix performance of the audit log parser without regexp
* Add conditional to check for the availability of SPL
* Add cache for the audit logs to make dashboard responsive
* Modify frequency of the file system scans to run daily
* Remove option to configure the maximum API timeout
* Modify location of the scanner options and scheduled tasks
* Add button to send the logs from the queue to the API

= 1.8.6 =
* Add default language for internationalization fallback

= 1.8.5 =
* Fix minor bugs after post-testing of the new release
* Add full support for internationalization with en_US locale
* Add full support for internationalization with es_ES locale

= 1.8.4 =
* Modify the entire interface to offer a fresh design
* Add support for internationalization via gettext
* Modify the structure of the project for maintainability
* Remove minified files to facilitate future contributions
* Add warning message in the reset plugin tool page
* Fix loading sequence for additional PHP files
* Add restriction to prevent direct access to PHP files
* Fix file search by name when the directory is passed
* Add HTTP request parameters to track some settings
* Fix reset plugin tool with the new WordPress API
* Fix length of the pagination helper with many pages
* Add performance boost for the failed logins page
* Modify structure of the failed logins data analyzer
* Fix deactivation of all the scheduled tasks from settings
* Modify entire code base to enforce HTTPS over HTTP
* Remove heartbeat settings after performance improvement
* Remove unnecessary XHR event monitor and report
* Remove deprecated functions from previous releases
* Remove deprecated tool to scan for error_log files
* Modify failed logins logger with wrong passwords
* Remove plugin checksum dependency to avoid asset cache
* Modify minimum PHP version in hardening page
* Fix email alerts with non-existing site_url option
* Add tool to import and export the plugin settings
* Add uninstall instructions during deactivation of the plugin
* Fix plugin reinstall procedure with backup and prechecks
* Modify mechanism to ignore irrelevant WordPress core files
* Modify list of available scheduled task frequencies
* Fix lazy load of the CSS and Scripts on the correct pages
* Add audit log message fixer for the wpephpcompat_jobs event
* Fix website URL in the template for the email alerts
* Add message in the core integrity tool for false positives
* Add option to reset the content of some storage files
* Add mechanism to display self-hosting logs as fallback
* Fix incoherent failed login processor on pagination
* Add option to display differences in core integrity checks
* Modify the default and maximum timeout for the API
* Fix static data storage path to allow server migrations
* Add option to ignore non-registered custom post-types
* Add more details into the event that monitors post deletions
* Fix event monitor for plugin activation and deactivation
* Fix dynamic directory tree deletion with improved performance
* Fix automatic deletion of conflicting plugins
* Add event monitor for all supported post status transitions
* Add one-time newsletter invitation after plugin updates
* Add code to delete legacy plugin options from database
* Modify error on non-processed files in the integrity checks
* Fix overflow of HTTP requests to SiteCheck API on failures
* Fix handling of the actions in the core integrity checks
* Add message and button to reset the audit logs cache
* Add ajax request to load malware scans for performance

= 1.8.3 =
* Removed goo.gl links
* Fixed fatal error when PHPMailer failed
* Fixed incorrect selected value in settings
* Added SiteCheck for arbitrary domain
* Various code cleanup

= 1.8.2 =
* Modified logic of the settings in database checker
* Modified default value for the available updates alerts
* Fixed undefined array and object keys in audit logs
* Fixed incompatibilities with foreign API service responses
* Added development option to keep using the database
* Added panel with information about the plugin settings
* Added conditional to prevent redeclaration of class
* Fixed cache flush method used to delete datastore

= 1.8.1 =
* Modified default setting for the core integrity alerts
* Added more files to the core integrity ignore list
* Fixed support for custom data storage directory
* Fixed admin notices after changing alert settings
* Fixed settings and audit logs for the firewall page
* Fixed regression with clear cache in firewall page

= 1.8.0 =
* Added error message when storage is not writable
* Fixed option getter to migrate plugin settings if possible
* Fixed base directory name without PHP __DIR__ constant
* Fixed user authentication denial when no blocked users
* Fixed htaccess standard rules checker with no WP_Rewrite

= 1.7.19 =
* Added method to rescue HTTP requests using sockets
* Fixed mishandled JSON data in audit logs Ajax request
* Modified list of firewall features and promo video

= 1.7.18 =
* Added options library using external file instead of the database
* Modified API calls using custom HTTP request using Curl
* Fixed core files marked as broken in a Windows server
* Fixed pagination links in last and failed logins page
* Fixed password with ampersands in email alert
* Fixed allowlist hardening using the authz_core module
* Removed unnecessary emails to reduce spam
* Added constant to stop execution of admin init hooks
* Added explanation for invalid emails and no MX records
* Added link to open the form to insert the API key manually
* Added more options in the IP discoverer setting
* Added option to configure malware scanner timeout
* Added option to configure the API communication protocol
* Added option to reset the malware scanner cache
* Added scheduled task and email alert for available updates
* Added tool to block user accounts from attempting a login
* Added tool to debug HTTP requests to the API services
* Various minor adjustments and fixes

= 1.7.17 =
* Added API service failback mechanism
* Added core integrity email on force scan
* Slight interface redesign
* Various bugfixes and improvements

= 1.7.16 =
* Fixing a low severity XSS (needs admin access to create it)

= 1.7.14 =
* Added alternative method to send email alerts
* Added button to reset options with explanation
* Added suggestion for new users to check plugin settings
* Allow mark as fixed non-writable core files
* Fixed display menus items single or network panels
* Fixed handle boolean values in PHP config retrieval
* Fixed non-standard content location in core integrity
* Fixed user identifier as integer on password reset
* Modified css and js files to reduce size
* Modified do not load resources on hidden sidebar
* Modified fully redesign of general settings page
* Modified hide update warning if versions are the same
* Modified wording of post-types alert settings
* Removed ellipsis of long IPv6 addresses in last logins
* Removed unnecessary dns lookups in infosys page
* Removed unnecessary monospace fonts in settings status
* Removed unnecessary ssl verification option processor

= 1.7.13 =
* Fixed issue affecting site performance
* Fixed clear hardening of previous versions
* Modified report and block non-processable ajax actions
* Added configure DNS lookups for reverse proxy detection
* Added option to configure comment monitor and logs
* Added option to configure the XHR monitor and logs

= 1.7.12 =
* Improved hardening options
* Added more logging events
* Various bugfixes and improvements

= 1.7.11 =
* Reverted change for firewall detection to protect legacy users

= 1.7.10 =
* Added better checks for SSL issues
* Fix for audit log timezones
* Various bugfixes and improvements

= 1.7.9 =
* Improved reinstallation process
* Updated sidebar banners
* Various bugfixes and improvements

= 1.7.8 =
* Fixed bug on the secret keys hardening.

= 1.7.7 =
* Added better support for directory separators
* Added option to remove API key from plugin
* Various bugfixes and improvements

= 1.7.6 =
* Added audit log reporting.
* Added more settings for better control.
* Added support for more actions.
* Improved multisite support.
* Added support for reverse proxies.
* Various bugfixes and improvements.

= 1.7.5 =
* Added better handling of API responses of remote scanner.

= 1.7.4 =
* Added option for keeping failed logins until the user removes them.
* Bugfixes for user reported issues.

= 1.7.3 =
* Error log panel.
* Various bug fixes.

= 1.7.2 =
* Messaging and FAQ updates.

= 1.7.1 =
* Fixed remote scanning that was not loading automatically on some installs.

= 1.7.0 =
* Added Hardening option to remove error log files
* Bug fixes on some new registrations.
* Changed format of the internal logs to json.

= 1.6.9 =
* Multiple bug fixes (as reported on the support forums).
* Added heartbeat for the file scans.
* Code cleanup.

= 1.6.8 =
* Fixing interface.

= 1.6.7 =
* Added Support for integrity checks on i18n installations.
* Fixed the setting change bug.

= 1.6.6 =
* Internal code cleanup and re-organization.
* More white lists for the integrity checks.
* Additional settings to customize some of the warnings.

= 1.6.5 =
* Fixed integrity checking display.

= 1.6.4 =
* Fixed API generation bug.

= 1.6.3 =
* Added proper brute force alerts.
* Added option to restrict number of emails.
* Added more description to the emails.
* Added a list of failed login attempts inside the last login tab.

= 1.6.2 =
* Setting a maximum number of emails per hour.
* Fixing typos.

= 1.6.1 =
* Initial release with new auditing options.

= 1.6.0 =
* A new dashboard to welcome users to the new features of the plugin.
* Overall design of the interface of all the pages were modified.
* SiteCheck scanner results were filled with more information.
* SiteCheck scanner results markers when the site is infected/clean.
* System Info page were simplified with tabulation containers.
* Integrity check for administrator accounts was optimized.
* Integrity check for outdated plugins/themes was optimized and merged.
* IPv6 support in last logins statistics.

= 1.5.7 =
* WordPress 3.9 compatibility

= 1.5.6 =
* Added IPv6 support.
* Fixed links and messaging.

= 1.5.5 =
* Added list of logged in users.
* Added system page.
* Change the integrity checking to use WP API.

= 1.5.4 =
* Bug fixes.

= 1.5.2 =
* Adding additional information about .htaccess hacks and the server environment.

= 1.5.0 =
* Fixing last login and giving better warns on permission errors.
* Making the integrity check messages more clear.

= 1.4.8 =
* New and clean design for the scan results.
* Adding a web firewall check on our hardening page.

= 1.4.7 =
* Cleaning up the code a bit.
* Only displaying last login messages to admin users.
* Storing the logs into a log file instead of the db.

= 1.4.6 =
* Increasing last login table to the last 100 entries.

= 1.4.5 =
* Fixing some issues on the last login and allowing the option to disable it.

= 1.4.4 =
* Small bug fixes + forcing a re-scan on every scan attempt (not using the cache anymore).

= 1.4.3 =
* Fixing a few PHP warnings.

= 1.4.2 =
* Fixing a few PHP warnings.

= 1.4.1 =
* Small bug fixes.
* Adding last IP to the last login page.

= 1.4 =
* Added post-hack options (reset all passwords).
* Added last-login.
* Added more hardening and the option to revert any hardening done.

= 1.3 =
* Removed some PHP warnings and code clean up.
* Added WordPress integrity checks.
* Added plugin/theme/user checks.

= 1.2.2 =
* Tested on WP 3.5.1

= 1.2.1 =
* Tested on WP 3.5-RC4
* Style changes

= 1.2 =
* Cleared PHP warnings
* Added /inc directory
* Added /lib directory
* Logo added
* Default stylesheet added
* Header area added
* Sidebar area added
* Restyled 1-click hardening page
* Removed old malware page

= 1.1.7 =
* Tested on WP 3.5-RC3.

= 1.1.6 =
* Upgrading for WP 3.3.

= 1.1.5 =
* Removed PHP warnings / code cleaning.

= 1.1.3 =
* Cleaning up the results.
* Added 1-click hardening.

= 1.1.2 =
* First release that is good to be used (debugging code removed).

= 1.1.1 =
* First public release.
