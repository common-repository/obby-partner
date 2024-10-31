<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Obby_Partner
 * @subpackage Obby_Partner/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->

<div class="wrap">

  <h2><?php echo esc_html(get_admin_page_title()); ?></h2>
  
  <form method="post" name="obby_partner_options" action="options.php">

    <?php
      // Grab all options
      $options = get_option($this->plugin_name);

      // Activation code
      $activation_code = $options['activation_code'];
    ?>

    <?php
      settings_fields($this->plugin_name);
      do_settings_sections($this->plugin_name);
    ?>

    <div id="post-body-content">
      <div class="meta-box-sortables ui-sortable">
        <div class="postbox">
          <div class="inside">
            <p><?php esc_attr_e('Thanks for using our Obby Partner plugin. This little tool will make everyones life so much easier - you won’t ever have to inform Obby of new classes or dates again, and Obby won’t have to nag you about them :)'); ?></p>
            <p><?php esc_attr_e('Just some of the boring stuff so you know exactly what the plugin does and does not do:'); ?></p>
            <ul>
              <li><?php esc_attr_e('- This plugin allows Obby to collect data on the stock levels of your classes so that our systems can update and reflect what you have live on your site. This means that we collect all your class and stock level data.'); ?></li>
              <li><?php esc_attr_e('- We do not under any circumstance at all collect any customer data (i.e. we do not see the name, contact or payment details of any of your customers). That is a slippery can of worms we just don’t want to touch!'); ?></li>
              <li><?php esc_attr_e('- This plugin is read-only. Meaning that we can only see stuff, we in no way have the ability to update anything on your systems ourselves.'); ?></li>
              <li><?php esc_attr_e('- This plugin can be disabled at any time just by going into the plugin page in your WordPress administration.'); ?></li>
            </ul>
            <br>
            <p><?php esc_attr_e('Thanks for helping us make the Obby platform better and being part of the journey!'); ?></p>
          </div>
        </div>
      </div>
    </div>
  
    <!-- Activation code -->
    <fieldset>
      <legend class="screen-reader-text"><span><?php _e('Activation code', $this->plugin_name); ?></span></legend>
      <h4 for="<?php echo $this->plugin_name; ?>-activation_code"><?php _e('Activation code', $this->plugin_name); ?></h4>
      <input type="text" id="<?php echo $this->plugin_name; ?>-activation_code" name="<?php echo $this->plugin_name; ?>[activation_code]" class="large-text" value="<?php echo $activation_code; ?>" />
    </fieldset>

    <?php submit_button(__('Save', $this->plugin_name), 'primary', 'submit', TRUE); ?>

  </form>

</div>
