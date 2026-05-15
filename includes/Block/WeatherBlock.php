<?php

declare( strict_types=1 );

namespace WeatherBlock\Block;

use WeatherBlock\API\WeatherEndpoint;

class WeatherBlock {

	public function register(): void {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	public function register_block(): void {
		register_block_type(
			WEATHER_BLOCK_PATH . 'block.json',
			[
				'render_callback' => [ $this, 'render' ],
			]
		);

		$this->register_frontend_script();
	}

	private function register_frontend_script(): void {
		$asset_file = WEATHER_BLOCK_PATH . 'build/frontend.asset.php';
		$asset      = file_exists( $asset_file )
			? require $asset_file
			: [ 'dependencies' => [], 'version' => WEATHER_BLOCK_VERSION ];

		wp_register_script(
			'weather-block-frontend',
			WEATHER_BLOCK_URL . 'build/frontend.js',
			$asset['dependencies'],
			$asset['version'],
			[ 'strategy' => 'defer', 'in_footer' => true ]
		);
	}

	public function render( array $attributes ): string {
		$post_ids   = array_map( 'absint', (array) ( $attributes['postIds'] ?? [] ) );
		$lat        = sanitize_text_field( (string) ( $attributes['lat'] ?? '' ) );
		$lon        = sanitize_text_field( (string) ( $attributes['lon'] ?? '' ) );
		$has_coords = '' !== $lat && '' !== $lon;

		$posts      = $this->get_valid_posts( $post_ids );
		$featured   = $posts[0] ?? null;
		$secondary1 = $posts[1] ?? null;
		// third slot: weather takes priority over the third post
		$secondary2 = $has_coords ? null : ( $posts[2] ?? null );

		if ( ! $featured && ! $secondary1 && ! $secondary2 && ! $has_coords ) {
			return '';
		}

		if ( $has_coords ) {
			$visibility = $this->extract_visibility( $attributes );
			wp_enqueue_script( 'weather-block-frontend' );
			wp_localize_script(
				'weather-block-frontend',
				'weatherBlockData',
				[
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( WeatherEndpoint::NONCE_ACTION ),
				]
			);
			wp_localize_script(
				'weather-block-frontend',
				'weatherBlockI18n',
				[
					'temperature' => __( 'Temperature', 'weather-block' ),
					'feelsLike'   => __( 'Feels like', 'weather-block' ),
					'humidity'    => __( 'Humidity', 'weather-block' ),
					'pressure'    => __( 'Pressure', 'weather-block' ),
					'windSpeed'   => __( 'Wind', 'weather-block' ),
					'sunrise'     => __( 'Sunrise', 'weather-block' ),
					'sunset'      => __( 'Sunset', 'weather-block' ),
					'error'       => __( 'Weather data unavailable.', 'weather-block' ),
				]
			);
		}

		$wrapper_attrs = get_block_wrapper_attributes();

		ob_start();
		?>
		<div <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div class="weather-block__posts">

				<?php if ( $featured ) : ?>
				<article class="weather-block__post-card weather-block__post-card--featured">
					<?php $this->render_post_image( $featured ); ?>
					<div class="weather-block__post-card-body">
						<?php $this->render_post_category( $featured ); ?>
						<h2 class="weather-block__post-card-title">
							<a href="<?php echo esc_url( (string) get_permalink( $featured->ID ) ); ?>">
								<?php echo esc_html( get_the_title( $featured ) ); ?>
							</a>
						</h2>
						<?php $this->render_post_excerpt( $featured ); ?>
					</div>
				</article>
				<?php endif; ?>

				<?php if ( $secondary1 || $secondary2 || $has_coords ) : ?>
				<div class="weather-block__posts-secondary">
					<?php if ( $secondary1 ) : ?>
						<?php $this->render_secondary_card( $secondary1 ); ?>
					<?php endif; ?>

					<?php if ( $has_coords ) : ?>
					<div
						class="weather-block__weather"
						data-lat="<?php echo esc_attr( $lat ); ?>"
						data-lon="<?php echo esc_attr( $lon ); ?>"
						data-visibility="<?php echo esc_attr( (string) wp_json_encode( $visibility ) ); ?>"
						aria-live="polite"
						aria-busy="true"
					>
						<p class="weather-block__weather-loading">
							<?php esc_html_e( 'Loading weather…', 'weather-block' ); ?>
						</p>
					</div>
					<?php elseif ( $secondary2 ) : ?>
						<?php $this->render_secondary_card( $secondary2 ); ?>
					<?php endif; ?>
				</div>
				<?php endif; ?>

			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	private function extract_visibility( array $attributes ): array {
		$fields = [
			'showLocation',
			'showTemperature',
			'showFeelsLike',
			'showCondition',
			'showHumidity',
			'showPressure',
			'showWindSpeed',
			'showSunrise',
			'showSunset',
		];

		$visibility = [];
		foreach ( $fields as $field ) {
			$visibility[ $field ] = (bool) ( $attributes[ $field ] ?? true );
		}

		return $visibility;
	}

	private function render_secondary_card( \WP_Post $post ): void {
		?>
		<article class="weather-block__post-card weather-block__post-card--secondary">
			<?php $this->render_post_image( $post ); ?>
			<div class="weather-block__post-card-body">
				<?php $this->render_post_category( $post ); ?>
				<h3 class="weather-block__post-card-title">
					<a href="<?php echo esc_url( (string) get_permalink( $post->ID ) ); ?>">
						<?php echo esc_html( get_the_title( $post ) ); ?>
					</a>
				</h3>
				<?php $this->render_post_excerpt( $post ); ?>
			</div>
		</article>
		<?php
	}

	private function render_post_image( \WP_Post $post ): void {
		$thumbnail_id  = (int) get_post_thumbnail_id( $post->ID );
		$thumbnail_url = $thumbnail_id
			? wp_get_attachment_image_url( $thumbnail_id, 'medium_large' )
			: false;
		$permalink = get_permalink( $post->ID );
		?>
		<a
			href="<?php echo esc_url( (string) $permalink ); ?>"
			class="weather-block__post-card-image-link"
			tabindex="-1"
			aria-hidden="true"
		>
			<?php if ( $thumbnail_url ) : ?>
			<div class="weather-block__post-card-image">
				<img
					src="<?php echo esc_url( $thumbnail_url ); ?>"
					alt="<?php echo esc_attr( get_the_title( $post ) ); ?>"
					loading="lazy"
					decoding="async"
				/>
			</div>
			<?php else : ?>
			<div class="weather-block__post-card-image weather-block__post-card-image--placeholder">
				<svg aria-hidden="true" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
					<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/>
				</svg>
			</div>
			<?php endif; ?>
		</a>
		<?php
	}

	private function render_post_category( \WP_Post $post ): void {
		$categories    = get_the_category( $post->ID );
		$category_name = ! empty( $categories ) ? $categories[0]->name : '';

		if ( ! $category_name ) {
			return;
		}
		?>
		<span class="weather-block__post-card-category">
			<?php echo esc_html( $category_name ); ?>
		</span>
		<?php
	}

	private function render_post_excerpt( \WP_Post $post ): void {
		$excerpt = get_the_excerpt( $post );

		if ( ! $excerpt ) {
			return;
		}
		?>
		<p class="weather-block__post-card-excerpt">
			<?php echo esc_html( $excerpt ); ?>
		</p>
		<?php
	}

	private function get_valid_posts( array $post_ids ): array {
		$posts = [];

		foreach ( array_slice( $post_ids, 0, 3 ) as $id ) {
			if ( ! $id ) {
				continue;
			}

			$post = get_post( $id );
			if ( $post instanceof \WP_Post && 'publish' === $post->post_status ) {
				$posts[] = $post;
			}
		}

		return $posts;
	}
}
