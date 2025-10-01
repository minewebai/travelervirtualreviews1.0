<?php
include_once('manual-review-generator.php');

// Register "All Reviews" as a submenu under the main Virtual Reviews menu
add_action('admin_menu', 'tvr_add_admin_submenu');

function tvr_add_admin_submenu() {
    add_submenu_page(
        'traveler-virtual-reviews',
        'All Reviews',
        'All Reviews',
        'manage_options',
        'traveler-virtual-reviews',
        'tvr_admin_page'
    );
}

function tvr_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access.');
    }

    $config = tvr_get_config();
    $post_types = $config['post_types'];
    $languages = $config['languages'];
    $rating_meta_key = $config['rating_meta_key'];
    $message = '';

    // Get location posts
    $locations = get_posts([
        'post_type' => 'location',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'fields' => 'ids'
    ]);
    $location_options = array_map(function($id) {
        return ['id' => $id, 'title' => get_the_title($id)];
    }, $locations);

    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('tvr_action', 'tvr_nonce')) {
        if (isset($_POST['tvr_action'])) {
            if ($_POST['tvr_action'] === 'generate' && isset($_POST['tvr_post_id'], $_POST['tvr_count'], $_POST['tvr_language'], $_POST['tvr_location'], $_POST['tvr_post_language'], $_POST['tvr_season_start_date'], $_POST['tvr_season_end_date'])) {
                $post_id   = absint($_POST['tvr_post_id']);
                $count     = absint($_POST['tvr_count']);
                $language  = sanitize_text_field($_POST['tvr_language']);
                $post_lang = sanitize_text_field($_POST['tvr_post_language']);
                $location  = sanitize_text_field($_POST['tvr_location']);
                $season_start_full = sanitize_text_field($_POST['tvr_season_start_date']);
                $season_end_full = sanitize_text_field($_POST['tvr_season_end_date']);

                if ($count < 1 || $count > 20) {
                    $message = '<div class="error"><p>Number of reviews must be between 1 and 20.</p></div>';
                } elseif (!array_key_exists($language, $languages)) {
                    $message = '<div class="error"><p>Invalid review language selected.</p></div>';
                } elseif (!array_key_exists($post_lang, $languages)) {
                    $message = '<div class="error"><p>Invalid post language selected.</p></div>';
                } elseif (!get_post($post_id) || !array_key_exists(get_post_type($post_id), $post_types)) {
                    $message = '<div class="error"><p>Invalid post ID or post type.</p></div>';
                } elseif (empty($season_start_full) || empty($season_end_full)) {
                    $message = '<div class="error"><p>Season start and end dates must be selected.</p></div>';
                } else {
                    tvr_generate_reviews($post_id, $count, $language, $location, $season_start_full, $season_end_full);
                }
            }
        }
    }

    // Handle sorting and filtering
    $sort_by = isset($_GET['sort_by']) ? sanitize_text_field($_GET['sort_by']) : 'date';
    $sort_order = isset($_GET['sort_order']) && in_array($_GET['sort_order'], ['asc', 'desc']) ? $_GET['sort_order'] : 'desc';
    $filter_post_type = isset($_GET['filter_post_type']) ? sanitize_text_field($_GET['filter_post_type']) : '';

    $args = [
        'number' => 20,
        'status' => 'approve',
        'orderby' => $sort_by === 'post' ? 'post_id' : ($sort_by === 'author' ? 'comment_author' : 'comment_date'),
        'order' => strtoupper($sort_order),
    ];

    if ($filter_post_type && array_key_exists($filter_post_type, $post_types)) {
        $args['post_type'] = $filter_post_type;
    }

    $comments = get_comments($args);

    // Detect selected language for posts (default = current WPML language)
    $selected_post_lang = isset($_POST['tvr_post_language'])
        ? sanitize_text_field($_POST['tvr_post_language'])
        : (function_exists('icl_object_id') ? apply_filters('wpml_current_language', NULL) : 'en');

    ?>
    <div class="wrap">
        <h1>Traveler Virtual Reviews</h1>
        <?php echo $message; ?>
        <form method="post" id="tvr-generate-form">
            <?php wp_nonce_field('tvr_action', 'tvr_nonce'); ?>
            <input type="hidden" name="tvr_action" value="generate">
            <h2>Generate Reviews</h2>
            <table class="form-table">
                <tr>
                    <th><label for="tvr_post_language">Post Language</label></th>
                    <td>
                        <select name="tvr_post_language" id="tvr_post_language" onchange="document.getElementById('tvr-generate-form').submit();">
                            <?php foreach ($languages as $code => $lang) : ?>
                                <option value="<?php echo esc_attr($code); ?>" <?php selected($selected_post_lang, $code); ?>>
                                    <?php echo esc_html($lang['name']); ?> (<?php echo $code; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Select the WPML language of posts to show below.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="tvr_post_id">Select Post</label></th>
                    <td>
                        <select name="tvr_post_id" id="tvr_post_id" required>
                            <option value="">Select a post...</option>
                            <?php
                            foreach ($post_types as $type => $label) {
                                // Get posts only in the selected WPML language
                                $posts = get_posts([
                                    'post_type' => $type,
                                    'numberposts' => -1,
                                    'post_status' => 'publish',
                                    'suppress_filters' => false, // needed for WPML
                                    'lang' => $selected_post_lang,
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
                <tr>
                    <th><label for="tvr_location">Location</label></th>
                    <td>
                        <select name="tvr_location" id="tvr_location">
                            <option value="">Select location (or leave blank for post's location)</option>
                            <?php foreach ($location_options as $loc) : ?>
                                <option value="<?php echo esc_attr($loc['title']); ?>">
                                    <?php echo esc_html($loc['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="tvr_count">Number of Reviews</label></th>
                    <td>
                        <input type="number" name="tvr_count" id="tvr_count" value="5" min="1" max="20" required />
                    </td>
                </tr>
                <tr>
                    <th><label for="tvr_language">Review Language</label></th>
                    <td>
                        <select name="tvr_language" id="tvr_language">
                            <?php
                            $current_lang = function_exists('icl_object_id') ? apply_filters('wpml_current_language', NULL) : 'en';
                            foreach ($languages as $code => $lang) {
                                $selected = ($code === $current_lang) ? 'selected' : '';
                                echo "<option value='{$code}' {$selected}>" . esc_html($lang['name']) . " ({$code})</option>";
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="tvr_season_start_date">Season Start Date</label></th>
                    <td><input type="date" name="tvr_season_start_date" id="tvr_season_start_date" required></td>
                </tr>
                <tr>
                    <th><label for="tvr_season_end_date">Season End Date</label></th>
                    <td><input type="date" name="tvr_season_end_date" id="tvr_season_end_date" required></td>
                </tr>
            </table>
            <?php submit_button('Generate Reviews'); ?>
        </form>
    </div>
    <?php
}
?>