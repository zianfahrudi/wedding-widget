<?php
/**
 * Add to Google Calendar button widget.
 *
 * @package WeddingWidget
 */

namespace WeddingWidget\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WW_Calendar extends Widget_Base {

	public function get_name() {
		return 'ww-calendar';
	}

	public function get_title() {
		return esc_html__( 'Add to Calendar', 'wedding-widget' );
	}

	public function get_icon() {
		return 'eicon-calendar';
	}

	public function get_categories() {
		return array( 'wedding-widget' );
	}

	public function get_keywords() {
		return array( 'calendar', 'google', 'event', 'reminder', 'wedding' );
	}

	public function get_style_depends() {
		return array( 'wedding-widget' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			array( 'label' => esc_html__( 'Event', 'wedding-widget' ) )
		);

		$this->add_control( 'event_title', array(
			'label'   => esc_html__( 'Event Title', 'wedding-widget' ),
			'type'    => Controls_Manager::TEXT,
			'default' => esc_html__( 'Our Wedding', 'wedding-widget' ),
		) );

		$this->add_control( 'start', array(
			'label'   => esc_html__( 'Start', 'wedding-widget' ),
			'type'    => Controls_Manager::DATE_TIME,
			'default' => gmdate( 'Y-m-d 10:00', strtotime( '+30 days' ) ),
		) );

		$this->add_control( 'end', array(
			'label'   => esc_html__( 'End', 'wedding-widget' ),
			'type'    => Controls_Manager::DATE_TIME,
			'default' => gmdate( 'Y-m-d 13:00', strtotime( '+30 days' ) ),
		) );

		$this->add_control( 'location', array(
			'label'   => esc_html__( 'Location', 'wedding-widget' ),
			'type'    => Controls_Manager::TEXT,
			'default' => '',
		) );

		$this->add_control( 'details', array(
			'label'   => esc_html__( 'Details', 'wedding-widget' ),
			'type'    => Controls_Manager::TEXTAREA,
			'default' => '',
		) );

		$this->add_control( 'button_text', array(
			'label'   => esc_html__( 'Button Text', 'wedding-widget' ),
			'type'    => Controls_Manager::TEXT,
			'default' => esc_html__( 'Save the Date', 'wedding-widget' ),
		) );

		$this->add_responsive_control( 'align', array(
			'label'     => esc_html__( 'Alignment', 'wedding-widget' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => array(
				'flex-start' => array( 'title' => esc_html__( 'Left', 'wedding-widget' ), 'icon' => 'eicon-text-align-left' ),
				'center'     => array( 'title' => esc_html__( 'Center', 'wedding-widget' ), 'icon' => 'eicon-text-align-center' ),
				'flex-end'   => array( 'title' => esc_html__( 'Right', 'wedding-widget' ), 'icon' => 'eicon-text-align-right' ),
			),
			'default'   => 'center',
			'selectors' => array( '{{WRAPPER}} .ww-cal' => 'justify-content: {{VALUE}};' ),
		) );

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			array(
				'label' => esc_html__( 'Button', 'wedding-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control( 'color', array(
			'label'     => esc_html__( 'Text Color', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#ffffff',
			'selectors' => array( '{{WRAPPER}} .ww-cal__btn' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'bg', array(
			'label'     => esc_html__( 'Background', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#4285F4',
			'selectors' => array( '{{WRAPPER}} .ww-cal__btn' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_control( 'radius', array(
			'label'      => esc_html__( 'Radius', 'wedding-widget' ),
			'type'       => Controls_Manager::SLIDER,
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
			'default'    => array( 'size' => 8, 'unit' => 'px' ),
			'selectors'  => array( '{{WRAPPER}} .ww-cal__btn' => 'border-radius: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'btn_typo',
				'selector' => '{{WRAPPER}} .ww-cal__btn',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Format a local datetime string into the Google Calendar UTC format.
	 *
	 * @param string $datetime Local datetime (site timezone).
	 * @return string Ymd\THis\Z
	 */
	private function to_gcal_utc( $datetime ) {
		if ( empty( $datetime ) ) {
			return '';
		}
		// Interpret the picker value as site-local time, convert to GMT.
		$gmt = get_gmt_from_date( $datetime, 'Ymd\THis\Z' );
		return $gmt ? $gmt : '';
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$start = $this->to_gcal_utc( $settings['start'] ?? '' );
		$end   = $this->to_gcal_utc( $settings['end'] ?? '' );

		$url = add_query_arg(
			array_filter(
				array(
					'action'   => 'TEMPLATE',
					'text'     => rawurlencode( $settings['event_title'] ?? '' ),
					'dates'    => ( $start && $end ) ? $start . '/' . $end : '',
					'details'  => ! empty( $settings['details'] ) ? rawurlencode( $settings['details'] ) : '',
					'location' => ! empty( $settings['location'] ) ? rawurlencode( $settings['location'] ) : '',
				)
			),
			'https://calendar.google.com/calendar/render'
		);
		?>
		<div class="ww-cal">
			<a class="ww-cal__btn" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">
				<?php echo esc_html( $settings['button_text'] ?? esc_html__( 'Save the Date', 'wedding-widget' ) ); ?>
			</a>
		</div>
		<?php
	}
}
