<?php
/*
Plugin Name: Traveler Virtual Reviews
Description: Generate and manage dynamic 5-star reviews for WP-Traveler tours, activities, hotels, and rentals with multilingual support.
Version: 5.2
Author: xAI Custom
License: GPL-2.0
*/

if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'includes/config.php';
require_once plugin_dir_path(__FILE__) . 'includes/review-generator.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-interface.php';
require_once plugin_dir_path(__FILE__) . 'includes/frontend.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-manual-reviews.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-edit-reviews.php';

// Enqueue admin styles on all relevant plugin pages
function tvr_enqueue_admin_styles() {
    if (
        isset($_GET['page']) && in_array($_GET['page'], [
            'traveler-virtual-reviews',
            'tvr-manual-reviews',
            'tvr-edit-reviews',
            'tvr-fix-scores'
        ])
    ) {
        wp_enqueue_style('tvr-admin-styles', plugins_url('css/admin-style.css', __FILE__), [], '5.1');
    }
}
add_action('admin_enqueue_scripts', 'tvr_enqueue_admin_styles');

// Add main menu and all submenus
add_action('admin_menu', function() {
    add_menu_page(
        'Traveler Virtual Reviews',
        'Virtual Reviews',
        'manage_options',
        'traveler-virtual-reviews',
        'tvr_admin_page',
        'dashicons-star-filled',
        6
    );
    add_submenu_page(
        'traveler-virtual-reviews',
        'Manual Reviews',
        'Manual Reviews',
        'manage_options',
        'tvr-manual-reviews',
        'tvr_manual_reviews_page'
    );
    add_submenu_page(
        'traveler-virtual-reviews',
        'Edit Reviews',
        'Edit Reviews',
        'manage_options',
        'tvr-edit-reviews',
        'tvr_edit_reviews_page'
    );
    add_submenu_page(
        'traveler-virtual-reviews',
        'Fix Review Scores',
        'Fix Review Scores',
        'manage_options',
        'tvr-fix-scores',
        'tvr_fix_scores_page'
    );
});

// Include standard comments in review score
function tvr_include_reviews_in_avg_rate($avg_rate, $post_id) {
    $comments = get_comments([
        'post_id' => $post_id,
        'type'    => '',
        'status'  => 'approve',
    ]);
    $total_rate = 0; $count = 0;
    foreach ($comments as $comment) {
        $rate = get_comment_meta($comment->comment_ID, 'comment_rate', true);
        if ($rate && is_numeric($rate)) {
            $total_rate += floatval($rate);
            $count++;
        }
    }
    $new_avg = $count > 0 ? round($total_rate / $count, 1) : 0;
    return $new_avg > 0 ? $new_avg : $avg_rate;
}
add_filter('st_get_review_rate', 'tvr_include_reviews_in_avg_rate', 10, 2);

// --- NEW: Force update review stats meta for a post ---
function tvr_force_update_review_stats($post_id) {
    // Get all approved comments for this post
    $comments = get_comments([
        'post_id' => $post_id,
        'status'  => 'approve',
    ]);
    // Count ratings
    $stats = [
        '5' => 0,
        '4' => 0,
        '3' => 0,
        '2' => 0,
        '1' => 0,
    ];
    foreach ($comments as $comment) {
        $rate = get_comment_meta($comment->comment_ID, 'comment_rate', true);
        $rate = intval($rate);
        if ($rate >= 1 && $rate <= 5) {
            $stats["$rate"]++;
        }
    }
    // Update meta (adjust meta keys as needed for your theme/plugin)
    update_post_meta($post_id, 'review_stats', $stats);
    update_post_meta($post_id, '_review_stats', $stats);
    update_post_meta($post_id, 'st_review_stats', $stats);
}

// The Fix Review Scores page function
function tvr_fix_scores_page() {
    $message = '';
    $config = function_exists('tvr_get_config') ? tvr_get_config() : [];
    $post_types = isset($config['post_types']) ? $config['post_types'] : [
        'st_tours' => 'Tour',
        'st_hotel' => 'Hotel',
        'st_rental' => 'Rental',
        'st_activity' => 'Activity'
    ];
    $languages = isset($config['languages']) ? $config['languages'] : [];

    // detect selected WPML language
    $selected_lang = isset($_POST['tvr_fix_post_language'])
        ? sanitize_text_field($_POST['tvr_fix_post_language'])
        : (function_exists('icl_object_id') ? apply_filters('wpml_current_language', NULL) : 'en');

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('tvr_fix_scores_action', 'tvr_fix_scores_nonce')) {
        $fix_post_id = isset($_POST['tvr_fix_post_id']) ? absint($_POST['tvr_fix_post_id']) : 0;
        if ($fix_post_id && function_exists('traveler_update_full_review_scores')) {
            traveler_update_full_review_scores($fix_post_id);
            // --- NEW: Force update review summary meta ---
            tvr_force_update_review_stats($fix_post_id);
            $message = "<div class='updated'><p>Review scores fixed for post ID {$fix_post_id}.</p></div>";
        } else {
            $message = "<div class='error'><p>Error: Invalid post or fixer function not found.</p></div>";
        }
    }
    ?>
    <div class="wrap">
        <h1>Fix Review Scores</h1>
        <?php echo $message; ?>
        <form method="post">
            <?php wp_nonce_field('tvr_fix_scores_action', 'tvr_fix_scores_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="tvr_fix_post_language">Post Language</label></th>
                    <td>
                        <select name="tvr_fix_post_language" id="tvr_fix_post_language" onchange="this.form.submit();">
                            <?php foreach ($languages as $code => $lang): ?>
                                <option value="<?php echo esc_attr($code); ?>" <?php selected($selected_lang, $code); ?>>
                                    <?php echo esc_html($lang['name']); ?> (<?php echo $code; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Select the WPML language of posts to show below.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="tvr_fix_post_id">Select Post</label></th>
                    <td>
                        <select name="tvr_fix_post_id" id="tvr_fix_post_id" required>
                            <option value="">Select a post...</option>
                            <?php
                            foreach ($post_types as $type => $label) {
                                // WPML filtered posts
                                $posts = get_posts([
                                    'post_type' => $type,
                                    'numberposts' => -1,
                                    'post_status' => 'publish',
                                    'suppress_filters' => false,
                                    'lang' => $selected_lang,
                                ]);
                                if ($posts) {
                                    echo "<optgroup label='{$label}s'>";
                                    foreach ($posts as $post) {
                                        echo "<option value='{$post->ID}'>" . esc_html($post->post_title) . " (ID: {$post->ID})</option>";
                                    }
                                    echo "</optgroup>";
                                }
                            }
                            ?>
                        </select>
                    </td>
                </tr>
            </table>
            <button type="submit" class="button button-primary">Fix Review Scores for This Post</button>
        </form>
    </div>
    <?php
}
?>
