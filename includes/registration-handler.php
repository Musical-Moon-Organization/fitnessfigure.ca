<?php

function register_user() {
    if (!isset($_POST['username']) || !isset($_POST['email']) || !isset($_POST['password']) || !isset($_POST['role'])) {
        wp_redirect(home_url('/register?registration_error=missing_fields'));
        exit;
    }

    $username = sanitize_user($_POST['username']);
    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];
    $role = sanitize_text_field($_POST['role']);

    if (username_exists($username) || email_exists($email)) {
        wp_redirect(home_url('/register?registration_error=user_exists'));
        exit;
    }

    $user_id = wp_create_user($username, $password, $email);

    if (is_wp_error($user_id)) {
        wp_redirect(home_url('/register?registration_error=failed_creation'));
        exit;
    }

    wp_update_user(array('ID' => $user_id, 'role' => $role));

    // Create an empty instructor profile if the role is 'instructor'
    if ($role === 'instructor') {
        $profile_data = array(
            'post_title' => $username,
            'post_content' => '',
            'post_status' => 'publish',
            'post_type' => 'instructor_profile',
            'post_author' => $user_id,
        );
        wp_insert_post($profile_data);
    }

    wp_redirect(home_url('/login?registration_success=1'));
    exit;
}
add_action('admin_post_nopriv_register', 'register_user');
add_action('admin_post_register', 'register_user');

?>