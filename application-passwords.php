<?php
/**
 * Plugin Name: Application Passwords
 * Plugin URI: https://github.com/georgestephanis/application-passwords
 * Description: A prototype framework to add application passwords to core.
 * Author: George Stephanis
 * Version: 0.1-dev
 * Author URI: https://stephanis.info
 */

define( 'APPLICATION_PASSWORDS_VERSION', '0.1-dev' );

/**
 * Include the application passwords system.
 */
require_once( dirname( __FILE__ ) . '/class.application-passwords.php' );
Application_Passwords::add_hooks();
