<?php
/**
 * Cover widget: full-screen invitation cover with an "open" reveal.
 *
 * @package WeddingWidget
 */

namespace WeddingWidget\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WW_Cover extends Widget_Base {

	public function get_name() {
		return 'ww-cover';
	}

	public function get_title() {
		return esc_html__( 'Cover', 'wedding-widget' );
	}

	public function get_icon() {
		return 'eicon-site-identity';
	}

	public function get_categories() {
		return array( 'wedding-widget' );
	}

	public function get_keywords() {
		return array( 'cover', 'invitation', 'wedding', 'opening' );
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
			array( 'label' => esc_html__( 'Content', 'wedding-widget' ) )
		);

		$this->add_control( 'pretitle', array(
			'label'   => esc_html__( 'Pre-title', 'wedding-widget' ),
			'type'    => Controls_Manager::TEXT,
			'default' => esc_html__( 'The Wedding Of', 'wedding-widget' ),
			'dynamic' => array( 'active' => true ),
		) );

		$this->add_control( 'couple', array(
			'label'   => esc_html__( 'Couple Names', 'wedding-widget' ),
			'type'    => Controls_Manager::TEXT,
			'default' => 'Dimas & Sinta',
			'dynamic' => array( 'active' => true ),
		) );

		$this->add_control( 'date_text', array(
			'label'   => esc_html__( 'Date', 'wedding-widget' ),
			'type'    => Controls_Manager::TEXT,
			'default' => esc_html__( 'Saturday, 1 December 2026', 'wedding-widget' ),
			'dynamic' => array( 'active' => true ),
		) );

		$this->add_control( 'dear', array(
			'label'   => esc_html__( 'Dear / To', 'wedding-widget' ),
			'type'    => Controls_Manager::TEXT,
			'default' => esc_html__( 'Dear', 'wedding-widget' ),
		) );

		$this->add_control( 'guest_from_url', array(
			'label'        => esc_html__( 'Guest Name From URL (?to=)', 'wedding-widget' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'description'  => esc_html__( 'Reads the guest name from the "to" query parameter.', 'wedding-widget' ),
		) );

		$this->add_control( 'button_text', array(
			'label'   => esc_html__( 'Button Text', 'wedding-widget' ),
			'type'    => Controls_Manager::TEXT,
			'default' => esc_html__( 'Open Invitation', 'wedding-widget' ),
		) );

		$this->add_control( 'effect', array(
			'label'   => esc_html__( 'Opening Effect', 'wedding-widget' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'slide-up',
			'options' => array(
				'slide-up'    => esc_html__( 'Slide Up', 'wedding-widget' ),
				'slide-down'  => esc_html__( 'Slide Down', 'wedding-widget' ),
				'slide-left'  => esc_html__( 'Slide Left', 'wedding-widget' ),
				'slide-right' => esc_html__( 'Slide Right', 'wedding-widget' ),
				'fade'        => esc_html__( 'Fade', 'wedding-widget' ),
				'zoom-out'    => esc_html__( 'Zoom Out', 'wedding-widget' ),
			),
		) );

		$this->add_control( 'duration', array(
			'label'   => esc_html__( 'Duration (ms)', 'wedding-widget' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 700,
			'min'     => 150,
			'max'     => 3000,
			'step'    => 50,
		) );

		$this->end_controls_section();

		// Background.
		$this->start_controls_section(
			'section_bg',
			array(
				'label' => esc_html__( 'Background', 'wedding-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'cover_bg',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .ww-cover',
			)
		);

		$this->add_control( 'overlay_color', array(
			'label'     => esc_html__( 'Overlay Color', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => 'rgba(0,0,0,0.45)',
			'selectors' => array( '{{WRAPPER}} .ww-cover__overlay' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'min_height', array(
			'label'      => esc_html__( 'Min Height', 'wedding-widget' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', 'vh' ),
			'range'      => array( 'px' => array( 'min' => 200, 'max' => 1200 ), 'vh' => array( 'min' => 30, 'max' => 100 ) ),
			'default'    => array( 'size' => 100, 'unit' => 'vh' ),
			'selectors'  => array( '{{WRAPPER}} .ww-cover' => 'min-height: {{SIZE}}{{UNIT}};' ),
		) );

		$this->end_controls_section();

		// Typography.
		$this->start_controls_section(
			'section_typo',
			array(
				'label' => esc_html__( 'Text', 'wedding-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control( 'text_color', array(
			'label'     => esc_html__( 'Text Color', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#ffffff',
			'selectors' => array( '{{WRAPPER}} .ww-cover__inner' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'couple_typo',
				'label'    => esc_html__( 'Couple Names', 'wedding-widget' ),
				'selector' => '{{WRAPPER}} .ww-cover__couple',
			)
		);

		$this->end_controls_section();

		// Button.
		$this->start_controls_section(
			'section_button',
			array(
				'label' => esc_html__( 'Button', 'wedding-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control( 'btn_color', array(
			'label'     => esc_html__( 'Text Color', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#111111',
			'selectors' => array( '{{WRAPPER}} .ww-cover__btn' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'btn_bg', array(
			'label'     => esc_html__( 'Background', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#ffffff',
			'selectors' => array( '{{WRAPPER}} .ww-cover__btn' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'btn_typo',
				'selector' => '{{WRAPPER}} .ww-cover__btn',
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$guest = '';
		if ( 'yes' === ( $settings['guest_from_url'] ?? 'yes' ) && isset( $_GET['to'] ) ) {
			$guest = sanitize_text_field( wp_unslash( $_GET['to'] ) );
		}

		$effect   = $settings['effect'] ?? 'slide-up';
		$duration = absint( $settings['duration'] ?? 700 );
		?>
		<div class="ww-cover" data-ww-cover data-effect="<?php echo esc_attr( $effect ); ?>" data-duration="<?php echo esc_attr( $duration ); ?>">
			<div class="ww-cover__overlay"></div>
			<div class="ww-cover__inner">
				<?php if ( ! empty( $settings['pretitle'] ) ) : ?>
					<p class="ww-cover__pretitle"><?php echo esc_html( $settings['pretitle'] ); ?></p>
				<?php endif; ?>

				<?php if ( ! empty( $settings['couple'] ) ) : ?>
					<h2 class="ww-cover__couple"><?php echo esc_html( $settings['couple'] ); ?></h2>
				<?php endif; ?>

				<?php if ( ! empty( $settings['date_text'] ) ) : ?>
					<p class="ww-cover__date"><?php echo esc_html( $settings['date_text'] ); ?></p>
				<?php endif; ?>

				<?php if ( '' !== $guest ) : ?>
					<div class="ww-cover__guest">
						<span class="ww-cover__dear"><?php echo esc_html( $settings['dear'] ?? '' ); ?></span>
						<strong class="ww-cover__guest-name"><?php echo esc_html( $guest ); ?></strong>
					</div>
				<?php endif; ?>

				<button type="button" class="ww-cover__btn" data-ww-cover-open>
					<?php echo esc_html( $settings['button_text'] ?? esc_html__( 'Open Invitation', 'wedding-widget' ) ); ?>
				</button>
			</div>
		</div>
		<?php
	}
}
