<?php
// Check if a user is logged in
if(is_user_logged_in()) {
    // Get the current user ID
    $user_id = get_current_user_id();

    // Query for products linked to the user
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_product_vendors',
                'value' => $user_id,
            ),
        ),
    );

    $products = get_posts($args);

    // Loop through the products and display them
    foreach ($products as $product) {
        echo do_shortcode('[product_page id="' . $product->ID . '"]');
    }
} else {
    echo 'Please log in to view your bookings.';
}
?>