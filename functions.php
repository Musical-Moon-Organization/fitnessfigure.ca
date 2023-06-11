<?php

require_once get_stylesheet_directory() . '/includes/user-roles.php';
require_once get_stylesheet_directory() . '/includes/register-form.php';
require_once get_stylesheet_directory() . '/includes/login-form.php';
require_once get_stylesheet_directory() . '/includes/registration-handler.php';
require_once get_stylesheet_directory() . '/includes/user-profile.php';

function my_theme_scripts() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( 'parent-style' ) );
    wp_enqueue_style( 'bootstrap-css', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css' );
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


// List all service sellers
function list_all_vendors_shortcode() {
    $vendors = get_terms( 'wcpv_product_vendors', array( 'hide_empty' => false ) );
    $vendor_list = '<ul>';
    
    foreach ( $vendors as $vendor ) {
        $vendor_list .= '<li><a href="' . esc_url( get_term_link( $vendor ) ) . '">' . esc_html( $vendor->name ) . '</a></li>';
    }
    
    $vendor_list .= '</ul>';
    return $vendor_list;
}
add_shortcode( 'list_all_vendors', 'list_all_vendors_shortcode' );


// Fetch vendor name for a given user and set in user account
function vendor_links_shortcode($atts) {
    $username = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    $user = get_user_by('slug', $username);
    $user_id = ($user) ? $user->ID : null;
    
    $vendors = get_terms('wcpv_product_vendors', array('hide_empty' => false));
    $vendor_links = array();

    if (!empty($vendors)) {
        foreach ($vendors as $vendor) {
            $vendor_data = get_term_meta($vendor->term_id, 'vendor_data', true);
            if (isset($vendor_data['admins']) && in_array($user_id, (array)$vendor_data['admins'])) {
                $vendor_link = get_term_link($vendor);
                if (!is_wp_error($vendor_link)) {
                    $vendor_links[] = '<div class="service_link"><h5> <a href="' . esc_url($vendor_link) . '">' . esc_html($vendor->name) . '</a> </h5></div>';
                }
            }
        }
    }

    // Prepare the output
    if (!empty($vendor_links)) {
    $output = '<div class="service_title"><h5>Make a Booking:</h5></div>';
    $output .= implode(', ', $vendor_links);
    } else {
        // No vendor links - show a message
        $output = '<p>No booking details available.</p>';
    }

    return $output;
}
add_shortcode('vendor_links', 'vendor_links_shortcode');



// Show Vendor name beside user in admin
/*
add_action('show_user_profile', 'show_vendor_name');
add_action('edit_user_profile', 'show_vendor_name');

function show_vendor_name($user) {
    $vendors = WCPV_Vendors::get_vendors_from_user($user->ID);
    
    if (!empty($vendors)) {
        echo '<h3>Vendors</h3><ul>';
        foreach ($vendors as $vendor) {
            echo '<li>' . esc_html($vendor->term->name) . '</li>';
        }
        echo '</ul>';
    }
}
*/

// Show/hide buttons based on user session
add_filter( 'wp_nav_menu_items', 'add_login_logout_link', 10, 2 );
function add_login_logout_link( $items, $args ) {
    if ( $args->theme_location == 'Navigation' ) { // is your theme location name
        if ( is_user_logged_in() ) {
            $items .= '<li><a href="'. wp_logout_url() .'">Logout</a></li>';
        } else {
            $items .= '<li><a href="'. wp_login_url() .'">Login</a></li>';
        }
    }
    return $items;
}


// Custom Booking Functions Wih Bookly and Ultimate Member
add_action('user_register', 'create_bookly_staff_member');
add_action('delete_user', 'delete_bookly_staff_member');

function create_bookly_staff_member($user_id) {
    $user = get_userdata($user_id);

    // Check if user has 'Bookable Member' role
    if(in_array('bookable_member', $user->roles)) {
        // Create new Bookly staff member linked to this user
        // You'll need to fill in the details of this part based on how Bookly handles staff members
    }
}

function delete_bookly_staff_member($user_id) {
    // Delete Bookly staff member linked to this user
    // You'll need to fill in the details of this part based on how Bookly handles staff members
}

// Custom Booking with WP Simmple Booking
add_action('user_register', 'create_wpsbc_calendar');

function create_wpsbc_calendar($user_id) {
    global $wpdb;

    $user = get_userdata($user_id);

    // Check if user has 'Subscriber' role
    if(in_array('Subscriber', $user->roles)) {
        // Generate a random iCal hash
        $ical_hash = wp_generate_password(12, false);

        // Create new calendar in WP Simple Booking Calendar's database table
        $wpdb->insert(
            'wp_wpsbc_calendars', 
            array(
                'name' => $user->display_name, // Calendar's name
                'date_created' => current_time('mysql'), // Current date/time
                'date_modified' => current_time('mysql'), // Current date/time
                'status' => 'active', // Active status
                'ical_hash' => $ical_hash // iCal hash
            ),
            array(
                '%s', // name
                '%s', // date_created
                '%s', // date_modified
                '%s', // status
                '%s'  // ical_hash
            )
        );
    }
}


// Linking Dokan Payment/Booking with Ultimate Member
add_action('um_after_register_fields', 'add_custom_role', 10, 1);
function add_custom_role($args) {
   if (isset($args['custom_fields']['role']['options']['vendor'])) {
       $args['custom_fields']['role']['options']['vendor'] = 'Vendor';
   }
   return $args;
}



add_action('um_after_profile', 'show_user_products', 100);

function show_user_products($user_id) {
    // get the current user
    $current_user = wp_get_current_user();

    if ($current_user->ID != $user_id) {
        // current user is not the same as the profile we're viewing
        return;
    }

    // arguments to get the products
    $args = array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'author' => $user_id,
        'posts_per_page' => -1
    );

    // fetch the products
    $products = new WP_Query($args);

    if ($products->have_posts()) {
        echo '<h2>' . __('My Products', 'fitness-figure') . '</h2>';
        echo '<ul class="user-products-list">';
        while ($products->have_posts()) : $products->the_post();
            echo '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
        endwhile;
        echo '</ul>';
    } else {
        echo '<p>' . __('No products found', 'fitness-figure') . '</p>';
    }

    wp_reset_query();
}


// Woocom
/*
function create_vendor_and_booking_product($user_id) {
    // Get the user object
    $user = get_user_by('id', $user_id);

    // Check if the user exists
    if(!$user) return;

    // Create vendor
    $vendor_data = array(
        'name' => $user->user_nicename,
        'admins' => array($user_id),  // Vendor Admins
        // Add more fields as needed
    );
    $vendor_id = WC_Product_Vendors_Utils::create_vendor($vendor_data); // This function may not exist or may be named differently depending on the version of the Product Vendors plugin

    // Create booking product and assign to vendor
    $product = new WC_Product_Booking(); // This might need to be adjusted depending on how you're supposed to create a booking product
    $product->set_name('Booking for ' . $user->user_nicename);
    // Set more product data as needed
    $product_id = $product->save();

    // Link vendor to product
    update_post_meta($product_id, '_product_vendors', $vendor_id);
}
add_action('user_register', 'create_vendor_and_booking_product');
*/


// display booking in profile
$user_id = um_profile_id(); // This fetches the profile ID

// Query to fetch the products associated with the user.
$args = array(
    'post_type' => 'product',
    'posts_per_page' => -1,
    'meta_query' => array(
        array(
            'key' => '_product_vendors', // This should be the meta key used by Product Vendors to link vendors to products.
            'value' => $user_id,
        ),
    ),
);

$products = get_posts($args);

// Loop through the products and output them.
foreach ($products as $product) {
    echo do_shortcode('[product_page id="' . $product->ID . '"]');
}


// link vendor to user profile
// Get the ID of the profile being viewed
$user_id = um_profile_id();

// Get the vendor linked to the user
$vendor_data = WC_Product_Vendors_Utils::get_vendor_data_from_user($user_id); // Note: You may need to find the correct method to get a vendor from a user ID depending on the Product Vendors plugin

// Check if a vendor is linked to the user
if($vendor_data) {
    // Output vendor information
    echo '<h2>Vendor Information</h2>';
    echo '<p>Vendor Name: ' . esc_html($vendor_data['name']) . '</p>';
    // Output more vendor data as needed
}


// Add a new tab to the profile page
add_filter('um_profile_tabs', 'add_custom_profile_tab', 1000);
function add_custom_profile_tab($tabs) {

    $tabs['bookings'] = array(
        'name' => 'Bookings',
        'icon' => 'um-faicon-book',
        'custom' => true,
        'default_subnav' => 'all' // Set a default subnav
    );

    return $tabs;
}

// Add the content to the new tab
add_action('um_profile_content_bookings_all', 'um_profile_content_bookings_default');
function um_profile_content_bookings_default($args) {

    $user_id = um_profile_id();

    // Get the booking products linked to the user
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

    $booking_products = get_posts($args);

    if(!empty($booking_products)) {
        foreach ($booking_products as $product) {
            // Display each product using WooCommerce's [product_page] shortcode
            echo do_shortcode('[product_page id="' . $product->ID . '"]');
        }
    } else {
        echo 'No bookings found.';
    }
}

// Automatically creating a vendor profile when a new user registers
function auto_create_vendor_for_new_user($user_id) {
    // Get user data
    $user_info = get_userdata($user_id);

    // Create new term (vendor) with the user's username
    $vendor = wp_insert_term(
        $user_info->user_login, // the term 
        'wcpv_product_vendors' // the taxonomy
    );

    // Check if there was an error creating the term
    if (!is_wp_error($vendor)) {
        // No error, set the user as admin of the vendor
        $vendor_data = array(
            'admins' => array($user_id)
        );

        update_term_meta($vendor['term_id'], 'vendor_data', $vendor_data);
    }
}
add_action('user_register', 'auto_create_vendor_for_new_user');

@ini_set( 'upload_max_size' , '256M' );
@ini_set( 'post_max_size', '256M');
@ini_set( 'max_execution_time', '300' );