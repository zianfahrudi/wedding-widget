<?php
/**
 * Seeds bundled template JSON files (shipped inside the plugin) into the
 * private "ww_template" CPT, so templates are available immediately after the
 * plugin is installed/activated — no manual upload required.
 *
 * Drop Elementor template JSON exports into the plugin's /templates folder.
 * Optionally add a same-named image next to each JSON (elegant.json +
 * elegant.jpg) to use as its preview thumbnail, and/or a templates/manifest.json
 * to define titles and categories.
 *
 * @package WeddingWidget
 */

namespace WeddingWidget;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WW_Template_Seeder {

	/**
	 * Option that records which bundled files have already been seeded.
	 * Format: array( '<relative file> => <md5 of contents>' ).
	 */
	const OPTION = 'ww_seeded_templates';

	/**
	 * Relative folder (inside the plugin) that holds the bundled JSON files.
	 */
	const DIR = 'templates';

	/**
	 * Run the seeder once per new/changed bundled file.
	 *
	 * Scans the /templates folder and any sub-folders. A JSON file placed inside
	 * a sub-folder is automatically assigned that folder's name as its category
	 * (e.g. templates/flower/rustic.json -> category "Flower"). Files in the
	 * root have no category unless set via manifest.json.
	 *
	 * Safe to call on every load: it short-circuits quickly when there is
	 * nothing new to import.
	 */
	public function maybe_seed() {
		$root = trailingslashit( WEDDING_WIDGET_PATH . self::DIR );
		if ( ! is_dir( $root ) ) {
			return;
		}

		$files = $this->collect_json_files( $root );
		if ( empty( $files ) ) {
			return;
		}

		$seeded   = get_option( self::OPTION, array() );
		$seeded   = is_array( $seeded ) ? $seeded : array();
		$manifest = $this->load_manifest( $root );
		$changed  = false;

		foreach ( $files as $file ) {
			$basename = basename( $file );

			// Skip any manifest files.
			if ( 'manifest.json' === $basename ) {
				continue;
			}

			$raw = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local bundled file.
			if ( false === $raw ) {
				continue;
			}

			// Key by path relative to the templates root so the same filename can
			// live in different category folders without colliding.
			$relative = ltrim( str_replace( $root, '', $file ), '/\\' );
			$hash     = md5( $raw );

			// Already imported and unchanged -> skip.
			if ( isset( $seeded[ $relative ] ) && $seeded[ $relative ] === $hash ) {
				continue;
			}

			$data = json_decode( $raw, true );
			if ( ! is_array( $data ) || ( ! isset( $data['content'] ) && ! isset( $data[0] ) ) ) {
				continue;
			}

			// Manifest may be keyed by relative path ("flower/rustic.json") or
			// by bare filename ("rustic.json").
			$meta = array();
			if ( isset( $manifest[ $relative ] ) && is_array( $manifest[ $relative ] ) ) {
				$meta = $manifest[ $relative ];
			} elseif ( isset( $manifest[ $basename ] ) && is_array( $manifest[ $basename ] ) ) {
				$meta = $manifest[ $basename ];
			}

			// Default category from the immediate sub-folder name.
			if ( empty( $meta['category'] ) ) {
				$folder = $this->category_from_path( $root, $file );
				if ( '' !== $folder ) {
					$meta['category'] = $folder;
				}
			}

			$post_id = $this->import_one( $basename, $raw, $data, $meta, trailingslashit( dirname( $file ) ) );
			if ( $post_id ) {
				$seeded[ $relative ] = $hash;
				$changed             = true;
			}
		}

		if ( $changed ) {
			update_option( self::OPTION, $seeded, false );
		}
	}

	/**
	 * Recursively collect every *.json file under the templates root.
	 *
	 * @param string $root Templates directory (trailing slash).
	 * @return string[] Absolute file paths.
	 */
	private function collect_json_files( $root ) {
		$found = glob( $root . '*.json' );
		$found = is_array( $found ) ? $found : array();

		$dirs = glob( $root . '*', GLOB_ONLYDIR );
		if ( is_array( $dirs ) ) {
			foreach ( $dirs as $dir ) {
				$found = array_merge( $found, $this->collect_json_files( trailingslashit( $dir ) ) );
			}
		}
		return $found;
	}

	/**
	 * Derive a human-friendly category from the file's immediate parent folder
	 * (relative to the templates root). Returns '' for files in the root.
	 *
	 * @param string $root Templates root (trailing slash).
	 * @param string $file Absolute file path.
	 * @return string
	 */
	private function category_from_path( $root, $file ) {
		$relative_dir = trim( str_replace( $root, '', trailingslashit( dirname( $file ) ) ), '/\\' );
		if ( '' === $relative_dir ) {
			return '';
		}
		// Use the first path segment as the category.
		$segments = preg_split( '#[/\\\\]#', $relative_dir );
		$slug     = $segments[0];
		// Humanize: "adat-jawa" / "adat_jawa" -> "Adat Jawa".
		$name = str_replace( array( '-', '_' ), ' ', $slug );
		$name = ucwords( $name );
		return sanitize_text_field( $name );
	}

	/**
	 * Import a single bundled template into the CPT.
	 *
	 * @param string $basename JSON file name.
	 * @param string $raw      Raw JSON contents.
	 * @param array  $data     Decoded JSON.
	 * @param array  $meta     Optional manifest entry (title/category/thumbnail).
	 * @param string $dir      Templates directory (trailing slash).
	 * @return int Post ID on success, 0 on failure.
	 */
	private function import_one( $basename, $raw, $data, $meta, $dir ) {
		// Title: manifest > JSON title > filename.
		if ( ! empty( $meta['title'] ) ) {
			$title = sanitize_text_field( $meta['title'] );
		} elseif ( ! empty( $data['title'] ) ) {
			$title = sanitize_text_field( $data['title'] );
		} else {
			$title = sanitize_file_name( pathinfo( $basename, PATHINFO_FILENAME ) );
		}
		if ( '' === $title ) {
			$title = __( 'Template', 'wedding-widget' );
		}

		// Type.
		$type    = ! empty( $data['type'] ) ? sanitize_key( $data['type'] ) : 'page';
		$allowed = array( 'page', 'section', 'container', 'header', 'footer', 'single', 'archive', 'popup', 'widget' );
		if ( ! in_array( $type, $allowed, true ) ) {
			$type = 'page';
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => 'ww_template',
				'post_status' => 'publish',
				'post_title'  => $title,
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			return 0;
		}

		update_post_meta( $post_id, '_ww_template_data', base64_encode( $raw ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- safe storage, not obfuscation.
		update_post_meta( $post_id, '_ww_template_type', $type );

		// Mark as bundled so it can be distinguished from manual uploads.
		update_post_meta( $post_id, '_ww_template_bundled', $basename );

		// Category from manifest.
		if ( ! empty( $meta['category'] ) ) {
			$term_id = $this->resolve_category( $meta['category'] );
			if ( $term_id ) {
				wp_set_object_terms( $post_id, array( $term_id ), 'ww_template_category' );
			}
		}

		// Thumbnail: manifest filename, or same-named image next to the JSON.
		$thumb_file = '';
		if ( ! empty( $meta['thumbnail'] ) ) {
			$candidate = $dir . basename( $meta['thumbnail'] );
			if ( file_exists( $candidate ) ) {
				$thumb_file = $candidate;
			}
		}
		if ( '' === $thumb_file ) {
			$base = pathinfo( $basename, PATHINFO_FILENAME );
			foreach ( array( 'jpg', 'jpeg', 'png', 'webp', 'gif' ) as $ext ) {
				$candidate = $dir . $base . '.' . $ext;
				if ( file_exists( $candidate ) ) {
					$thumb_file = $candidate;
					break;
				}
			}
		}
		if ( '' !== $thumb_file ) {
			$thumb_id = $this->sideload_local_image( $thumb_file );
			if ( $thumb_id ) {
				set_post_thumbnail( $post_id, $thumb_id );
			}
		}

		return (int) $post_id;
	}

	/**
	 * Load the optional manifest.json describing bundled templates.
	 *
	 * @param string $dir Templates directory (trailing slash).
	 * @return array Map of "<file.json>" => array( title, category, thumbnail ).
	 */
	private function load_manifest( $dir ) {
		$path = $dir . 'manifest.json';
		if ( ! file_exists( $path ) ) {
			return array();
		}
		$raw = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local bundled file.
		$map = json_decode( $raw, true );
		return is_array( $map ) ? $map : array();
	}

	/**
	 * Resolve (or create) a category term, returning its term ID (0 if none).
	 *
	 * @param string $name Category name.
	 * @return int
	 */
	private function resolve_category( $name ) {
		$name = sanitize_text_field( $name );
		if ( '' === $name ) {
			return 0;
		}
		$existing = term_exists( $name, 'ww_template_category' );
		if ( $existing && ! is_wp_error( $existing ) ) {
			return (int) $existing['term_id'];
		}
		$new = wp_insert_term( $name, 'ww_template_category' );
		return is_wp_error( $new ) ? 0 : (int) $new['term_id'];
	}

	/**
	 * Copy a bundled image into the Media Library and return its attachment ID.
	 *
	 * @param string $path Absolute path to the bundled image.
	 * @return int Attachment ID, or 0 on failure.
	 */
	private function sideload_local_image( $path ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$check   = wp_check_filetype( $path );
		$allowed = array( 'jpg', 'jpeg', 'png', 'gif', 'webp' );
		if ( empty( $check['ext'] ) || ! in_array( strtolower( $check['ext'] ), $allowed, true ) ) {
			return 0;
		}

		// Copy to a temp file so media_handle_sideload can move it without
		// touching the original bundled asset.
		$tmp = wp_tempnam( basename( $path ) );
		if ( ! $tmp || ! @copy( $path, $tmp ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			return 0;
		}

		$file_array = array(
			'name'     => basename( $path ),
			'tmp_name' => $tmp,
		);

		$attachment_id = media_handle_sideload( $file_array, 0, null, array( 'test_form' => false ) );
		if ( is_wp_error( $attachment_id ) ) {
			@unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink
			return 0;
		}
		return (int) $attachment_id;
	}
}
