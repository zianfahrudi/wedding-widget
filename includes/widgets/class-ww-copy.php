<?php
/**
 * Copy Text widget: copy-to-clipboard (e.g. bank account / e-gift).
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

class WW_Copy extends Widget_Base {

	public function get_name() {
		return 'ww-copy';
	}

	public function get_title() {
		return esc_html__( 'Copy Text', 'wedding-widget' );
	}

	public function get_icon() {
		return 'eicon-copy';
	}

	public function get_categories() {
		return array( 'wedding-widget' );
	}

	public function get_keywords() {
		return array( 'copy', 'clipboard', 'bank', 'account', 'gift' );
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
			array( 'label' => esc_html__( 'Copy Text', 'wedding-widget' ) )
		);

		$this->add_control( 'title', array(
			'label'   => esc_html__( 'Title', 'wedding-widget' ),
			'type'    => Controls_Manager::TEXT,
			'default' => esc_html__( 'Bank Transfer', 'wedding-widget' ),
			'dynamic' => array( 'active' => true ),
		) );

		$this->add_control( 'content', array(
			'label'   => esc_html__( 'Text to Copy', 'wedding-widget' ),
			'type'    => Controls_Manager::TEXT,
			'default' => '1234567890',
			'dynamic' => array( 'active' => true ),
		) );

		$this->add_control( 'show_content', array(
			'label'        => esc_html__( 'Show Text', 'wedding-widget' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'button_text', array(
			'label'   => esc_html__( 'Button Text', 'wedding-widget' ),
			'type'    => Controls_Manager::TEXT,
			'default' => esc_html__( 'Copy', 'wedding-widget' ),
		) );

		$this->add_control( 'copied_text', array(
			'label'   => esc_html__( 'Copied Message', 'wedding-widget' ),
			'type'    => Controls_Manager::TEXT,
			'default' => esc_html__( 'Copied!', 'wedding-widget' ),
		) );

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			array(
				'label' => esc_html__( 'Style', 'wedding-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control( 'title_color', array(
			'label'     => esc_html__( 'Title Color', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .ww-copy__title' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'text_color', array(
			'label'     => esc_html__( 'Text Color', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .ww-copy__text' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'btn_color', array(
			'label'     => esc_html__( 'Button Text Color', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#ffffff',
			'selectors' => array( '{{WRAPPER}} .ww-copy__btn' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'btn_bg', array(
			'label'     => esc_html__( 'Button Background', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#b08968',
			'selectors' => array( '{{WRAPPER}} .ww-copy__btn' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'text_typo',
				'label'    => esc_html__( 'Text Typography', 'wedding-widget' ),
				'selector' => '{{WRAPPER}} .ww-copy__text',
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$content  = $settings['content'] ?? '';
		$show     = ( 'yes' === ( $settings['show_content'] ?? 'yes' ) );
		?>
		<div class="ww-copy" data-ww-copy>
			<?php if ( ! empty( $settings['title'] ) ) : ?>
				<div class="ww-copy__title"><?php echo esc_html( $settings['title'] ); ?></div>
			<?php endif; ?>

			<?php if ( $show && '' !== $content ) : ?>
				<div class="ww-copy__text"><?php echo esc_html( $content ); ?></div>
			<?php endif; ?>

			<button type="button"
				class="ww-copy__btn"
				data-ww-copy-btn
				data-copy="<?php echo esc_attr( $content ); ?>"
				data-label="<?php echo esc_attr( $settings['button_text'] ?? esc_html__( 'Copy', 'wedding-widget' ) ); ?>"
				data-copied="<?php echo esc_attr( $settings['copied_text'] ?? esc_html__( 'Copied!', 'wedding-widget' ) ); ?>">
				<?php echo esc_html( $settings['button_text'] ?? esc_html__( 'Copy', 'wedding-widget' ) ); ?>
			</button>
		</div>
		<?php
	}
}
