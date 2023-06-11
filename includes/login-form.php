<?php
/* Login Form */
function login_form() {
    ob_start(); // Start output buffering

    if (is_user_logged_in()) {
        echo "You are already logged in.";
        return;
    }
    ?>

    <form action="<?php echo esc_url(site_url('wp-login.php', 'login_post')); ?>" method="post">
        <label for="log">Username:</label>
        <input type="text" name="log" id="log" required>
        <br>
        <label for="pwd">Password:</label>
        <input type="password" name="pwd" id="pwd" required>
        <br>
        <input type="submit" value="Login">
    </form>

    <?php
    return ob_get_clean(); // Return the buffered content
}
add_shortcode('login', 'login_form');
?>