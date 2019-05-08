<?php

namespace BEAPI\Raygun;

use BEAPI\Raygun\Framework\Hookable;

class Compatibility implements Hookable {

	public function hooks() {
		add_action( 'admin_init', [ $this, 'admin_init' ] );
	}

	/**
	 * admin_init hook callback
	 *
	 * @since 0.1
	 */
	public function admin_init() {
		// Not on ajax
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		// Check activation
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		load_plugin_textdomain( 'beapi-raygun', false, \BEAPI_RAYGUN_DIR . '/languages' );

		add_action( 'admin_notices', [ $this, 'admin_notices' ] );
	}

	/**
	 * Notify the user about the incompatibility issue.
	 */
	public function admin_notices() {
		echo '<div class="notice error is-dismissible">';
		// translators: the %s is for displaying the debug help, the BEAPI_RAYGUN_API_KEY to defined into <code> tags.
		echo '<p>' . sprintf( esc_html__( 'You need to define the constant %s', 'beapi-raygun' ), '<code>BEAPI_RAYGUN_API_KEY</code>' ) . ' to make the plugin work.</p>';
		echo '</div>';
	}

}
