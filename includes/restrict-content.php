<?php

add_action('template_redirect', 'spp_restrict_content');
add_filter('sensei_can_user_view_lesson', 'spp_can_user_view_lesson', 10, 3);
add_action('wp_head', 'spp_remove_actions');

function spp_restrict_content() {
    $post = get_post();

    if($post->post_type === 'lesson') {
        $access_type = get_post_meta( $post->ID, 'lesson_access_type', true );

        if (empty($access_type) || $access_type === 'free') {
            if (is_user_logged_in()) {
                return;
            } else {
                wp_redirect(home_url());
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
                        wp_redirect(home_url());
                        return;
                    }
                }
            } else {
                wp_redirect(home_url());
                return;
            }
        }
    }
}

function spp_can_user_view_lesson($can_user_view_lesson, $lesson_id, $user_id) {
    // restriction is handled with spp_restrict_content()
    // so, all we need to do is show the content.
    return true;
}

function spp_remove_actions() {
    remove_action('sensei_single_lesson_content_inside_before', array( 'Sensei_Lesson', 'course_signup_link' ), 30);
}
