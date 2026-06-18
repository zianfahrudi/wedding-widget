<?php
/**
 * Admin dashboard: overview page + template (JSON) manager.
 *
 * @package WeddingWidget
 */

namespace WeddingWidget;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WW_Admin_Dashboard {

	const CAP  = 'manage_options';
	const SLUG = 'wedding-widget';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_ww_upload_template', array( $this, 'handle_upload' ) );
		add_action( 'admin_post_ww_edit_template', array( $this, 'handle_edit' ) );
		add_action( 'admin_post_ww_delete_template', array( $this, 'handle_delete' ) );
		add_action( 'admin_post_ww_bulk_delete', array( $this, 'handle_bulk_delete' ) );
	}

	public function register_menu() {
		add_menu_page(
			esc_html__( 'Wedding Widget', 'wedding-widget' ),
			esc_html__( 'Wedding Widget', 'wedding-widget' ),
			self::CAP,
			self::SLUG,
			array( $this, 'render_dashboard' ),
			'dashicons-heart',
			58
		);

		add_submenu_page(
			self::SLUG,
			esc_html__( 'Dashboard', 'wedding-widget' ),
			esc_html__( 'Dashboard', 'wedding-widget' ),
			self::CAP,
			self::SLUG,
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			self::SLUG,
			esc_html__( 'Templates', 'wedding-widget' ),
			esc_html__( 'Templates', 'wedding-widget' ),
			self::CAP,
			self::SLUG . '-templates',
			array( $this, 'render_templates' )
		);
	}

	/* --------------------------------------------------------------------- */
	/* Pages                                                                 */
	/* --------------------------------------------------------------------- */

	private function widgets_list() {
		return array(
			'Countdown', 'Cover', 'RSVP', 'WhatsApp', 'Copy Text',
			'Add to Calendar', 'Music', 'Timeline', 'QR Code', 'Wishes',
		);
	}

	/**
	 * Query templates managed by this plugin (stored in Elementor's local library).
	 *
	 * @return \WP_Post[]
	 */
	private function query_templates() {
		return get_posts(
			array(
				'post_type'   => 'ww_template',
				'post_status' => 'publish',
				'numberposts' => -1,
				'orderby'     => 'date',
				'order'       => 'DESC',
			)
		);
	}

	public function render_dashboard() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		$template_count = count( $this->query_templates() );
		?>
		<div class="wrap ww-dash">
			<h1><?php esc_html_e( 'Wedding Widget', 'wedding-widget' ); ?></h1>
			<p class="description"><?php esc_html_e( 'A set of independent Elementor widgets for wedding invitation sites.', 'wedding-widget' ); ?></p>

			<div class="ww-dash__cards">
				<div class="ww-dash__card">
					<h2><?php esc_html_e( 'Widgets', 'wedding-widget' ); ?></h2>
					<p><?php echo count( $this->widgets_list() ); ?> <?php esc_html_e( 'Elementor widgets under the "Wedding Widget" category.', 'wedding-widget' ); ?></p>
					<ul class="ww-dash__chips">
						<?php foreach ( $this->widgets_list() as $w ) : ?>
							<li><?php echo esc_html( $w ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>

				<div class="ww-dash__card">
					<h2><?php esc_html_e( 'Templates', 'wedding-widget' ); ?></h2>
					<p>
						<?php
						/* translators: %d: number of templates */
						printf( esc_html__( '%d template(s) available in the Elementor library.', 'wedding-widget' ), $template_count );
						?>
					</p>
					<p><?php esc_html_e( 'Upload Elementor template JSON files. They appear in a dedicated "Wedding Widget" library (the heart icon) inside the Elementor editor canvas — not in Elementor\'s default "My Templates".', 'wedding-widget' ); ?></p>
					<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG . '-templates' ) ); ?>"><?php esc_html_e( 'Manage Templates', 'wedding-widget' ); ?></a>
				</div>
			</div>
		</div>
		<style>
			.ww-dash__cards{display:flex;flex-wrap:wrap;gap:20px;margin-top:20px}
			.ww-dash__card{flex:1 1 320px;background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:18px 20px}
			.ww-dash__card h2{margin-top:0}
			.ww-dash__chips{display:flex;flex-wrap:wrap;gap:8px;list-style:none;margin:12px 0 0;padding:0}
			.ww-dash__chips li{background:#f0f0f1;border-radius:999px;padding:4px 12px;font-size:12px}
		</style>
		<?php
	}

	public function render_templates() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}

		$notice = isset( $_GET['ww_msg'] ) ? sanitize_key( wp_unslash( $_GET['ww_msg'] ) ) : '';
		$count  = isset( $_GET['ww_n'] ) ? absint( $_GET['ww_n'] ) : 0;

		$categories = get_terms(
			array(
				'taxonomy'   => 'ww_template_category',
				'hide_empty' => false,
			)
		);
		if ( is_wp_error( $categories ) ) {
			$categories = array();
		}

		// Edit mode.
		$edit_id   = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
		$edit_post = $edit_id ? get_post( $edit_id ) : null;
		if ( $edit_post && 'ww_template' === $edit_post->post_type ) {
			$this->render_edit_form( $edit_post, $categories );
			return;
		}

		$templates = $this->query_templates();
		?>
		<div class="wrap ww-tpl">
			<h1><?php esc_html_e( 'Templates', 'wedding-widget' ); ?></h1>

			<?php if ( 'uploaded' === $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p>
					<?php
					/* translators: %d: number of templates uploaded */
					printf( esc_html( _n( '%d template uploaded.', '%d templates uploaded.', max( 1, $count ), 'wedding-widget' ) ), (int) $count );
					?>
				</p></div>
			<?php elseif ( 'deleted' === $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p>
					<?php
					/* translators: %d: number of templates deleted */
					printf( esc_html( _n( '%d template deleted.', '%d templates deleted.', max( 1, $count ), 'wedding-widget' ) ), (int) $count );
					?>
				</p></div>
			<?php elseif ( 'edited' === $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Template updated.', 'wedding-widget' ); ?></p></div>
			<?php elseif ( 'invalid' === $notice ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Invalid or unreadable JSON file.', 'wedding-widget' ); ?></p></div>
			<?php elseif ( 'toobig' === $notice ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'File is too large (max 3 MB).', 'wedding-widget' ); ?></p></div>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Upload Templates (JSON)', 'wedding-widget' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Export templates/pages from Elementor as JSON, then upload one or many at once. Optionally assign a category and a preview thumbnail.', 'wedding-widget' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" style="margin:14px 0 28px;">
				<input type="hidden" name="action" value="ww_upload_template">
				<?php wp_nonce_field( 'ww_upload_template' ); ?>
				<table class="form-table" style="max-width:640px;">
					<tr>
						<th scope="row"><label for="ww_template_file"><?php esc_html_e( 'Template JSON (multiple allowed)', 'wedding-widget' ); ?></label></th>
						<td><input type="file" id="ww_template_file" name="ww_template_file[]" accept="application/json,.json" multiple required></td>
					</tr>
					<tr>
						<th scope="row"><label for="ww_template_category"><?php esc_html_e( 'Category', 'wedding-widget' ); ?></label></th>
						<td>
							<input type="text" id="ww_template_category" name="ww_template_category" class="regular-text" list="ww_cat_list" placeholder="<?php esc_attr_e( 'e.g. Adat, Flower, Minimalist', 'wedding-widget' ); ?>">
							<datalist id="ww_cat_list">
								<?php foreach ( $categories as $cat ) : ?>
									<option value="<?php echo esc_attr( $cat->name ); ?>"></option>
								<?php endforeach; ?>
							</datalist>
							<p class="description"><?php esc_html_e( 'Applied to all files in this upload. Type a new name to create a category.', 'wedding-widget' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ww_template_thumb"><?php esc_html_e( 'Thumbnail (optional)', 'wedding-widget' ); ?></label></th>
						<td>
							<input type="file" id="ww_template_thumb" name="ww_template_thumb" accept="image/*">
							<p class="description"><?php esc_html_e( 'Applied to every template in this upload. JPG, PNG, WEBP or GIF. Max 3 MB.', 'wedding-widget' ); ?></p>
						</td>
					</tr>
				</table>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Upload', 'wedding-widget' ); ?></button>
			</form>

			<h2><?php esc_html_e( 'Available Templates', 'wedding-widget' ); ?></h2>
			<?php if ( empty( $templates ) ) : ?>
				<p><?php esc_html_e( 'No templates yet.', 'wedding-widget' ); ?></p>
			<?php else : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Delete the selected templates?', 'wedding-widget' ) ); ?>');">
					<input type="hidden" name="action" value="ww_bulk_delete">
					<?php wp_nonce_field( 'ww_bulk_delete' ); ?>
					<p><button type="submit" class="button"><?php esc_html_e( 'Delete Selected', 'wedding-widget' ); ?></button></p>
					<table class="widefat striped" style="max-width:960px;">
						<thead>
							<tr>
								<td style="width:28px;"><input type="checkbox" id="ww-check-all"></td>
								<th style="width:90px;"><?php esc_html_e( 'Preview', 'wedding-widget' ); ?></th>
								<th><?php esc_html_e( 'Title', 'wedding-widget' ); ?></th>
								<th><?php esc_html_e( 'Category', 'wedding-widget' ); ?></th>
								<th><?php esc_html_e( 'Type', 'wedding-widget' ); ?></th>
								<th><?php esc_html_e( 'Date', 'wedding-widget' ); ?></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $templates as $tpl ) :
								$type      = get_post_meta( $tpl->ID, '_ww_template_type', true );
								$terms     = get_the_terms( $tpl->ID, 'ww_template_category' );
								$cat_names = ( $terms && ! is_wp_error( $terms ) ) ? wp_list_pluck( $terms, 'name' ) : array();
								$edit_link = admin_url( 'admin.php?page=' . self::SLUG . '-templates&edit=' . $tpl->ID );
								$del_link  = wp_nonce_url(
									admin_url( 'admin-post.php?action=ww_delete_template&id=' . $tpl->ID ),
									'ww_delete_template_' . $tpl->ID
								);
								?>
								<tr>
									<td><input type="checkbox" class="ww-check" name="ids[]" value="<?php echo esc_attr( $tpl->ID ); ?>"></td>
									<td>
										<?php if ( has_post_thumbnail( $tpl->ID ) ) : ?>
											<?php echo get_the_post_thumbnail( $tpl->ID, array( 80, 80 ), array( 'style' => 'width:80px;height:auto;border-radius:6px;display:block;' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core escaped output. ?>
										<?php else : ?>
											<span class="ww-tpl__noimg" style="display:inline-flex;align-items:center;justify-content:center;width:80px;height:54px;background:#f0f0f1;border-radius:6px;color:#a7aaad;font-size:11px;"><?php esc_html_e( 'No image', 'wedding-widget' ); ?></span>
										<?php endif; ?>
									</td>
									<td><?php echo esc_html( $tpl->post_title ); ?></td>
									<td><?php echo esc_html( $cat_names ? implode( ', ', $cat_names ) : '—' ); ?></td>
									<td><?php echo esc_html( $type ? $type : 'page' ); ?></td>
									<td><?php echo esc_html( get_the_date( '', $tpl ) ); ?></td>
									<td>
										<a href="<?php echo esc_url( $edit_link ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'wedding-widget' ); ?></a>
										<a href="<?php echo esc_url( $del_link ); ?>" class="button button-link-delete" onclick="return confirm('<?php echo esc_js( __( 'Delete this template?', 'wedding-widget' ) ); ?>');"><?php esc_html_e( 'Delete', 'wedding-widget' ); ?></a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</form>
				<script>
					( function () {
						var all = document.getElementById( 'ww-check-all' );
						if ( ! all ) { return; }
						all.addEventListener( 'change', function () {
							document.querySelectorAll( '.ww-check' ).forEach( function ( c ) { c.checked = all.checked; } );
						} );
					} )();
				</script>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the edit form for a single template.
	 *
	 * @param \WP_Post $post       Template post.
	 * @param array    $categories Available category terms.
	 */
	private function render_edit_form( $post, $categories ) {
		$terms    = get_the_terms( $post->ID, 'ww_template_category' );
		$cat_name = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';
		$back     = admin_url( 'admin.php?page=' . self::SLUG . '-templates' );
		?>
		<div class="wrap ww-tpl">
			<h1><?php esc_html_e( 'Edit Template', 'wedding-widget' ); ?></h1>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<input type="hidden" name="action" value="ww_edit_template">
				<input type="hidden" name="id" value="<?php echo esc_attr( $post->ID ); ?>">
				<?php wp_nonce_field( 'ww_edit_template_' . $post->ID ); ?>
				<table class="form-table" style="max-width:640px;">
					<tr>
						<th scope="row"><label for="ww_edit_title"><?php esc_html_e( 'Title', 'wedding-widget' ); ?></label></th>
						<td><input type="text" id="ww_edit_title" name="ww_title" class="regular-text" value="<?php echo esc_attr( $post->post_title ); ?>" required></td>
					</tr>
					<tr>
						<th scope="row"><label for="ww_edit_category"><?php esc_html_e( 'Category', 'wedding-widget' ); ?></label></th>
						<td>
							<input type="text" id="ww_edit_category" name="ww_template_category" class="regular-text" list="ww_cat_list" value="<?php echo esc_attr( $cat_name ); ?>">
							<datalist id="ww_cat_list">
								<?php foreach ( $categories as $cat ) : ?>
									<option value="<?php echo esc_attr( $cat->name ); ?>"></option>
								<?php endforeach; ?>
							</datalist>
							<p class="description"><?php esc_html_e( 'Leave empty to remove the category.', 'wedding-widget' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Current Thumbnail', 'wedding-widget' ); ?></th>
						<td>
							<?php if ( has_post_thumbnail( $post->ID ) ) : ?>
								<?php echo get_the_post_thumbnail( $post->ID, array( 120, 120 ), array( 'style' => 'width:120px;height:auto;border-radius:8px;display:block;margin-bottom:8px;' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core output. ?>
								<label><input type="checkbox" name="ww_remove_thumb" value="1"> <?php esc_html_e( 'Remove current thumbnail', 'wedding-widget' ); ?></label>
							<?php else : ?>
								<em><?php esc_html_e( 'No thumbnail set.', 'wedding-widget' ); ?></em>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ww_edit_thumb"><?php esc_html_e( 'Replace Thumbnail', 'wedding-widget' ); ?></label></th>
						<td>
							<input type="file" id="ww_edit_thumb" name="ww_template_thumb" accept="image/*">
							<p class="description"><?php esc_html_e( 'JPG, PNG, WEBP or GIF. Max 3 MB.', 'wedding-widget' ); ?></p>
						</td>
					</tr>
				</table>
				<p>
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Changes', 'wedding-widget' ); ?></button>
					<a href="<?php echo esc_url( $back ); ?>" class="button"><?php esc_html_e( 'Cancel', 'wedding-widget' ); ?></a>
				</p>
			</form>
		</div>
		<?php
	}

	/* --------------------------------------------------------------------- */
	/* Handlers                                                              */
	/* --------------------------------------------------------------------- */

	private function redirect( $msg, $count = 0 ) {
		$args = array( 'ww_msg' => $msg );
		if ( $count ) {
			$args['ww_n'] = (int) $count;
		}
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php?page=' . self::SLUG . '-templates' ) ) );
		exit;
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
	 * Handle a bulk template upload (one or many JSON files), with an optional
	 * shared category and shared thumbnail.
	 */
	public function handle_upload() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Permission denied.', 'wedding-widget' ) );
		}
		check_admin_referer( 'ww_upload_template' );

		if ( empty( $_FILES['ww_template_file'] ) || ! isset( $_FILES['ww_template_file']['name'] ) ) {
			$this->redirect( 'invalid' );
		}

		$files = $_FILES['ww_template_file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- validated per item below.
		$names = (array) $files['name'];
		$total = count( $names );

		$term_id  = $this->resolve_category( isset( $_POST['ww_template_category'] ) ? wp_unslash( $_POST['ww_template_category'] ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce checked above.
		$thumb_id = $this->sideload_thumbnail();

		$created = 0;
		for ( $i = 0; $i < $total; $i++ ) {
			$error = isset( $files['error'][ $i ] ) ? (int) $files['error'][ $i ] : UPLOAD_ERR_NO_FILE;
			if ( UPLOAD_ERR_OK !== $error ) {
				continue;
			}
			$tmp = $files['tmp_name'][ $i ];
			if ( ! is_uploaded_file( $tmp ) ) {
				continue;
			}
			if ( isset( $files['size'][ $i ] ) && (int) $files['size'][ $i ] > 3 * 1024 * 1024 ) {
				continue;
			}
			$name = $files['name'][ $i ];
			if ( 'json' !== strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) ) {
				continue;
			}

			$raw  = file_get_contents( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local uploaded temp file.
			$data = json_decode( $raw, true );
			if ( ! is_array( $data ) || ( ! isset( $data['content'] ) && ! isset( $data[0] ) ) ) {
				continue;
			}

			$title = ! empty( $data['title'] ) ? sanitize_text_field( $data['title'] ) : sanitize_file_name( pathinfo( $name, PATHINFO_FILENAME ) );
			if ( '' === $title ) {
				$title = __( 'Template', 'wedding-widget' );
			}

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
				continue;
			}

			update_post_meta( $post_id, '_ww_template_data', base64_encode( $raw ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- safe storage, not obfuscation.
			update_post_meta( $post_id, '_ww_template_type', $type );
			if ( $term_id ) {
				wp_set_object_terms( $post_id, array( $term_id ), 'ww_template_category' );
			}
			if ( $thumb_id ) {
				set_post_thumbnail( $post_id, $thumb_id );
			}
			++$created;
		}

		if ( 0 === $created ) {
			$this->redirect( 'invalid' );
		}
		$this->redirect( 'uploaded', $created );
	}

	/**
	 * Sideload the optional shared preview image. Returns the attachment ID or 0.
	 *
	 * @return int
	 */
	private function sideload_thumbnail() {
		if ( empty( $_FILES['ww_template_thumb'] ) || ! isset( $_FILES['ww_template_thumb']['tmp_name'] ) ) {
			return 0;
		}
		$thumb = $_FILES['ww_template_thumb']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- validated below.

		if ( ! isset( $thumb['error'] ) || UPLOAD_ERR_NO_FILE === (int) $thumb['error'] ) {
			return 0;
		}
		if ( UPLOAD_ERR_OK !== (int) $thumb['error'] || ! is_uploaded_file( $thumb['tmp_name'] ) ) {
			return 0;
		}
		if ( isset( $thumb['size'] ) && (int) $thumb['size'] > 3 * 1024 * 1024 ) {
			return 0;
		}

		$check   = wp_check_filetype( isset( $thumb['name'] ) ? $thumb['name'] : '' );
		$allowed = array( 'jpg', 'jpeg', 'png', 'gif', 'webp' );
		if ( empty( $check['ext'] ) || ! in_array( strtolower( $check['ext'] ), $allowed, true ) ) {
			return 0;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$attachment_id = media_handle_upload( 'ww_template_thumb', 0, array(), array( 'test_form' => false ) );
		return is_wp_error( $attachment_id ) ? 0 : (int) $attachment_id;
	}

	public function handle_delete() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Permission denied.', 'wedding-widget' ) );
		}
		check_admin_referer( 'ww_delete_template_' . $id );

		$post = $id ? get_post( $id ) : null;
		if ( $post && 'ww_template' === $post->post_type ) {
			// Note: the thumbnail attachment is left in the Media Library because
			// it may be shared by other templates from a bulk upload.
			wp_delete_post( $id, true );
		}
		$this->redirect( 'deleted', 1 );
	}

	/**
	 * Update an existing template's title, category and/or thumbnail.
	 */
	public function handle_edit() {
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Permission denied.', 'wedding-widget' ) );
		}
		check_admin_referer( 'ww_edit_template_' . $id );

		$post = $id ? get_post( $id ) : null;
		if ( ! $post || 'ww_template' !== $post->post_type ) {
			$this->redirect( 'invalid' );
		}

		$title = isset( $_POST['ww_title'] ) ? sanitize_text_field( wp_unslash( $_POST['ww_title'] ) ) : '';
		if ( '' !== $title && $title !== $post->post_title ) {
			wp_update_post(
				array(
					'ID'         => $id,
					'post_title' => $title,
				)
			);
		}

		// Category (replace; empty clears it).
		$cat     = isset( $_POST['ww_template_category'] ) ? wp_unslash( $_POST['ww_template_category'] ) : '';
		$term_id = $this->resolve_category( $cat );
		wp_set_object_terms( $id, $term_id ? array( $term_id ) : array(), 'ww_template_category' );

		// Remove existing thumbnail if requested.
		if ( ! empty( $_POST['ww_remove_thumb'] ) ) {
			delete_post_thumbnail( $id );
		}

		// Replace with a newly uploaded thumbnail if provided.
		$thumb_id = $this->sideload_thumbnail();
		if ( $thumb_id ) {
			set_post_thumbnail( $id, $thumb_id );
		}

		$this->redirect( 'edited' );
	}

	/**
	 * Delete several templates at once.
	 */
	public function handle_bulk_delete() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Permission denied.', 'wedding-widget' ) );
		}
		check_admin_referer( 'ww_bulk_delete' );

		$ids     = isset( $_POST['ids'] ) ? array_map( 'absint', (array) $_POST['ids'] ) : array();
		$deleted = 0;
		foreach ( $ids as $id ) {
			$post = $id ? get_post( $id ) : null;
			if ( $post && 'ww_template' === $post->post_type ) {
				wp_delete_post( $id, true );
				++$deleted;
			}
		}
		$this->redirect( 'deleted', $deleted );
	}
}
