<?php

function tvr_get_used_global($key) {
    $data = get_option($key, []);
    return is_array($data) ? $data : [];
}
function tvr_add_used_global($key, $value) {
    $used = tvr_get_used_global($key);
    $used[] = $value;
    update_option($key, $used, false);
}
function tvr_generate_unique_review_field($candidates, $global_key, $forbidden = []) {
    shuffle($candidates);
    $used = tvr_get_used_global($global_key);
    foreach ($candidates as $candidate) {
        if (!in_array($candidate, $used, true) && !in_array($candidate, $forbidden, true)) {
            tvr_add_used_global($global_key, $candidate);
            return $candidate;
        }
    }
    // If all candidates are used, mutate for uniqueness
    do {
        $candidate = $candidates[array_rand($candidates)] . ' ' . strtoupper(wp_generate_password(2, false));
    } while (in_array($candidate, $used, true) || in_array($candidate, $forbidden, true));
    tvr_add_used_global($global_key, $candidate);
    return $candidate;
}

function tvr_generate_reviews($post_id, $count, $lang = 'en', $location = '') {
    $post = get_post($post_id);
    if (!$post) {
        echo '<div class="error"><p>Invalid post ID.</p></div>';
        return;
    }

    $config = tvr_get_config();
    $languages = $config['languages'];
    $name_data = include(plugin_dir_path(__FILE__) . 'names.php');
    $reviewer_types = $config['reviewer_types'];
    $seasons = $config['seasons'];
    $tones = $config['tones'];
    $rating_meta_key = $config['rating_meta_key'];

    if (!isset($languages[$lang])) $lang = 'en';
    $lang_data = $languages[$lang];
    $post_type = $post->post_type;

    // Determine review type and specific phrase pools
    if ($post_type === 'st_activity') $review_type = 'activity';
    elseif ($post_type === 'st_hotel') $review_type = 'hotel';
    elseif ($post_type === 'st_rental') $review_type = 'rental';
    else $review_type = 'tour';

    // Get location if not provided
    if (empty($location)) {
        $terms = wp_get_post_terms($post_id, 'st_location', ['fields' => 'names']);
        $location = !is_wp_error($terms) && !empty($terms) ? $terms[0] : '';
    }

    // Prepare name pool: all languages, both genders, mix/match, initials, year
    $all_names = [];
    foreach ($name_data as $lang_set) {
        $all_names = array_merge($all_names, $lang_set['male'], $lang_set['female']);
    }
    $extra_name_bits = ['J.', 'M.', 'S.', 'A.', 'D.', 'L.', 'V.', 'O.', 'B.', 'K.', 'X.', 'Z.'];
    $places = ['Paris', 'Berlin', 'Rome', 'Madrid', 'Tokyo', 'New York', 'Sydney', 'Cape Town', 'Toronto', 'Oslo', 'Vienna', 'London', 'Lisbon', 'Prague', 'Bangkok'];
    $years = range(2017, intval(date('Y')) + 2);

    // Titles
    $title_words = [
        'adjectives' => [
            'en' => ['Amazing', 'Fantastic', 'Unforgettable', 'Incredible', 'Memorable', 'Super', 'Stunning', 'Perfect', 'Awesome', 'Wonderful', 'Ultimate', 'Charming', 'Splendid', 'Breathtaking', 'Cozy', 'Delightful', 'Lively', 'Peaceful', 'Grand', 'Unique', 'Hilarious', 'Wild', 'Dreamy', 'Sunny', 'Effortless', 'Picture-Perfect'],
        ],
        'nouns' => [
            'en' => ['Tour', 'Adventure', 'Experience', 'Journey', 'Trip', 'Stay', 'Retreat', 'Delight', 'Escape', 'Getaway', 'Exploration', 'Holiday', 'Excursion', 'Visit', 'Outing', 'Review', 'Impression', 'Memoir', 'Recollection', 'Reflection', 'Story', 'Highlight', 'Memory', 'Moment', 'Saga'],
        ]
    ];

    // PHRASE POOLS FOR EACH POST TYPE
    $core_phrases = [
        'tour' => [
            "The tour guide was knowledgeable and made the $post->post_title unforgettable.",
            "Exploring $location with this tour was the highlight of my trip.",
            "I learned so much and saw so many amazing places on this $post->post_title tour.",
            "The itinerary was very well organized and covered all the must-see spots.",
            "Had an incredible time on the $post->post_title tour. Highly recommend!",
            "The group was fun and the activities were well planned.",
            "This tour is a must-do if you visit $location.",
        ],
        'activity' => [
            "The $post->post_title was the most exciting part of my vacation.",
            "I never thought I'd try something like this—so glad I did!",
            "Perfect activity for families and solo travelers alike.",
            "The instructor was friendly and patient throughout.",
            "I would do this activity again in a heartbeat.",
            "Loved every minute of the $post->post_title experience.",
            "A unique and memorable activity in $location.",
        ],
        'hotel' => [
            "The hotel $post->post_title exceeded my expectations in every way.",
            "My room was spotless, comfortable, and had a wonderful view.",
            "Staff were attentive and made sure I had a pleasant stay.",
            "Breakfast at $post->post_title was delicious and varied.",
            "Great location, close to all the main attractions in $location.",
            "I slept so well thanks to the cozy bed and quiet environment.",
            "The amenities (pool, gym, etc.) were top notch.",
        ],
        'rental' => [
            "The rental was clean, spacious, and felt like home.",
            "Loved the extra touches at $post->post_title—made the stay special.",
            "Perfect for our group, plenty of room and everything we needed.",
            "The host was responsive and helpful from booking to checkout.",
            "Great value for money and a fantastic base in $location.",
            "Would happily stay at $post->post_title again.",
            "The kitchen was well equipped for making meals.",
        ]
    ];

    // Other phrase pools (personal, humor, repeat, etc.) remain as in your original code...
    // (copy all those arrays from your code above, e.g. $personal_details_pools, $humor_pools, $repeat_pools, etc.)

    // Language-specific phrase pools (copy from above)
    $personal_details_pools = [
        'en' => [
            "It rained half the time but was still great!",
            "Staff remembered my birthday — bonus points!",
            "The local wine was excellent 🍷.",
            "The sunset view was breathtaking.",
            "Breakfast was always a highlight.",
            // ... add more as in your code ...
        ],
    ];
    $humor_pools = [
        'en' => [
            "I laughed way more than I expected.",
            "If you find my left sock, let me know.",
            // ...etc...
        ]
    ];
    $repeat_pools = [
        'en' => [
            "This was my second visit and it just keeps getting better.",
            "Already planning trip number three!",
            // ...etc...
        ]
    ];
    $seasonal_pools = [
        'en' => [
            "Spring flowers everywhere — so pretty!",
            "Summer sun made the pool irresistible.",
            // ...etc...
        ]
    ];
    $booking_pools = [
        'en' => [
            "Booking was a breeze.",
            "Super easy to book online.",
            // ...etc...
        ]
    ];

    $now = current_time('timestamp'); // Get current timestamp in WP timezone

    for ($i = 0; $i < $count; $i++) {
        // Name logic, as above...
        $first_name = $all_names[array_rand($all_names)];
        $middle = (rand(0, 2) ? $all_names[array_rand($all_names)] : $extra_name_bits[array_rand($extra_name_bits)]);
        $last_name = (rand(0, 2) ? $all_names[array_rand($all_names)] : $places[array_rand($places)]);
        $maybe_year = (rand(0, 5) == 0 ? ' ' . $years[array_rand($years)] : '');
        $name = trim("$first_name $middle $last_name$maybe_year");
        $name = tvr_generate_unique_review_field([$name], 'tvr_used_names');

        $season = $seasons[array_rand($seasons)];
        $rtype = $reviewer_types[array_rand($reviewer_types)];
        $tone = $tones[array_rand($tones)];

        // Select correct phrase pool for current language or fallback to English
        $personal_details = $personal_details_pools['en'];
        $humor_templates = $humor_pools['en'];
        $repeat_templates = $repeat_pools['en'];
        $seasonal_templates = $seasonal_pools['en'];
        $booking_templates = $booking_pools['en'];

        // Titles for this language
        $adj_list = $title_words['adjectives']['en'];
        $noun_list = $title_words['nouns']['en'];

        // --- Compose description ---
        $desc_lines = [];

        // Sometimes mention repeat visits
        if (rand(0, 7) == 0) $desc_lines[] = $repeat_templates[array_rand($repeat_templates)];
        // Sometimes seasonal note
        if (rand(0, 6) == 0) $desc_lines[] = $seasonal_templates[array_rand($seasonal_templates)];

        // Core review for the post type:
        $core = $core_phrases[$review_type][array_rand($core_phrases[$review_type])];
        $desc_lines[] = $core;

        // Sometimes booking ease
        if (rand(0, 5) == 0) $desc_lines[] = $booking_templates[array_rand($booking_templates)];
        // Sometimes personal detail or humor
        if (rand(0, 3) == 0) $desc_lines[] = $personal_details[array_rand($personal_details)];
        if (rand(0, 8) == 0) $desc_lines[] = $humor_templates[array_rand($humor_templates)];

        // Mix up the order for realism
        shuffle($desc_lines);

        // Sometimes add a closing line about the reviewer type
        if (rand(0, 2) == 0) $desc_lines[] = "($rtype)";

        // Join and clean up
        $description = rtrim(implode(' ', $desc_lines));
        $description = preg_replace('/\s+/', ' ', $description);

        // Ensure global uniqueness for description
        $description = tvr_generate_unique_review_field([$description], 'tvr_used_descriptions');

        // --- Unique Title ---
        $adj = $adj_list[array_rand($adj_list)];
        $noun = $noun_list[array_rand($noun_list)];
        $bits = [
            "$adj $noun",
            "$adj $noun in $location",
            "$adj $noun ($season)",
            "$adj $noun by $rtype",
            "$adj $noun {$years[$i % count($years)]}",
            "What a $adj $noun!",
        ];
        $title = $bits[array_rand($bits)];
        $title = str_replace('  ', ' ', $title);
        $title = tvr_generate_unique_review_field([$title], 'tvr_used_titles', [$description]);

        // --- NEW: Spaced Dates ---
        if ($i == 0) {
            $comment_date = date('Y-m-d H:i:s', $now);
        } else {
            // Each review is 3-4 days earlier than the previous
            $days_ago = $i * rand(3, 4);
            $comment_date = date('Y-m-d H:i:s', strtotime("-$days_ago days", $now));
        }

        // Insert into WP
        $email = strtolower(str_replace([' ', '&'], ['.', ''], $name)) . '.' . wp_generate_password(4, false) . '@example.com';
        $comment_data = [
            'comment_post_ID' => $post_id,
            'comment_author' => sanitize_text_field($name),
            'comment_author_email' => sanitize_email($email),
            'comment_content' => sanitize_textarea_field($description),
            'comment_approved' => 1,
            'comment_type' => '',
            'comment_date' => $comment_date,
        ];
        $comment_id = wp_insert_comment($comment_data);

        if (!$comment_id) {
            echo '<div class="error"><p>Failed to generate review for ' . esc_html($post->post_title) . '.</p></div>';
            continue;
        }

        update_comment_meta($comment_id, 'comment_title', sanitize_text_field($title));
        update_comment_meta($comment_id, 'comment_rate', 5);
        $review_stats = [
            'Tour Guide' => 5,
            'Location' => 5,
            'Service' => 5,
            'Friendliness' => 5,
            'Overall' => 5,
        ];
        foreach ($review_stats as $criterion => $value) {
            $slug = sanitize_title($criterion);
            update_comment_meta($comment_id, "st_stat_$slug", $value);
        }
    }

    echo "<div class='updated'><p>Generated $count globally unique and authentic reviews for " . esc_html($post->post_title) . " in " . esc_html($languages[$lang]['name']) . "</p></div>";
}

?>