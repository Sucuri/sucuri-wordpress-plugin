#!/bin/bash
set -e

# Fixtures for the Cache-Control header spec.
#
# Runs inside the wp-env cli container (cwd = WP docroot, /var/www/html),
# invoked from playwright/support/cache-control.ts via runPluginScript().
#
# Four commands, because the spec needs state at four different moments:
#
#   seed <slug>   Sweep leftovers, create the post/page/category the header
#                 assertions read, print their canonical URLs as JSON.
#   teardown      Restore parked posts, then sweep the fixture content.
#   quarantine    Park every scheduled ("future") post as a draft.
#   restore       Put the parked posts back to "future".
#
# Why quarantine exists: cachecontrol.lib.php getFuturePostMaxTime() clamps
# max-age to the seconds remaining until the next scheduled post, then adds
# wp_rand(2, 32) of jitter. Any future post near enough to now therefore turns a
# "max-age=600" assertion into a random number. Parking them for the duration of
# each test is what makes the emitted header deterministic.
#
# `wp eval` deliberately runs WITHOUT --skip-plugins here: it mirrors what the
# spec did inline before this script existed, so the plugin's own post hooks
# still fire while the fixture content is created.

export SUCURI_FIXTURE_META="_sucuri_e2e_cache_fixture"
export SUCURI_FUTURE_META="_sucuri_e2e_original_future"

# Delete any fixture content left behind by an interrupted run. Matching on the
# meta marker (not the slug) is what makes re-seeding idempotent.
sweep_fixture_content() {
    wp eval '
$marker = getenv("SUCURI_FIXTURE_META");

$posts = get_posts(array(
    "post_type"      => array("post", "page"),
    "post_status"    => "any",
    "meta_key"       => $marker,
    "posts_per_page" => -1,
));

foreach ($posts as $post) {
    wp_delete_post($post->ID, true);
}

$terms = get_terms(array(
    "taxonomy"   => "category",
    "hide_empty" => false,
    "meta_key"   => $marker,
    "meta_value" => 1,
));

foreach ($terms as $term) {
    wp_delete_term($term->term_id, "category");
}
'
}

seed_fixture_content() {
    sweep_fixture_content

    SUCURI_CATEGORY_SLUG="$1" wp eval '
$slug   = getenv("SUCURI_CATEGORY_SLUG");
$marker = getenv("SUCURI_FIXTURE_META");

$category = wp_insert_term("Sucuri E2E Cache", "category", array("slug" => $slug));

if (is_wp_error($category)) {
    // A term with this slug outlived an earlier run without carrying the meta
    // marker, so the sweep above could not see it. Reuse it rather than fail.
    $categoryId = (int) $category->get_error_data("term_exists");
} else {
    $categoryId = (int) $category["term_id"];
}

if (!$categoryId) {
    WP_CLI::error("cache-control fixture: could not create or resolve the category");
}

update_term_meta($categoryId, $marker, 1);

$postId = wp_insert_post(array(
    "post_title"    => "Sucuri E2E Cache Post",
    "post_name"     => "sucuri-e2e-cache-post",
    "post_status"   => "publish",
    "post_type"     => "post",
    "post_category" => array($categoryId),
    "meta_input"    => array($marker => 1),
));

$pageId = wp_insert_post(array(
    "post_title"  => "Sucuri E2E Cache Page",
    "post_name"   => "sucuri-e2e-cache-page",
    "post_status" => "publish",
    "post_type"   => "page",
    "meta_input"  => array($marker => 1),
));

foreach (array("post" => $postId, "page" => $pageId) as $label => $value) {
    if (is_wp_error($value) || !$value) {
        WP_CLI::error("cache-control fixture: could not create the $label");
    }
}

// Canonical URLs, asked of WordPress rather than built by hand, so the spec
// holds under whatever permalink structure the environment uses and never hands
// support/http.ts a URL that canonical-redirects. User 1 is the admin account
// wp-env installs.
echo wp_json_encode(array(
    "postUrl"     => wp_make_link_relative(get_permalink($postId)),
    "pageUrl"     => wp_make_link_relative(get_permalink($pageId)),
    "categoryUrl" => wp_make_link_relative(get_category_link($categoryId)),
    "authorUrl"   => wp_make_link_relative(get_author_posts_url(1)),
));
'
}

quarantine_future_posts() {
    wp eval '
$marker = getenv("SUCURI_FUTURE_META");

$query = new WP_Query(array(
    "post_type"      => "any",
    "post_status"    => "future",
    "posts_per_page" => -1,
));

global $wpdb;

foreach ($query->posts as $post) {
    update_post_meta($post->ID, $marker, 1);
    // Direct UPDATE on purpose: wp_update_post() would fire the future_to_draft
    // transition and clear this post publish_future_post cron event, which the
    // restore below could not put back.
    $wpdb->update($wpdb->posts, array("post_status" => "draft"), array("ID" => $post->ID));
    clean_post_cache($post->ID);
}
'
}

restore_future_posts() {
    wp eval '
$marker = getenv("SUCURI_FUTURE_META");

$query = new WP_Query(array(
    "post_type"      => "any",
    "post_status"    => "draft",
    "meta_key"       => $marker,
    "posts_per_page" => -1,
));

global $wpdb;

foreach ($query->posts as $post) {
    $wpdb->update($wpdb->posts, array("post_status" => "future"), array("ID" => $post->ID));
    delete_post_meta($post->ID, $marker);
    clean_post_cache($post->ID);
}
'
}

case "${1:-}" in
    seed)
        if [ -z "${2:-}" ]; then
            echo "seed requires a category slug" >&2
            exit 64
        fi
        seed_fixture_content "$2"
        ;;
    teardown)
        restore_future_posts
        sweep_fixture_content
        ;;
    quarantine)
        # Restore first so an interrupted previous test cannot leave a post
        # parked and then get double-marked here.
        restore_future_posts
        quarantine_future_posts
        ;;
    restore)
        restore_future_posts
        ;;
    *)
        echo "usage: $(basename "$0") <seed|teardown|quarantine|restore> [category-slug]" >&2
        exit 64
        ;;
esac
