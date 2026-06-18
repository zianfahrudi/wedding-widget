<?php
/**
 * Custom Elementor template-library source backed by the private "ww_template"
 * CPT. Used as the data pipeline for inserting templates via the plugin's own
 * in-editor library modal (it is not surfaced in Elementor's default modal).
 *
 * @package WeddingWidget
 */

namespace WeddingWidget;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\TemplateLibrary\Source_Base' ) ) {
	return;
}

class WW_Template_Source extends \Elementor\TemplateLibrary\Source_Base {

	public function get_id() {
		return 'wedding-widget';
	}

	public function get_title() {
		return esc_html__( 'Wedding Widget', 'wedding-widget' );
	}

	public function register_data() {}

	public function get_items( $args = array() ) {
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
			$items[] = $this->get_item( $post->ID );
		}
		return $items;
	}

	public function get_item( $template_id ) {
		$post = get_post( $template_id );
		if ( ! $post ) {
			return array();
		}
		$type      = get_post_meta( $template_id, '_ww_template_type', true );
		$thumbnail = get_the_post_thumbnail_url( $template_id, 'medium' );

		return array(
			'template_id'     => $post->ID,
			'source'          => $this->get_id(),
			'type'            => $type ? $type : 'page',
			'subtype'         => '',
			'title'           => $post->post_title,
			'thumbnail'       => $thumbnail ? $thumbnail : '',
			'date'            => strtotime( $post->post_date ),
			'author'          => get_the_author_meta( 'display_name', $post->post_author ),
			'hasPageSettings' => false,
			'tags'            => array(),
			'url'             => '',
		);
	}

	/**
	 * Process a raw element tree for insertion (regenerate IDs + run on_import).
	 *
	 * @param array $content Raw elements array.
	 * @return array
	 */
	public function prepare_content_for_insert( $content ) {
		if ( empty( $content ) || ! is_array( $content ) ) {
			return array();
		}
		$content = $this->replace_elements_ids( $content );
		$content = $this->process_export_import_content( $content, 'on_import' );
		return $content;
	}

	public function get_data( array $args ) {
		$template_id = isset( $args['template_id'] ) ? absint( $args['template_id'] ) : 0;
		$content     = WW_Loader::get_template_content( $template_id );
		$content     = $this->prepare_content_for_insert( $content );
		$data        = WW_Loader::get_template_data( $template_id );

		return array(
			'content'       => $content,
			'page_settings' => ( isset( $data['page_settings'] ) && is_array( $data['page_settings'] ) ) ? $data['page_settings'] : array(),
			'type'          => isset( $data['type'] ) ? $data['type'] : 'page',
		);
	}

	public function delete_template( $template_id ) {
		return (bool) wp_delete_post( $template_id, true );
	}

	public function save_item( $template_data ) {
		return new \WP_Error( 'ww_not_supported', esc_html__( 'Use the Wedding Widget dashboard to add templates.', 'wedding-widget' ) );
	}

	public function update_item( $new_data ) {
		return new \WP_Error( 'ww_not_supported', esc_html__( 'Not supported.', 'wedding-widget' ) );
	}

	public function export_template( $template_id ) {
		return new \WP_Error( 'ww_not_supported', esc_html__( 'Not supported.', 'wedding-widget' ) );
	}
}
