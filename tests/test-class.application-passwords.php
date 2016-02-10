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
}