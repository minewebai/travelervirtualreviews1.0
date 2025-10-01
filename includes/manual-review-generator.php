<?php
function tvr_manual_review_generator_page() {
    echo '<div class="wrap"><h1>Manual Review Generator</h1>';

    if (!empty($_POST['generate_count'])) {
        $count = intval($_POST['generate_count']);
        echo '<h2>Generated Reviews</h2>';
        echo '<form method="post"><table class="widefat fixed striped">';
        echo '<thead><tr><th>Name</th><th>Email</th><th>Rating</th><th>Review Title</th><th>Review Content</th><th>Date</th></tr></thead><tbody>';

        // Set the base date as today in WP timezone
        $now = current_time('timestamp');

        for ($i = 0; $i < $count; $i++) {
            $name = 'User' . rand(1000, 9999);
            $email = strtolower($name) . '@example.com';

            // Calculate date for each review
            if ($i == 0) {
                $review_date = date('Y-m-d', $now);
            } else {
                // Each review is 3-4 days earlier than the previous
                $days_ago = $i * rand(3, 4);
                $review_date = date('Y-m-d', strtotime("-$days_ago days", $now));
            }

            echo '<tr>';
            echo '<td><input type="text" name="review['.$i.'][name]" value="'.$name.'" /></td>';
            echo '<td><input type="email" name="review['.$i.'][email]" value="'.$email.'" /></td>';
            echo '<td>★★★★★</td>';
            echo '<td><input type="text" name="review['.$i.'][title]" /></td>';
            echo '<td><textarea name="review['.$i.'][content]"></textarea></td>';
            echo '<td><input type="date" name="review['.$i.'][date]" value="'.$review_date.'" /></td>';
            echo '</tr>';
        }

        echo '</tbody></table><br><input type="submit" class="button-primary" value="Save Reviews (Coming Soon)" disabled /></form>';
    }

    echo '<form method="post"><p>Enter number of reviews to generate: 
        <input type="number" name="generate_count" min="1" max="50" required>
        <input type="submit" class="button" value="Generate"></p></form></div>';
}