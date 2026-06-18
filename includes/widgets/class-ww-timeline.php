<?php
/**
 * Timeline widget: a vertical "love story" timeline.
 *
 * @package WeddingWidget
 */

namespace WeddingWidget\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WW_Timeline extends Widget_Base {

	public function get_name() {
		return 'ww-timeline';
	}

	public function get_title() {
		return esc_html__( 'Timeline', 'wedding-widget' );
	}

	public function get_icon() {
		return 'eicon-time-line';
	}

	public function get_categories() {
		return array( 'wedding-widget' );
	}

	public function get_keywords() {
		return array( 'timeline', 'story', 'love', 'journey', 'wedding' );
	}

	public function get_style_depends() {
		return array( 'wedding-widget' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			array( 'label' => esc_html__( 'Story Items', 'wedding-widget' ) )
		);

		$repeater = new Repeater();

		$repeater->add_control( 'title', array(
			'label'   => esc_html__( 'Title', 'wedding-widget' ),
			'type'    => Controls_Manager::TEXT,
			'default' => esc_html__( 'Our Story', 'wedding-widget' ),
			'dynamic' => array( 'active' => true ),
		) );

		$repeater->add_control( 'date', array(
			'label'   => esc_html__( 'Date', 'wedding-widget' ),
			'type'    => Controls_Manager::TEXT,
			'default' => esc_html__( '1 January 2024', 'wedding-widget' ),
			'dynamic' => array( 'active' => true ),
		) );

		$repeater->add_control( 'description', array(
			'label'   => esc_html__( 'Description', 'wedding-widget' ),
			'type'    => Controls_Manager::TEXTAREA,
			'default' => esc_html__( 'Tell the story of this moment here.', 'wedding-widget' ),
			'dynamic' => array( 'active' => true ),
		) );

		$repeater->add_control( 'image', array(
			'label'   => esc_html__( 'Image', 'wedding-widget' ),
			'type'    => Controls_Manager::MEDIA,
			'dynamic' => array( 'active' => true ),
		) );

		$this->add_control( 'items', array(
			'label'       => esc_html__( 'Items', 'wedding-widget' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $repeater->get_controls(),
			'title_field' => '{{{ title }}}',
			'default'     => array(
				array(
					'title'       => esc_html__( 'First Met', 'wedding-widget' ),
					'date'        => esc_html__( '8 July 2018', 'wedding-widget' ),
					'description' => esc_html__( 'The day our journey began.', 'wedding-widget' ),
				),
				array(
					'title'       => esc_html__( 'The Proposal', 'wedding-widget' ),
					'date'        => esc_html__( '5 February 2023', 'wedding-widget' ),
					'description' => esc_html__( 'He asked, she said yes.', 'wedding-widget' ),
				),
				array(
					'title'       => esc_html__( 'The Wedding', 'wedding-widget' ),
					'date'        => esc_html__( '1 December 2026', 'wedding-widget' ),
					'description' => esc_html__( 'Celebrating our love with family and friends.', 'wedding-widget' ),
				),
			),
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

		$this->add_control( 'line_color', array(
			'label'     => esc_html__( 'Line & Dot Color', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#b08968',
			'selectors' => array(
				'{{WRAPPER}} .ww-timeline::before' => 'background-color: {{VALUE}};',
				'{{WRAPPER}} .ww-timeline__dot'    => 'background-color: {{VALUE}};',
			),
		) );

		$this->add_control( 'card_bg', array(
			'label'     => esc_html__( 'Card Background', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#ffffff',
			'selectors' => array( '{{WRAPPER}} .ww-timeline__card' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_control( 'title_color', array(
			'label'     => esc_html__( 'Title Color', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#1f2937',
			'selectors' => array( '{{WRAPPER}} .ww-timeline__title' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typo',
				'label'    => esc_html__( 'Title Typography', 'wedding-widget' ),
				'selector' => '{{WRAPPER}} .ww-timeline__title',
			)
		);

		$this->add_control( 'date_color', array(
			'label'     => esc_html__( 'Date Color', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#b08968',
			'selectors' => array( '{{WRAPPER}} .ww-timeline__date' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'text_color', array(
			'label'     => esc_html__( 'Text Color', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#4b5563',
			'selectors' => array( '{{WRAPPER}} .ww-timeline__desc' => 'color: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$items    = $settings['items'] ?? array();

		if ( empty( $items ) ) {
			return;
		}
		?>
		<div class="ww-timeline">
			<?php foreach ( $items as $item ) :
				$img = ! empty( $item['image']['url'] ) ? $item['image']['url'] : '';
				?>
				<div class="ww-timeline__item">
					<span class="ww-timeline__dot" aria-hidden="true"></span>
					<div class="ww-timeline__card">
						<?php if ( $img ) : ?>
							<div class="ww-timeline__media">
								<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $item['title'] ?? '' ); ?>" loading="lazy">
							</div>
						<?php endif; ?>
						<div class="ww-timeline__body">
							<?php if ( ! empty( $item['date'] ) ) : ?>
								<div class="ww-timeline__date"><?php echo esc_html( $item['date'] ); ?></div>
							<?php endif; ?>
							<?php if ( ! empty( $item['title'] ) ) : ?>
								<h3 class="ww-timeline__title"><?php echo esc_html( $item['title'] ); ?></h3>
							<?php endif; ?>
							<?php if ( ! empty( $item['description'] ) ) : ?>
								<div class="ww-timeline__desc"><?php echo wp_kses_post( wpautop( $item['description'] ) ); ?></div>
							<?php endif; ?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
