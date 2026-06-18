<?php
/**
 * RSVP widget: attendance form + wishes list + simple statistics.
 *
 * Stores entries as WordPress comments of type "ww_rsvp" on the current post,
 * with the attendance status in the "ww_attendance" comment meta.
 *
 * @package WeddingWidget
 */

namespace WeddingWidget\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use WeddingWidget\WW_Loader;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WW_RSVP extends Widget_Base {

	public function get_name() {
		return 'ww-rsvp';
	}

	public function get_title() {
		return esc_html__( 'RSVP', 'wedding-widget' );
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	public function get_categories() {
		return array( 'wedding-widget' );
	}

	public function get_keywords() {
		return array( 'rsvp', 'wishes', 'guestbook', 'attendance', 'wedding' );
	}

	public function get_script_depends() {
		return array( 'wedding-widget' );
	}

	public function get_style_depends() {
		return array( 'wedding-widget' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			array( 'label' => esc_html__( 'RSVP', 'wedding-widget' ) )
		);

		$this->add_control( 'show_form', array(
			'label'        => esc_html__( 'Show Form', 'wedding-widget' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'show_stats', array(
			'label'        => esc_html__( 'Show Statistics', 'wedding-widget' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'show_list', array(
			'label'        => esc_html__( 'Show Wishes List', 'wedding-widget' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'list_limit', array(
			'label'   => esc_html__( 'List Limit', 'wedding-widget' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 30,
			'min'     => 1,
			'max'     => 500,
		) );

		$this->add_control( 'autofill_name', array(
			'label'        => esc_html__( 'Auto-fill Name From URL (?to=)', 'wedding-widget' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->end_controls_section();

		// Style.
		$this->start_controls_section(
			'section_style',
			array(
				'label' => esc_html__( 'Style', 'wedding-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control( 'accent', array(
			'label'     => esc_html__( 'Accent Color', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#b08968',
			'selectors' => array(
				'{{WRAPPER}} .ww-rsvp__submit'        => 'background-color: {{VALUE}};',
				'{{WRAPPER}} .ww-rsvp__field:focus'   => 'border-color: {{VALUE}};',
				'{{WRAPPER}} .ww-rsvp__stat-value'    => 'color: {{VALUE}};',
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

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$post_id  = get_the_ID();

		$show_form  = ( 'yes' === ( $settings['show_form'] ?? 'yes' ) );
		$show_stats = ( 'yes' === ( $settings['show_stats'] ?? 'yes' ) );
		$show_list  = ( 'yes' === ( $settings['show_list'] ?? 'yes' ) );
		$limit      = absint( $settings['list_limit'] ?? 30 );

		$guest = '';
		if ( 'yes' === ( $settings['autofill_name'] ?? 'yes' ) && isset( $_GET['to'] ) ) {
			$guest = sanitize_text_field( wp_unslash( $_GET['to'] ) );
		}

		$comments = array();
		$counts   = array( 'attending' => 0, 'not_attending' => 0, 'maybe' => 0 );

		if ( $post_id && ( $show_list || $show_stats ) ) {
			$comments = get_comments( array(
				'post_id' => $post_id,
				'type'    => 'ww_rsvp',
				'status'  => 'approve',
				'number'  => $limit,
				'orderby' => 'comment_date',
				'order'   => 'DESC',
			) );

			$stat_comments = get_comments( array(
				'post_id' => $post_id,
				'type'    => 'ww_rsvp',
				'status'  => 'approve',
			) );
			foreach ( $stat_comments as $c ) {
				$a = get_comment_meta( $c->comment_ID, 'ww_attendance', true );
				if ( isset( $counts[ $a ] ) ) {
					$counts[ $a ]++;
				}
			}
		}

		$total = array_sum( $counts );
		?>
		<div class="ww-rsvp" data-ww-rsvp data-post="<?php echo esc_attr( $post_id ); ?>">

			<?php if ( $show_stats ) : ?>
				<div class="ww-rsvp__stats">
					<div class="ww-rsvp__stat">
						<span class="ww-rsvp__stat-value" data-stat="total"><?php echo esc_html( $total ); ?></span>
						<span class="ww-rsvp__stat-label"><?php esc_html_e( 'Total', 'wedding-widget' ); ?></span>
					</div>
					<div class="ww-rsvp__stat">
						<span class="ww-rsvp__stat-value" data-stat="attending"><?php echo esc_html( $counts['attending'] ); ?></span>
						<span class="ww-rsvp__stat-label"><?php esc_html_e( 'Attending', 'wedding-widget' ); ?></span>
					</div>
					<div class="ww-rsvp__stat">
						<span class="ww-rsvp__stat-value" data-stat="not_attending"><?php echo esc_html( $counts['not_attending'] ); ?></span>
						<span class="ww-rsvp__stat-label"><?php esc_html_e( 'Not Attending', 'wedding-widget' ); ?></span>
					</div>
					<div class="ww-rsvp__stat">
						<span class="ww-rsvp__stat-value" data-stat="maybe"><?php echo esc_html( $counts['maybe'] ); ?></span>
						<span class="ww-rsvp__stat-label"><?php esc_html_e( 'Maybe', 'wedding-widget' ); ?></span>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $show_form ) : ?>
				<form class="ww-rsvp__form" data-ww-rsvp-form>
					<input type="text" class="ww-rsvp__field" name="name" placeholder="<?php esc_attr_e( 'Your name', 'wedding-widget' ); ?>" value="<?php echo esc_attr( $guest ); ?>" required>
					<select class="ww-rsvp__field" name="attendance" required>
						<option value="attending"><?php esc_html_e( 'Attending', 'wedding-widget' ); ?></option>
						<option value="not_attending"><?php esc_html_e( 'Not Attending', 'wedding-widget' ); ?></option>
						<option value="maybe"><?php esc_html_e( 'Maybe', 'wedding-widget' ); ?></option>
					</select>
					<textarea class="ww-rsvp__field" name="message" rows="3" placeholder="<?php esc_attr_e( 'Your wishes...', 'wedding-widget' ); ?>" required></textarea>
					<button type="submit" class="ww-rsvp__submit"><?php esc_html_e( 'Send', 'wedding-widget' ); ?></button>
					<p class="ww-rsvp__feedback" data-ww-rsvp-feedback aria-live="polite"></p>
				</form>
			<?php endif; ?>

			<?php if ( $show_list ) : ?>
				<ul class="ww-rsvp__list" data-ww-rsvp-list>
					<?php if ( empty( $comments ) ) : ?>
						<li class="ww-rsvp__empty"><?php esc_html_e( 'No wishes yet. Be the first!', 'wedding-widget' ); ?></li>
					<?php else : ?>
						<?php foreach ( $comments as $c ) :
							$a = get_comment_meta( $c->comment_ID, 'ww_attendance', true );
							?>
							<li class="ww-rsvp__item">
								<div class="ww-rsvp__item-head">
									<span class="ww-rsvp__item-name"><?php echo esc_html( $c->comment_author ); ?></span>
									<span class="ww-rsvp__badge ww-rsvp__badge--<?php echo esc_attr( str_replace( '_', '-', $a ) ); ?>"><?php echo esc_html( WW_Loader::attendance_label( $a ) ); ?></span>
								</div>
								<div class="ww-rsvp__item-message"><?php echo esc_html( wp_strip_all_tags( $c->comment_content ) ); ?></div>
								<div class="ww-rsvp__item-date"><?php echo esc_html( date_i18n( 'd M Y, H:i', strtotime( $c->comment_date ) ) ); ?></div>
							</li>
						<?php endforeach; ?>
					<?php endif; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}
}
