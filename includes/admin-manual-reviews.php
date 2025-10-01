<?php
// Manual Reviews Page for Traveler Virtual Reviews
// DO NOT add add_submenu_page here - handled by main plugin file!

function tvr_manual_reviews_page() {
    // Security nonce for saving reviews
    if (isset($_POST['save_reviews'])) {
        check_admin_referer('tvr_save_manual_reviews', 'tvr_manual_reviews_nonce');
    }

    $num_reviews = isset($_POST['num_reviews']) ? intval($_POST['num_reviews']) : 0;
    $selected_post = isset($_POST['selected_post']) ? intval($_POST['selected_post']) : 0;
    $selected_location = isset($_POST['selected_location']) ? sanitize_text_field($_POST['selected_location']) : '';

    ?>
    <div class="wrap">
        <h1>Manual Reviews Generator</h1>
        <style>
            .tvr-narrow { width: 40px; min-width: 40px; max-width: 60px; text-align: center; }
            .tvr-medium { width: 120px; min-width: 90px; max-width: 180px; }
            .tvr-title { width: 220px; min-width: 150px; max-width: 300px; }
            .tvr-rating { width: 48px; min-width: 40px; max-width: 60px; text-align: center; }
            .tvr-comment { width: 340px; min-width: 240px; }
            .tvr-table textarea { width: 98%; min-width: 230px; min-height: 48px; resize: vertical; }
        </style>
        <form method="post">
            <?php wp_nonce_field('tvr_generate_manual_reviews', 'tvr_generate_manual_reviews_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="selected_post">Select Tour/Post</label></th>
                    <td>
                        <select name="selected_post" id="selected_post" required>
                            <option value="">-- Select --</option>
                            <?php
                            // Group posts by post type with <optgroup> for clarity
                            $post_types = [
                                'st_tours'    => 'Tour',
                                'st_hotel'    => 'Hotel',
                                'st_rental'   => 'Rental',
                                'st_activity' => 'Activity'
                            ];
                            foreach ($post_types as $type => $label) {
                                $posts = get_posts([
                                    'post_type'      => $type,
                                    'posts_per_page' => -1,
                                    'post_status'    => 'publish'
                                ]);
                                if ($posts) {
                                    echo "<optgroup label='{$label}s'>";
                                    foreach ($posts as $post) {
                                        $selected = $selected_post == $post->ID ? 'selected' : '';
                                        echo '<option value="' . esc_attr($post->ID) . '" ' . $selected . '>' . esc_html($post->post_title) . " (ID: {$post->ID})</option>";
                                    }
                                    echo "</optgroup>";
                                }
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="selected_location">Select Location</label></th>
                    <td>
                        <select name="selected_location" id="selected_location" required>
                            <option value="">-- Select Location --</option>
                            <?php
                            // Get location posts (not taxonomy terms)
                            $locations = get_posts([
                                'post_type'      => 'location',
                                'posts_per_page' => -1,
                                'post_status'    => 'publish',
                                'fields'         => 'ids'
                            ]);
                            $location_options = array_map(function($id) {
                                return ['id' => $id, 'title' => get_the_title($id)];
                            }, $locations);
                            foreach ($location_options as $loc) {
                                $selected = $selected_location == $loc['title'] ? 'selected' : '';
                                echo '<option value="' . esc_attr($loc['title']) . '" ' . $selected . '>' . esc_html($loc['title']) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="num_reviews">Number of Reviews</label></th>
                    <td>
                        <input type="number" name="num_reviews" id="num_reviews" min="1" max="100" value="<?php echo esc_attr($num_reviews ?: 10); ?>" required>
                    </td>
                </tr>
            </table>
            <p>
                <input type="submit" name="generate_table" class="button button-primary" value="Generate Table">
            </p>
        </form>

        <?php if ($num_reviews && $selected_post && $selected_location && isset($_POST['generate_table']) && check_admin_referer('tvr_generate_manual_reviews', 'tvr_generate_manual_reviews_nonce')): ?>
            <h2>Manual Entry Table</h2>
            <form method="post">
                <?php wp_nonce_field('tvr_save_manual_reviews', 'tvr_manual_reviews_nonce'); ?>
                <input type="hidden" name="selected_post" value="<?php echo esc_attr($selected_post); ?>">
                <input type="hidden" name="selected_location" value="<?php echo esc_attr($selected_location); ?>">
                <input type="hidden" name="num_reviews" value="<?php echo esc_attr($num_reviews); ?>">
                <table class="wp-list-table widefat fixed striped tvr-table">
                    <thead>
                        <tr>
                            <th class="tvr-narrow">#</th>
                            <th class="tvr-narrow">Name</th>
                            <th class="tvr-narrow">Email</th>
                            <th class="tvr-title">Title</th>
                            <th class="tvr-rating">Rating</th>
                            <th class="tvr-comment">Comment</th>
                            <th class="tvr-medium">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        for($i=1; $i<=$num_reviews; $i++):
                            $fake_name = 'John Doe ' . $i;
                            $fake_email = 'fake' . $i . '@example.com';
                            $fake_title = 'Wonderful Experience';
                        ?>
                        <tr>
                            <td class="tvr-narrow"><?php echo $i; ?></td>
                            <td class="tvr-narrow"><input type="text" name="reviews[<?php echo $i; ?>][name]" value="<?php echo esc_attr($fake_name); ?>" style="width:70px"></td>
                            <td class="tvr-narrow"><input type="email" name="reviews[<?php echo $i; ?>][email]" value="<?php echo esc_attr($fake_email); ?>" style="width:110px"></td>
                            <td class="tvr-title"><input type="text" name="reviews[<?php echo $i; ?>][title]" value="<?php echo esc_attr($fake_title); ?>" style="width:95%"></td>
                            <td class="tvr-rating">★★★★★</td>
                            <td class="tvr-comment"><textarea name="reviews[<?php echo $i; ?>][comment]" rows="2"></textarea></td>
                            <td class="tvr-medium">
                                <input type="date" name="reviews[<?php echo $i; ?>][date]" value="<?php echo esc_attr(date('Y-m-d')); ?>" style="width:110px">
                            </td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
                <p>
                    <input type="submit" name="save_reviews" class="button button-primary" value="Save Reviews">
                </p>
            </form>
        <?php endif; ?>

        <?php
        // Save reviews as real comments with meta fields
        if (isset($_POST['save_reviews']) && isset($_POST['reviews']) && check_admin_referer('tvr_save_manual_reviews', 'tvr_manual_reviews_nonce')) {
            $saved = 0;
            foreach ($_POST['reviews'] as $review) {
                if (empty($review['comment'])) continue; // Only save if comment is entered

                // Use chosen date, and set time part to current time
                $date = !empty($review['date']) ? $review['date'] : date('Y-m-d');
                $comment_date = $date . ' ' . date('H:i:s');

                $comment_data = [
                    'comment_post_ID' => $selected_post,
                    'comment_author' => sanitize_text_field($review['name']),
                    'comment_author_email' => sanitize_email($review['email']),
                    'comment_content' => sanitize_textarea_field($review['comment']),
                    'comment_approved' => 1,
                    'comment_type' => '',
                    'comment_date' => $comment_date,
                ];
                $comment_id = wp_insert_comment($comment_data);
                if ($comment_id) {
                    update_comment_meta($comment_id, 'comment_title', sanitize_text_field($review['title']));
                    update_comment_meta($comment_id, 'comment_rate', 5);
                    // Add all Traveler stat fields as 5 stars AND store the array as a single meta key (as in generator)
                    $review_stats = [
                        'Tour Guide'   => 5,
                        'Location'     => 5,
                        'Service'      => 5,
                        'Friendliness' => 5,
                        'Overall'      => 5,
                    ];
                    foreach ($review_stats as $criterion => $value) {
                        $slug = sanitize_title($criterion);
                        update_comment_meta($comment_id, "st_stat_$slug", $value);
                    }
                    // Store the array in a single meta key as well (for admin display)
                    update_comment_meta($comment_id, 'st_review_stats', $review_stats);
                    $saved++;
                }
            }
            echo '<div class="notice notice-success"><p>' . esc_html($saved) . ' reviews saved.</p></div>';
        }
        ?>
    </div>
    <?php
}
?>