<?php

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

/**
 * Read, parse and handle everything related with the templates.
 *
 * A web template system uses a template processor to combine web templates to
 * form finished web pages, possibly using some data source to customize the
 * pages or present a large amount of content on similar-looking pages. It is a
 * web publishing tool present in content management systems, web application
 * frameworks, and HTML editors.
 *
 * Web templates can be used like the template of a form letter to either
 * generate a large number of "static" (unchanging) web pages in advance, or to
 * produce "dynamic" web pages on demand.
 */
class SucuriScanTemplate extends SucuriScanRequest
{
    /**
     * Replace all pseudo-variables from a string of characters.
     *
     * @param  string $content The content of a template file which contains pseudo-variables.
     * @param  array  $params  List of pseudo-variables that will be replaced in the template.
     * @return string          The content of the template with the pseudo-variables replated.
     */
    private static function replacePseudoVars($content = '', $params = array())
    {
        if (is_array($params)) {
            foreach ($params as $keyname => $kvalue) {
                $tplkey = 'SUCURI.' . $keyname;
                $with_escape = '%%' . $tplkey . '%%';
                $wout_escape = '%%%' . $tplkey . '%%%';

                if (strpos($content, $wout_escape) !== false) {
                    $content = str_replace($wout_escape, $kvalue, $content);
                } elseif (strpos($content, $with_escape) !== false) {
                    $kvalue = SucuriScan::escape($kvalue);
                    $content = str_replace($with_escape, $kvalue, $content);
                }
            }

            return $content;
        }

        return false;
    }

    /**
     * Check if the ads in the sidebar are visible or not.
     *
     * @return boolean True if the ads must be hidden.
     */
    private static function noAdvertisement()
    {
        return (bool) (
            defined('SUCURISCAN_HIDE_ADS')
            && SUCURISCAN_HIDE_ADS === true
        );
    }

    /**
     * Gather and generate the information required globally by all the template files.
     *
     * @param  string $target Scenario where the params are going to be replaced.
     * @param  array  $params Key-value array with variables shared with the template.
     * @return array          Additional list of variables for the template files.
     */
    private static function sharedParams($target = null, $params = array())
    {
        $params = is_array($params) ? $params : array();

        // Base parameters, required to render all the pages.
        $params = self::linksAndNavbar($params);

        // Global parameters, used through out all the pages.
        $params['PageTitle'] = isset($params['PageTitle']) ? '('.$params['PageTitle'].')' : '';
        $params['PageNonce'] = wp_create_nonce('sucuriscan_page_nonce');
        $params['PageStyleClass'] = isset($params['PageStyleClass']) ? $params['PageStyleClass'] : 'base';
        $params['CleanDomain'] = self::getDomain();

        // Get a list of admin users for the API key generation.
        if ($target === 'modal' /* Get API key if required */
            && SucuriScanAPI::getPluginKey() === false
        ) {
            $admin_users = SucuriScan::getUsersForAPIKey();
            $params['AdminEmails'] = self::selectOptions($admin_users);
        }

        // Hide the advertisements from the layout.
        if (self::noAdvertisement()) {
            $params['LayoutType'] = 'onecolumn';
            $params['AdsVisibility'] = 'hidden';
            $params['ReviewNavbarButton'] = 'visible';
            $params['PageSidebarContent'] = '';
        } else {
            $params['LayoutType'] = 'twocolumns';
            $params['AdsVisibility'] = 'visible';
            $params['ReviewNavbarButton'] = 'hidden';
            $params['PageSidebarContent'] = self::getTemplate('bsidebar', $params, 'section');
        }

        return $params;
    }

    /**
     * Return a string indicating the visibility of a HTML component.
     *
     * @param  boolean $visible Whether the condition executed returned a positive value or not.
     * @return string           A string indicating the visibility of a HTML component.
     */
    public static function visibility($visible = false)
    {
        return ($visible === true ? 'visible' : 'hidden');
    }

    /**
     * Generate an URL pointing to the page indicated in the function and that must
     * be loaded through the administrator panel.
     *
     * @param  string  $page Short name of the page that will be generated.
     * @param  boolean $ajax True if the URL should point to the Ajax handler.
     * @return string        Full string containing the link of the page.
     */
    public static function getUrl($page = '', $ajax = false)
    {
        $suffix = ($ajax === true) ? 'admin-ajax' : 'admin';
        $url_path = SucuriScan::adminURL($suffix . '.php?page=sucuriscan');

        if (!empty($page)) {
            $url_path .= '_' . strtolower($page);
        }

        if (SucuriScan::isMultiSite()) {
            $url_path = str_replace(
                'wp-admin/network/admin-ajax.php',
                'wp-admin/admin-ajax.php',
                $url_path
            );
        }

        return $url_path;
    }

    /**
     * Generate an URL pointing to the page indicated in the function and that must
     * be loaded through the Ajax handler of the administrator panel.
     *
     * @param  string $page Short name of the page that will be generated.
     * @return string       Full string containing the link of the page.
     */
    public static function getAjaxUrl($page = '')
    {
        return self::getUrl($page, true);
    }

    /**
     * Complement the list of pseudo-variables that will be used in the base
     * template files, this will also generate the navigation bar and detect which
     * items in it are selected by the current page.
     *
     * @param  array  $params Key-value array with pseudo-variables shared with the template.
     * @return array          A complementary list of pseudo-variables for the template files.
     */
    private static function linksAndNavbar($params = array())
    {
        global $sucuriscan_pages;

        $params = is_array($params) ? $params : array();
        $sub_pages = is_array($sucuriscan_pages) ? $sucuriscan_pages : array();

        $params['Navbar'] = '';
        $params['CurrentPageFunc'] = '';

        if ($_page = self::get('page', '_page')) {
            $params['CurrentPageFunc'] = $_page;
        }

        foreach ($sub_pages as $sub_page_func => $sub_page_title) {
            if ($sub_page_func === 'sucuriscan_scanner'
                && SucuriScanSiteCheck::hasBeenDisabled()
            ) {
                continue;
            }

            $func_parts = explode('_', $sub_page_func, 2);

            if (isset($func_parts[1])) {
                $unique_name = $func_parts[1];
                $pseudo_var = 'URL.' . ucwords($unique_name);
            } else {
                $unique_name = '';
                $pseudo_var = 'URL.Home';
            }

            $params[$pseudo_var] = self::getUrl($unique_name);

            // Copy URL variable and create an Ajax handler.
            $pseudo_var_ajax = 'Ajax' . $pseudo_var;
            $params[$pseudo_var_ajax] = self::getAjaxUrl($unique_name);

            $navbar_item_css_class = 'nav-tab';

            if ($params['CurrentPageFunc'] == $sub_page_func) {
                $navbar_item_css_class .= "\x20nav-tab-active";
            }

            $params['Navbar'] .= sprintf(
                "<a class='%s' href='%s'>%s</a>\n",
                $navbar_item_css_class,
                $params[$pseudo_var],
                $sub_page_title
            );
        }

        return $params;
    }

    /**
     * Generate a HTML code using a template and replacing all the pseudo-variables
     * by the dynamic variables provided by the developer through one of the parameters
     * of the function.
     *
     * @param  string $html   The HTML content of a template file with its pseudo-variables parsed.
     * @param  array  $params Key-value array with pseudo-variables shared with the template.
     * @return string         The formatted HTML content of the base template.
     */
    public static function getBaseTemplate($html = '', $params = array())
    {
        $params = is_array($params) ? $params : array();

        $params = self::sharedParams('base', $params);
        $params['PageContent'] = $html;

        return self::getTemplate('base', $params);
    }

    /**
     * Generate a HTML code using a template and replacing all the pseudo-variables
     * by the dynamic variables provided by the developer through one of the parameters
     * of the function.
     *
     * @param  string  $template Filename of the template that will be used to generate the page.
     * @param  array   $params   Key-value array with pseudo-variables shared with the template.
     * @param  boolean $type     Template type; either page, section or snippet.
     * @return string            Formatted HTML code after pseudo-variables replacement.
     */
    public static function getTemplate($template = '', $params = array(), $type = 'page')
    {
        if (!is_array($params)) {
            $params = array();
        }

        if ($type == 'page' || $type == 'section') {
            $fpath_pattern = '%s/%s/inc/tpl/%s.html.tpl';
        } elseif ($type == 'snippet') {
            $fpath_pattern = '%s/%s/inc/tpl/%s.snippet.tpl';
        } else {
            $fpath_pattern = null;
        }

        if ($fpath_pattern !== null) {
            $output = '';
            $fpath = sprintf(
                $fpath_pattern,
                WP_PLUGIN_DIR,
                SUCURISCAN_PLUGIN_FOLDER,
                $template
            );

            if (file_exists($fpath) && is_readable($fpath)) {
                $output = @file_get_contents($fpath);

                $params['SucuriURL'] = SUCURISCAN_URL;

                // Detect the current page URL.
                if ($_page = self::get('page', '_page')) {
                    $params['CurrentURL'] = SucuriScan::adminURL('admin.php?page=' . $_page);
                } else {
                    $params['CurrentURL'] = SucuriScan::adminURL();
                }

                // Replace the global pseudo-variables in the section/snippets templates.
                if ($template == 'base'
                    && array_key_exists('PageContent', $params)
                    && @preg_match('/%%SUCURI\.(.+)%%/', $params['PageContent'])
                ) {
                    $params['PageContent'] = self::replacePseudoVars($params['PageContent'], $params);
                }

                $output = self::replacePseudoVars($output, $params);
            }

            if ($template == 'base' || $type != 'page') {
                return $output;
            }

            return self::getBaseTemplate($output, $params);
        }

        return '';
    }

    /**
     * Generate a HTML code using a template and replacing all the pseudo-variables
     * by the dynamic variables provided by the developer through one of the parameters
     * of the function.
     *
     * @param  string $template Filename of the template that will be used to generate the page.
     * @param  array  $params   Key-value array with pseudo-variables shared with the template.
     * @return string           The formatted HTML page after replace all the pseudo-variables.
     */
    public static function getSection($template = '', $params = array())
    {
        $params = self::sharedParams('section', $params);

        return self::getTemplate($template, $params, 'section');
    }

    /**
     * Generate a HTML code using a template and replacing all the pseudo-variables
     * by the dynamic variables provided by the developer through one of the parameters
     * of the function.
     *
     * @param  string $template Filename of the template that will be used to generate the page.
     * @param  array  $params   Key-value array with pseudo-variables shared with the template.
     * @return string           The formatted HTML page after replace all the pseudo-variables.
     */
    public static function getModal($template = '', $params = array())
    {
        $required = array(
            'Title' => 'Lorem ipsum dolor sit amet',
            'Visibility' => 'visible',
            'Identifier' => 'foobar',
            'CssClass' => '',
            'Content' => '<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do
                eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim
                veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo
                consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse
                cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non
                proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>',
        );

        if (!empty($template) && $template != 'none') {
            $params['Content'] = self::getSection($template);
        }

        foreach ($required as $param_name => $param_value) {
            if (!isset($params[$param_name])) {
                $params[$param_name] = $param_value;
            }
        }

        $params['Visibility'] = 'sucuriscan-' . $params['Visibility'];
        $params['Identifier'] = 'sucuriscan-' . $template . '-modal';
        $params = self::sharedParams('modal', $params);

        return self::getTemplate('modalwindow', $params, 'section');
    }

    /**
     * Generate a HTML code using a template and replacing all the pseudo-variables
     * by the dynamic variables provided by the developer through one of the parameters
     * of the function.
     *
     * @param  string $template Filename of the template that will be used to generate the page.
     * @param  array  $params   Key-value array with pseudo-variables shared with the template.
     * @return string           The formatted HTML page after replace all the pseudo-variables.
     */
    public static function getSnippet($template = '', $params = array())
    {
        return self::getTemplate($template, $params, 'snippet');
    }

    /**
     * Generate the HTML code necessary to render a list of options in a form.
     *
     * @param  array  $allowed_values List with keys and values allowed for the options.
     * @param  string $selected_val   Value of the option that will be selected by default.
     * @return string                 Option list for a select form field.
     */
    public static function selectOptions($allowed_values = array(), $selected_val = '')
    {
        $options = '';

        foreach ($allowed_values as $option_name => $option_label) {
            $options .= sprintf(
                "<option %s value='%s'>%s</option>\n",
                ($option_name === $selected_val ? 'selected="selected"' : ''),
                SucuriScan::escape($option_name),
                SucuriScan::escape($option_label)
            );
        }

        return $options;
    }

    /**
     * Detect which number in a pagination was clicked.
     *
     * @return integer Page number of the link clicked in a pagination.
     */
    public static function pageNumber()
    {
        $paged = self::get('paged', '[0-9]{1,5}');

        return ($paged ? intval($paged) : 1);
    }

    /**
     * Generate the HTML code to display a pagination.
     *
     * @param  string  $base_url     Base URL for the links before the page number.
     * @param  integer $total_items  Total quantity of items retrieved from a query.
     * @param  integer $max_per_page Maximum number of items that will be shown per page.
     * @return string                HTML code for a pagination generated using the provided data.
     */
    public static function pagination($base_url = '', $total_items = 0, $max_per_page = 1)
    {
        // Calculate the number of links for the pagination.
        $html_links = '';
        $page_number = self::pageNumber();
        $max_pages = ceil($total_items / $max_per_page);
        $final_page = $max_pages;
        $start_page = 1;
        $extra_url = '';

        // Fix for inline anchor URLs.
        if (@preg_match('/^(.+)(#.+)$/', $base_url, $match)) {
            $base_url = $match[1];
            $extra_url = $match[2];
        }

        // Keep the number of pagination buttons at limit.
        if ($max_pages > SUCURISCAN_MAX_PAGINATION_BUTTONS) {
            $final_page = SUCURISCAN_MAX_PAGINATION_BUTTONS;
            $middle = $final_page / 2; /* middle point */

            if ($page_number > $middle) {
                $start_page = $page_number - ($middle - 1);
                $final_page = $page_number + $middle;
            }
        }

        // Generate the HTML links for the pagination.
        for ($j = $start_page; $j <= $final_page; $j++) {
            $link_class = 'sucuriscan-pagination-link';

            if ($page_number == $j) {
                $link_class .= "\x20sucuriscan-pagination-active";
            }

            $html_links .= sprintf(
                '<li><a href="%s&paged=%d%s" class="%s" data-page="%d">%s</a></li>',
                $base_url,
                $j,
                $extra_url,
                $link_class,
                $j,
                $j
            );
        }

        return $html_links;
    }
}
