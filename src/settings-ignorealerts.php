<?php

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

function sucuriscan_settings_ignore_rules()
{
    $notify_new_site_content = SucuriScanOption::getOption(':notify_post_publication');

    $template_variables = array(
        'IgnoreRules.MessageVisibility' => 'visible',
        'IgnoreRules.TableVisibility' => 'hidden',
        'IgnoreRules.PostTypes' => '',
    );

    if ($notify_new_site_content == 'enabled') {
        $post_types = get_post_types();
        $ignored_events = SucuriScanOption::getIgnoredEvents();

        $template_variables['IgnoreRules.MessageVisibility'] = 'hidden';
        $template_variables['IgnoreRules.TableVisibility'] = 'visible';
        $counter = 0;

        foreach ($post_types as $post_type) {
            $counter++;
            $css_class = ($counter % 2 === 0) ? 'alternate' : '';
            $post_type_title = ucwords(str_replace('_', chr(32), $post_type));

            if (array_key_exists($post_type, $ignored_events)) {
                $is_ignored_text = 'YES';
                $was_ignored_at = SucuriScan::datetime($ignored_events[ $post_type ]);
                $is_ignored_class = 'danger';
                $button_action = 'remove';
                $button_class = 'button-primary';
                $button_text = 'Allow';
            } else {
                $is_ignored_text = 'NO';
                $was_ignored_at = 'Not ignored';
                $is_ignored_class = 'success';
                $button_action = 'add';
                $button_class = 'button-primary button-danger';
                $button_text = 'Ignore';
            }

            $template_variables['IgnoreRules.PostTypes'] .= SucuriScanTemplate::getSnippet(
                'settings-ignorerules',
                array(
                    'IgnoreRules.CssClass' => $css_class,
                    'IgnoreRules.Num' => $counter,
                    'IgnoreRules.PostTypeTitle' => $post_type_title,
                    'IgnoreRules.IsIgnored' => $is_ignored_text,
                    'IgnoreRules.WasIgnoredAt' => $was_ignored_at,
                    'IgnoreRules.IsIgnoredClass' => $is_ignored_class,
                    'IgnoreRules.PostType' => $post_type,
                    'IgnoreRules.Action' => $button_action,
                    'IgnoreRules.ButtonClass' => 'button ' . $button_class,
                    'IgnoreRules.ButtonText' => $button_text,
                )
            );
        }
    }

    return SucuriScanTemplate::getSection('settings-ignorerules', $template_variables);
}
