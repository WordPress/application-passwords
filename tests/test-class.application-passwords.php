<?php
/**
 * Test the main application passwords class.
 *
 * @since 0.1-dev
 *
 * @package Two_Factor
 */
class Test_Application_Passwords extends WP_UnitTestCase {

	/**
	 * @see Application_Passwords::add_hooks()
	 */
	function test_add_hooks() {
		$this->assertEquals( 10, has_action( 'authenticate', array( 'Application_Passwords', 'authenticate' ) ) );
	}

	/**
	 * HTTP Auth headers are used to determine the current user.
	 *
	 * @covers Application_Passwords::rest_api_auth_handler()
	 */
	public function test_can_login_user_through_http_auth_headers() {
		$user_id = $this->factory->user->create(
			array(
				'user_login' => 'http_auth_login',
				'user_pass'  => 'http_auth_pass',
			)
		);

		$_SERVER['PHP_AUTH_USER'] = 'http_auth_login';
		$_SERVER['PHP_AUTH_PW']   = 'http_auth_pass';

		// Note that wp_get_current_user() calls the `determine_current_user` filter.
		$this->assertEquals(
			0,
			wp_get_current_user()->ID,
			'HTTP Auth headers are ignored for non-REST API requests'
		);

		// Now fake a REST API request.
		add_fiter( 'application_password_is_api_request', '__return_true' );
		$this->assertEquals(
			$user_id,
			wp_get_current_user()->ID,
			'HTTP Auth headers are ignored for non-REST API requests'
		);
		remove_fiter( 'application_password_is_api_request', '__return_true' );

		unset( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] );
	}
}
