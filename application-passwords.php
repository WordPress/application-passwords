<?php
/**
 * Plugin Name: Application Passwords
 * Plugin URI: http://github.com/georgestephanis/application-passwords/
 * Description: A prototype framework to add application passwords to core.
 * Author: George Stephanis
 * Version: 0.1-dev
 * Author URI: http://stephanis.info
 */

if ( ! defined('APPLICATION_PASSWORDS_PLUGIN_DIR_URL') ) {
  define('APPLICATION_PASSWORDS_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Include the application passwords system.
 */
require_once( dirname( __FILE__ ) . '/classes/class.application-passwords.php' );
Application_Passwords::add_hooks( plugin_basename( __FILE__ ) );
