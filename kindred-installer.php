<?php
/*
    Plugin Name: Kindred Installer
    description: Kindred SDK and conversion tracking installer
    Author: Ryan Bosley
    Version: 1.0.0

    Kindred Installer integrates your website with Kindred, provided you have registered a Kindred brand account
    Copyright (C) <2019>  <Ryan Bosley>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/
class Kindred {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'create_plugin_settings_page' ) ); 
        add_action( 'admin_init', array( $this, 'setup_sections' ) );
        add_action( 'admin_init', array( $this, 'setup_fields' ) );
        add_action( 'wp_head', array( &$this, 'install_sdk' ) );
        add_action( 'woocommerce_thankyou', array( $this, 'kindred_conversion_tracking_thank_you_page') );
    }

    //Create the plugin page and add the plugin as an option under 'Settings'
    public function create_plugin_settings_page() {
        $page_title = 'Kindred SDK and tracking installer';
        $menu_title = 'Kindred Installer';
        $capability = 'manage_options';
        $slug = 'kindred_fields';
        $callback = array( $this, 'plugin_settings_page_content' );
        $icon = 'dashicons-admin-plugins';
        $position = 100;
    
        add_submenu_page( 'options-general.php', $page_title, $menu_title, $capability, $slug, $callback );
    }
    
    //Create page content
    public function plugin_settings_page_content() { ?>
        <div class="wrap">
            <h2>Kindred SDK and tracking installer</h2>
            <form method="post" action="options.php">
                <?php
                    settings_fields( 'kindred_fields' );
                    do_settings_sections( 'kindred_fields' );
                    submit_button();
                ?>
            </form>
            <h3> How do I find my unique code?</h3>
            <ul>
                <li>-Log in to <a href="https://app.kindred.co" target="_blank">Kindred</a></li>
                <li>-Click on <b>Settings</b></li>
                <li>-Select the <b>Installation Code</b> tab</li>
                <li>-In the first textbox containing a script there will be a part that looks like this: <i>kindred('init','<b>XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX</b>');</i> with your unique code instead of X's</li>
                <li>-Copy your unique code (without the quotes) and paste it in the field of the Kindred Installer plugin on Wordpress</li>
            </ul>
        </div> <?php
    }

    //Setup the section for the textbox
    public function setup_sections() {
        add_settings_section( 'kindred_code', '', array( $this, 'section_callback' ), 'kindred_fields' );
    }

    //Setup empty section title to avoid error
    public function section_callback( $arguments ) {
        
    }

    //Add the setting for the unique Kindred code and register the setting
    public function setup_fields() {
        add_settings_field( 'kindred_code', 'Enter your unique brand code:', array( $this, 'field_callback' ), 'kindred_fields', 'kindred_code');
        register_setting( 'kindred_fields', 'kindred_code' );
    }

    //Create the textbox
    public function field_callback( $arguments ) {
        echo '<input name="kindred_code" id="kindred_code" type="text" style="width:22em;" value="' . get_option( 'kindred_code' ) . '" />';
    }

    //Install the SDK in the <head> of every page of the website with the user unique Kindred code
    function install_sdk() {
        $kindred_code = get_option( 'kindred_code' );
        if ( $kindred_code != '' ) {  //Only add Javascript if kindred_code has been added
            ?>
                <script> 
                (function(k,i,n,d,r,ed){ k[d]=ed=k[d]||function(){(ed.q=ed.q||[]).push(arguments)};k=i.createElement(n);
                 k.src=r;k.async=1;n=i.getElementsByTagName(n)[0];n.parentNode.insertBefore(k,n); })
                (window,document,'script','kindred','https://cdn.kindred.co/sdk/sdk.js');
                 kindred('init','<?php echo $kindred_code ?>');
                 kindred('capture',location.href); 
                 </script>
            <?php
        }
    }

    //Install conversion tracking for the 'Order Confirmed' page 
    function kindred_conversion_tracking_thank_you_page ($order_id) {
        $kindred_code = get_option( 'kindred_code' );
        if ( $kindred_code != '' ) {   //Only add Javascript if kindred_code has been added
            //get order object
            $order = wc_get_order( $order_id );
            //get data in object
            $order_data = $order->get_data();
            //get currency as 3 letter string
            $order_currency = $order_data['currency']; 

            //get subtotal (non-number format)
            $order_subtotal = $order->get_subtotal(); 
            // Get the correct number format (2 decimals)
            $order_subtotal = number_format( $order_subtotal, 2 ); //convert subtotal to number format

            ?>
            <!-- Conversion tracking script -->
            <script>kindred('trackConversion', '<?php echo $order_id; ?>', Math.round(<?php echo $order_subtotal; ?> * 100), '<?php echo $order_currency; ?>') </script>
            <?php
        }
    }
}
new Kindred();