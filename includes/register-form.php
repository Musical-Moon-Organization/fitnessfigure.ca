<?php

function fitness_website_register_form() {
    if (is_user_logged_in()) {
        echo "You are already logged in.";
        return;
    }
    ?>

    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
        <input type="hidden" name="action" value="fitness_website_register">
        <label for="username">Username:</label>
        <input type="text" name="username" id="username" required>
        <br>
        <label for="email">Email:</label>
        <input type="email" name="email" id="email" required>
        <br>
        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required>
        <br>
        <label for="role">Role:</label>
        <select name="role" id="role" required>
            <option value="student">Student</option>
            <option value="instructor">Instructor</option>
        </select>
        <br>
        <input type="submit" value="Register">
    </form>

    <?php
}
add_shortcode('fitness_website_register', 'fitness_website_register_form');

?>