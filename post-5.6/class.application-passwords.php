<?php

class Application_Passwords {

	/**
	 * Add various hooks.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @static
	 */
	public static function add_hooks() {
		// Anything we need to do, add the actions or filters in here.
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
	}

	/**
	 * Registers the hidden admin page to handle auth.
	 */
	public static function admin_menu() {
		add_submenu_page( null, __( 'Approve Application' ), null, 'exist', 'auth_app', array( __CLASS__, 'auth_app_page' ) );
	}
	
	/**
	 * Page for authorizing applications.
	 */
	public static function auth_app_page() {
		$new_url = admin_url( 'authorize-application.php' );
		$new_url = add_query_arg( $_GET, $new_url );
		$new_url = remove_query_arg( 'page', $new_url );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Authorize Application' ); ?></h1>
			<p><?php esc_html_e( 'The authorize application page has moved as of WordPress 5.6!' ); ?></p>
			<p><a href="<?php echo esc_url( $new_url ); ?>" class="button button-primary"><?php esc_html_e( 'You may continue to the new authorization page here 🔗' ); ?></a></p>
		</div>
		<?php
	}
}
