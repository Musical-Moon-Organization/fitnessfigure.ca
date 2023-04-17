<?php

function fitness_website_instructor_profile() {
    $labels = array(
        'name' => __('Instructor Profiles'),
        'singular_name' => __('Instructor Profile'),
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'instructor-profiles'),
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'show_in_rest' => true,
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'capabilities' => array(
            'edit_others_posts' => 'edit_others_instructor_profiles',
            'delete_others_posts' => 'delete_others_instructor_profiles',
            'delete_private_posts' => 'delete_private_instructor_profiles',
        ),
    );

    register_post_type('instructor_profile', $args);
}
add_action('init', 'fitness_website_instructor_profile');

?>