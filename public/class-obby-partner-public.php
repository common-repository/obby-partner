<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Obby_Partner
 * @subpackage Obby_Partner/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Obby_Partner
 * @subpackage Obby_Partner/public
 * @author     Your Name <email@example.com>
 */
class Obby_Partner_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		$this->obby_partner_options = get_option($this->plugin_name);

	}

	/**
   * Create function to grab on WooCommerce webhooks.
   *
   * @since    1.0.0
   */
  public function obby_partner_hooks() {

    if(!empty($this->obby_partner_options['activation_code'])) {

    	// Product
    	add_action('save_post', array($this, 'obby_partner_product'), 10, 1);

    	// Order
    	add_action('woocommerce_thankyou', array($this, 'obby_partner_order'), 10, 1);
    	add_action('woocommerce_order_status_changed', array($this, 'obby_partner_order'), 10, 1);

    }
    
  }

  /**
   * When a product is saved (new or existing).
   *
   * @since    1.0.0
   */
  public function obby_partner_product($post_id) {

  	// Get post type
  	$post = get_post($post_id);

  	if ($post->post_type == 'product' || $post->post_type == 'tribe_events') {
  		$payload = $this->obby_partner_build_payload($post->post_type, $post_id);

  		$payload_check = array_filter((array)$payload);
	  	if (!empty($payload_check)) {
	  		wp_schedule_single_event( time(), 'action_obby_partner_deliver_webhook_async', array( $this->plugin_name, $payload, 'false' ) );
	  	}
  	}
  }

  /**
   * When a new order is placed.
   *
   * @since    1.0.0
   */
  public function obby_partner_order($order_id) {

  	$payload = $this->obby_partner_build_payload('order', $order_id);

		$payload_check = array_filter((array)$payload);
  	if (!empty($payload_check)) {
  		wp_schedule_single_event( time(), 'action_obby_partner_deliver_webhook_async', array( $this->plugin_name, $payload, 'false' ) );
  	}

  }

  private function obby_partner_build_payload($resource, $id) {

  	// build the payload with the same user context as the user who created
		// the webhook -- this avoids permission errors as background processing
		// runs with no user context
		$current_user = get_current_user_id();
		wp_set_current_user( $this->obby_partner_get_user_id( $id ) );

		// include & load API classes
		WC()->api->includes();
		WC()->api->register_resources( new WC_API_Server( '/' ) );

		$payload = new stdClass();

  	switch( $resource ) {

  		case 'tribe_events':
  			// Check if the current id is for an event
		    $wp_query = new WP_Query( array( 'post_type' => 'tribe_events', 'post_status' => 'publish', 'p' => $id ) );
		    $total = $wp_query->found_posts;

		    if ($total != 0) {
		    	$tribe_event = tribe_get_events(array('p' => $id, 'posts_per_page' => 1));
					$event = $tribe_event[0];
		    	$event->categories = wp_get_post_categories($id);
		      $event->tags = wp_get_post_tags($id);
		      $event->tickets = obby_partner_get_tickets($id);
		      $payload->event = $event;
		    }
				break;

			case 'product':
				$payload = WC()->api->WC_API_Products->get_product( $id );
				break;

			case 'order':
				$payload = WC()->api->WC_API_Orders->get_order( $id );

				// Check if any of the product lines are event tickets
				$order = $payload['order'];
				$line_items = $order['line_items'];
				$new_line_items = array();
				foreach ($line_items as $line_item) {
					$new_line_item = $line_item;
					$ticket_id = get_post_meta($line_item['product_id'], '_tribe_wooticket_for_event', true);
					if (!empty($ticket_id)) {
						$new_line_item['event'] = get_post($ticket_id);
					}
					array_push($new_line_items, $new_line_item);
				}
				$payload['order']['line_items'] = $new_line_items;
				break;
		}

  	// restore the current user
		wp_set_current_user( $current_user );

		return $payload;

  }

  /**
	 * Get the user ID for this webhook.
	 *
	 * @since 1.0
	 * @return int|string user ID
	 */
	private function obby_partner_get_user_id( $id ) {

		return $this->obby_partner_get_post_data( $id )->post_author;

	}

	/**
	 * Get the post data for the webhook.
	 *
	 * @since 1.0
	 * @return null|WP_Post
	 */
	private function obby_partner_get_post_data( $id ) {

		return $post_data = get_post( $id );

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Obby_Partner_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Obby_Partner_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/obby-partner-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Obby_Partner_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Obby_Partner_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/obby-partner-public.js', array( 'jquery' ), $this->version, false );

	}

}
