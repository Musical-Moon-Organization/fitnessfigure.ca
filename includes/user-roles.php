<!-- Custom user roles -->
<?php
function create_custom_roles() {
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
add_action('init', 'create_custom_roles');
?>