<?php

add_action( 'woocommerce_payment_complete', 'spp_order_completed' );
function spp_order_completed( $order_id ) {
	$order = wc_get_order( $order_id );

    if(!$order->has_status('completed')) {
        return;
    }

	$user_id = $order->get_user_id();

    $order_items = $order->get_items();

    foreach( $order_items as $item_id => $item ){
        $wc_product = $item->get_product();

        $course_id = $wc_product->get_meta('spp_course_id');

        if (isset($course_id)) {
            update_user_meta( $user_id, 'course-access-' . $course_id, 'yes' );
        }
    }
}
