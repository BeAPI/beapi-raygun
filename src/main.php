<?php

namespace BEAPI\Raygun;

use BEAPI\Raygun\Framework\Bootable;
use BEAPI\Raygun\Framework\Hookable;
use BEAPI\Raygun\Trackers\JS;
use BEAPI\Raygun\Trackers\PHP;

class Main {
	public function register() {

		if ( ! $this->is_ready() ) {
			// possibly display a notice, trigger error
			( new Compatibility() )->hooks();

			return;
		}

		$classes = [
			JS::class,
			PHP::class,
		];

		foreach ( $classes as $class ) {
			$cclass     = new $class();
			$interfaces = class_implements( $cclass );

			if ( isset( $interfaces[ Bootable::class ] ) ) {
				$cclass->boot();
			}

			if ( isset( $interfaces[ Hookable::class ] ) ) {
				$cclass->hooks();
			}
		}
	}

	public function is_ready() {
		return defined( 'BEAPI_RAYGUN_API_KEY' ) && ! empty( BEAPI_RAYGUN_API_KEY );
	}
}
