<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Display reviews on single post pages - DISABLED to use theme's review system
/*
function tvr_display_reviews($content) {
    if (is_single() && in_array(get_post_type(), ['st_tours', 'st_hotel', 'st_rental'])) {
        $post_id = get_the_ID();
        $config = tvr_get_config();
        $rating_meta_key = $config['rating_meta_key'];

        // Get current language for WPML
        $current_lang = apply_filters('wpml_current_language', null);

        // Query comments
        $args = [
            'post_id' => $post_id,
            'type' => 'st_reviews',
            'status' => 'approve',
            'number' => 5, // Limit to 5 reviews
        ];
        $comments = get_comments($args);

        ob_start();
        ?>
        <div class="tvr-reviews">
            <h2><?php esc_html_e('Reviews', 'traveler-virtual-reviews'); ?></h2>
            <?php if ($comments) : ?>
                <ul class="tvr-review-list">
                    <?php foreach ($comments as $comment) : ?>
                        <?php
                        $comment_title = get_comment_meta($comment->comment_ID, 'comment_title', true);
                        $comment_rate = get_comment_meta($comment->comment_ID, 'comment_rate', true);
                        $review_stats = get_comment_meta($comment->comment_ID, $rating_meta_key, true);
                        ?>
                        <li class="tvr-review">
                            <h3><?php echo esc_html($comment_title); ?></h3>
<div class="tvr-rating review-star">
    <?php
    $rating = intval($comment_rate);
    for ($i = 0; $i < 5; $i++) {
        if ($i < $rating) {
            echo '<i class="fa fa-star"></i>';
        } else {
            echo '<i class="fa fa-star-o"></i>'; // optional: show empty stars
        }
    }
    ?>
</div>
                            <div class="tvr-content">
                                <?php echo wp_kses_post($comment->comment_content); ?>
                            </div>
                            <?php if ($review_stats) : ?>
                                <ul class="tvr-stats">
                                    <?php foreach ($review_stats as $criterion => $value) : ?>
                                        <li>
                                            <?php echo esc_html($criterion); ?>: 
                                            <?php echo esc_html($value); ?>/5
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            <div class="tvr-author">
                                <?php echo esc_html(sprintf(__('By %s', 'traveler-virtual-reviews'), $comment->comment_author)); ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p><?php esc_html_e('No reviews yet.', 'traveler-virtual-reviews'); ?></p>
            <?php endif; ?>
        </div>
        <style>
            .tvr-reviews { margin: 20px 0; }
            .tvr-review-list { list-style: none; padding: 0; }
            .tvr-review { border-bottom: 1px solid #eee; padding: 10px 0; }
            .tvr-rating { color: #f5a623; font-weight: bold; }
            .tvr-content { margin: 10px 0; }
            .tvr-stats { list-style: none; padding: 0; font-size: 0.9em; }
            .tvr-author { font-style: italic; color: #666; }
        </style>
        <?php
        $content .= ob_get_clean();
    }
    return $content;
}
add_filter('the_content', 'tvr_display_reviews');
*/

// Register text domain for translations
function tvr_load_textdomain() {
    load_plugin_textdomain('traveler-virtual-reviews', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'tvr_load_textdomain');
?>