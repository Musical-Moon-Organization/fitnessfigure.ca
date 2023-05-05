<?php
get_header();

// Check if the search query is for users.
$is_user_search = isset( $_GET['post_type'] ) && 'user' === $_GET['post_type'];

if ( $is_user_search ) {
    $search_term = get_search_query();
    $args = array(
        'search'         => '*' . esc_attr( $search_term ) . '*',
        'search_columns' => array( 'user_login', 'user_nicename', 'user_email', 'user_url', 'display_name' ),
    );
    
    // The Query
    $user_query = new WP_User_Query( $args );
    
    // User Loop
    if ( ! empty( $user_query->results ) ) {
        echo '<h2>Search Results for: ' . $search_term . '</h2>';
        echo '<ul>';
        foreach ( $user_query->results as $user ) {
            echo '<li><a href="' . um_user_profile_url( $user->ID ) . '">' . $user->display_name . '</a></li>';
        }
        echo '</ul>';
    } else {
        echo 'No users found.';
    }
} else {
    // Fallback to the default search results template.
    while ( have_posts() ) {
        the_post();
        get_template_part( 'templates', 'search' );
    }
}

get_footer();
