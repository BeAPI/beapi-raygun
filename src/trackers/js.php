<?php

namespace BEAPI\Raygun\Trackers;

use BEAPI\Raygun\Framework\Hookable;

class JS implements Hookable {

	/**
	 * Hooks to add for making the JS script work.
	 *
	 * @author Nicolas JUEN
	 */
	public function hooks() {
		add_action( 'wp_head', [ $this, 'js_script' ], 0 );
		add_action( 'admin_print_scripts', [ $this, 'js_script' ], 0 );
	}

	/**
	 * JS script tag to add on the frontend AND backend.
	 *
	 * @author Nicolas JUEN
	 */
	public function js_script() {
		$options = $this->get_options();
		// @formatter:off
		?>
		<script type="text/javascript">
			!function(a,b,c,d,e,f,g,h){a.RaygunObject=e,a[e]=a[e]||function(){
			(a[e].o=a[e].o||[]).push(arguments)},f=b.createElement(c),g=b.getElementsByTagName(c)[0],
			f.async=1,f.src=d,g.parentNode.insertBefore(f,g),h=a.onerror,a.onerror=function(b,c,d,f,g){
			h&&h(b,c,d,f,g),g||(g=new Error(b)),a[e].q=a[e].q||[],a[e].q.push({
			e:g})}}(window,document,"script","//cdn.raygun.io/raygun4js/raygun.min.js","rg4js");
		</script>
		<script>
			<?php
			// @formatter:on
			foreach ( $options as $option => $value ) {
				$format = "rg4js( '%s', '%s' )\n";
				if ( \is_array( $value ) ) {
					$value  = wp_json_encode( $value );
					$format = "rg4js( '%s', %s )\n";
				}
				printf( $format, $option, $value );
			}
			?>
		</script>
		<?php
	}

	/**
	 * Get options for the JS stack.
	 *
	 * @return array
	 * @author Nicolas JUEN
	 */
	public function get_options() {
		$user = false;
		if ( function_exists( 'wp_get_current_user' ) ) {
			$user = wp_get_current_user();
		}

		return apply_filters( 'beapi_raygun_js_options', [
			'apiKey'               => BEAPI_RAYGUN_API_KEY,
			'enableCrashReporting' => true,
			'setVersion'           => get_bloginfo( 'version' ),
			'withTags'             => [ 'JS' ],
			'enablePulse'          => false,
			'setUser'              => ! $user ? false : [
				'isAnonymous' => false,
				'identifier'  => $user->ID,
				'email'       => $user->user_email,
				'firstName'   => $user->first_name,
				'fullName'    => $user->user_firstname . ' ' . $user->user_lastname,
			],
		], $this );
	}
}
