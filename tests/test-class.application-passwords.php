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
	 * Regular front-end requests are not REST requests.
	 *
	 * @covers Application_Passwords::is_api_request()
	 */
	public function test_rest_api_request() {
		$this->assertFalse( Application_Passwords::is_api_request() );
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
			'None-REST API requests are ignored'
		);

		// Fake the REST API request. TODO: how can we mock this or initialize the actual REST API?
		define( 'REST_REQUEST', true );

		$this->assertEquals(
			$user_id,
			wp_get_current_user()->ID,
			'Override the user from HTTP Auth headers during REST API requests'
		);

		// Cleanup all the global state.
		unset( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] );
	}
}
