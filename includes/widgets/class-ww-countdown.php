<?php
/**
 * Countdown widget.
 *
 * @package WeddingWidget
 */

namespace WeddingWidget\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WW_Countdown extends Widget_Base {

	public function get_name() {
		return 'ww-countdown';
	}

	public function get_title() {
		return esc_html__( 'Countdown', 'wedding-widget' );
	}

	public function get_icon() {
		return 'eicon-countdown';
	}

	public function get_categories() {
		return array( 'wedding-widget' );
	}

	public function get_keywords() {
		return array( 'countdown', 'timer', 'date', 'wedding' );
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
			array( 'label' => esc_html__( 'Countdown', 'wedding-widget' ) )
		);

		$this->add_control(
			'target_date',
			array(
				'label'       => esc_html__( 'Target Date', 'wedding-widget' ),
				'type'        => Controls_Manager::DATE_TIME,
				'default'     => gmdate( 'Y-m-d 00:00', strtotime( '+30 days' ) ),
				'description' => esc_html__( 'The date and time the countdown ends.', 'wedding-widget' ),
			)
		);

		$this->add_control(
			'show_labels',
			array(
				'label'        => esc_html__( 'Show Labels', 'wedding-widget' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control( 'label_days', array(
			'label'   => esc_html__( 'Days Label', 'wedding-widget' ),
			'type'    => Controls_Manager::TEXT,
			'default' => esc_html__( 'Days', 'wedding-widget' ),
		) );
		$this->add_control( 'label_hours', array(
			'label'   => esc_html__( 'Hours Label', 'wedding-widget' ),
			'type'    => Controls_Manager::TEXT,
			'default' => esc_html__( 'Hours', 'wedding-widget' ),
		) );
		$this->add_control( 'label_minutes', array(
			'label'   => esc_html__( 'Minutes Label', 'wedding-widget' ),
			'type'    => Controls_Manager::TEXT,
			'default' => esc_html__( 'Minutes', 'wedding-widget' ),
		) );
		$this->add_control( 'label_seconds', array(
			'label'   => esc_html__( 'Seconds Label', 'wedding-widget' ),
			'type'    => Controls_Manager::TEXT,
			'default' => esc_html__( 'Seconds', 'wedding-widget' ),
		) );

		$this->end_controls_section();

		// Style: boxes.
		$this->start_controls_section(
			'section_style',
			array(
				'label' => esc_html__( 'Boxes', 'wedding-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'gap',
			array(
				'label'      => esc_html__( 'Gap', 'wedding-widget' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
				'default'    => array( 'size' => 16, 'unit' => 'px' ),
				'selectors'  => array(
					'{{WRAPPER}} .ww-countdown' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'box_bg',
			array(
				'label'     => esc_html__( 'Box Background', 'wedding-widget' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#111111',
				'selectors' => array( '{{WRAPPER}} .ww-countdown__box' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'box_padding',
			array(
				'label'      => esc_html__( 'Box Padding', 'wedding-widget' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'default'    => array( 'top' => 16, 'right' => 16, 'bottom' => 16, 'left' => 16, 'unit' => 'px' ),
				'selectors'  => array(
					'{{WRAPPER}} .ww-countdown__box' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'box_radius',
			array(
				'label'      => esc_html__( 'Box Radius', 'wedding-widget' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'default'    => array( 'size' => 12, 'unit' => 'px' ),
				'selectors'  => array( '{{WRAPPER}} .ww-countdown__box' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'box_border',
				'selector' => '{{WRAPPER}} .ww-countdown__box',
			)
		);

		$this->add_control(
			'digit_heading',
			array(
				'label'     => esc_html__( 'Digits', 'wedding-widget' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'digit_color',
			array(
				'label'     => esc_html__( 'Digit Color', 'wedding-widget' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array( '{{WRAPPER}} .ww-countdown__digit' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'digit_typography',
				'selector' => '{{WRAPPER}} .ww-countdown__digit',
			)
		);

		$this->add_control(
			'label_heading',
			array(
				'label'     => esc_html__( 'Labels', 'wedding-widget' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'label_color',
			array(
				'label'     => esc_html__( 'Label Color', 'wedding-widget' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array( '{{WRAPPER}} .ww-countdown__label' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'label_typography',
				'selector' => '{{WRAPPER}} .ww-countdown__label',
			)
		);

		$this->add_responsive_control(
			'align',
			array(
				'label'     => esc_html__( 'Alignment', 'wedding-widget' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start' => array( 'title' => esc_html__( 'Left', 'wedding-widget' ), 'icon' => 'eicon-text-align-left' ),
					'center'     => array( 'title' => esc_html__( 'Center', 'wedding-widget' ), 'icon' => 'eicon-text-align-center' ),
					'flex-end'   => array( 'title' => esc_html__( 'Right', 'wedding-widget' ), 'icon' => 'eicon-text-align-right' ),
				),
				'default'   => 'center',
				'selectors' => array( '{{WRAPPER}} .ww-countdown' => 'justify-content: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$target = ! empty( $settings['target_date'] ) ? $settings['target_date'] : gmdate( 'Y-m-d 00:00', strtotime( '+30 days' ) );
		// Convert the editor-local datetime to a timestamp using the site timezone.
		$timestamp = strtotime( get_gmt_from_date( $target ) . ' UTC' );
		if ( ! $timestamp ) {
			$timestamp = strtotime( '+30 days' );
		}

		$show_labels = ( 'yes' === ( $settings['show_labels'] ?? 'yes' ) );

		$units = array(
			'days'    => $settings['label_days'] ?? esc_html__( 'Days', 'wedding-widget' ),
			'hours'   => $settings['label_hours'] ?? esc_html__( 'Hours', 'wedding-widget' ),
			'minutes' => $settings['label_minutes'] ?? esc_html__( 'Minutes', 'wedding-widget' ),
			'seconds' => $settings['label_seconds'] ?? esc_html__( 'Seconds', 'wedding-widget' ),
		);
		?>
		<div class="ww-countdown" data-ww-countdown data-target="<?php echo esc_attr( $timestamp ); ?>">
			<?php foreach ( $units as $unit => $label ) : ?>
				<div class="ww-countdown__box">
					<span class="ww-countdown__digit" data-unit="<?php echo esc_attr( $unit ); ?>">00</span>
					<?php if ( $show_labels ) : ?>
						<span class="ww-countdown__label"><?php echo esc_html( $label ); ?></span>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
