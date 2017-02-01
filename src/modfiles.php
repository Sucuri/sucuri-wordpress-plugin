<?php

/**
 * Scan the content directory for files that were modified during the last seven
 * days (by default) or during the number of days specified by the user in the
 * request. Note that this operation may fail with an internal server error if
 * the project contains too many files that the PHP interpreter can check in a
 * single run.
 *
 * @return void
 */
function sucuriscan_modified_files()
{
    // TODO: Keep the array values hardcoded for now.
    $valid_day_ranges = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 20, 30, 60);
    $template_variables = array(
        'ModifiedFiles.List' => '',
        'ModifiedFiles.SelectOptions' => '',
        'ModifiedFiles.Days' => 0,
    );

    // Generate the options for the select field of the page form.
    if ($valid_day_ranges) {
        $options = array();
        foreach ($valid_day_ranges as $day) {
            $options[$day] = sprintf(
                '%d day%s ago',
                $day,
                ($day === 1)?'':'s'
            );
        }
        $options_html = SucuriScanTemplate::selectOptions($options, 7);
        $template_variables['ModifiedFiles.SelectOptions'] = $options_html;
    }

    return SucuriScanTemplate::getSection('integrity-modifiedfiles', $template_variables);
}

function sucuriscan_scanner_modfiles_days($back_days = 0)
{
    $default_number_of_days = 7;
    $back_days = intval($back_days);

    // Keep the number of days in range.
    if ($back_days === false) {
        $back_days = $default_number_of_days;
    } else {
        if ($back_days <= 0) {
            $back_days = 1;
        } elseif ($back_days >= 60) {
            $back_days = 60;
        }
    }

    return $back_days;
}

function sucuriscan_scanner_modfiles_ajax()
{
    if (SucuriScanRequest::post('form_action') == 'get_modfiles') {
        $response = '';
        $hashes = sucuriscan_get_integrity_tree(WP_CONTENT_DIR, true);
        $back_days = SucuriScanRequest::post(':last_days', '[0-9]+');
        $back_days = sucuriscan_scanner_modfiles_days($back_days);

        if (!empty($hashes)) {
            $counter = 0;
            $back_days = current_time('timestamp') - ($back_days * 86400);

            foreach ($hashes as $file_path => $file_info) {
                if (isset($file_info['modified_at'])
                    && $file_info['modified_at'] >= $back_days
                ) {
                    $css_class = ($counter % 2 === 0) ? '' : 'alternate';
                    $mod_date = SucuriScan::datetime($file_info['modified_at']);

                    $response .= SucuriScanTemplate::getSnippet(
                        'integrity-modifiedfiles',
                        array(
                            'ModifiedFiles.DateTime' => $mod_date,
                            'ModifiedFiles.FilePath' => $file_path,
                            'ModifiedFiles.CheckSum' => $file_info['checksum'],
                            'ModifiedFiles.FileSize' => $file_info['filesize'],
                            'ModifiedFiles.FileSizeHuman' => SucuriScan::human_filesize($file_info['filesize']),
                            'ModifiedFiles.FileSizeNumber' => number_format($file_info['filesize']),
                            'ModifiedFiles.CssClass' => $css_class,
                        )
                    );
                    $counter++;
                }
            }
        }

        print($response);
        exit(0);
    }
}
