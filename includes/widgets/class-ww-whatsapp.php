<?php
/**
 * WhatsApp button widget.
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

class WW_WhatsApp extends Widget_Base {

	public function get_name() {
		return 'ww-whatsapp';
	}

	public function get_title() {
		return esc_html__( 'WhatsApp', 'wedding-widget' );
	}

	public function get_icon() {
		return 'eicon-whatsapp';
	}

	public function get_categories() {
		return array( 'wedding-widget' );
	}

	public function get_keywords() {
		return array( 'whatsapp', 'wa', 'chat', 'button' );
	}

	public function get_style_depends() {
		return array( 'wedding-widget' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			array( 'label' => esc_html__( 'WhatsApp', 'wedding-widget' ) )
		);

		$this->add_control( 'text', array(
			'label'   => esc_html__( 'Button Text', 'wedding-widget' ),
			'type'    => Controls_Manager::TEXT,
			'default' => esc_html__( 'Chat on WhatsApp', 'wedding-widget' ),
			'dynamic' => array( 'active' => true ),
		) );

		$this->add_control( 'phone', array(
			'label'       => esc_html__( 'WhatsApp Number', 'wedding-widget' ),
			'type'        => Controls_Manager::TEXT,
			'placeholder' => '08xxxxxxxxxx',
			'description' => esc_html__( 'Local or international format; it will be normalized to international.', 'wedding-widget' ),
			'dynamic'     => array( 'active' => true ),
		) );

		$this->add_control( 'country_code', array(
			'label'       => esc_html__( 'Default Country Code', 'wedding-widget' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => '62',
			'description' => esc_html__( 'Used to replace a leading 0. Default 62 (Indonesia).', 'wedding-widget' ),
		) );

		$this->add_control( 'message', array(
			'label'   => esc_html__( 'Prefilled Message', 'wedding-widget' ),
			'type'    => Controls_Manager::TEXTAREA,
			'default' => '',
			'dynamic' => array( 'active' => true ),
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
			'selectors' => array( '{{WRAPPER}} .ww-wa' => 'justify-content: {{VALUE}};' ),
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
			'selectors' => array( '{{WRAPPER}} .ww-wa__btn' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'bg', array(
			'label'     => esc_html__( 'Background', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#25D366',
			'selectors' => array( '{{WRAPPER}} .ww-wa__btn' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_control( 'radius', array(
			'label'      => esc_html__( 'Radius', 'wedding-widget' ),
			'type'       => Controls_Manager::SLIDER,
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
			'default'    => array( 'size' => 8, 'unit' => 'px' ),
			'selectors'  => array( '{{WRAPPER}} .ww-wa__btn' => 'border-radius: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'btn_typo',
				'selector' => '{{WRAPPER}} .ww-wa__btn',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Normalize a phone number to international digits.
	 *
	 * @param string $phone        Raw phone.
	 * @param string $country_code Default country code.
	 * @return string
	 */
	private function normalize_phone( $phone, $country_code ) {
		$digits = preg_replace( '/[^0-9]/', '', $phone );
		if ( '' === $digits ) {
			return '';
		}
		if ( 0 === strpos( $digits, '0' ) ) {
			$digits = $country_code . substr( $digits, 1 );
		}
		return $digits;
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$phone = $this->normalize_phone(
			$settings['phone'] ?? '',
			preg_replace( '/[^0-9]/', '', $settings['country_code'] ?? '62' )
		);

		$href = '#';
		if ( '' !== $phone ) {
			$href = 'https://wa.me/' . $phone;
			if ( ! empty( $settings['message'] ) ) {
				$href = add_query_arg( 'text', rawurlencode( $settings['message'] ), $href );
			}
		}
		?>
		<div class="ww-wa">
			<a class="ww-wa__btn" href="<?php echo esc_url( $href ); ?>" target="_blank" rel="noopener noreferrer">
				<?php echo esc_html( $settings['text'] ?? '' ); ?>
			</a>
		</div>
		<?php
	}
}
