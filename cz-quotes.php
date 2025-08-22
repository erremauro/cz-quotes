<?php
/**
 * Plugin Name: CZ Quotes
 * Description: Custom Post Type "Citazioni" with Author/Extra fields and [zen_quotes] shortcode.
 * Version:     0.0.1
 * Author:      CZ
 * Text Domain: cz-quotes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CZ_Quotes {
	const CPT                = 'cz_quote';
	const CPT_SLUG           = 'citazioni';
	const META_AUTHOR        = '_czq_author';
	const META_EXTRA         = '_czq_extra';
	const SHORTCODE          = 'zen_quotes';
	const TD                 = 'cz-quotes';

	public function __construct() {
		add_action( 'init', [ $this, 'register_cpt' ] );
		add_action( 'init', [ $this, 'register_meta' ] );

		add_action( 'add_meta_boxes', [ $this, 'register_metabox' ] );
		add_action( 'save_post_' . self::CPT, [ $this, 'save_metabox' ] );

		add_shortcode( self::SHORTCODE, [ $this, 'shortcode_zen_quotes' ] );

		// Enqueue CSS
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );

		register_activation_hook( __FILE__, [ __CLASS__, 'on_activate' ] );
		register_deactivation_hook( __FILE__, [ __CLASS__, 'on_deactivate' ] );
	}

	/** Register CPT "Citazioni" available at /citazioni */
	public function register_cpt() {
		$labels = [
			'name'          => __( 'Citazioni', self::TD ),
			'singular_name' => __( 'Citazione', self::TD ),
			'add_new_item'  => __( 'Aggiungi nuova Citazione', self::TD ),
			'edit_item'     => __( 'Modifica Citazione', self::TD ),
			'new_item'      => __( 'Nuova Citazione', self::TD ),
			'view_item'     => __( 'Vedi Citazione', self::TD ),
			'search_items'  => __( 'Cerca Citazioni', self::TD ),
			'all_items'     => __( 'Tutte le Citazioni', self::TD ),
			'menu_name'     => __( 'Citazioni', self::TD ),
		];

		register_post_type(
			self::CPT,
			[
				'labels'             => $labels,
				'public'             => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_rest'       => true,
				'menu_position'      => 20,
				'menu_icon'          => 'dashicons-format-quote',
				'has_archive'        => true,
				'rewrite'            => [ 'slug' => self::CPT_SLUG, 'with_front' => false ],
				'supports'           => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
				'capability_type'    => 'post',
				'publicly_queryable' => true,
			]
		);
	}

	/** Register Author/Extra meta */
	public function register_meta() {
		register_post_meta(
			self::CPT,
			self::META_AUTHOR,
			[
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'auth_callback'     => fn() => current_user_can( 'edit_posts' ),
				'sanitize_callback' => 'sanitize_text_field',
			]
		);

		register_post_meta(
			self::CPT,
			self::META_EXTRA,
			[
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'auth_callback'     => fn() => current_user_can( 'edit_posts' ),
				'sanitize_callback' => 'wp_kses_post',
			]
		);
	}

	/** Add metabox */
	public function register_metabox() {
		add_meta_box(
			'czq_metabox',
			__( 'Dettagli Citazione', self::TD ),
			[ $this, 'render_metabox' ],
			[ self::CPT ],
			'side',
			'default'
		);
	}

	/** Render metabox */
	public function render_metabox( $post ) {
		wp_nonce_field( 'czq_save_meta', 'czq_meta_nonce' );

		$author = get_post_meta( $post->ID, self::META_AUTHOR, true );
		$extra  = get_post_meta( $post->ID, self::META_EXTRA, true );

		echo '<p><label for="czq_author" style="display:block;font-weight:600;">' . esc_html__( 'Autore della Citazione', self::TD ) . '</label>';
		echo '<input type="text" id="czq_author" name="czq_author" value="' . esc_attr( $author ) . '" class="widefat" /></p>';

		echo '<p><label for="czq_extra" style="display:block;font-weight:600;">' . esc_html__( 'Informazioni Aggiuntive', self::TD ) . '</label>';
		echo '<textarea id="czq_extra" name="czq_extra" class="widefat" rows="5" placeholder="' . esc_attr__( 'Fonte, contesto, riferimenti…', self::TD ) . '">' . esc_textarea( $extra ) . '</textarea></p>';
	}

	/** Save metabox */
	public function save_metabox( $post_id ) {
		if ( ! isset( $_POST['czq_meta_nonce'] ) || ! wp_verify_nonce( $_POST['czq_meta_nonce'], 'czq_save_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( get_post_type( $post_id ) !== self::CPT ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['czq_author'] ) ) {
			update_post_meta( $post_id, self::META_AUTHOR, sanitize_text_field( wp_unslash( $_POST['czq_author'] ) ) );
		}
		if ( isset( $_POST['czq_extra'] ) ) {
			update_post_meta( $post_id, self::META_EXTRA, wp_kses_post( wp_unslash( $_POST['czq_extra'] ) ) );
		}
	}

	/** Enqueue CSS */
	public function enqueue_styles() {
		wp_register_style(
			'cz-quotes-style',
			plugins_url( 'assets/css/cz-quotes.css', __FILE__ ),
			[],
			file_exists( plugin_dir_path( __FILE__ ) . 'assets/css/cz-quotes.css' ) ? filemtime( plugin_dir_path( __FILE__ ) . 'assets/css/cz-quotes.css' ) : self::VERSION
		);
		wp_enqueue_style( 'cz-quotes-style' );
	}

	/** Shortcode */
	public function shortcode_zen_quotes( $atts ) {
		$atts = shortcode_atts(
			[
				'limit'     => 1,
				'frequency' => 'refresh',
				'show'      => 'random',
			],
			$atts,
			self::SHORTCODE
		);

		$limit     = max( 1, intval( $atts['limit'] ) );
		$frequency = in_array( $atts['frequency'], [ 'daily', 'refresh' ], true ) ? $atts['frequency'] : 'refresh';
		$show      = in_array( $atts['show'], [ 'latest', 'random' ], true ) ? $atts['show'] : 'random';

		// Daily cache
		$cache_key = '';
		if ( 'daily' === $frequency ) {
			$today     = current_time( 'Ymd' );
			$cache_key = 'czq_daily_' . md5( $today . '|' . $limit . '|' . $show );
			$cached    = get_transient( $cache_key );
			if ( $cached ) {
				return $this->render_quotes_by_ids( $cached );
			}
		}

		$args = [
			'post_type'      => self::CPT,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
		];

		if ( 'latest' === $show ) {
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
		} else {
			$args['orderby'] = 'rand';
		}

		$q = new WP_Query( $args );
		if ( ! $q->have_posts() ) {
			return '<div class="czq-quotes czq-empty">' . esc_html__( 'Nessuna citazione disponibile.', self::TD ) . '</div>';
		}

		$ids = wp_list_pluck( $q->posts, 'ID' );

		if ( 'daily' === $frequency ) {
			set_transient( $cache_key, $ids, DAY_IN_SECONDS );
		}

		return $this->render_quotes_by_ids( $ids );
	}

	private function render_quotes_by_ids( array $ids ) : string {
		$out = '<div class="czq-quotes">';
		foreach ( $ids as $pid ) {
			$title  = get_the_title( $pid );
			$link   = get_permalink( $pid );
			$body   = apply_filters( 'the_content', get_post_field( 'post_content', $pid ) );
			$author = get_post_meta( $pid, self::META_AUTHOR, true );
			$extra  = get_post_meta( $pid, self::META_EXTRA, true );

			$out .= '<article class="czq-quote">';
			$out .= '<div class="czq-quote__content">' . $body . '</div>';
			if ( $author ) {
				$out .= '<p class="czq-quote__author">' . esc_html( $author ) . '</p>';
			}
			if ( $extra ) {
				$out .= '<div class="czq-quote__extra">' . wp_kses_post( wpautop( $extra ) ) . '</div>';
			}
			$out .= '</article>';
		}
		$out .= '</div>';
		return $out;
	}

	public static function on_activate() {
		$inst = new self();
		$inst->register_cpt();
		flush_rewrite_rules();
	}

	public static function on_deactivate() {
		flush_rewrite_rules();
	}
}

new CZ_Quotes();
