<?php

add_action( 'add_meta_boxes', 'spp_add_meta_boxes' );
add_action( 'save_post', 'spp_lesson_save_lmeta_boxes' );

function spp_add_meta_boxes( $post_type ) {
    add_meta_box(
        'spp_lesson_access_type',
        __( 'Lesson Access Type', 'sensei-powerpack' ),
        'spp_lesson_access_type_meta_box_content',
        'lesson',
        'side',
        'low'
    );
}

function spp_lesson_access_type_meta_box_content($post) {
    wp_nonce_field( 'spp_lesson_metabox', 'spp_lesson_metabox_nonce' );
 
    $value = get_post_meta( $post->ID, 'lesson_access_type', true );

    ?>
    <label for="lesson_access_type">Choose a lesson access type for this lesson:</label>
    <select name="lesson_access_type"> <?php
        $options = array(
            'free' => __( 'Free', 'sensei-powerpack' ),
            'public' => __( 'Public', 'sensei-powerpack' ),
            'paid' => __( 'Paid', 'sensei-powerpack' ),
        );

        foreach($options as $key => $label) {
            $selected = ($value == $key) ? 'selected' : '';
            echo "<option value='$key' $selected>$label</option>";
        }
    ?></select>
    <?php
}

function spp_lesson_save_lmeta_boxes( $post_id ) {
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
