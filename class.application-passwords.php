<?php
/**
 * Class for displaying, modifying, & sanitizing application passwords.
 *
 * @since 0.1-dev
 *
 * @package Two_Factor
 */
class Application_Passwords {

	/**
	 * The user meta application password key.
	 * @type string
	 */
	const USERMETA_KEY_APPLICATION_PASSWORDS = '_application_passwords';

	/**
	 * Add various hooks.
	 *
	 * @since 0.1-dev
	 *
	 * @access public
	 * @static
	 */
	 public static function add_hooks($file) {
		add_filter( 'plugin_action_links_' . $file,		array( __CLASS__, 'add_action_links' ) );
 		add_filter( 'authenticate',                		array( __CLASS__, 'authenticate' ), 10, 3 );
 		add_action( 'show_user_profile',           		array( __CLASS__, 'show_user_profile' ) );
 		add_action( 'rest_api_init',               		array( __CLASS__, 'rest_api_init' ) );
 		add_filter( 'determine_current_user',      		array( __CLASS__, 'rest_api_auth_handler' ), 20 );
 		add_filter( 'wp_rest_server_class',        		array( __CLASS__, 'wp_rest_server_class' ) );
	}

	/**
	 * Add a new plugin link to the Application passwords.  Without this link it is hard to find out where to go
	 * to create these passwords.
	 *
	 * @since 0.1-dev
	 *
	 * @access public
	 * @static
	 * @param mixed $links
	 * @return array
	 */
	public static function add_action_links($links) {

		$password_link = array(
			'<a href="' . admin_url( 'profile.php#application-passwords' ) . '">' . __('Passwords') . '</a>',
		);

		return array_merge( $links, $password_link );
	}

	/**
	 * Prevent caching of unauthenticated status.  See comment below.
	 *
	 * We don't actually care about the `wp_rest_server_class` filter, it just
	 * happens right after the constant we do care about is defined.
	 *
	 * @since 0.1-dev
	 *
	 * @access public
	 * @static
	 */
	public static function wp_rest_server_class( $class ) {
		global $current_user;
		if ( defined( 'REST_REQUEST' )
		     && REST_REQUEST
		     && $current_user instanceof WP_User
		     && 0 === $current_user->ID ) {
			/*
			 * For our authentication to work, we need to remove the cached lack
			 * of a current user, so the next time it checks, we can detect that
			 * this is a rest api request and allow our override to happen.  This
			 * is because the constant is defined later than the first get current
			 * user call may run.
			 */
			$current_user = null;
		}
		return $class;
	}

	/**
	 * Handle declaration of REST API endpoints.
	 *
	 * @since 0.1-dev
	 *
	 * @access public
	 * @static
	 */
	public static function rest_api_init() {
		// List existing application passwords
		register_rest_route( '2fa/v1', '/application-passwords/(?P<user_id>[\d]+)', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => __CLASS__ . '::rest_list_application_passwords',
			'permission_callback' => __CLASS__ . '::rest_edit_user_callback',
		) );

		// Add new application passwords
		register_rest_route( '2fa/v1', '/application-passwords/(?P<user_id>[\d]+)/add', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => __CLASS__ . '::rest_add_application_password',
			'permission_callback' => __CLASS__ . '::rest_edit_user_callback',
			'args' => array(
				'name' => array(
					'required' => true,
				),
			),
		) );

		// Delete an application password
		register_rest_route( '2fa/v1', '/application-passwords/(?P<user_id>[\d]+)/(?P<slug>[\da-fA-F]{12})', array(
			'methods' => WP_REST_Server::DELETABLE,
			'callback' => __CLASS__ . '::rest_delete_application_password',
			'permission_callback' => __CLASS__ . '::rest_edit_user_callback',
		) );

		// Delete all application passwords for a given user
		register_rest_route( '2fa/v1', '/application-passwords/(?P<user_id>[\d]+)', array(
			'methods' => WP_REST_Server::DELETABLE,
			'callback' => __CLASS__ . '::rest_delete_all_application_passwords',
			'permission_callback' => __CLASS__ . '::rest_edit_user_callback',
		) );
	}

	/**
	 * REST API endpoint to list existing application passwords for a user.
	 *
	 * @since 0.1-dev
	 *
	 * @access public
	 * @static
	 *
	 * @param $data
	 *
	 * @return array
	 */
	public static function rest_list_application_passwords( $data ) {
		$application_passwords = self::get_user_application_passwords( $data['user_id'] );
		$with_slugs = array();

		if ( $application_passwords ) {
			foreach ( $application_passwords as $item ) {
				$item['slug'] = self::password_unique_slug( $item );
				unset( $item['raw'] );
				unset( $item['password'] );

				$item['created'] = date( get_option( 'date_format', 'r' ), $item['created'] );

				if ( empty( $item['last_used'] ) ) {
					$item['last_used'] =  '—';
				} else {
					$item['last_used'] = date( get_option( 'date_format', 'r' ), $item['last_used'] );
				}

				if ( empty( $item['last_ip'] ) ) {
					$item['last_ip'] =  '—';
				}

				$with_slugs[ $item['slug'] ] = $item;
			}
		}

		return $with_slugs;
	}

	/**
	 * REST API endpoint to add a new application password for a user.
	 *
	 * @since 0.1-dev
	 *
	 * @access public
	 * @static
	 *
	 * @param $data
	 *
	 * @return array
	 */
	public static function rest_add_application_password( $data ) {
		list( $new_password, $new_item ) = self::create_new_application_password( $data['user_id'], $data['name'] );

		// Some tidying before we return it.
		$new_item['slug']      = self::password_unique_slug( $new_item );
		$new_item['created']   = date( get_option( 'date_format', 'r' ), $new_item['created'] );
		$new_item['last_used'] = '—';
		$new_item['last_ip']   = '—';
		unset( $new_item['password'] );

		return array(
			'row'      => $new_item,
			'password' => self::chunk_password( $new_password )
		);
	}

	/**
	 * REST API endpoint to delete a given application password.
	 *
	 * @since 0.1-dev
	 *
	 * @access public
	 * @static
	 *
	 * @param $data
	 *
	 * @return bool
	 */
	public static function rest_delete_application_password( $data ) {
		return self::delete_application_password( $data['user_id'], $data['slug'] );
	}

	/**
	 * REST API endpoint to delete all of a user's application passwords.
	 *
	 * @since 0.1-dev
	 *
	 * @access public
	 * @static
	 *
	 * @param $data
	 *
	 * @return int The number of deleted passwords
	 */
	public static function rest_delete_all_application_passwords( $data ) {
		return self::delete_all_application_passwords( $data['user_id'] );
	}

	/**
	 * Whether or not the current user can edit the specified user.
	 *
	 * @since 0.1-dev
	 *
	 * @access public
	 * @static
	 *
	 * @param $data
	 *
	 * @return bool
	 */
	public static function rest_edit_user_callback( $data ) {
		return current_user_can( 'edit_user', $data['user_id'] );
	}

	/**
	 * Loosely Based on https://github.com/WP-API/Basic-Auth/blob/master/basic-auth.php
	 *
	 * @since 0.1-dev
	 *
	 * @access public
	 * @static
	 *
	 * @param $input_user
	 *
	 * @return WP_User|bool
	 */
	public static function rest_api_auth_handler( $input_user ){
		// Don't authenticate twice
		if ( ! empty( $input_user ) ) {
			return $input_user;
		}

		// Check that we're trying to authenticate
		if ( ! isset( $_SERVER['PHP_AUTH_USER'] ) ) {
			return $input_user;
		}

		$user = self::authenticate( $input_user, $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] );

		if ( is_a( $user, 'WP_User' ) ) {
			return $user->ID;
		}

		// If it wasn't a user what got returned, just pass on what we had received originally.
		return $input_user;
	}

	/**
	 * Filter the user to authenticate.
	 *
	 * @since 0.1-dev
	 *
	 * @access public
	 * @static
	 *
	 * @param WP_User $input_user User to authenticate.
	 * @param string  $username   User login.
	 * @param string  $password   User password.
	 *
	 * @return mixed
	 */
	public static function authenticate( $input_user, $username, $password ) {
		$api_request = ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST );
		if ( ! apply_filters( 'application_password_is_api_request', $api_request ) ) {
			return $input_user;
		}

		$user = get_user_by( 'login',  $username );

		// If the login name is invalid, short circuit.
		if ( ! $user ) {
			return $input_user;
		}

		/*
		 * Strip out anything non-alphanumeric. This is so passwords can be used with
		 * or without spaces to indicate the groupings for readability.
		 *
		 * Generated application passwords are exclusively alphanumeric.
		 */
		$password = preg_replace( '/[^a-z\d]/i', '', $password );

		$hashed_passwords = get_user_meta( $user->ID, self::USERMETA_KEY_APPLICATION_PASSWORDS, true );

		foreach ( $hashed_passwords as $key => $item ) {
			if ( wp_check_password( $password, $item['password'], $user->ID ) ) {
				$item['last_used'] = time();
				$item['last_ip']   = $_SERVER['REMOTE_ADDR'];
				$hashed_passwords[ $key ] = $item;
				update_user_meta( $user->ID, self::USERMETA_KEY_APPLICATION_PASSWORDS, $hashed_passwords );
				return $user;
			}
		}

		// By default, return what we've been passed.
		return $input_user;
	}

	/**
	 * Display the application password section in a users profile.
	 *
	 * This executes during the `show_user_security_settings` action.
	 *
	 * @since 0.1-dev
	 *
	 * @access public
	 * @static
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 */
	public static function show_user_profile( $user ) {
		wp_enqueue_style( 'application-passwords-css', plugin_dir_url( __FILE__ ) . 'application-passwords.css', array() );
		wp_enqueue_script( 'application-passwords-js', plugin_dir_url( __FILE__ ) . 'application-passwords.js', array() );
		wp_localize_script( 'application-passwords-js', 'appPass', array(
			'root'       => esc_url_raw( rest_url() ),
			'namespace'  => '2fa/v1',
			'nonce'      => wp_create_nonce( 'wp_rest' ),
			'user_id'    => $user->ID,
		) );

		?>
		<div class="application-passwords hide-if-no-js" id="application-passwords-section">
			<h2 id="application-passwords"><?php esc_html_e( 'Application Passwords' ); ?></h2>
			<p><?php esc_html_e( 'Application passwords allow authentication via non-interactive systems, such as XMLRPC or the REST API, without providing your actual password. Application passwords can be easily revoked. They cannot be used for traditional logins to your website.' ); ?></p>
			<div class="create-application-password">
				<input type="text" size="30" name="new_application_password_name" placeholder="<?php esc_attr_e( 'New Application Password Name' ); ?>" class="input" />
				<?php submit_button( __( 'Add New' ), 'secondary', 'do_new_application_password', false ); ?>
			</div>

			<div class="application-passwords-list-table-wrapper">
			<?php
				require( dirname( __FILE__ ) . '/class.application-passwords-list-table.php' );
				$application_passwords_list_table = new Application_Passwords_List_Table();
				$application_passwords_list_table->items = array_reverse( self::get_user_application_passwords( $user->ID ) );
				$application_passwords_list_table->prepare_items();
				$application_passwords_list_table->display();
			?>
			</div>
		</div>

		<script type="text/html" id="tmpl-new-application-password">
			<div class="new-application-password notification-dialog-wrap">
				<div class="app-pass-dialog-background notification-dialog-background">
					<div class="app-pass-dialog notification-dialog">
						<div class="new-application-password-content">
							<?php
							printf(
								esc_html_x( 'Your new password for %1$s is: %2$s', 'application, password' ),
								'<strong>{{ data.name }}</strong>',
								'<kbd>{{ data.password }}</kbd>'
							);
							?>
						</div>
						<p><?php esc_attr_e( 'Be sure to save this in a safe location.  You will not be able to retrieve it.' ); ?></p>
						<button class="button button-primary application-password-modal-dismiss"><?php esc_attr_e( 'Dismiss' ); ?></button>
					</div>
				</div>
			</div>
		</script>

		<script type="text/html" id="tmpl-application-password-row">
			<tr data-slug="{{ data.slug }}">
				<td class="name column-name has-row-actions column-primary" data-colname="<?php echo esc_attr( 'Name' ); ?>">
					{{ data.name }}
				</td>
				<td class="created column-created" data-colname="<?php echo esc_attr( 'Created' ); ?>">
					{{ data.created }}
				</td>
				<td class="last_used column-last_used" data-colname="<?php echo esc_attr( 'Last Used' ); ?>">
					{{ data.last_used }}
				</td>
				<td class="last_ip column-last_ip" data-colname="<?php echo esc_attr( 'Last IP' ); ?>">
					{{ data.last_ip }}
				</td>
				<td class="revoke column-revoke" data-colname="<?php echo esc_attr( 'Revoke' ); ?>">
					<input type="submit" name="revoke-application-password" class="button delete" value="<?php esc_attr_e( 'Revoke' ); ?>">
				</td>
			</tr>
		</script>
		<?php
	}

	/**
	 * Generate a new application password.
	 *
	 * @since 0.1-dev
	 *
	 * @access public
	 * @static
	 *
	 * @param int    $user_id User ID.
	 * @param string $name    Password name.
	 * @return array          The first key in the array is the new password, the second is its row in the table.
	 */
	public static function create_new_application_password( $user_id, $name ) {
		$new_password    = wp_generate_password( 16, false );
		$hashed_password = wp_hash_password( $new_password );

		$new_item = array(
			'name'      => $name,
			'password'  => $hashed_password,
			'created'   => time(),
			'last_used' => null,
			'last_ip'   => null,
		);

		$passwords = self::get_user_application_passwords( $user_id );
		if ( ! $passwords ) {
			$passwords = array();
		}

		$passwords[] = $new_item;
		self::set_user_application_passwords( $user_id, $passwords );

		return array( $new_password, $new_item );
	}

	/**
	 * Delete a specified application password.
	 *
	 * @since 0.1-dev
	 *
	 * @access public
	 * @static
	 *
	 * @see Application_Passwords::password_unique_slug()
	 *
	 * @param int    $user_id User ID.
	 * @param string $slug The generated slug of the password in question.
	 * @return bool Whether the password was successfully found and deleted.
	 */
	public static function delete_application_password( $user_id, $slug ) {
		$passwords = self::get_user_application_passwords( $user_id );

		foreach ( $passwords as $key => $item ) {
			if ( self::password_unique_slug( $item ) === $slug ) {
				unset( $passwords[ $key ] );
				self::set_user_application_passwords( $user_id, $passwords );
				return true;
			}
		}

		// Specified Application Password not found!
		return false;
	}

	/**
	 * Deletes all application passwords for the given user.
	 *
	 * @since 0.1-dev
	 *
	 * @access public
	 * @static
	 *
	 * @param int    $user_id User ID.
	 * @return int   The number of passwords that were deleted.
	 */
	public static function delete_all_application_passwords( $user_id ) {
		$passwords = self::get_user_application_passwords( $user_id );

		if ( is_array( $passwords ) ) {
			self::set_user_application_passwords( $user_id, array() );
			return sizeof( $passwords );
		}

		return 0;
	}

	/**
	 * Generate a unique repeateable slug from the hashed password, name, and when it was created.
	 *
	 * @since 0.1-dev
	 *
	 * @access public
	 * @static
	 *
	 * @param array $item The current item.
	 * @return string
	 */
	public static function password_unique_slug( $item ) {
		$concat = $item['name'] . '|' . $item['password'] . '|' . $item['created'];
		$hash   = md5( $concat );
		return substr( $hash, 0, 12 );
	}

	/**
	 * Sanitize and then split a passowrd into smaller chunks.
	 *
	 * @since 0.1-dev
	 *
	 * @access public
	 * @static
	 *
	 * @param string $raw_password Users raw password.
	 * @return string
	 */
	public static function chunk_password( $raw_password ) {
		$raw_password = preg_replace( '/[^a-z\d]/i', '', $raw_password );
		return trim( chunk_split( $raw_password, 4, ' ' ) );
	}

	/**
	 * Get a users application passwords.
	 *
	 * @since 0.1-dev
	 *
	 * @access public
	 * @static
	 *
	 * @param int $user_id User ID.
	 * @return array
	 */
	public static function get_user_application_passwords( $user_id ) {
		return get_user_meta( $user_id, self::USERMETA_KEY_APPLICATION_PASSWORDS, true );
	}

	/**
	 * Set a users application passwords.
	 *
	 * @since 0.1-dev
	 *
	 * @access public
	 * @static
	 *
	 * @param int   $user_id User ID.
	 * @param array $passwords Application passwords.
	 *
	 * @return bool
	 */
	public static function set_user_application_passwords( $user_id, $passwords ) {
		return update_user_meta( $user_id, self::USERMETA_KEY_APPLICATION_PASSWORDS, $passwords );
	}
}
