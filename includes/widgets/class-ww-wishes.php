<?php
/**
 * Wishes widget: guestbook with stats header, avatars, reply/edit/delete,
 * scrollable list + pagination.
 *
 * Stores entries as WordPress comments of type "ww_wish" on the current post.
 * Replies are child comments. Edit/delete are permitted to the author (via an
 * ownership token stored in localStorage) or to users who can moderate comments.
 *
 * @package WeddingWidget
 */

namespace WeddingWidget\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use WeddingWidget\WW_Loader;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WW_Wishes extends Widget_Base {

	public function get_name() {
		return 'ww-wishes';
	}

	public function get_title() {
		return esc_html__( 'Wishes', 'wedding-widget' );
	}

	public function get_icon() {
		return 'eicon-comments';
	}

	public function get_categories() {
		return array( 'wedding-widget' );
	}

	public function get_keywords() {
		return array( 'wishes', 'guestbook', 'comments', 'greetings', 'wedding', 'ucapan' );
	}

	public function get_script_depends() {
		return array( 'wedding-widget' );
	}

	public function get_style_depends() {
		return array( 'wedding-widget' );
	}

	private function align_options() {
		return array(
			'flex-start' => array( 'title' => esc_html__( 'Left', 'wedding-widget' ), 'icon' => 'eicon-text-align-left' ),
			'center'     => array( 'title' => esc_html__( 'Center', 'wedding-widget' ), 'icon' => 'eicon-text-align-center' ),
			'flex-end'   => array( 'title' => esc_html__( 'Right', 'wedding-widget' ), 'icon' => 'eicon-text-align-right' ),
		);
	}

	protected function register_controls() {
		/* ---- Content ---- */
		$this->start_controls_section(
			'section_content',
			array( 'label' => esc_html__( 'Wishes', 'wedding-widget' ) )
		);

		$this->add_control( 'show_stats', array(
			'label'        => esc_html__( 'Show Stats Header', 'wedding-widget' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'collect_attendance', array(
			'label'        => esc_html__( 'Ask Attendance', 'wedding-widget' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'allow_reply', array(
			'label'        => esc_html__( 'Allow Reply', 'wedding-widget' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'allow_edit_delete', array(
			'label'        => esc_html__( 'Allow Edit / Delete', 'wedding-widget' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'description'  => esc_html__( 'Authors can edit/delete their own entry (in the same browser); moderators can manage all.', 'wedding-widget' ),
		) );

		$this->add_control( 'list_limit', array(
			'label'   => esc_html__( 'Max Loaded Wishes', 'wedding-widget' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 100,
			'min'     => 1,
			'max'     => 1000,
		) );

		$this->add_control( 'per_page', array(
			'label'   => esc_html__( 'Wishes Per Page', 'wedding-widget' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 5,
			'min'     => 1,
			'max'     => 50,
		) );

		$this->add_control( 'autofill_name', array(
			'label'        => esc_html__( 'Auto-fill Name From URL (?to=)', 'wedding-widget' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->end_controls_section();

		/* ---- Text (Bahasa) ---- */
		$this->start_controls_section(
			'section_text',
			array( 'label' => esc_html__( 'Text (Bahasa)', 'wedding-widget' ) )
		);

		$this->add_control( 'label_count', array( 'label' => esc_html__( 'Wishes Word', 'wedding-widget' ), 'type' => Controls_Manager::TEXT, 'default' => 'Ucapan' ) );
		$this->add_control( 'label_attending', array( 'label' => esc_html__( 'Attending Label', 'wedding-widget' ), 'type' => Controls_Manager::TEXT, 'default' => 'Hadir' ) );
		$this->add_control( 'label_not_attending', array( 'label' => esc_html__( 'Not Attending Label', 'wedding-widget' ), 'type' => Controls_Manager::TEXT, 'default' => 'Tidak Hadir' ) );
		$this->add_control( 'label_maybe', array( 'label' => esc_html__( 'Maybe Label', 'wedding-widget' ), 'type' => Controls_Manager::TEXT, 'default' => 'Masih Ragu' ) );
		$this->add_control( 'ph_name', array( 'label' => esc_html__( 'Name Placeholder', 'wedding-widget' ), 'type' => Controls_Manager::TEXT, 'default' => 'Nama Anda' ) );
		$this->add_control( 'ph_message', array( 'label' => esc_html__( 'Message Placeholder', 'wedding-widget' ), 'type' => Controls_Manager::TEXT, 'default' => 'Tulis ucapan & doa...' ) );
		$this->add_control( 'btn_text', array( 'label' => esc_html__( 'Submit Button', 'wedding-widget' ), 'type' => Controls_Manager::TEXT, 'default' => 'Kirim' ) );
		$this->add_control( 'reply_text', array( 'label' => esc_html__( 'Reply Button', 'wedding-widget' ), 'type' => Controls_Manager::TEXT, 'default' => 'Balas' ) );
		$this->add_control( 'edit_text', array( 'label' => esc_html__( 'Edit Button', 'wedding-widget' ), 'type' => Controls_Manager::TEXT, 'default' => 'Edit' ) );
		$this->add_control( 'delete_text', array( 'label' => esc_html__( 'Delete Button', 'wedding-widget' ), 'type' => Controls_Manager::TEXT, 'default' => 'Hapus' ) );
		$this->add_control( 'save_text', array( 'label' => esc_html__( 'Save Button', 'wedding-widget' ), 'type' => Controls_Manager::TEXT, 'default' => 'Simpan' ) );
		$this->add_control( 'cancel_text', array( 'label' => esc_html__( 'Cancel Button', 'wedding-widget' ), 'type' => Controls_Manager::TEXT, 'default' => 'Batal' ) );
		$this->add_control( 'prev_text', array( 'label' => esc_html__( 'Previous Button', 'wedding-widget' ), 'type' => Controls_Manager::TEXT, 'default' => 'Sebelumnya' ) );
		$this->add_control( 'next_text', array( 'label' => esc_html__( 'Next Button', 'wedding-widget' ), 'type' => Controls_Manager::TEXT, 'default' => 'Berikutnya' ) );
		$this->add_control( 'confirm_delete', array( 'label' => esc_html__( 'Delete Confirm', 'wedding-widget' ), 'type' => Controls_Manager::TEXT, 'default' => 'Hapus ucapan ini?' ) );
		$this->add_control( 'empty_text', array( 'label' => esc_html__( 'Empty Message', 'wedding-widget' ), 'type' => Controls_Manager::TEXT, 'default' => 'Belum ada ucapan. Jadilah yang pertama!' ) );

		$this->end_controls_section();

		$this->register_style_controls();
	}

	private function register_style_controls() {
		/* ---- Count ---- */
		$this->start_controls_section(
			'section_style_count',
			array( 'label' => esc_html__( 'Stats: Count', 'wedding-widget' ), 'tab' => Controls_Manager::TAB_STYLE )
		);

		$this->add_responsive_control( 'count_align', array(
			'label'     => esc_html__( 'Position', 'wedding-widget' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => $this->align_options(),
			'default'   => 'flex-start',
			'selectors' => array( '{{WRAPPER}} .ww-wishes__count' => 'justify-content: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'stats_gap', array(
			'label'      => esc_html__( 'Gap (Count ↔ Breakdown)', 'wedding-widget' ),
			'type'       => Controls_Manager::SLIDER,
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
			'default'    => array( 'size' => 14, 'unit' => 'px' ),
			'selectors'  => array( '{{WRAPPER}} .ww-wishes__header' => 'gap: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_control( 'count_color', array(
			'label'     => esc_html__( 'Color', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#1f2937',
			'selectors' => array( '{{WRAPPER}} .ww-wishes__count' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'count_typo',
				'selector' => '{{WRAPPER}} .ww-wishes__count, {{WRAPPER}} .ww-wishes__count strong, {{WRAPPER}} .ww-wishes__count-label',
			)
		);

		$this->end_controls_section();

		/* ---- Breakdown ---- */
		$this->start_controls_section(
			'section_style_breakdown',
			array( 'label' => esc_html__( 'Stats: Breakdown', 'wedding-widget' ), 'tab' => Controls_Manager::TAB_STYLE )
		);

		$this->add_responsive_control( 'breakdown_align', array(
			'label'     => esc_html__( 'Position', 'wedding-widget' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => $this->align_options(),
			'default'   => 'center',
			'selectors' => array( '{{WRAPPER}} .ww-wishes__breakdown' => 'justify-content: {{VALUE}};' ),
		) );

		$this->add_control( 'breakdown_color', array(
			'label'     => esc_html__( 'Text Color', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#4b5563',
			'selectors' => array( '{{WRAPPER}} .ww-wishes__breakdown' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'color_attending', array(
			'label'     => esc_html__( 'Attending Number', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#16a34a',
			'selectors' => array( '{{WRAPPER}} .ww-wishes__bd--attending b' => 'color: {{VALUE}};' ),
		) );
		$this->add_control( 'color_not_attending', array(
			'label'     => esc_html__( 'Not Attending Number', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#dc2626',
			'selectors' => array( '{{WRAPPER}} .ww-wishes__bd--not-attending b' => 'color: {{VALUE}};' ),
		) );
		$this->add_control( 'color_maybe', array(
			'label'     => esc_html__( 'Maybe Number', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#ca8a04',
			'selectors' => array( '{{WRAPPER}} .ww-wishes__bd--maybe b' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'breakdown_typo',
				'selector' => '{{WRAPPER}} .ww-wishes__breakdown',
			)
		);

		$this->end_controls_section();

		/* ---- Avatar ---- */
		$this->start_controls_section(
			'section_style_avatar',
			array( 'label' => esc_html__( 'Avatar', 'wedding-widget' ), 'tab' => Controls_Manager::TAB_STYLE )
		);

		$this->add_control( 'avatar_size', array(
			'label'      => esc_html__( 'Size', 'wedding-widget' ),
			'type'       => Controls_Manager::SLIDER,
			'range'      => array( 'px' => array( 'min' => 24, 'max' => 80 ) ),
			'default'    => array( 'size' => 40, 'unit' => 'px' ),
			'selectors'  => array(
				'{{WRAPPER}} .ww-rsvp__avatar' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; font-size: calc({{SIZE}}{{UNIT}} * 0.4);',
			),
		) );

		$this->add_control( 'avatar_text_color', array(
			'label'     => esc_html__( 'Text Color', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#ffffff',
			'selectors' => array( '{{WRAPPER}} .ww-rsvp__avatar' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'avatar_bg', array(
			'label'       => esc_html__( 'Background (override)', 'wedding-widget' ),
			'type'        => Controls_Manager::COLOR,
			'description' => esc_html__( 'Leave empty to auto-generate a color from each name.', 'wedding-widget' ),
			'selectors'   => array( '{{WRAPPER}} .ww-rsvp__avatar' => 'background-color: {{VALUE}} !important;' ),
		) );

		$this->end_controls_section();

		/* ---- Pagination ---- */
		$this->start_controls_section(
			'section_style_pagination',
			array( 'label' => esc_html__( 'Pagination', 'wedding-widget' ), 'tab' => Controls_Manager::TAB_STYLE )
		);

		$this->add_responsive_control( 'pagination_align', array(
			'label'     => esc_html__( 'Position', 'wedding-widget' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => $this->align_options(),
			'default'   => 'center',
			'selectors' => array( '{{WRAPPER}} .ww-wishes__pagination' => 'justify-content: {{VALUE}};' ),
		) );

		$this->add_control( 'page_text_color', array(
			'label'     => esc_html__( 'Button Text', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#1f2937',
			'selectors' => array( '{{WRAPPER}} .ww-wishes__page-btn' => 'color: {{VALUE}};' ),
		) );
		$this->add_control( 'page_bg', array(
			'label'     => esc_html__( 'Button Background', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .ww-wishes__page-btn' => 'background-color: {{VALUE}};' ),
		) );
		$this->add_control( 'page_border_color', array(
			'label'     => esc_html__( 'Button Border', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => 'rgba(0,0,0,0.15)',
			'selectors' => array( '{{WRAPPER}} .ww-wishes__page-btn' => 'border-color: {{VALUE}};' ),
		) );
		$this->add_control( 'page_radius', array(
			'label'      => esc_html__( 'Button Radius', 'wedding-widget' ),
			'type'       => Controls_Manager::SLIDER,
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
			'default'    => array( 'size' => 8, 'unit' => 'px' ),
			'selectors'  => array( '{{WRAPPER}} .ww-wishes__page-btn' => 'border-radius: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_control( 'page_info_color', array(
			'label'     => esc_html__( 'Page Info Color', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .ww-wishes__page-info' => 'color: {{VALUE}};' ),
		) );
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'page_typo',
				'selector' => '{{WRAPPER}} .ww-wishes__page-btn, {{WRAPPER}} .ww-wishes__page-info',
			)
		);

		$this->end_controls_section();

		/* ---- Card / general ---- */
		$this->start_controls_section(
			'section_style_general',
			array( 'label' => esc_html__( 'Card & List', 'wedding-widget' ), 'tab' => Controls_Manager::TAB_STYLE )
		);

		$this->add_control( 'accent', array(
			'label'     => esc_html__( 'Accent Color', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#b08968',
			'selectors' => array(
				'{{WRAPPER}} .ww-rsvp__submit'      => 'background-color: {{VALUE}};',
				'{{WRAPPER}} .ww-rsvp__field:focus' => 'border-color: {{VALUE}};',
			),
		) );

		$this->add_control( 'card_bg', array(
			'label'     => esc_html__( 'Card Background', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#ffffff',
			'selectors' => array( '{{WRAPPER}} .ww-rsvp' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_control( 'text_color', array(
			'label'     => esc_html__( 'Text Color', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#1f2937',
			'selectors' => array( '{{WRAPPER}} .ww-rsvp' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'list_max_height', array(
			'label'      => esc_html__( 'List Max Height', 'wedding-widget' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', 'vh' ),
			'range'      => array( 'px' => array( 'min' => 120, 'max' => 900 ), 'vh' => array( 'min' => 20, 'max' => 100 ) ),
			'default'    => array( 'size' => 360, 'unit' => 'px' ),
			'selectors'  => array( '{{WRAPPER}} .ww-rsvp__list' => 'max-height: {{SIZE}}{{UNIT}};' ),
		) );

		$this->end_controls_section();
	}

	/**
	 * Render a single comment node (top-level item or reply).
	 */
	private function render_comment( $comment, $att_labels, $is_reply = false ) {
		$name = $comment->comment_author;
		$a    = $is_reply ? '' : get_comment_meta( $comment->comment_ID, 'ww_attendance', true );
		$cls  = $is_reply ? 'ww-rsvp__reply' : 'ww-rsvp__item';
		?>
		<li class="<?php echo esc_attr( $cls ); ?>" data-comment-id="<?php echo esc_attr( $comment->comment_ID ); ?>">
			<div class="ww-rsvp__row">
				<span class="ww-rsvp__avatar" style="background-color:<?php echo esc_attr( WW_Loader::avatar_color( $name ) ); ?>"><?php echo esc_html( WW_Loader::initials( $name ) ); ?></span>
				<div class="ww-rsvp__body">
					<div class="ww-rsvp__item-head">
						<span class="ww-rsvp__item-name"><?php echo esc_html( $name ); ?></span>
						<?php if ( $a ) : ?>
							<span class="ww-rsvp__badge ww-rsvp__badge--<?php echo esc_attr( str_replace( '_', '-', $a ) ); ?>"><?php echo esc_html( $att_labels[ $a ] ?? WW_Loader::attendance_label( $a ) ); ?></span>
						<?php endif; ?>
					</div>
					<div class="ww-rsvp__item-message" data-role="message"><?php echo esc_html( wp_strip_all_tags( $comment->comment_content ) ); ?></div>
					<div class="ww-rsvp__item-meta">
						<span class="ww-rsvp__item-date"><?php echo esc_html( date_i18n( 'd M Y, H:i', strtotime( $comment->comment_date ) ) ); ?></span>
						<span class="ww-rsvp__actions" data-role="actions"></span>
					</div>
				</div>
			</div>
			<?php if ( ! $is_reply ) : ?>
				<ul class="ww-rsvp__replies" data-role="replies"></ul>
			<?php endif; ?>
		</li>
		<?php
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$post_id  = get_the_ID();

		$collect    = ( 'yes' === ( $settings['collect_attendance'] ?? 'yes' ) );
		$show_stats = ( 'yes' === ( $settings['show_stats'] ?? 'yes' ) );
		$allow_reply = ( 'yes' === ( $settings['allow_reply'] ?? 'yes' ) );
		$allow_ed    = ( 'yes' === ( $settings['allow_edit_delete'] ?? 'yes' ) );
		$limit      = absint( $settings['list_limit'] ?? 100 );
		$per_page   = max( 1, absint( $settings['per_page'] ?? 5 ) );

		$label_count = $settings['label_count'] ?? 'Ucapan';
		$label_hadir = $settings['label_attending'] ?? 'Hadir';
		$label_tidak = $settings['label_not_attending'] ?? 'Tidak Hadir';
		$label_ragu  = $settings['label_maybe'] ?? 'Masih Ragu';
		$att_labels  = array( 'attending' => $label_hadir, 'not_attending' => $label_tidak, 'maybe' => $label_ragu );

		$guest = '';
		if ( 'yes' === ( $settings['autofill_name'] ?? 'yes' ) && isset( $_GET['to'] ) ) {
			$guest = sanitize_text_field( wp_unslash( $_GET['to'] ) );
		}

		$top    = array();
		$counts = array( 'attending' => 0, 'not_attending' => 0, 'maybe' => 0 );
		$total  = 0;

		if ( $post_id ) {
			$top = get_comments( array(
				'post_id' => $post_id,
				'type'    => 'ww_wish',
				'status'  => 'approve',
				'parent'  => 0,
				'number'  => $limit,
				'orderby' => 'comment_date',
				'order'   => 'DESC',
			) );

			$all = get_comments( array(
				'post_id' => $post_id,
				'type'    => 'ww_wish',
				'status'  => 'approve',
				'parent'  => 0,
			) );
			$total = count( $all );
			foreach ( $all as $c ) {
				$a = get_comment_meta( $c->comment_ID, 'ww_attendance', true );
				if ( isset( $counts[ $a ] ) ) {
					$counts[ $a ]++;
				}
			}
		}

		$data_attr = array(
			'data-ww-rsvp'             => '',
			'data-kind'               => 'ww_wish',
			'data-post'               => $post_id,
			'data-per-page'           => $per_page,
			'data-allow-reply'        => $allow_reply ? '1' : '0',
			'data-allow-edit'         => $allow_ed ? '1' : '0',
			'data-label-attending'    => $label_hadir,
			'data-label-not-attending' => $label_tidak,
			'data-label-maybe'        => $label_ragu,
			'data-txt-reply'          => $settings['reply_text'] ?? 'Balas',
			'data-txt-edit'           => $settings['edit_text'] ?? 'Edit',
			'data-txt-delete'         => $settings['delete_text'] ?? 'Hapus',
			'data-txt-save'           => $settings['save_text'] ?? 'Simpan',
			'data-txt-cancel'         => $settings['cancel_text'] ?? 'Batal',
			'data-txt-confirm'        => $settings['confirm_delete'] ?? 'Hapus ucapan ini?',
			'data-ph-name'            => $settings['ph_name'] ?? 'Nama Anda',
			'data-ph-reply'           => $settings['ph_message'] ?? 'Tulis balasan...',
		);
		$attr_str = '';
		foreach ( $data_attr as $k => $v ) {
			$attr_str .= ' ' . $k . '="' . esc_attr( $v ) . '"';
		}
		?>
		<div class="ww-rsvp ww-wishes"<?php echo $attr_str; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped per value above. ?>>

			<?php if ( $show_stats ) : ?>
				<div class="ww-wishes__header">
					<div class="ww-wishes__count">
						<strong data-stat="total"><?php echo esc_html( number_format_i18n( $total ) ); ?></strong>
						<span class="ww-wishes__count-label"><?php echo esc_html( $label_count ); ?></span>
					</div>
					<div class="ww-wishes__breakdown">
						<span class="ww-wishes__bd ww-wishes__bd--attending"><b data-stat="attending"><?php echo esc_html( number_format_i18n( $counts['attending'] ) ); ?></b> <?php echo esc_html( $label_hadir ); ?></span>
						<span class="ww-wishes__bd ww-wishes__bd--not-attending"><b data-stat="not_attending"><?php echo esc_html( number_format_i18n( $counts['not_attending'] ) ); ?></b> <?php echo esc_html( $label_tidak ); ?></span>
						<span class="ww-wishes__bd ww-wishes__bd--maybe"><b data-stat="maybe"><?php echo esc_html( number_format_i18n( $counts['maybe'] ) ); ?></b> <?php echo esc_html( $label_ragu ); ?></span>
					</div>
				</div>
			<?php endif; ?>

			<form class="ww-rsvp__form" data-ww-rsvp-form>
				<input type="text" class="ww-rsvp__field" name="name" placeholder="<?php echo esc_attr( $settings['ph_name'] ?? 'Nama Anda' ); ?>" value="<?php echo esc_attr( $guest ); ?>" required>
				<?php if ( $collect ) : ?>
					<select class="ww-rsvp__field" name="attendance" required>
						<option value="attending"><?php echo esc_html( $label_hadir ); ?></option>
						<option value="not_attending"><?php echo esc_html( $label_tidak ); ?></option>
						<option value="maybe"><?php echo esc_html( $label_ragu ); ?></option>
					</select>
				<?php endif; ?>
				<textarea class="ww-rsvp__field" name="message" rows="3" placeholder="<?php echo esc_attr( $settings['ph_message'] ?? 'Tulis ucapan & doa...' ); ?>" required></textarea>
				<button type="submit" class="ww-rsvp__submit"><?php echo esc_html( $settings['btn_text'] ?? 'Kirim' ); ?></button>
				<p class="ww-rsvp__feedback" data-ww-rsvp-feedback aria-live="polite"></p>
			</form>

			<ul class="ww-rsvp__list" data-ww-rsvp-list data-empty-text="<?php echo esc_attr( $settings['empty_text'] ?? '' ); ?>">
				<?php if ( empty( $top ) ) : ?>
					<li class="ww-rsvp__empty"><?php echo esc_html( $settings['empty_text'] ?? '' ); ?></li>
				<?php else : ?>
					<?php
					foreach ( $top as $c ) {
						ob_start();
						$this->render_comment( $c, $att_labels, false );
						$item_html = ob_get_clean();

						// Inject replies for this item.
						$replies = get_comments( array(
							'parent'  => $c->comment_ID,
							'status'  => 'approve',
							'orderby' => 'comment_date',
							'order'   => 'ASC',
						) );
						$replies_html = '';
						foreach ( $replies as $r ) {
							ob_start();
							$this->render_comment( $r, $att_labels, true );
							$replies_html .= ob_get_clean();
						}
						if ( '' !== $replies_html ) {
							$item_html = str_replace(
								'<ul class="ww-rsvp__replies" data-role="replies"></ul>',
								'<ul class="ww-rsvp__replies" data-role="replies">' . $replies_html . '</ul>',
								$item_html
							);
						}
						echo $item_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- composed of escaped fragments.
					}
					?>
				<?php endif; ?>
			</ul>

			<div class="ww-wishes__pagination" data-ww-pagination hidden>
				<button type="button" class="ww-wishes__page-btn" data-ww-prev disabled><?php echo esc_html( $settings['prev_text'] ?? 'Sebelumnya' ); ?></button>
				<span class="ww-wishes__page-info" data-ww-page-info>1 / 1</span>
				<button type="button" class="ww-wishes__page-btn" data-ww-next disabled><?php echo esc_html( $settings['next_text'] ?? 'Berikutnya' ); ?></button>
			</div>
		</div>
		<?php
	}
}
