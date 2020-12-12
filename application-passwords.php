<?php
/**
 * Plugin Name: Application Passwords
 * Plugin URI: https://github.com/WordPress/application-passwords
 * Description: Creates unique passwords for applications to authenticate users without revealing their main passwords.
 * Author: George Stephanis
 * Version: 1.0.0
 * Author URI: https://github.com/georgestephanis
 */

define( 'APPLICATION_PASSWORDS_VERSION', '1.0.0' );

/**
 * Include the application passwords system.
 */
if ( class_exists( 'WP_Application_Passwords' ) ) {
    require_once( dirname( __FILE__ ) . '/post-5.6/class.application-passwords.php' );
} else {
    require_once( dirname( __FILE__ ) . '/pre-5.6/class.application-passwords.php' );
}

Application_Passwords::add_hooks();
