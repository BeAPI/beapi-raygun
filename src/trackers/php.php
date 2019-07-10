<?php

namespace BEAPI\Raygun\Trackers;

use BEAPI\Raygun\Framework\Bootable;
use BEAPI\Raygun\Framework\Hookable;
use Raygun4php\RaygunClient;

class PHP implements Hookable, Bootable {
	/**
	 * The raygun client instance to use all over the platform.
	 * @var RaygunClient
	 */
	private $client;

	/**
	 * Boot the object by instanciating the Raygun Client and adding the error handlers.
	 *
	 * @author Nicolas JUEN
	 */
	public function boot() {
		$options      = $this->get_options();
		$this->client = new RaygunClient( $options['apiKey'], true, false, false );
		$this->client->SetVersion( $options['setVersion'] );
		if ( ! empty( $options['filteredParams'] ) && is_array( $options['filteredParams'] ) ) {
			$this->client->setFilterParams( $options['filteredParams'] );
		}

		// Setup Exceptions and error handlers
		set_exception_handler( [ $this, 'exception_handler' ] );
		set_error_handler( [ $this, 'error_handler' ] );
	}

	/**
	 * Hooks to add.
	 *
	 * @author Nicolas JUEN
	 */
	public function hooks() {
		add_action( 'plugins_loaded', [ $this, 'add_user_data' ], 1 );
	}

	/**
	 * Add the current user data if possible.
	 *
	 * @author Nicolas JUEN
	 */
	public function add_user_data() {
		$options = $this->get_options();
		if ( empty( $options['setUser'] ) ) {
			return;
		}
		$user = $options['setUser'];

		$this->client->setUser( $user['identifier'], $user['firstName'], $user['fullName'], $user['email'], false );
	}

	/**
	 * Get options for the PHP stack.
	 *
	 * @return array
	 * @author Nicolas JUEN
	 */
	public function get_options() {

		$default = [
			'apiKey'         => BEAPI_RAYGUN_API_KEY,
			'setVersion'     => get_bloginfo( 'version' ),
			'withTags'       => [ 'PHP' ],
			'enablePulse'    => false,
			'filteredParams' => [
				'/DB_NAME/i'     => true,
				'/DB_USER/i'     => true,
				'/DB_PASSWORD/i' => true,
				'/DB_HOST/i'     => true,
			],
		];

		if ( function_exists( 'wp_get_current_user' ) ) {
			$user = wp_get_current_user();

			$default['setUser'] = ! is_user_logged_in() ? false : [
				'isAnonymous' => false,
				'identifier'  => $user->ID,
				'email'       => $user->user_email,
				'firstName'   => $user->first_name,
				'fullName'    => $user->user_firstname . ' ' . $user->user_lastname,
			];
		}

		return apply_filters( 'beapi_raygun_php_options', $default, $this );
	}

	/**
	 * Add custom data to the PHP report.
	 *
	 * @see https://github.com/10up/wp-newrelic/blob/master/classes/class-wp-nr-apm.php#L129
	 * @return array
	 * @author Nicolas JUEN
	 */
	public function get_custom_data() {
		global $wp_query;
		$custom_data = [];

		// Set theme

		// Set Ajax/CLI/CRON/Gearman/Web
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$req_type = 'ajax';
		} elseif ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			$req_type = 'cron';
		} elseif ( defined( 'WP_CLI' ) && WP_CLI ) {
			$req_type = 'cli';
		} elseif ( is_admin() ) {
			$req_type = 'admin';
		} else {
			$req_type = 'web';
		}

		$theme                        = wp_get_theme();
		$custom_data['type']          = $req_type;
		$custom_data['theme']         = $theme->get( 'Name' );
		$custom_data['theme_version'] = $theme->get( 'Version' );

		// set transaction
		if ( is_front_page() && is_home() ) {
			$custom_data['page'] = 'Default Home Page';
		} elseif ( is_front_page() ) {
			$custom_data['page'] = 'Front Page';
		} elseif ( is_home() && false === get_query_var( 'sitemap', false ) ) {
			$custom_data['page'] = 'Blog Page';
		} elseif ( is_single() ) {
			$post_type           = ( ! empty( $wp_query->query['post_type'] ) ) ? $wp_query->query['post_type'] : 'Post';
			$custom_data['page'] = 'Single - ' . $post_type;
		} elseif ( is_page() ) {
			$custom_data['page'] = 'Page';
		} elseif ( is_date() ) {
			$custom_data['page'] = 'Date Archive';
		} elseif ( is_search() ) {
			$custom_data['page'] = 'Search Page' . isset( $wp_query->query['s'] ) ? '- ' . $wp_query->query['s'] : '';
		} elseif ( is_feed() ) {
			$custom_data['page'] = 'Feed';
		} elseif ( is_post_type_archive() ) {
			$post_type           = post_type_archive_title( '', false );
			$custom_data['page'] = 'Archive - ' . $post_type;
		} elseif ( is_category() ) {
			$custom_data['page'] = 'Category' . isset( $wp_query->query['category_name'] ) ? ' - ' . $wp_query->query['category_name'] : '';
		} elseif ( is_tag() ) {
			$custom_data['page'] = 'Tag' . isset( $wp_query->query['tag'] ) ? ' - ' . $wp_query->query['tag'] : '';
		} elseif ( is_tax() ) {
			$tax                 = key( $wp_query->tax_query->queried_terms );
			$term                = implode( ' | ', $wp_query->tax_query->queried_terms[ $tax ]['terms'] );
			$custom_data['page'] = 'Tax - ' . $tax . ' - ' . $term;
		} elseif ( defined( 'REST_REQUEST' ) && filter_var( REST_REQUEST, FILTER_VALIDATE_BOOLEAN ) ) {
			$custom_data['page'] = 'REST API';
		}

		return apply_filters( 'beapi_raygun_php_custom_data', $custom_data, $this );
	}

	/**
	 * The Exception handler to send everything to the API.
	 *
	 * @param $exception
	 *
	 * @author Nicolas JUEN
	 */
	public function exception_handler( $exception ) {
		$options = $this->get_options();
		$this->client->SendException( $exception, $options['withTags'], $this->get_custom_data() );
	}

	/**
	 * The classic error handler to send to the API.
	 *
	 * @param $errno
	 * @param $errstr
	 * @param $errfile
	 * @param $errline
	 *
	 * @author Nicolas JUEN
	 */
	public function error_handler( $errno, $errstr, $errfile, $errline ) {
		$options = $this->get_options();
		$this->client->SendError( $errno, $errstr, $errfile, $errline, $options['withTags'], $this->get_custom_data() );
	}
}
