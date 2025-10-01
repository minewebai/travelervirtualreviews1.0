<?php
// Edit Reviews Submenu for Traveler Virtual Reviews

add_action('admin_menu', function() {
    add_submenu_page(
        'virtual-reviews',
        'Edit Reviews',
        'Edit Reviews',
        'manage_options',
        'tvr-edit-reviews',
        'tvr_edit_reviews_page'
    );
});

function tvr_edit_reviews_page() {
    // Handle Save All
    if (isset($_POST['save_all']) && isset($_POST['reviews'])) {
        foreach ($_POST['reviews'] as $cid => $fields) {
            tvr_save_review($cid, $fields);
        }
        echo '<div class="notice notice-success"><p>All edited reviews have been saved.</p></div>';
    }
    // Handle Save Single
    if (isset($_POST['save_single']) && isset($_POST['review_id']) && isset($_POST['reviews'][$_POST['review_id']])) {
        $cid = intval($_POST['review_id']);
        tvr_save_review($cid, $_POST['reviews'][$cid]);
        echo '<div class="notice notice-success"><p>Review #'.$cid.' saved.</p></div>';
    }
    // Handle Delete Single
    if (isset($_POST['delete_review']) && isset($_POST['delete_review_id'])) {
        $del_id = intval($_POST['delete_review_id']);
        wp_delete_comment($del_id, true);
        echo '<div class="notice notice-warning"><p>Review #'.$del_id.' deleted.</p></div>';
    }
    // Handle Delete Selected
    if (isset($_POST['delete_selected']) && isset($_POST['selected_reviews'])) {
        $deleted = 0;
        foreach ($_POST['selected_reviews'] as $del_id) {
            $del_id = intval($del_id);
            wp_delete_comment($del_id, true);
            $deleted++;
        }
        echo '<div class="notice notice-warning"><p>Deleted '.$deleted.' selected review(s).</p></div>';
    }

    $selected_post = isset($_POST['selected_post']) ? intval($_POST['selected_post']) : 0;
    $post_types = ['st_tours' => 'Tour', 'st_activity' => 'Activity', 'st_hotel' => 'Hotel', 'st_rental' => 'Rental'];
    ?>
    <div class="wrap">
        <h1>Edit Reviews</h1>
        <style>
            .tvr-table th.tvr-id,
            .tvr-table td.tvr-id {
                width: 40px;
                text-align: center;
                padding-left: 0;
                padding-right: 0;
            }
            .tvr-table th.tvr-rating,
            .tvr-table td.tvr-rating {
                width: 60px;
                min-width: 45px;
                max-width: 70px;
                text-align: center;
                padding-left: 0;
                padding-right: 0;
            }
            .tvr-table th.tvr-comment,
            .tvr-table td.tvr-comment {
                width: 400px;
                min-width: 280px;
            }
            .tvr-table textarea {
                width: 98%;
                min-width: 250px;
                min-height: 60px;
                resize: vertical;
            }
            .tvr-table input[type="number"].tvr-rating-input {
                width: 38px;
                text-align: center;
            }
            .tvr-delete-btn {
                background-color: #dc3232;
                color: #fff;
                border: none;
                border-radius: 3px;
                padding: 2px 10px;
                margin-left: 2px;
                cursor: pointer;
            }
            .tvr-delete-btn:hover {
                background-color: #a00;
            }
        </style>
        <form method="post" id="tvr-edit-select-form">
            <table class="form-table">
                <tr>
                    <th><label for="selected_post">Select Tour/Post</label></th>
                    <td>
                        <select name="selected_post" id="selected_post" onchange="this.form.submit()">
                            <option value="">-- Select --</option>
                            <?php
                            foreach ($post_types as $type => $label) {
                                $posts = get_posts([
                                    'post_type' => $type,
                                    'posts_per_page' => -1,
                                    'post_status' => 'publish'
                                ]);
                                if ($posts) {
                                    echo "<optgroup label='{$label}s'>";
                                    foreach ($posts as $post) {
                                        $selected = $selected_post == $post->ID ? 'selected' : '';
                                        echo '<option value="' . esc_attr($post->ID) . '" ' . $selected . '>' . esc_html($post->post_title) . '</option>';
                                    }
                                    echo "</optgroup>";
                                }
                            }
                            ?>
                        </select>
                    </td>
                </tr>
            </table>
        </form>
        <form method="post" id="tvr-edit-table-form">
            <input type="hidden" name="selected_post" value="<?php echo esc_attr($selected_post); ?>">
            <?php if ($selected_post): ?>
                <?php
                $comments = get_comments([
                    'post_id' => $selected_post,
                    'status' => 'approve',
                    'number' => 100,
                    'orderby' => 'comment_date',
                    'order' => 'DESC',
                ]);
                if ($comments): ?>
                    <p>
                        <button type="submit" name="save_all" class="button button-primary">Save All</button>
                        <button type="submit" name="delete_selected" class="button tvr-delete-btn">Delete Selected</button>
                    </p>
                    <table class="wp-list-table widefat fixed striped tvr-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="tvr-select-all" style="margin:0;"></th>
                                <th class="tvr-id">ID</th>
                                <th>Author</th>
                                <th>Title</th>
                                <th class="tvr-comment">Comment</th>
                                <th>Date</th>
                                <th class="tvr-rating">Tour Guide</th>
                                <th class="tvr-rating">Location</th>
                                <th class="tvr-rating">Service</th>
                                <th class="tvr-rating">Friendliness</th>
                                <th class="tvr-rating">Overall</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($comments as $comment): 
                            $comment_title = get_comment_meta($comment->comment_ID, 'comment_title', true);
                            $criteria_labels = [
                                'Tour Guide' => 'tour-guide',
                                'Location' => 'location',
                                'Service' => 'service',
                                'Friendliness' => 'friendliness',
                                'Overall' => 'overall'
                            ];
                            $criteria_vals = [];
                            foreach ($criteria_labels as $label => $slug) {
                                $criteria_vals[$slug] = get_comment_meta($comment->comment_ID, "st_stat_$slug", true);
                            }
                        ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="selected_reviews[]" value="<?php echo $comment->comment_ID; ?>" class="tvr-select-review">
                                </td>
                                <td class="tvr-id"><?php echo $comment->comment_ID; ?></td>
                                <td><input type="text" name="reviews[<?php echo $comment->comment_ID; ?>][author]" value="<?php echo esc_attr($comment->comment_author); ?>"></td>
                                <td><input type="text" name="reviews[<?php echo $comment->comment_ID; ?>][title]" value="<?php echo esc_attr($comment_title); ?>"></td>
                                <td class="tvr-comment"><textarea name="reviews[<?php echo $comment->comment_ID; ?>][content]" rows="4"><?php echo esc_textarea($comment->comment_content); ?></textarea></td>
                                <td><input type="datetime-local" name="reviews[<?php echo $comment->comment_ID; ?>][date]" value="<?php echo esc_attr(date('Y-m-d\TH:i', strtotime($comment->comment_date))); ?>"></td>
                                <td class="tvr-rating"><input type="number" min="1" max="5" class="tvr-rating-input" name="reviews[<?php echo $comment->comment_ID; ?>][tour-guide]" value="<?php echo esc_attr($criteria_vals['tour-guide'] ?: 5); ?>"></td>
                                <td class="tvr-rating"><input type="number" min="1" max="5" class="tvr-rating-input" name="reviews[<?php echo $comment->comment_ID; ?>][location]" value="<?php echo esc_attr($criteria_vals['location'] ?: 5); ?>"></td>
                                <td class="tvr-rating"><input type="number" min="1" max="5" class="tvr-rating-input" name="reviews[<?php echo $comment->comment_ID; ?>][service]" value="<?php echo esc_attr($criteria_vals['service'] ?: 5); ?>"></td>
                                <td class="tvr-rating"><input type="number" min="1" max="5" class="tvr-rating-input" name="reviews[<?php echo $comment->comment_ID; ?>][friendliness]" value="<?php echo esc_attr($criteria_vals['friendliness'] ?: 5); ?>"></td>
                                <td class="tvr-rating"><input type="number" min="1" max="5" class="tvr-rating-input" name="reviews[<?php echo $comment->comment_ID; ?>][overall]" value="<?php echo esc_attr($criteria_vals['overall'] ?: 5); ?>"></td>
                                <td>
                                    <button class="button" name="save_single" value="1" type="submit" onclick="document.getElementById('review_id').value='<?php echo $comment->comment_ID; ?>';">Save</button>
                                    <button class="tvr-delete-btn" name="delete_review" value="1" type="submit" onclick="document.getElementById('delete_review_id').value='<?php echo $comment->comment_ID; ?>';">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <input type="hidden" name="review_id" id="review_id" value="">
                    <input type="hidden" name="delete_review_id" id="delete_review_id" value="">
                    <p>
                        <button type="submit" name="save_all" class="button button-primary">Save All</button>
                        <button type="submit" name="delete_selected" class="button tvr-delete-btn">Delete Selected</button>
                    </p>
                <?php else: ?>
                    <p>No reviews found for this post.</p>
                <?php endif; ?>
            <?php endif; ?>
        </form>
    </div>
    <script>
    // Save single: set hidden review_id
    document.querySelectorAll('button[name="save_single"]').forEach(btn => {
      btn.addEventListener('click', function(e) {
        document.getElementById('review_id').value = this.closest('tr').querySelector('input[type="text"],textarea').name.match(/\[(\d+)\]/)[1];
      });
    });
    // Delete single: set hidden delete_review_id
    document.querySelectorAll('button[name="delete_review"]').forEach(btn => {
      btn.addEventListener('click', function(e) {
        document.getElementById('delete_review_id').value = this.closest('tr').querySelector('input[type="text"],textarea').name.match(/\[(\d+)\]/)[1];
      });
    });
    // Select all checkboxes
    document.getElementById('tvr-select-all')?.addEventListener('change', function() {
      document.querySelectorAll('.tvr-select-review').forEach(cb => cb.checked = this.checked);
    });
    </script>
    <?php
}

// Save review data utility
function tvr_save_review($comment_id, $fields) {
    $comment_id = intval($comment_id);
    $update = [];
    if (isset($fields['author'])) $update['comment_author'] = sanitize_text_field($fields['author']);
    if (isset($fields['content'])) $update['comment_content'] = sanitize_textarea_field($fields['content']);
    if (isset($fields['date'])) $update['comment_date'] = date('Y-m-d H:i:s', strtotime($fields['date']));
    if (!empty($update)) {
        $update['comment_ID'] = $comment_id;
        wp_update_comment($update);
    }
    if (isset($fields['title'])) update_comment_meta($comment_id, 'comment_title', sanitize_text_field($fields['title']));
    // Save all criteria
    $criteria = [];
    $labels = ['tour-guide','location','service','friendliness','overall'];
    foreach ($labels as $slug) {
        if (isset($fields[$slug])) {
            $val = max(1, min(5, intval($fields[$slug])));
            update_comment_meta($comment_id, "st_stat_$slug", $val);
            $criteria[tvr_criteria_label($slug)] = $val;
        }
    }
    // Save the array like the generator
    if ($criteria) update_comment_meta($comment_id, 'st_review_stats', $criteria);
}
function tvr_criteria_label($slug) {
    switch ($slug) {
        case 'tour-guide': return 'Tour Guide';
        case 'location': return 'Location';
        case 'service': return 'Service';
        case 'friendliness': return 'Friendliness';
        case 'overall': return 'Overall';
        default: return $slug;
    }
}
?>