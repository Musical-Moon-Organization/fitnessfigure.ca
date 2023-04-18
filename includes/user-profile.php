<?php
function fitness_figure_user_profile() {
    ob_start();
    
    if (!is_user_logged_in()) {
        echo 'You must be logged in to edit your profile.';
        return;
    }

    $user_id = get_current_user_id();
    
    if (isset($_POST['update_profile'])) {
        fitness_figure_update_user_profile($user_id);
    }

    $user_data = get_userdata($user_id);
    $user_meta = get_user_meta($user_id);
    ?>

    <form action="" method="post" enctype="multipart/form-data">
        <label for="display_name">Display Name:</label>
        <input type="text" name="display_name" id="display_name" value="<?php echo esc_attr($user_data->display_name); ?>" required>
        <br>
        <label for="description">About:</label>
        <textarea name="description" id="description" rows="5" cols="30"><?php echo esc_textarea($user_meta['description'][0]); ?></textarea>
        <br>
        <label for="phone">Phone:</label>
        <input type="tel" name="phone" id="phone" value="<?php echo esc_attr($user_meta['phone'][0]); ?>">
        <br>
        <label for="profile_picture">Profile Picture:</label>
        <input type="file" name="profile_picture" id="profile_picture">
        <br>
        <input type="submit" name="update_profile" value="Update Profile">
    </form>

    <?php
    return ob_get_clean();
}
add_shortcode('edit_profile', 'fitness_figure_user_profile');

// View User Profile
function fitness_figure_view_user_profile($atts) {
    ob_start();

    if (isset($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);
    } else {
        echo "No user specified.";
        return;
    }

    $user_data = get_userdata($user_id);
    if ($user_data === false) {
        echo "User not found.";
        return;
    }

    $user_meta = get_user_meta($user_id);

    // Display profile information
    echo "<h2>" . esc_html($user_data->display_name) . "</h2>";
    echo "<p>Username: " . esc_html($user_data->user_login) . "</p>";
    echo "<p>About: " . nl2br(esc_html($user_meta['description'][0])) . "</p>";
    echo "<p>Phone: " . esc_html($user_meta['phone'][0]) . "</p>";

    return ob_get_clean();
}
add_shortcode('view_user_profile', 'fitness_figure_view_user_profile');

// List Profiles
function fitness_figure_list_profiles() {
    ob_start();

    // Fetch all users with the roles 'instructor' and 'student'
    $users = get_users(array(
        'role__in' => array('instructor', 'student')
    ));

    // Display users as a list with links to view their profiles
    echo '<ul class="user-profile-list">';
    foreach ($users as $user) {
        $profile_url = home_url("/view-profile?user_id={$user->ID}");
        echo '<li><a href="' . esc_url($profile_url) . '">' . esc_html($user->display_name) . '</a></li>';
    }
    echo '</ul>';

    return ob_get_clean();
}
add_shortcode('list_profiles', 'fitness_figure_list_profiles');


// View own profile
function fitness_figure_view_own_profile($atts) {
    ob_start();

    $atts = shortcode_atts(array('user_id' => 0), $atts, 'view_own_profile');
    $user_id = intval($atts['user_id']);
    
    if ($user_id === 0) {
        if (!is_user_logged_in()) {
            echo 'You must be logged in to view your profile.';
            return;
        }
        $user_id = get_current_user_id();
    }

    $user_data = get_userdata($user_id);
    $user_meta = get_user_meta($user_id);

    // Display profile information
    echo "<h2>" . esc_html($user_data->display_name) . "</h2>";
    echo "<p>About: " . nl2br(esc_html($user_meta['description'][0])) . "</p>";
    echo "<p>Phone: " . esc_html($user_meta['phone'][0]) . "</p>";

    return ob_get_clean();
}
add_shortcode('view_own_profile', 'fitness_figure_view_own_profile');

// Update User profile
function fitness_figure_update_user_profile($user_id) {
    if (isset($_POST['display_name'])) {
        wp_update_user(array('ID' => $user_id, 'display_name' => sanitize_text_field($_POST['display_name'])));
    }

    if (isset($_POST['description'])) {
        update_user_meta($user_id, 'description', sanitize_textarea_field($_POST['description']));
    }

    if (isset($_POST['phone'])) {
        update_user_meta($user_id, 'phone', sanitize_text_field($_POST['phone']));
    }

    if (!empty($_FILES['profile_picture']['tmp_name'])) {
        $upload_overrides = array('test_form' => false);
        $uploaded_file = wp_handle_upload($_FILES['profile_picture'], $upload_overrides);

        if (!isset($uploaded_file['error'])) {
            $file_url = $uploaded_file['url'];
            update_user_meta($user_id, 'profile_picture', $file_url);
        }
    }
}

?>