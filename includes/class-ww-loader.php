<?php
/**
 * Plugin loader: category, assets, widget registration and RSVP/Wishes AJAX.
 *
 * @package WeddingWidget
 */

namespace WeddingWidget;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WW_Loader {

	/**
	 * @var WW_Loader|null
	 */
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'register_assets' ) );
		add_action( 'elementor/frontend/after_register_styles', array( $this, 'register_styles' ) );

		// AJAX (logged in + guests).
		add_action( 'wp_ajax_ww_rsvp_submit', array( $this, 'ajax_rsvp_submit' ) );
		add_action( 'wp_ajax_nopriv_ww_rsvp_submit', array( $this, 'ajax_rsvp_submit' ) );
		add_action( 'wp_ajax_ww_wish_edit', array( $this, 'ajax_wish_edit' ) );
		add_action( 'wp_ajax_nopriv_ww_wish_edit', array( $this, 'ajax_wish_edit' ) );
		add_action( 'wp_ajax_ww_wish_delete', array( $this, 'ajax_wish_delete' ) );
		add_action( 'wp_ajax_nopriv_ww_wish_delete', array( $this, 'ajax_wish_delete' ) );

		// Admin Comments screen enhancements + dashboard.
		if ( is_admin() ) {
			require_once WEDDING_WIDGET_PATH . 'includes/class-ww-admin.php';
			new WW_Admin();

			require_once WEDDING_WIDGET_PATH . 'includes/class-ww-admin-dashboard.php';
			new WW_Admin_Dashboard();
		}

		// Private template library (kept out of Elementor's default "My Templates").
		add_action( 'init', array( $this, 'register_template_cpt' ) );
		add_action( 'elementor/init', array( $this, 'register_elementor_source' ) );

		// Custom in-editor template library (launcher icon + modal).
		add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'enqueue_editor_assets' ) );
		add_action( 'wp_ajax_ww_list_templates', array( $this, 'ajax_list_templates' ) );
		add_action( 'wp_ajax_ww_template_content', array( $this, 'ajax_template_content' ) );
	}

	/**
	 * Register the private CPT used to store imported Elementor templates.
	 * It is intentionally NOT an elementor_library post, so these templates do
	 * not appear in Elementor's default "My Templates" tab.
	 */
	public function register_template_cpt() {
		register_post_type(
			'ww_template',
			array(
				'label'           => esc_html__( 'Wedding Templates', 'wedding-widget' ),
				'public'          => false,
				'show_ui'         => false,
				'show_in_menu'    => false,
				'rewrite'         => false,
				'query_var'       => false,
				'supports'        => array( 'title', 'thumbnail' ),
				'capability_type' => 'post',
			)
		);

		register_taxonomy(
			'ww_template_category',
			'ww_template',
			array(
				'label'        => esc_html__( 'Template Categories', 'wedding-widget' ),
				'public'       => false,
				'show_ui'      => false,
				'hierarchical' => false,
				'rewrite'      => false,
				'query_var'    => false,
			)
		);
	}

	/**
	 * Register the custom Elementor source (data pipeline for get_data on insert).
	 */
	public function register_elementor_source() {
		if ( ! class_exists( '\Elementor\TemplateLibrary\Source_Base' ) ) {
			return;
		}
		require_once WEDDING_WIDGET_PATH . 'includes/class-ww-template-source.php';
		if ( isset( \Elementor\Plugin::instance()->templates_manager ) ) {
			\Elementor\Plugin::instance()->templates_manager->register_source( 'WeddingWidget\\WW_Template_Source' );
		}
	}

	/**
	 * Decode a stored template's JSON payload.
	 *
	 * @param int $post_id Template post ID.
	 * @return array
	 */
	public static function get_template_data( $post_id ) {
		$encoded = get_post_meta( $post_id, '_ww_template_data', true );
		if ( ! $encoded ) {
			return array();
		}
		$raw  = base64_decode( $encoded ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- internal storage, not obfuscation.
		$data = json_decode( $raw, true );
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Extract the importable element tree from a stored template.
	 *
	 * @param int $post_id Template post ID.
	 * @return array
	 */
	public static function get_template_content( $post_id ) {
		$data    = self::get_template_data( $post_id );
		$content = array();
		if ( isset( $data['content'] ) && is_array( $data['content'] ) ) {
			$content = $data['content'];
		} elseif ( isset( $data[0] ) && is_array( $data[0] ) ) {
			$content = $data;
		}
		return $content;
	}

	/**
	 * Enqueue the custom in-editor template-library assets.
	 */
	public function enqueue_editor_assets() {
		$js_rel  = 'assets/js/ww-editor.js';
		$css_rel = 'assets/css/ww-editor.css';
		$js_abs  = WEDDING_WIDGET_PATH . $js_rel;
		$css_abs = WEDDING_WIDGET_PATH . $css_rel;

		wp_enqueue_style( 'ww-editor', WEDDING_WIDGET_URL . $css_rel, array(), file_exists( $css_abs ) ? filemtime( $css_abs ) : WEDDING_WIDGET_VERSION );
		wp_enqueue_script( 'ww-editor', WEDDING_WIDGET_URL . $js_rel, array( 'jquery', 'elementor-editor' ), file_exists( $js_abs ) ? filemtime( $js_abs ) : WEDDING_WIDGET_VERSION, true );

		wp_localize_script(
			'ww-editor',
			'WWEditor',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ww_editor' ),
				'source'  => 'wedding-widget',
				'i18n'    => array(
					'library'   => esc_html__( 'Wedding Widget', 'wedding-widget' ),
					'title'     => esc_html__( 'Wedding Widget Templates', 'wedding-widget' ),
					'search'    => esc_html__( 'Search templates by name...', 'wedding-widget' ),
					'all'       => esc_html__( 'All', 'wedding-widget' ),
					'empty'     => esc_html__( 'No templates found. Upload some from Wedding Widget > Templates.', 'wedding-widget' ),
					'insert'    => esc_html__( 'Insert', 'wedding-widget' ),
					'inserting' => esc_html__( 'Inserting...', 'wedding-widget' ),
					'error'     => esc_html__( 'Could not insert the template.', 'wedding-widget' ),
				),
			)
		);
	}

	/**
	 * AJAX: list templates for the custom editor modal.
	 */
	public function ajax_list_templates() {
		check_ajax_referer( 'ww_editor', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'wedding-widget' ) ), 403 );
		}

		$posts = get_posts(
			array(
				'post_type'   => 'ww_template',
				'post_status' => 'publish',
				'numberposts' => -1,
				'orderby'     => 'date',
				'order'       => 'DESC',
			)
		);

		$items = array();
		foreach ( $posts as $post ) {
			$thumb = get_the_post_thumbnail_url( $post->ID, 'medium' );
			$terms = get_the_terms( $post->ID, 'ww_template_category' );
			$cats  = array();
			if ( $terms && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$cats[] = array(
						'slug' => $term->slug,
						'name' => $term->name,
					);
				}
			}
			$items[] = array(
				'id'         => $post->ID,
				'title'      => $post->post_title,
				'type'       => get_post_meta( $post->ID, '_ww_template_type', true ) ? get_post_meta( $post->ID, '_ww_template_type', true ) : 'page',
				'thumbnail'  => $thumb ? $thumb : '',
				'categories' => $cats,
			);
		}

		wp_send_json_success( array( 'templates' => $items ) );
	}

	/**
	 * AJAX: return the processed element tree for a template, ready to insert.
	 */
	public function ajax_template_content() {
		check_ajax_referer( 'ww_editor', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'wedding-widget' ) ), 403 );
		}

		$id      = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$post    = $id ? get_post( $id ) : null;
		if ( ! $post || 'ww_template' !== $post->post_type ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Template not found.', 'wedding-widget' ) ), 404 );
		}

		$content = self::get_template_content( $id );
		if ( empty( $content ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Template is empty.', 'wedding-widget' ) ), 400 );
		}

		// Process through Elementor's import pipeline (regenerate IDs + on_import).
		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::instance()->templates_manager ) ) {
			$source = \Elementor\Plugin::instance()->templates_manager->get_source( 'wedding-widget' );
			if ( $source && method_exists( $source, 'prepare_content_for_insert' ) ) {
				$content = $source->prepare_content_for_insert( $content );
			}
		}

		wp_send_json_success(
			array(
				'content' => $content,
				'type'    => get_post_meta( $id, '_ww_template_type', true ) ? get_post_meta( $id, '_ww_template_type', true ) : 'page',
			)
		);
	}

	/**
	 * Register a dedicated Elementor category.
	 *
	 * @param \Elementor\Elements_Manager $manager Elements manager.
	 */
	public function register_category( $manager ) {
		$manager->add_category(
			'wedding-widget',
			array(
				'title' => esc_html__( 'Wedding Widget', 'wedding-widget' ),
				'icon'  => 'eicon-heart',
			)
		);
	}

	/**
	 * Register frontend scripts.
	 */
	public function register_assets() {
		$rel = 'assets/js/wedding-widget.js';
		$abs = WEDDING_WIDGET_PATH . $rel;
		$ver = file_exists( $abs ) ? filemtime( $abs ) : WEDDING_WIDGET_VERSION;

		wp_register_script( 'wedding-widget', WEDDING_WIDGET_URL . $rel, array(), $ver, true );
		wp_localize_script(
			'wedding-widget',
			'WeddingWidgetData',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'ww_rsvp' ),
				'canModerate'  => current_user_can( 'moderate_comments' ),
				'i18n'         => array(
					'sending'       => esc_html__( 'Sending...', 'wedding-widget' ),
					'thanks'        => esc_html__( 'Thank you! Your response has been recorded.', 'wedding-widget' ),
					'error'         => esc_html__( 'Something went wrong. Please try again.', 'wedding-widget' ),
					'required'      => esc_html__( 'Please fill in your name and message.', 'wedding-widget' ),
					'copied'        => esc_html__( 'Copied!', 'wedding-widget' ),
					'reply'         => esc_html__( 'Reply', 'wedding-widget' ),
					'edit'          => esc_html__( 'Edit', 'wedding-widget' ),
					'delete'        => esc_html__( 'Delete', 'wedding-widget' ),
					'save'          => esc_html__( 'Save', 'wedding-widget' ),
					'cancel'        => esc_html__( 'Cancel', 'wedding-widget' ),
					'confirmDelete' => esc_html__( 'Delete this wish?', 'wedding-widget' ),
					'replyPlaceholder' => esc_html__( 'Write a reply...', 'wedding-widget' ),
				),
			)
		);
	}

	/**
	 * Register frontend styles.
	 */
	public function register_styles() {
		$rel = 'assets/css/wedding-widget.css';
		$abs = WEDDING_WIDGET_PATH . $rel;
		$ver = file_exists( $abs ) ? filemtime( $abs ) : WEDDING_WIDGET_VERSION;

		wp_register_style( 'wedding-widget', WEDDING_WIDGET_URL . $rel, array(), $ver );
	}

	/**
	 * Register all widgets.
	 *
	 * @param \Elementor\Widgets_Manager $manager Widgets manager.
	 */
	public function register_widgets( $manager ) {
		$widgets = array(
			'class-ww-countdown.php' => 'WeddingWidget\\Widgets\\WW_Countdown',
			'class-ww-cover.php'     => 'WeddingWidget\\Widgets\\WW_Cover',
			'class-ww-rsvp.php'      => 'WeddingWidget\\Widgets\\WW_RSVP',
			'class-ww-whatsapp.php'  => 'WeddingWidget\\Widgets\\WW_WhatsApp',
			'class-ww-copy.php'      => 'WeddingWidget\\Widgets\\WW_Copy',
			'class-ww-calendar.php'  => 'WeddingWidget\\Widgets\\WW_Calendar',
			'class-ww-music.php'     => 'WeddingWidget\\Widgets\\WW_Music',
			'class-ww-timeline.php'  => 'WeddingWidget\\Widgets\\WW_Timeline',
			'class-ww-qr.php'        => 'WeddingWidget\\Widgets\\WW_QR',
			'class-ww-wishes.php'    => 'WeddingWidget\\Widgets\\WW_Wishes',
		);

		foreach ( $widgets as $file => $class ) {
			require_once WEDDING_WIDGET_PATH . 'includes/widgets/' . $file;
			if ( class_exists( $class ) ) {
				$manager->register( new $class() );
			}
		}
	}

	/**
	 * Handle a wish / RSVP submission (also handles replies via "parent").
	 */
	public function ajax_rsvp_submit() {
		check_ajax_referer( 'ww_rsvp', 'nonce' );

		$post_id    = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$name       = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$message    = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
		$attendance = isset( $_POST['attendance'] ) ? sanitize_key( wp_unslash( $_POST['attendance'] ) ) : '';
		$kind       = isset( $_POST['kind'] ) ? sanitize_key( wp_unslash( $_POST['kind'] ) ) : 'ww_rsvp';
		$parent     = isset( $_POST['parent'] ) ? absint( $_POST['parent'] ) : 0;

		if ( ! in_array( $kind, array( 'ww_rsvp', 'ww_wish' ), true ) ) {
			$kind = 'ww_rsvp';
		}

		$allowed_attendance = array( 'attending', 'not_attending', 'maybe' );
		if ( '' !== $attendance && ! in_array( $attendance, $allowed_attendance, true ) ) {
			$attendance = 'attending';
		}

		if ( ! $post_id || '' === $name || '' === $message ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Missing required fields.', 'wedding-widget' ) ), 400 );
		}

		$post = get_post( $post_id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid invitation.', 'wedding-widget' ) ), 400 );
		}

		// Validate parent (for replies): must belong to the same post and be a wish/rsvp.
		if ( $parent > 0 ) {
			$parent_comment = get_comment( $parent );
			if ( ! $parent_comment
				|| (int) $parent_comment->comment_post_ID !== $post_id
				|| ! in_array( $parent_comment->comment_type, array( 'ww_rsvp', 'ww_wish' ), true ) ) {
				$parent = 0;
			}
		}

		$name    = mb_substr( $name, 0, 120 );
		$message = mb_substr( $message, 0, 2000 );

		// Capture the commenter IP so it can be shown in the admin Comments screen.
		$ip = '';
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$candidate = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
			$ip        = filter_var( $candidate, FILTER_VALIDATE_IP ) ? $candidate : '';
		}

		$comment_id = wp_insert_comment(
			array(
				'comment_post_ID'      => $post_id,
				'comment_author'       => $name,
				'comment_content'      => $message,
				'comment_type'         => $kind,
				'comment_parent'       => $parent,
				'comment_approved'     => 1,
				'comment_author_IP'    => $ip,
				'comment_author_email' => '',
			)
		);

		if ( ! $comment_id ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Could not save your response.', 'wedding-widget' ) ), 500 );
		}

		if ( '' !== $attendance && 0 === $parent ) {
			add_comment_meta( $comment_id, 'ww_attendance', $attendance, true );
		}

		// Ownership token so the (guest) author can later edit/delete this entry.
		$token = wp_generate_password( 20, false );
		add_comment_meta( $comment_id, 'ww_token', $token, true );

		wp_send_json_success(
			array(
				'commentId'      => (int) $comment_id,
				'token'          => $token,
				'parent'         => (int) $parent,
				'name'           => $name,
				'initials'       => self::initials( $name ),
				'avatarColor'    => self::avatar_color( $name ),
				'message'        => $message,
				'attendance'     => $attendance,
				'attendanceText' => '' !== $attendance ? self::attendance_label( $attendance ) : '',
				'date'           => date_i18n( 'd M Y, H:i' ),
			)
		);
	}

	/**
	 * Edit an existing wish (author via token, or a moderator).
	 */
	public function ajax_wish_edit() {
		check_ajax_referer( 'ww_rsvp', 'nonce' );

		$comment_id = isset( $_POST['comment_id'] ) ? absint( $_POST['comment_id'] ) : 0;
		$token      = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
		$message    = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

		$comment = $comment_id ? get_comment( $comment_id ) : null;
		if ( ! $comment || ! in_array( $comment->comment_type, array( 'ww_rsvp', 'ww_wish' ), true ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Entry not found.', 'wedding-widget' ) ), 404 );
		}

		if ( ! $this->can_manage( $comment_id, $token ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'You are not allowed to edit this.', 'wedding-widget' ) ), 403 );
		}

		if ( '' === $message ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Message cannot be empty.', 'wedding-widget' ) ), 400 );
		}

		$message = mb_substr( $message, 0, 2000 );
		wp_update_comment(
			array(
				'comment_ID'      => $comment_id,
				'comment_content' => $message,
			)
		);

		wp_send_json_success( array( 'message' => $message ) );
	}

	/**
	 * Delete a wish (author via token, or a moderator). Children are removed too.
	 */
	public function ajax_wish_delete() {
		check_ajax_referer( 'ww_rsvp', 'nonce' );

		$comment_id = isset( $_POST['comment_id'] ) ? absint( $_POST['comment_id'] ) : 0;
		$token      = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';

		$comment = $comment_id ? get_comment( $comment_id ) : null;
		if ( ! $comment || ! in_array( $comment->comment_type, array( 'ww_rsvp', 'ww_wish' ), true ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Entry not found.', 'wedding-widget' ) ), 404 );
		}

		if ( ! $this->can_manage( $comment_id, $token ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'You are not allowed to delete this.', 'wedding-widget' ) ), 403 );
		}

		// Remove direct replies first.
		$children = get_comments(
			array(
				'parent' => $comment_id,
				'status' => 'all',
			)
		);
		foreach ( $children as $child ) {
			wp_delete_comment( $child->comment_ID, true );
		}
		wp_delete_comment( $comment_id, true );

		wp_send_json_success( array( 'commentId' => $comment_id ) );
	}

	/**
	 * Whether the current request may edit/delete a given comment.
	 *
	 * @param int    $comment_id Comment ID.
	 * @param string $token      Ownership token supplied by the client.
	 * @return bool
	 */
	private function can_manage( $comment_id, $token ) {
		if ( current_user_can( 'moderate_comments' ) ) {
			return true;
		}
		$stored = (string) get_comment_meta( $comment_id, 'ww_token', true );
		return '' !== $stored && is_string( $token ) && '' !== $token && hash_equals( $stored, $token );
	}

	/**
	 * Build up-to-two-letter initials from a name.
	 *
	 * @param string $name Name.
	 * @return string
	 */
	public static function initials( $name ) {
		$name = trim( wp_strip_all_tags( (string) $name ) );
		if ( '' === $name ) {
			return '?';
		}
		$parts = preg_split( '/\s+/', $name );
		$first = function_exists( 'mb_substr' ) ? mb_substr( $parts[0], 0, 1 ) : substr( $parts[0], 0, 1 );
		$out   = $first;
		if ( count( $parts ) > 1 ) {
			$last = end( $parts );
			$out .= function_exists( 'mb_substr' ) ? mb_substr( $last, 0, 1 ) : substr( $last, 0, 1 );
		}
		return function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $out ) : strtoupper( $out );
	}

	/**
	 * Deterministic accent color for an avatar, derived from the name.
	 *
	 * @param string $name Name.
	 * @return string HSL color.
	 */
	public static function avatar_color( $name ) {
		$hash = crc32( strtolower( trim( (string) $name ) ) );
		$hue  = $hash % 360;
		return 'hsl(' . $hue . ', 55%, 55%)';
	}

	/**
	 * Human-readable attendance label.
	 *
	 * @param string $key Attendance key.
	 * @return string
	 */
	public static function attendance_label( $key ) {
		switch ( $key ) {
			case 'attending':
				return esc_html__( 'Attending', 'wedding-widget' );
			case 'not_attending':
				return esc_html__( 'Not Attending', 'wedding-widget' );
			case 'maybe':
				return esc_html__( 'Maybe', 'wedding-widget' );
		}
		return '';
	}
}
