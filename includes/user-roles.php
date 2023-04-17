<?php
function fitness_website_user_roles() {
    add_role(
        'instructor',
        __('Instructor'),
        array(
            'read' => true,
            'edit_posts' => true,
            'delete_posts' => false,
        )
    );

    add_role(
        'student',
        __('Student'),
        array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
        )
    );
}
add_action('init', 'fitness_website_user_roles');
?>