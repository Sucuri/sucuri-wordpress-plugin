<?php
/**
 * Cache options library
 *
 * @package Sucuri
 */

if ( ! defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if ( ! headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

/**
 * Cache options library
 *
 * @package Sucuri
 */
class SucuriScanCacheHeaders extends SucuriScan
{
    public function __construct()
    {
        //$this->setCacheHeaders();
    }

    private function getCacheControlStaleFactorer($factor, $max_age)
    {
        if (is_paged() && is_int($factor) && $factor > 0) {
            $multiplier = get_query_var('paged') - 1;

            if ($multiplier > 0) {
                $factored_max_age = $factor * $multiplier;

                if ($factored_max_age >= ($max_age * 10)) {
                    return $max_age * 10;
                }

                return $factored_max_age;
            }
        }

        return 0;
    }

    // TODO: Verify how we can improve this query.
    private function getIsFutureNowMaxTime($max_time_future)
    {
        $future_post = new WP_Query(array(
            'post_status'         => 'future',
            'posts_per_page'      => 1,
            'orderby'             => 'date',
            'order'               => 'ASC',
            'ignore_sticky_posts' => 1
        ));

        if ($future_post->have_posts()) {
            $local_nowtime = intval(current_time('timestamp', 0));

            while ($future_post->have_posts()) {
                $future_post->the_post();
                $local_futuretime = get_the_time('U');

                if (($local_nowtime + $max_time_future) > $local_futuretime) {
                    $max_time_future = $local_futuretime - $local_nowtime + rand(2, 32);
                }
            }

            wp_reset_postdata();
        }

        return $max_time_future;
    }

    private function getCacheDirectives($max_age, $s_maxage, $staleerror, $stalereval)
    {
        $directive = "";

        if (!empty($max_age) && is_int($max_age) && $max_age > 0) {
            $directive = "max-age=$max_age";
        }

        if (!empty($s_maxage) && is_int($s_maxage) && $s_maxage > 0 && $s_maxage != $max_age) {
            if ( ! $directive != "") {
                $directive = "public";
            }

            $directive = "$directive, s-maxage=$s_maxage";
        }

        // append RFC 5861 headers only if the request is cacheable
        if ($directive != "") {

            if (!empty($staleerror) && is_int($staleerror) && $staleerror > 0) {
                $directive = "$directive, stale-if-error=$staleerror";
            }

            if (!empty($stalereval) && is_int($stalereval) && $stalereval > 0) {
                $directive = "$directive, stale-while-revalidate=$stalereval";
            }

            $directive = apply_filters('cache_control_cachedirective', $directive);

            return $directive;

        }

        // request isn't cacheable
        return "no-cache, no-store, must-revalidate";
    }

    private function getCacheDirectiveFromOption($option_name)
    {
        $cache_options = SucuriScanOption::getOption(':cache_options');

        $option = $cache_options[$option_name];

        $max_age    = intval($option['max_age']);
        $s_maxage   = intval($option['s_maxage']);
        $staleerror = intval($option['stale_if_error']);
        $stalereval = intval($option['stale_while_revalidate']);

        // dynamically shorten caching time when a scheduled post is imminent
        if ($option_name != 'attachment' &&
            $option_name != 'dates' &&
            $option_name != 'pages' &&
            $option_name != 'singles' &&
            $option_name != 'notfound') {
            $max_age  = $this->getIsFutureNowMaxTime($max_age);
            $s_maxage = $this->getIsFutureNowMaxTime($s_maxage);
        }

        if (is_paged() && isset($option['paged'])) {
            $page_factor = intval(get_option('cache_control_' . $option['id'] . '_paged', $option['paged']));
            $max_age     += $this->getCacheControlStaleFactorer($page_factor, $max_age);
            $s_maxage    += $this->getCacheControlStaleFactorer($page_factor, $s_maxage);
        }

        // TODO: Get rid of that get_option call
        if ($option_name == 'singles' && get_option('cache_control_singles_mmulti') == 1) {
            $date_now = new DateTime();
            $date_mod = new DateTime(get_the_modified_date('c'));

            $last_com = get_comments('post_id=' . get_the_ID() . '&number=1&include_unapproved=1&number=1&orderby=comment_date');
            if ($last_com != null) {
                $last_com = new DateTime($last_com[0]->comment_date);
                $date_mod = max(array($date_mod, $last_com));
            }

            $date_diff    = $date_now->diff($date_mod);
            $months_stale = $date_diff->m + ($date_diff->y * 12);

            if ($months_stale > 0) {
                $max_age  = intval($max_age * (($months_stale + 12) / 12));
                $s_maxage = intval($s_maxage * (($months_stale + 12) / 12));
            }
        }

        return $this->getCacheDirectives($max_age, $s_maxage, $staleerror, $stalereval);
    }

    private function isNoCacheable()
    {
        global $wp_query;

        $noncacheable = (is_preview() ||
                         is_user_logged_in() ||
                         is_trackback() ||
                         is_admin()
        );

        // Requires post password, and post has been unlocked.
        if ( ! $noncacheable &&
             isset($wp_query) &&
             isset($wp_query->posts) &&
             count($wp_query->posts) >= 1 &&
             ! empty($wp_query->posts[0]->post_password) &&
             ! post_password_required()) {
            $noncacheable = true;
        } elseif ( ! $noncacheable && function_exists('is_woocommerce')) {
            $noncacheable = (is_cart() ||
                             is_checkout() ||
                             is_account_page());
        }

        // TODO: Investigate this filter
        // $noncacheable = apply_filters('cache_control_nocacheables', $noncacheable);

        return $noncacheable;
    }

    private function isWoocommerceInstalled()
    {
        return (function_exists('is_woocommerce') ||
                file_exists(WP_PLUGIN_DIR . '/woocommerce/woocommerce.php'));
    }

    private function selectCacheDirective()
    {
        if ($this->isNoCacheable()) {
            return $this->getCacheDirectives(false, false, false, false);
        } elseif (is_feed()) {
            return $this->getCacheDirectiveFromOption('feeds');
        } elseif (is_front_page() && !is_paged()) {
            return $this->getCacheDirectiveFromOption('front_page');
        } elseif (is_single()) {
            return $this->getCacheDirectiveFromOption('posts');
        } elseif (is_page()) {
            return $this->getCacheDirectiveFromOption('pages');
        } elseif (is_home()) {
            return $this->getCacheDirectiveFromOption('main_index');
        } elseif (is_category()) {
            return $this->getCacheDirectiveFromOption('categories');
        } elseif (is_tag()) {
            return $this->getCacheDirectiveFromOption('tags');
        } elseif (is_author()) {
            return $this->getCacheDirectiveFromOption('authors');
        } elseif (is_attachment()) {
            return $this->getCacheDirectiveFromOption('attachment_pages');
        } elseif (is_search()) {
            return $this->getCacheDirectiveFromOption('search_results');
        } elseif (is_404()) {
            return $this->getCacheDirectiveFromOption('404_not_found');
        } elseif (is_date()) {
            if ((is_year() && strcmp(get_the_time('Y'), date('Y')) < 0) ||
                (is_month() && strcmp(get_the_time('Y-m'), date('Y-m')) < 0) ||
                ((is_day() || is_time()) && strcmp(get_the_time('Y-m-d'), date('Y-m-d')) < 0)) {
                return $this->getCacheDirectiveFromOption('dates');
            } else {
                return $this->getCacheDirectiveFromOption('home');
            }
        } elseif ($this->isWoocommerceInstalled()) {
            if (function_exists('is_product') && is_product()) {
                return $this->getCacheDirectiveFromOption('woocommerce_product');
            } elseif (function_exists('is_product_category') && is_product_category()) {
                return $this->getCacheDirectiveFromOption('woocommerce_category');
            }
        }

        return $this->getCacheDirectives(false, false, false, false);
    }

    private function cache_control_merge_http_header($directives)
    {
        if ( ! empty($directives)) {
            header("Cache-Control: $directives", false);
        }
    }

    private function cache_control_send_headers()
    {
        cache_control_send_http_header(cache_control_select_directive());
    }

    public function setCacheHeaders()
    {
        // Headers are already sent; Nothing to do here.
//		if (headers_sent()) {
//			return false;
//		}

        $header = $this->selectCacheDirective();

        $cacheHeader = 'Cache-Control: max-age=' . $header;

        header($cacheHeader);

        var_dump($cacheHeader);
    }
}
