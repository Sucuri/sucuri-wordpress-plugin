<?php
/**
 * Code related to the cache control headers settings.
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

/**
 * Cache-Control library.
 *
 * We use this library to set the cache control headers based on the user's
 * settings. The cache control headers are used to control how the browser
 * and proxies cache the content of the website.
 *
 * Please enable site caching on your WAF to use these settings.
 *
 * Please note that this is an advanced feature, and we took some inspiration
 * from another WordPress plugin called "cache-control", which hasn't been updated
 * in a long time. We've made some improvements and added some new features,
 * but we still want to give credit to the original author.
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Daniel Cid <dcid@sucuri.net>
 * @copyright  2010-2018 Sucuri Inc.
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
 * @link       https://wordpress.org/plugins/sucuri-scanner
 */
class SucuriScanCacheHeaders extends SucuriScan
{
    protected function getCacheControlStaleFactor($factor, $maxAge)
    {
        if (is_paged() && is_int($factor) && $factor > 0) {
            $multiplier = get_query_var('paged') - 1;

            if ($multiplier > 0) {
                $factoredMaxAge = $factor * $multiplier;

                if ($factoredMaxAge >= ($maxAge * 10)) {
                    return $maxAge * 10;
                }

                return $factoredMaxAge;
            }
        }

        return 0;
    }

    protected function getFuturePostMaxTime($maxTimeFuture)
    {
        $futurePostQuery = new WP_Query(array(
            'post_status' => 'future',
            'posts_per_page' => 1,
            'orderby' => 'date',
            'order' => 'ASC',
            'ignore_sticky_posts' => 1
        ));

        if ($futurePostQuery->have_posts()) {
            $localNowTime = intval(current_time('timestamp', 0));

            while ($futurePostQuery->have_posts()) {
                $futurePostQuery->the_post();
                $localFutureTime = get_the_time('U');

                if (($localNowTime + $maxTimeFuture) > $localFutureTime) {
                    $maxTimeFuture = $localFutureTime - $localNowTime + rand(2, 32);
                }
            }

            wp_reset_postdata();
        }

        return $maxTimeFuture;
    }

    protected function getCacheDirectives($maxAge, $sMaxAge, $staleError, $staleRevalidate)
    {
        $directive = "";

        if (!empty($maxAge) && is_int($maxAge) && $maxAge > 0) {
            $directive = "max-age=$maxAge";
        }

        if (!empty($sMaxAge) && is_int($sMaxAge) && $sMaxAge > 0 && $sMaxAge != $maxAge) {
            if ($directive != "") {
                $directive = "public";
            }
            $directive .= ", s-maxage=$sMaxAge";
        }

        // Append RFC 5861 headers only if the request is cacheable
        if ($directive != "") {
            if (!empty($staleError) && is_int($staleError) && $staleError > 0) {
                $directive .= ", stale-if-error=$staleError";
            }

            if (!empty($staleRevalidate) && is_int($staleRevalidate) && $staleRevalidate > 0) {
                $directive .= ", stale-while-revalidate=$staleRevalidate";
            }

            $directive = apply_filters('cache_control_cache_directive', $directive);

            return $directive;
        }

        // Request isn't cacheable
        return "no-cache, no-store, must-revalidate";
    }

    protected function getCacheDirectiveFromOption($optionName)
    {
        $cacheOptions = SucuriScanOption::getOption(':headers_cache_control_options');
        $option = $cacheOptions[$optionName];

        $maxAge = intval($option['max_age']);
        $sMaxAge = intval($option['s_maxage']);
        $staleError = intval($option['stale_if_error']);
        $staleRevalidate = intval($option['stale_while_revalidate']);

        // Dynamically shorten caching time when a scheduled post is imminent
        if (!in_array($optionName, array('attachment_pages', 'dates', 'pages', 'singles', '404_not_found'))) {
            $maxAge = $this->getFuturePostMaxTime($maxAge);
            $sMaxAge = $this->getFuturePostMaxTime($sMaxAge);
        }

        if (is_paged() && isset($option['paged'])) {
            $pageFactor = intval(get_option('cache_control_' . $option['id'] . '_paged', $option['paged']));
            $maxAge += $this->getCacheControlStaleFactor($pageFactor, $maxAge);
            $sMaxAge += $this->getCacheControlStaleFactor($pageFactor, $sMaxAge);
        }

        if ($optionName == 'singles') {
            $dateNow = new DateTime();
            $dateModified = new DateTime(get_the_modified_date('c'));

            $lastComment = get_comments(array(
                'post_id' => get_the_ID(),
                'number' => 1,
                'include_unapproved' => 1,
                'orderby' => 'comment_date'
            ));

            if ($lastComment != null) {
                $lastCommentDate = new DateTime($lastComment[0]->comment_date);
                $dateModified = max(array($dateModified, $lastCommentDate));
            }

            $dateDiff = $dateNow->diff($dateModified);
            $monthsStale = $dateDiff->m + ($dateDiff->y * 12);

            if ($monthsStale > 0) {
                $maxAge = intval($maxAge * (($monthsStale + 12) / 12));
                $sMaxAge = intval($sMaxAge * (($monthsStale + 12) / 12));
            }
        }

        return $this->getCacheDirectives($maxAge, $sMaxAge, $staleError, $staleRevalidate);
    }

    protected function isNoCacheable()
    {
        global $wp_query;

        $nonCacheable = is_preview() || is_user_logged_in() || is_trackback() || is_admin();

        // Requires post password, and post has been unlocked.
        if (!$nonCacheable && isset($wp_query->posts) && count($wp_query->posts) >= 1 &&
            !empty($wp_query->posts[0]->post_password) && !post_password_required()) {
            $nonCacheable = true;
        } elseif (!$nonCacheable && function_exists('is_woocommerce')) {
            $nonCacheable = is_cart() || is_checkout() || is_account_page();
        }

        return $nonCacheable;
    }

    protected function isWooCommerceInstalled()
    {
        return in_array('woocommerce/woocommerce.php',
            apply_filters('active_plugins', get_option('active_plugins')));
    }

    protected function selectCacheDirective()
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
        } elseif ($this->isWooCommerceInstalled()) {
            if (function_exists('is_product') && is_product()) {
                return $this->getCacheDirectiveFromOption('woocommerce_product');
            } elseif (function_exists('is_product_category') && is_product_category()) {
                return $this->getCacheDirectiveFromOption('woocommerce_category');
            }
        }

        return $this->getCacheDirectives(false, false, false, false);
    }

    protected function mergeHttpHeader($directives)
    {
        if (!empty($directives)) {
            header("Cache-Control: $directives", true);
        }
    }

    public function setCacheHeaders()
    {
        if (headers_sent()) {
            // Headers are already sent; nothing to do here.
            return;
        }

        $header = $this->selectCacheDirective();
        $this->mergeHttpHeader($header);
    }
}
