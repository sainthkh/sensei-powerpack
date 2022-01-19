<?php

// redirection

add_action('template_redirect', 'spp_restrict_content');

function spp_restrict_content() {
    $post = get_post();

    if($post->post_type === 'lesson') {
        $access_type = get_post_meta( $post->ID, 'lesson_access_type', true );

        $login_page_id = get_option('spp_login_page', NULL);
        $login_url = $login_page_id 
            ? get_permalink($login_page_id)
            : home_url();

        if (empty($access_type) || $access_type === 'free') {
            if (is_user_logged_in()) {
                return;
            } else {
                wp_redirect($login_url);
                return;
            }
        }

        if ($access_type === 'public') {
            return;
        }

        if ($access_type === 'paid') {
            if (is_user_logged_in()) {
                $course_id = get_post_meta( $post->ID, '_lesson_course', true );

                if (!empty($course_id)) {
                    $user_id = get_current_user_id();
                    $course_access = get_user_meta( $user_id, 'course-access-' . $course_id, true );

                    if ($course_access === 'yes') {
                        return;
                    } else {
                        $landing_page_id = get_post_meta( $post->ID, 'course_landing_page', true);

                        $landing_page_url = $landing_page_id 
                            ? get_permalink($landing_page_id)
                            : home_url();

                        wp_redirect( $landing_page_url );
                        return;
                    }
                }
            } else {
                wp_redirect($login_url);
                return;
            }
        }
    }
}

// show content

add_filter('sensei_can_user_view_lesson', 'spp_can_user_view_lesson', 10, 3);

function spp_can_user_view_lesson($can_user_view_lesson, $lesson_id, $user_id) {
    // restriction is handled with spp_restrict_content()
    // so, all we need to do is show the content.
    return true;
}

// remove signup message

add_action('wp_head', 'spp_remove_actions');

function spp_remove_actions() {
    remove_action('sensei_single_lesson_content_inside_before', array( 'Sensei_Lesson', 'course_signup_link' ), 30);
}

// editor meta boxes

add_action( 'add_meta_boxes', 'spp_add_meta_boxes' );

add_action( 'save_post', 'spp_lesson_save_meta_boxes' );
add_action( 'admin_head', 'spp_lesson_remove_meta_boxes' );

add_action( 'save_post', 'spp_course_save_meta_boxes' );

function spp_add_meta_boxes( $post_type ) {
    add_meta_box(
        'spp_lesson_access_type',
        __( 'Lesson Access Type', 'sensei-powerpack' ),
        'spp_lesson_access_type_meta_box_content',
        'lesson',
        'side',
        'low'
    );

    add_meta_box(
        'spp_course_landing_page',
        __( 'Course Payment Landing Page', 'sensei-powerpack' ),
        'spp_course_landing_page_meta_box_content',
        'course',
        'side',
        'low'
    );
}

// lesson meta box

$spp_lesson_options = array(
    'free' => __( 'Free', 'sensei-powerpack' ),
    'public' => __( 'Public', 'sensei-powerpack' ),
    'paid' => __( 'Paid', 'sensei-powerpack' ),
);

function spp_lesson_access_type_meta_box_content($post) {
    global $spp_lesson_options;

    wp_nonce_field( 'spp_lesson_metabox', 'spp_lesson_metabox_nonce' );
 
    $value = get_post_meta( $post->ID, 'lesson_access_type', true );

    ?>
    <select name="lesson_access_type"> <?php
        foreach($spp_lesson_options as $key => $label) {
            $selected = ($value == $key) ? 'selected' : '';
            echo "<option value='$key' $selected>$label</option>";
        }
    ?></select>
    <?php
}

function spp_lesson_save_meta_boxes( $post_id ) {
    if (isset($_POST['post_type']) && $_POST['post_type'] !== 'lesson') {
        return $post_id;
    }

    // Check nonce

    if ( ! isset( $_POST['spp_lesson_metabox_nonce'] ) ) {
        return $post_id;
    }

    $nonce = $_POST['spp_lesson_metabox_nonce'];

    if ( ! wp_verify_nonce( $nonce, 'spp_lesson_metabox' ) ) {
        return $post_id;
    }

    // Check the user's permissions.
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return $post_id;
    }

    $mydata = sanitize_text_field( $_POST['lesson_access_type'] );
 
    // Update the meta field.
    update_post_meta( $post_id, 'lesson_access_type', $mydata );
}

function spp_lesson_remove_meta_boxes() {
    remove_meta_box( 'lesson-preview', 'lesson', 'side' );
}

// course meta box

function spp_course_landing_page_meta_box_content($post) {
    wp_nonce_field( 'spp_course_metabox', 'spp_course_metabox_nonce' );
 
    $value = get_post_meta( $post->ID, 'course_landing_page', true );

    ?>
    <select name="course_landing_page"> <?php
        $pages = get_pages(); 
        foreach ( $pages as $page ) {
            $selected = ($value == $page->ID) ? 'selected' : '';
            echo "<option value='$page->ID' $selected>$page->post_title</option>";
        }
    ?></select>
    <?php
}

function spp_course_save_meta_boxes( $post_id ) {
    if (isset($_POST['post_type']) && $_POST['post_type'] !== 'course') {
        return $post_id;
    }

    // Check nonce

    if ( ! isset( $_POST['spp_course_metabox_nonce'] ) ) {
        return $post_id;
    }

    $nonce = $_POST['spp_course_metabox_nonce'];

    if ( ! wp_verify_nonce( $nonce, 'spp_course_metabox' ) ) {
        return $post_id;
    }

    // Check the user's permissions.
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return $post_id;
    }

    $mydata = sanitize_text_field( $_POST['course_landing_page'] );
 
    // Update the meta field.
    update_post_meta( $post_id, 'course_landing_page', $mydata );
}

// lesson post table columns

add_filter( 'manage_lesson_posts_columns', 'spp_lesson_posts_columns_list' );
add_action( 'manage_lesson_posts_custom_column' , 'spp_lesson_post_column_value', 10, 2 );

function spp_lesson_posts_columns_list($columns) {
    $columns['access_type'] = __('Access Type', 'sensei-powerpack');
     
    return $columns;
}

function spp_lesson_post_column_value( $column, $post_id ) {
    global $spp_lesson_options;

    if ($column === 'access_type') {
        $access_type = get_post_meta($post_id, 'lesson_access_type', true);

        if (empty($access_type)) {
            $access_type = 'free';
        }

        echo $spp_lesson_options[$access_type];
    }
}

// Login page setting

add_filter('sensei_settings_fields', 'spp_login_page_setting');
add_action( 'update_option_sensei-settings', 'spp_update_login_page_setting', 10, 2 );

function spp_login_page_setting($fields) {
    $fields['login_page'] = array(
        'name'        => __( 'Login Page', 'sensei-powerpack' ),
        'description' => __( 'The page to login as a user', 'sensei-powerpack' ),
        'type'        => 'select',
        'default'     => get_option( 'spp_login_page', 0 ),
        'section'     => 'default-settings',
        'required'    => 0,
        // Get page list.
        'options'     => $fields['course_page']['options'],
    );

    return $fields;
}

// Options are saved in `sensei-settings` option.
// We're retrieving `login_page` here to save it as a separate option.
function spp_update_login_page_setting($old_value, $new_value) {
    if (isset($new_value['login_page'])) {
        update_option('spp_login_page', $new_value['login_page']);
    }
}
