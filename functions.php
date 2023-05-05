<?php 

require_once get_stylesheet_directory() . '/includes/user-roles.php';
require_once get_stylesheet_directory() . '/includes/register-form.php';
require_once get_stylesheet_directory() . '/includes/login-form.php';
require_once get_stylesheet_directory() . '/includes/registration-handler.php';
require_once get_stylesheet_directory() . '/includes/user-profile.php';

function my_theme_scripts() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( 'parent-style' ) );
    //wp_enqueue_style( 'bootstrap-css', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css' );
    wp_enqueue_script( 'bootstrap-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js', array( 'jquery' ), '', true );
    wp_enqueue_script( 'jquery', 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js', array(), '3.5.1', true );
}
add_action( 'wp_enqueue_scripts', 'my_theme_scripts' );

// Implemented search algorithm that can handle approximate string matching
// Added the Levenshtein distance algorithm To allow for typos in the search 
function levenshtein_distance( $string1, $string2 ) {
    $len1 = strlen( $string1 );
    $len2 = strlen( $string2 );

    if ( $len1 == 0 ) {
        return $len2;
    }

    if ( $len2 == 0 ) {
        return $len1;
    }

    $distance = array();

    for ( $i = 0; $i <= $len1; $i++ ) {
        $distance[ $i ] = array();
        $distance[ $i ][0] = $i;
    }

    for ( $j = 0; $j <= $len2; $j++ ) {
        $distance[0][ $j ] = $j;
    }

    for ( $i = 1; $i <= $len1; $i++ ) {
        for ( $j = 1; $j <= $len2; $j++ ) {
            $cost = ( $string1[ $i - 1 ] == $string2[ $j - 1 ] ) ? 0 : 1;

            $distance[ $i ][ $j ] = min(
                $distance[ $i - 1 ][ $j ] + 1,
                $distance[ $i ][ $j - 1 ] + 1,
                $distance[ $i - 1 ][ $j - 1 ] + $cost
            );
        }
    }

    return $distance[ $len1 ][ $len2 ];
}


// modified function calculates the Levenshtein distance between the search term and custom field values
function my_child_theme_um_custom_fields_search( $query_args, $args ) {
    $search_term = isset( $args['search'] ) ? sanitize_text_field( $args['search'] ) : '';

    if ( ! empty( $search_term ) ) {
        // Fetch custom field keys based on the 'checkbox_professional_details' meta key.
        global $wpdb;
        $custom_fields = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT meta_key FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
                'checkbox_professional_details_%'
            )
        );

        $meta_query = array( 'relation' => 'OR' );
        $matched_users_found = false;

        // Set a threshold for the Levenshtein distance.
        $levenshtein_threshold = 2;

        foreach ( $custom_fields as $custom_field ) {
            // Get user IDs with the current custom field.
            $user_ids = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s",
                    $custom_field
                )
            );

            $matched_user_ids = array();

            // Compare the search term with the custom field values.
            foreach ( $user_ids as $user_id ) {
                $custom_field_value = get_user_meta( $user_id, $custom_field, true );

                // Calculate the Levenshtein distance.
                $levenshtein_dist = levenshtein_distance( $search_term, $custom_field_value );

                // Check if the Levenshtein distance is within the threshold.
                if ( $levenshtein_dist <= $levenshtein_threshold ) {
                    $matched_user_ids[] = $user_id;
                }
            }

            // Add matched users to the meta_query.
            if ( ! empty( $matched_user_ids ) ) {
                $matched_users_found = true;
                $meta_query[] = array(
                    'key'     => $custom_field,
                    'value'   => $matched_user_ids,
                    'compare' => 'IN',
                );
            }
        }

        if ( $matched_users_found ) {
            if ( isset( $query_args['meta_query'] ) && is_array( $query_args['meta_query'] ) ) {
                $query_args['meta_query'] = array_merge( $query_args['meta_query'], $meta_query );
            } else {
                $query_args['meta_query'] = $meta_query;
            }
        }
    }

    return $query_args;
}
add_filter( 'um_prepare_user_query_args', 'my_child_theme_um_custom_fields_search', 10, 2 );





/*
// Extending the Ultimate Plugin 'Search' to utilize the custom fields
function my_child_theme_um_custom_fields_search( $query_args, $args ) {
    $search_term = isset( $args['search'] ) ? sanitize_text_field( $args['search'] ) : '';

    if ( ! empty( $search_term ) ) {
        // Fetch custom field keys based on the 'checkbox_professional_details' meta key.
        global $wpdb;
        $custom_fields = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT meta_key FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
                'checkbox_professional_details_%'
            )
        );

        $meta_query = array( 'relation' => 'OR' );

        foreach ( $custom_fields as $custom_field ) {
            $meta_query[] = array(
                'key'     => $custom_field,
                'value'   => $search_term,
                'compare' => 'LIKE',
            );
        }

        if ( isset( $query_args['meta_query'] ) && is_array( $query_args['meta_query'] ) ) {
            $query_args['meta_query'] = array_merge( $query_args['meta_query'], $meta_query );
        } else {
            $query_args['meta_query'] = $meta_query;
        }
    }

    return $query_args;
}
add_filter( 'um_prepare_user_query_args', 'my_child_theme_um_custom_fields_search', 10, 2 );
*/


//  Custom functions to display top-rated members
function my_child_theme_top_rated_members( $atts ) {
    $atts = shortcode_atts(
        array(
            'number' => 5, // The number of top-rated members to display.
        ),
        $atts,
        'top_rated_members'
    );

    $args = array(
        'number'    => $atts['number'],
        'orderby'   => 'meta_value_num',
        'meta_key'  => '_um_reviews_avg',
        'order'     => 'DESC',
    );

    $user_query = new WP_User_Query( $args );
    $top_rated_users = $user_query->get_results();

    ob_start();

    if ( ! empty( $top_rated_users ) ) {
        echo '<ul class="top-rated-members">';

        foreach ( $top_rated_users as $user ) {
            $user_rating = get_user_meta( $user->ID, 'um_reviews_avg', true );
            echo '<li>';
            echo '<a href="' . um_user_profile_url( $user->ID ) . '">' . esc_html( $user->display_name ) . '</a>';
            echo ' - Rating: ' . esc_html( $user_rating );
            echo '</li>';
        }

        echo '</ul>';
    } else {
        echo '<p>No top-rated members found.</p>';
    }

    return ob_get_clean();
}
add_shortcode( 'top_rated_members', 'my_child_theme_top_rated_members' );
