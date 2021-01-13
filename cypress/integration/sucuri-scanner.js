
describe( 'Run integration tests', () => {
	beforeEach( function() {
		cy.login();
	}	);

	it('can change malware scan target', () => {
		const testDomain = 'sucuri.net';

		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings#apiservice');

		cy.get('[data-cy=sucuriscan_sitecheck_target_input]').type(testDomain);
		cy.get('[data-cy=sucuriscan_sitecheck_target_submit]').click();

		cy.reload();

		cy.get('[data-cy=sucuriscan_sitecheck_target]').contains(testDomain);
	} );

	it('can reset logs, hardening and settings', () => {
		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings#general');

		cy.get('[data-cy=sucuriscan_reset_checkbox]').check();

		cy.get('[data-cy=sucuriscan_reset_submit]').click();

		cy.get('.sucuriscan-alert').contains('Local security logs, hardening and settings were deleted');
	});

	it('can update ip address discovery', () => {
		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings#general');

		cy.get('[data-cy=sucuriscan_ip_address_discovery_toggle_submit]').click();

		cy.get('.sucuriscan-alert').contains('The status of the DNS lookups for the reverse proxy detection has been changed');

		cy.get('[data-cy=sucuriscan_ip_address_discovery_toggle_submit]').contains('Enable');
		cy.get('[data-cy=sucuriscan_ip_address_discovery_toggle_submit]').click();

		cy.get('.sucuriscan-alert').contains('The status of the DNS lookups for the reverse proxy detection has been changed');
		cy.get('[data-cy=sucuriscan_ip_address_discovery_toggle_submit]').contains('Disable');

		cy.get('[data-cy=sucuriscan_addr_header_select]').select('HTTP_X_REAL_IP');
		cy.get('[data-cy=sucuriscan_addr_header_proceed]').click();

		cy.get('.sucuriscan-alert').contains('HTTP header was set to HTTP_X_REAL_IP');
		cy.get('.sucuriscan-alert').contains('Reverse proxy support was set to enabled');
	});

	it('can update reverse proxy setting', () => {
		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings#general');

		cy.get('[data-cy=sucuriscan_reverse_proxy_toggle]').click();

		cy.get('.sucuriscan-alert').contains('Reverse proxy support was set to disabled');
		cy.get('.sucuriscan-alert').contains('HTTP header was set to REMOTE_ADDR');

		cy.get('[data-cy=sucuriscan_reverse_proxy_toggle]').click();

		cy.get('.sucuriscan-alert').contains('Reverse proxy support was set to enabled');
		cy.get('.sucuriscan-alert').contains('HTTP header was set to HTTP_X_SUCURI_CLIENTIP');
	});

	it('can delete datastore files', () => {
		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings#general');

		cy.get('input[value="sucuri-auditqueue.php"]').click();
		cy.get('[data-cy=sucuriscan_general_datastore_delete_button]').click();
		cy.get('.sucuriscan-alert').contains('1 out of 1 files have been deleted.');

		cy.get('[data-cy=sucuriscan_general_datastore_delete_checkbox]').click();
		cy.get('[data-cy=sucuriscan_general_datastore_delete_button]').click();
		cy.get('.sucuriscan-alert').contains('files have been deleted.');
	});

	it('can import JSON settings', () => {
		const jsonPayload = '{"sucuriscan_addr_header":"REMOTE_ADDR","sucuriscan_api_protocol":"https","sucuriscan_api_service":"enabled","sucuriscan_cloudproxy_apikey":"","sucuriscan_diff_utility":"disabled","sucuriscan_dns_lookups":"enabled","sucuriscan_email_subject":"Sucuri Alert, :domain, :event, :remoteaddr","sucuriscan_emails_per_hour":5,"sucuriscan_ignored_events":"","sucuriscan_lastlogin_redirection":"enabled","sucuriscan_maximum_failed_logins":30,"sucuriscan_notify_available_updates":"disabled","sucuriscan_notify_bruteforce_attack":"disabled","sucuriscan_notify_failed_login":"disabled","sucuriscan_notify_plugin_activated":"enabled","sucuriscan_notify_plugin_change":"enabled","sucuriscan_notify_plugin_deactivated":"disabled","sucuriscan_notify_plugin_deleted":"disabled","sucuriscan_notify_plugin_installed":"disabled","sucuriscan_notify_plugin_updated":"disabled","sucuriscan_notify_post_publication":"enabled","sucuriscan_notify_scan_checksums":"disabled","sucuriscan_notify_settings_updated":"enabled","sucuriscan_notify_success_login":"disabled","sucuriscan_notify_theme_activated":"enabled","sucuriscan_notify_theme_deleted":"disabled","sucuriscan_notify_theme_editor":"enabled","sucuriscan_notify_theme_installed":"disabled","sucuriscan_notify_theme_updated":"disabled","sucuriscan_notify_to":"wordpress@example.com","sucuriscan_notify_user_registration":"disabled","sucuriscan_notify_website_updated":"disabled","sucuriscan_notify_widget_added":"disabled","sucuriscan_notify_widget_deleted":"disabled","sucuriscan_prettify_mails":"disabled","sucuriscan_revproxy":"enabled","sucuriscan_selfhosting_fpath":"","sucuriscan_selfhosting_monitor":"disabled","sucuriscan_use_wpmail":"enabled","trusted_ips":[]}';

		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings#general');

		cy.get('[data-cy=sucuriscan_import_export_settings_textarea]').type(jsonPayload, { parseSpecialCharSequences: false });
		cy.get('[data-cy=sucuriscan_import_export_settings_checkbox]').click();
		cy.get('[data-cy=sucuriscan_import_export_settings_submit]').click();

		cy.reload();

		cy.get('[data-cy=sucuriscan_addr_header_select]').contains('REMOTE_ADDR');
	});

	it('can update timezone setting', () => {
		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings#general');

		cy.get('[data-cy=sucuriscan_timezone_select]').select('UTC-07.00');
		cy.get('[data-cy=sucuriscan_timezone_submit]').click();
		cy.get('.sucuriscan-alert').contains('The timezone for the date and time in the audit logs has been changed');
	})

	it('can deactivate sucuri-scanner', () => {
		cy.visit('/wp-admin/plugins.php');
		
		cy.get('#deactivate-sucuri-scanner').click();

		cy.get('.notice').should('contain', 'Plugin deactivated.');
	}	);

	it('can activate sucuri-scanner', () => {
		cy.visit('/wp-admin/plugins.php');
		
		cy.get('#activate-sucuri-scanner').click();

		cy.get('.notice').should('contain', 'Plugin activated.');
	} );

	it('can modify scheduled tasks', () => {
		cy.visit('wp-admin/admin.php?page=sucuriscan_settings#scanner');

		cy.get('input[value="wp_update_plugins"]').click();
		cy.get('[data-cy=sucuriscan_cronjobs_select]').select('Quarterly (every 7776000 seconds)');
		cy.get('[data-cy=sucuriscan_cronjobs_submit]').click();

		cy.get('.sucuriscan-alert').contains('1 tasks has been re-scheduled to run quarterly.');

		cy.get('[data-cy=sucuriscan_row_wp_update_plugins]').find('td:nth-child(3)').contains('quarterly');
	});

	it('can activate and deactivate the WordPress integrity diff utility', () => {
		cy.visit('wp-admin/admin.php?page=sucuriscan_settings#scanner');

		cy.get('[data-cy=sucuriscan_scanner_integrity_diff_utility_toggle]').click();
		cy.get('.sucuriscan-alert').contains('The status of the integrity diff utility has been changed');
		cy.get('[data-cy=sucuriscan_scanner_integrity_diff_utility_toggle]').contains('Disable');

		cy.get('[data-cy=sucuriscan_scanner_integrity_diff_utility_toggle]').click();
		cy.get('.sucuriscan-alert').contains('The status of the integrity diff utility has been changed');
		cy.get('[data-cy=sucuriscan_scanner_integrity_diff_utility_toggle]').contains('Enable');
	});

	it('can ignore and unignore false positives (integrity diff utility)', () => {
		cy.visit('/wp-admin/admin.php?page=sucuriscan#auditlogs');

		cy.get('input[value="added@phpunit-wp-config.php"]').click();
		cy.get('[data-cy=sucuriscan_integrity_incorrect_checkbox]').click();
		cy.get('[data-cy=sucuriscan_integrity_incorrect_submit]').click();

		cy.get('.sucuriscan-alert').contains('1 out of 1 files were successfully processed.');

		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings#scanner');

		cy.get('[data-cy=sucuriscan_integrity_diff_false_positive_table]').contains('phpunit-wp-config.php');
		cy.get('input[value="phpunit-wp-config.php"').click();
		cy.get('[data-cy=sucuriscan_integrity_diff_false_positive_submit]').click();

		cy.get('.sucuriscan-alert').contains('The selected files have been successfully processed.');
		cy.get('[data-cy=sucuriscan_integrity_diff_false_positive_table]').contains('no data available');

		cy.visit('/wp-admin/admin.php?page=sucuriscan#auditlogs');
		cy.get('[data-cy=sucuriscan_integrity_list_table]').contains('phpunit-wp-config.php');
	});

	it('can ignore files and folders during the scans', () => {
		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings#scanner');

		cy.get('[data-cy=sucuriscan_ignore_files_folders_input]').type('sucuri-images');
		cy.get('[data-cy=sucuriscan_ignore_files_folders_ignore_submit]').click();

		cy.get('.sucuriscan-alert').contains('Selected files have been successfully processed.');
		cy.get('[data-cy=sucuriscan_ignore_files_folders_table]').contains('sucuri-images');

		cy.get('input[value="sucuri-images"]').click();
		cy.get('[data-cy=sucuriscan_ignore_files_folders_unignore_submit]').click();

		cy.get('.sucuriscan-alert').contains('Selected files have been successfully processed.');
		cy.get('[data-cy=sucuriscan_ignore_files_folders_table]').contains('no data available');
	});

	it('can toggle hardening options', () => {
		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings#hardening');

		cy.get('input[name=sucuriscan_hardening_firewall]').click();
		cy.get('.sucuriscan-alert').contains('The firewall is a premium service that you need purchase at - Sucuri Firewall');

		cy.get('input[name=sucuriscan_hardening_wpuploads]').click();
		cy.get('.sucuriscan-alert').contains('Hardening applied to the uploads directory');
		cy.get('input[name=sucuriscan_hardening_wpuploads_revert]').click();
		cy.get('.sucuriscan-alert').contains('Hardening reverted in the uploads directory');

		cy.get('input[name=sucuriscan_hardening_wpcontent]').click();
		cy.get('.sucuriscan-alert').contains('Hardening applied to the content directory');
		cy.get('input[name=sucuriscan_hardening_wpcontent_revert]').click();
		cy.get('.sucuriscan-alert').contains('Hardening reverted in the content directory');

		cy.get('input[name=sucuriscan_hardening_wpincludes]').click();
		cy.get('.sucuriscan-alert').contains('Hardening applied to the library directory');
		cy.get('input[name=sucuriscan_hardening_wpincludes_revert]').click();
		cy.get('.sucuriscan-alert').contains('Hardening reverted in the library directory');

		cy.get('input[name=sucuriscan_hardening_fileeditor]').click();
		cy.get('.sucuriscan-alert').contains('Hardening applied to the plugin and theme editor');
		cy.get('input[name=sucuriscan_hardening_fileeditor_revert]').click();
		cy.get('.sucuriscan-alert').contains('Hardening reverted in the plugin and theme editor');

		cy.get('input[name=sucuriscan_hardening_autoSecretKeyUpdater]').click();
		cy.get('.sucuriscan-alert').contains('Automatic Secret Keys Updater enabled. The default frequency is "Weekly"');
		cy.get('input[name=sucuriscan_hardening_autoSecretKeyUpdater_revert]').click();
		cy.get('.sucuriscan-alert').contains('Automatic Secret Keys Updater disabled.');
	});

	it('can whitelist blocked PHP files', () => {
		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings#hardening');

		cy.get('[data-cy=sucuriscan_hardening_whitelist_input]').type('ok.php');
		cy.get('[data-cy=sucuriscan_hardening_whitelist_select]').select('/var/www/html/wp-content');
		cy.get('[data-cy=sucuriscan_hardening_whitelist_submit]').click();

		cy.get('.sucuriscan-alert-error').contains('Access control file does not exists');
	});

	it('can update the secret keys', () => {
		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings#posthack');

		cy.get('[data-cy=sucuriscan_security_keys_checkbox]').click();
		cy.get('[data-cy=sucuriscan_security_keys_submit]').click();

		cy.get('.sucuriscan-alert').contains('Secret keys updated successfully (summary of the operation bellow).');

		cy.wait(3000);

		cy.reload();

		cy.login();

		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings#posthack');

		cy.get('[data-cy=sucuriscan_security_keys_autoupdater]').contains('Automatic Secret Keys Updater — Disabled');
		cy.get('[data-cy=sucuriscan_security_keys_autoupdater_select]').select('Quarterly');
		cy.get('[data-cy=sucuriscan_security_keys_autoupdater_submit]').click();

		cy.get('.sucuriscan-alert').contains('Automatic Secret Keys Updater enabled.');
		cy.get('[data-cy=sucuriscan_security_keys_autoupdater]').contains('Automatic Secret Keys Updater — Enabled');

		cy.get('[data-cy=sucuriscan_security_keys_autoupdater_select]').select('Disabled');
		cy.get('[data-cy=sucuriscan_security_keys_autoupdater_submit]').click();

		cy.get('.sucuriscan-alert').contains('Automatic Secret Keys Updater disabled.');
	});

	it('can reset installed plugins', () => {
		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings&sucuriscan_lastlogin=1#posthack');

		cy.get('input[value="akismet/akismet.php"]').click();
		cy.get('[data-cy=sucuriscan_reset_plugins_submit]').click();

		cy.get('[data-cy=sucuriscan_reset_plugin_response]').contains('Loading');

		cy.wait(2000);

		cy.get('[data-cy=sucuriscan_reset_plugin_response]').contains('Installed');
	});

	it('can modify alerts recipients', () => {
		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings&sucuriscan_lastlogin=1#alerts');

		cy.get('input[value="wordpress@example.com"]').click();
		cy.get('[data-cy=sucuriscan_alerts_test_recipient_submit]').click();

		cy.get('.sucuriscan-alert').contains('A test alert was sent to your email, check your inbox');

		cy.get('[data-cy=sucuriscan_alerts_recipient_input]').type('admin@sucuri.net');
		cy.get('[data-cy=sucuriscan_alerts_recipient_add_email_submit]').click();

		cy.get('.sucuriscan-alert').contains('The email alerts will be sent to: admin@sucuri.net');

		cy.get('input[value="admin@sucuri.net"]').click();
		cy.get('[data-cy=sucuriscan_alerts_delete_recipient_submit]').click();

		cy.get('.sucuriscan-alert').contains('These emails will stop receiving alerts: admin@sucuri.net');

		cy.get('input[value="wordpress@example.com"]').click();
		cy.get('[data-cy=sucuriscan_alerts_test_recipient_submit]').click();

		cy.get('.sucuriscan-alert').contains('A test alert was sent to your email, check your inbox');
	});

	it('can modify trusted ip addresses', () => {
		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings#alerts');

		cy.get('[data-cy=sucuriscan_trusted_ip_table]').contains('no data available');

		cy.get('[data-cy=sucuriscan_trusted_ip_input]').type('182.190.190.0/24');
		cy.get('[data-cy=sucuriscan_trusted_ip_add_ip_submit]').click();

		cy.get('.sucuriscan-alert').contains('Events generated from this IP will be ignored: 182.190.190.0/24');

		cy.get('[data-cy=sucuriscan_trusted_ip_input]').type('182.190.190.0/24');
		cy.get('[data-cy=sucuriscan_trusted_ip_add_ip_submit]').click();

		cy.get('.error').contains('The IP specified address was already added.');

		cy.get('[data-cy=sucuriscan_trusted_ip_table]').find('td:nth-child(2)').contains('182.190.190.0');
		cy.get('[data-cy=sucuriscan_trusted_ip_table]').find('td:nth-child(3)').contains('182.190.190.0/24');

		cy.get('input[name="sucuriscan_del_trust_ip[]"]').click();
		cy.get('[data-cy=sucuriscan_trusted_ip_delete_ip_submit]').click();

		cy.get('.sucuriscan-alert').contains('The selected IP addresses were successfully deleted.');
	});

	it('can modify alert subject', () => {
		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings#alerts');

		cy.get('input[value="Sucuri Alert, :event, :hostname"]').click();
		cy.get('[data-cy=sucuriscan_alerts_subject_submit]').click();

		cy.get('.sucuriscan-alert').contains('The email subject has been successfully updated');

		cy.get('input[value="custom"]').click();
		cy.get('[data-cy=sucuriscan_alerts_subject_input]').type('Security alert: :event');
		cy.get('[data-cy=sucuriscan_alerts_subject_submit]').click();

		cy.get('.sucuriscan-alert').contains('The email subject has been successfully updated');
	});

	it('can update max alerts per hour', () => {
		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings#alerts');

		cy.get('[data-cy=sucuriscan_alerts_per_hour_select]').select('Maximum 160 per hour');
		cy.get('[data-cy=sucuriscan_alerts_per_hour_submit]').click();

		cy.get('.sucuriscan-alert').contains('The maximum number of alerts per hour has been updated');
	});

	it('can update value after a brute force attack is considered', () => {
		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings#alerts');

		cy.get('[data-cy=sucuriscan_max_failed_logins_select]').select('480 failed logins per hour');
		cy.get('[data-cy=sucuriscan_max_failed_logins_submit]').click();

		cy.get('.sucuriscan-alert').contains('The plugin will assume that your website is under a brute-force attack after 480 failed logins are detected during the same hour');
	});

	it('can update the events that fire security alerts', () => {
		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings#alerts');

		cy.get('input[name="sucuriscan_notify_plugin_deleted"][value="1"]').click();
		cy.get('[data-cy=sucuriscan_save_alert_events_submit]').click();

		cy.get('.sucuriscan-alert').contains('The alert settings have been updated');

		cy.get('input[name="sucuriscan_notify_plugin_deleted"][value="1"]')
			.should('have.attr', 'checked', 'checked');

		cy.get('input[name="sucuriscan_notify_plugin_deleted"][value="1"]').click();
		cy.get('[data-cy=sucuriscan_save_alert_events_submit]').click();

		cy.get('.sucuriscan-alert').contains('The alert settings have been updated');

		cy.get('input[name="sucuriscan_notify_plugin_deleted"][value="1"]')
			.should('not.have.attr', 'checked');
	});

	it('can update alerts per post type', () => {
		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings#alerts');

		const custom_post_type = 'new_sucuri_post_type';

		cy.get('[data-cy=sucuriscan_alerts_post_type_input]').type(custom_post_type);
		cy.get('[data-cy=sucuriscan_alerts_post_type_submit]').click();

		cy.get('.sucuriscan-alert').contains('Post-type has been successfully ignored.');

		cy.reload();

		cy.get('[data-cy=sucuriscan_alerts_post_type_toggle_post_type_list]').click();

		cy.get(`input[value="${custom_post_type}"]`).should('not.have.attr', 'checked');

		cy.get('[data-cy=sucuriscan_alerts_post_type_input]').type(custom_post_type);
		cy.get('[data-cy=sucuriscan_alerts_post_type_submit]').click();

		cy.get('.sucuriscan-alert').contains('The post-type is already being ignored (duplicate).');

		cy.get('[data-cy=sucuriscan_alerts_post_type_toggle_post_type_list]').click();
		cy.get('input[value="nav_menu_item"]').click();
		cy.get('[data-cy=sucuriscan_alerts_post_type_save_submit]').click();

		cy.get('.sucuriscan-alert').contains('List of monitored post-types has been updated.');
	});

	it('can toggle api service communication', () => {
		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings#apiservice');

		cy.get('[data-cy=sucuriscan_api_status_toggle]').click();

		cy.get('.sucuriscan-alert').contains('The status of the API service has been changed');

		cy.get('[data-cy=sucuriscan_api_status_toggle]').contains('Enable');

		cy.get('[data-cy=sucuriscan_api_status_toggle]').click();

		cy.get('.sucuriscan-alert').contains('The status of the API service has been changed');

		cy.get('[data-cy=sucuriscan_api_status_toggle]').contains('Disable');
	});

	it('can update the wordpress checksum api ', () => {
		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings#apiservice');

		cy.get('[data-cy=sucuriscan_wordpress_checksum_api_input]').type('https://api.wordpress.org/core/checksums/1.0/?version=5.5.1&locale=es_ES');
		cy.get('[data-cy=sucuriscan_wordpress_checksum_api_submit]').click();

		cy.get('.updated').contains('The URL to retrieve the WordPress checksums has been changed');
	});

	it('can load website info OK', () => {
		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings#webinfo');

		cy.get('[data-cy=ABSPATH]').find('td:first-child').contains('ABSPATH');
		cy.get('[data-cy=ABSPATH]').find('td:last-child').contains('/var/www/html/');

		cy.get('[data-cy=sucuriscan_access_file_integrity]').contains('Htaccess file found in /var/www/html/.htaccess');
	});

	it('can send audit logs to sucuri servers', () => {
		cy.visit('/wp-admin/admin.php?page=sucuriscan#auditlogs');

		cy.intercept('POST', '/wp-admin/admin-ajax.php?page=sucuriscan', (req) => {
			if (req.body.includes('get_audit_logs')) {
				req.reply((res) => {
					res.send({ fixture: 'audit_logs.json' });
				});
			}

			if (req.body.includes('auditlogs_send_logs')) {
				req.reply((res) => {
					res.send({ fixture: 'auditlogs_send_logs.json' });
				});
			}
		});

		cy.get('[data-cy=sucuriscan_dashboard_send_audit_logs_submit]').click();

		cy.get('[data-cy=sucuriscan_auditlog_response_loading]').contains('Loading...');

		cy.get('.sucuriscan-auditlog-entry-title').contains('User authentication succeeded: admin');
	});

	it('can see last logins tab and delete last logins file', () => {
		cy.visit('/wp-admin/admin.php?page=sucuriscan_lastlogins#allusers');

		cy.get('[data-cy=sucuriscan_last_logins_table]').find('td:nth-child(1)').contains('admin (admin)');

		cy.get('[data-cy=sucuriscan_last_logins_delete_logins_button]').click();

		cy.get('.sucuriscan-alert').contains('sucuri-lastlogins.php was deleted.');
		cy.get('[data-cy=sucuriscan_last_logins_table]').contains('no data available');

		cy.get('[data-cy=sucuriscan_lastlogins_nav_admins]').click();

		cy.get('[data-cy=sucuriscan_successful_logins_table]').find('td:nth-child(1)').contains('admin');

		cy.get('[data-cy=sucuriscan_lastlogins_nav_loggedin]').click();

		cy.get('[data-cy=sucuriscan_successful_loggedin_table]').find('td:nth-child(2)').contains('admin');

		cy.get('[data-cy=sucuriscan_lastlogins_nav_failed]').click();

		cy.login('_x', 'NOT_A_WP_PASS');

		cy.login();

		cy.visit('/wp-admin/admin.php?page=sucuriscan_lastlogins#failed');

		cy.get('[data-cy=sucuriscan_failedlogins_table]').find('td:nth-child(1)').contains('admin_x');

		cy.get('[data-cy=sucuriscan_failedlogins_delete_logins_button]').click();

		cy.get('.sucuriscan-alert').contains('sucuri-failedlogins.php was deleted.');

		cy.get('[data-cy=sucuriscan_failedlogins_table]').contains('no data available');
	});

	it('can reset password', () => {
		cy.clearCookies();
		cy.reload();

		// This user is added automatically at .github/workflows/end-to-end-tests.yml
		cy.login('sucuri', 'password');

		cy.url().should('eq', 'http://localhost:8888/wp-admin/')

		cy.clearCookies();
		cy.reload();

		cy.login();

		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings#posthack');

		cy.get('input[value="2"]').click();
		cy.get('[data-cy=sucuriscan-reset-password-button]').click();

		cy.get('[data-cy=sucuriscan-reset-password-user-field]').contains('sucuri (Done)');

		cy.clearCookies();
		cy.reload();

		cy.login('sucuri', 'password');

		cy.get('#login_error').contains('The password you entered for the username sucuri is incorrect.');
	});
}	);
