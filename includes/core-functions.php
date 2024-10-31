<?php
/**
 * Wrapper function to execute the `op_send_initial_data`.
 *
 * @since 1.0
 */
function obby_partner_send_initial_data() {

  $current_user = get_current_user_id();

  // include & load API classes
  WC()->api->includes();
  WC()->api->register_resources( new WC_API_Server( '/' ) );

  // Check if user is using variable products or event tickets
  if (in_array('event-tickets-plus/event-tickets-plus.php', get_option('active_plugins'))) {

    // Send events
    try {

      $args = array( 'post_type' => 'tribe_events', 'post_status' => 'publish' );
      $wp_query = new WP_Query( $args );
      $total = $wp_query->found_posts;
      $tribe_events = tribe_get_events(array('posts_per_page' => $total));
      $events = array();
      $i = 0;

      foreach ( $tribe_events as $event ) {

        // build the products payload with the same user context as the user who created
        // the webhook -- this avoids permission errors as background processing
        // runs with no user context
        wp_set_current_user( obby_partner_get_user_id( $event->ID ) );

        $event->categories = wp_get_post_categories( $event->ID );
        $event->tags = wp_get_post_tags( $event->ID );
        $event->tickets = obby_partner_get_tickets( $event->ID );

        array_push($events, $event);

        $i++;
        if ($i % 10 == 0 || $i == $total) {
          wp_schedule_single_event( time() + (10 * $i), 'action_obby_partner_deliver_webhook_async', array( 'obby-partner', array ( 'events' => $events ), 'false' ) );
          $events = array();
        }
      }
    }
    catch (Exception $e) {
      wp_schedule_single_event( time(), 'action_obby_partner_deliver_webhook_async', array( 'obby-partner', array( 'Send events exception' => $e->getMessage() ), 'true' ) );
    }
  }
  else {

    // Send products
    try {

      $args = array( 'post_type' => 'product', 'post_status' => 'publish' );
      $wp_query = new WP_Query( $args );
      $total = $wp_query->found_posts;
      $args = array( 'post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => $total );
      $loop = new WP_Query( $args );
      $products = array();
      $i = 0;

      while ($loop->have_posts()) {

        $loop->the_post();

        $id = get_the_ID();

        // build the products payload with the same user context as the user who created
        // the webhook -- this avoids permission errors as background processing
        // runs with no user context
        wp_set_current_user( obby_partner_get_user_id( $id ) );

        $product = WC()->api->WC_API_Products->get_product( $id );
        array_push($products, $product['product']);

        $i++;
        if ($i % 10 == 0 || $i == $total) {
          wp_schedule_single_event( time() + (10 * $i), 'action_obby_partner_deliver_webhook_async', array( 'obby-partner', array ( 'products' => $products ), 'false' ) );
          $products = array();
        }
      }
    }

    catch (Exception $e) {
      wp_schedule_single_event( time(), 'action_obby_partner_deliver_webhook_async', array( 'obby-partner', array( 'Send products exception' => $e->getMessage() ), 'true' ) );
    }
  }

  // Send orders
  try {

    $args = array( 'post_type' => 'shop_order', 'post_status' => 'any' );
    $wp_query = new WP_Query( $args );
    $total = $wp_query->found_posts;
    $args = array( 'post_type' => 'shop_order', 'post_status' => 'any', 'posts_per_page' => $total );
    $loop = new WP_Query( $args );
    $orders = array();
    $i = 0;

    error_log('send orders');

    while ($loop->have_posts()) {

      $loop->the_post();

      $id = get_the_ID();

      error_log('order ' . $id);

      // build the orders payload with the same user context as the user who created
      // the webhook -- this avoids permission errors as background processing
      // runs with no user context
      wp_set_current_user( obby_partner_get_user_id( $id ) );

      $order = WC()->api->WC_API_Orders->get_order( $id );
      array_push($orders, $order['order']);

      $i++;
      if ($i % 10 == 0 || $i == $total) {
        wp_schedule_single_event( time() + (10 * $i), 'action_obby_partner_deliver_webhook_async', array( 'obby-partner', array( 'orders' => $orders ), 'true' ) );
        $orders = array();
      }
    }
  }
  catch (Exception $e) {
    wp_schedule_single_event( time(), 'action_obby_partner_deliver_webhook_async', array( 'obby-partner', array( 'Send orders exception' => $e->getMessage() ), 'true' ) );
  }

  // restore the current user
  wp_set_current_user( $current_user );

}
add_action( 'action_obby_partner_send_initial_data', 'obby_partner_send_initial_data', 10, 0 );

/**
 * Wrapper function to execute the `action_obby_partner_deliver_webhook_async` cron.
 * hook, see WC_Webhook::process().
 *
 * @since 1.0
 * @param int $webhook_id webhook ID to deliver.
 * @param mixed $payload data.
 * @param string $historic data.
 */
function obby_partner_deliver_webhook_async( $plugin_name, $payload, $historic ) {

  // Setup request args.
  $http_args = array(
    'method'      => 'POST',
    'timeout'     => 15,
    'redirection' => 0,
    'httpversion' => '1.0',
    'blocking'    => true,
    'user-agent'  => 'Wordpress',
    'body'        => trim( json_encode( $payload ) ),
    'headers'     => array( 'Content-Type' => 'application/json' ),
    'cookies'     => array(),
  );

  // Add custom headers.
  $http_args['headers']['X-OP-Webhook-Source']       = home_url( '/' );
  $http_args['headers']['X-OP-Webhook-HistoricData'] = $historic;
  $http_args['headers']['X-OP-Webhook-Signature']    = obby_partner_generate_signature( $http_args['body'] );

  // Grab all options
  $options = get_option($plugin_name);

  // Activation code
  $activation_code = $options['activation_code'];

  // Webhook away!
  wp_safe_remote_request( 'https://api.obby.co.uk/partner-updates/' . $activation_code, $http_args );

}
add_action( 'action_obby_partner_deliver_webhook_async', 'obby_partner_deliver_webhook_async', 10, 3 );

/**
 * Generate a base64-encoded HMAC-SHA256 signature of the payload body so the.
 * recipient can verify the authenticity of the webhook. Note that the signature.
 * is calculated after the body has already been encoded (JSON by default).
 *
 * @since 1.0
 * @param string $payload payload data to hash
 * @return string hash
 */
function obby_partner_generate_signature( $payload ) {

  $hash_algo = apply_filters( 'woocommerce_webhook_hash_algorithm', 'sha256', $payload, 0 );

  return base64_encode( hash_hmac( $hash_algo, $payload, obby_partner_generate_random_string(), true ) );
}

/**
 * Generate a random string.
 *
 * @since 1.0
 * @param int $length of the string
 * @return string random
 */
function obby_partner_generate_random_string($length = 20) {
  $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $charactersLength = strlen($characters);
  $randomString = '';

  for ($i = 0; $i < $length; $i++) {
    $randomString .= $characters[rand(0, $charactersLength - 1)];
  }

  return $randomString;
}

/**
 * Get the user ID for this webhook.
 *
 * @since 1.0
 * @return int|string user ID
 */
function obby_partner_get_user_id( $id ) {

  return obby_partner_get_post_data( $id )->post_author;

}

/**
 * Get the post data for the webhook.
 *
 * @since 1.0
 * @return null|WP_Post
 */
function obby_partner_get_post_data( $id ) {

  return get_post( $id );

}

/**
 * Returns all the tickets for an event.
 *
 * @param int $event_id
 *
 * @return array
 */
function obby_partner_get_tickets( $event_id ) {
  $ticket_ids = obby_partner_get_tickets_ids( $event_id );

  if ( ! $ticket_ids ) {
    return array();
  }

  $tickets = array();

  foreach ( $ticket_ids as $post ) {
    $tickets[] = obby_partner_get_ticket( $event_id, $post );
  }

  return $tickets;
}

/**
 * Returns all the ticket ids for an event.
 *
 * @param int $event_id
 *
 * @return array
 */
function obby_partner_get_tickets_ids( $event_id ) {
  if ( is_object( $event_id ) ) {
    $event_id = $event_id->ID;
  }

  $query = new WP_Query( array(
    'post_type'      => 'product',
    'meta_key'       => '_tribe_wooticket_for_event',
    'meta_value'     => $event_id,
    'meta_compare'   => '=',
    'posts_per_page' => - 1,
    'fields'         => 'ids',
    'post_status'    => 'publish',
  ) );

  return $query->posts;
}

/**
 * Gets an individual ticket
 *
 * @param $event_id
 * @param $ticket_id
 *
 * @return null|Tribe__Tickets__Ticket_Object
 */
function obby_partner_get_ticket( $event_id, $ticket_id ) {
  if ( class_exists( 'WC_Product_Simple' ) ) {
    $product = new WC_Product_Simple( $ticket_id );
  } else {
    $product = new WC_Product( $ticket_id );
  }

  if ( ! $product ) {
    return null;
  }

  $return = new stdClass();
  $product_data = $product->get_post_data();

  $return->title    = $product_data->post_title;
  $return->description    = $product_data->post_excerpt;
  $return->frontend_link  = get_permalink( $ticket_id );
  $return->ID             = $ticket_id;
  $return->name           = $product->get_title();
  $return->price          = $product->get_price();

  $stock = $product->get_stock_quantity();

  $manage_stock = get_post_meta( $ticket_id, '_manage_stock', true );
  if ( 'yes' === $manage_stock ) {
    $_stock = (int) get_post_meta( $ticket_id, '_stock', true );
    $total_sales = (int) get_post_meta( $ticket_id, 'total_sales', true );
    $stock = $_stock - $total_sales;
  }

  $return->stock = $stock;

  return $return;
}
