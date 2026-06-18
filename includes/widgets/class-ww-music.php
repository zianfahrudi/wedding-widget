<?php
/**
 * Music widget: floating click-to-toggle background audio.
 *
 * Supports uploaded audio, a direct audio link, or a YouTube video
 * (played audio-only via the YouTube IFrame API). Play/pause icons are
 * customizable and the toggle/spin animation is smoothed via CSS transitions.
 *
 * @package WeddingWidget
 */

namespace WeddingWidget\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Icons_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WW_Music extends Widget_Base {

	public function get_name() {
		return 'ww-music';
	}

	public function get_title() {
		return esc_html__( 'Music', 'wedding-widget' );
	}

	public function get_icon() {
		return 'eicon-headphones';
	}

	public function get_categories() {
		return array( 'wedding-widget' );
	}

	public function get_keywords() {
		return array( 'music', 'audio', 'sound', 'youtube', 'wedding' );
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
			array( 'label' => esc_html__( 'Music', 'wedding-widget' ) )
		);

		$this->add_control( 'source', array(
			'label'   => esc_html__( 'Audio Source', 'wedding-widget' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'upload',
			'options' => array(
				'upload'  => esc_html__( 'Upload Audio', 'wedding-widget' ),
				'link'    => esc_html__( 'Audio Link', 'wedding-widget' ),
				'youtube' => esc_html__( 'YouTube Video', 'wedding-widget' ),
			),
		) );

		$this->add_control( 'audio_upload', array(
			'label'      => esc_html__( 'Upload Audio', 'wedding-widget' ),
			'type'       => Controls_Manager::MEDIA,
			'media_type' => 'audio',
			'condition'  => array( 'source' => 'upload' ),
		) );

		$this->add_control( 'audio_link', array(
			'label'       => esc_html__( 'Audio URL', 'wedding-widget' ),
			'type'        => Controls_Manager::URL,
			'options'     => false,
			'placeholder' => 'https://example.com/song.mp3',
			'condition'   => array( 'source' => 'link' ),
			'dynamic'     => array( 'active' => true ),
		) );

		$this->add_control( 'youtube_url', array(
			'label'       => esc_html__( 'YouTube URL', 'wedding-widget' ),
			'type'        => Controls_Manager::TEXT,
			'placeholder' => 'https://youtu.be/xxxxxxxxxxx',
			'condition'   => array( 'source' => 'youtube' ),
			'dynamic'     => array( 'active' => true ),
		) );

		$this->add_control( 'autoplay', array(
			'label'        => esc_html__( 'Autoplay', 'wedding-widget' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'description'  => esc_html__( 'Browsers may block autoplay until the visitor interacts with the page.', 'wedding-widget' ),
		) );

		$this->add_control( 'loop', array(
			'label'        => esc_html__( 'Loop', 'wedding-widget' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'position', array(
			'label'   => esc_html__( 'Position', 'wedding-widget' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'bottom-right',
			'options' => array(
				'bottom-right' => esc_html__( 'Bottom Right', 'wedding-widget' ),
				'bottom-left'  => esc_html__( 'Bottom Left', 'wedding-widget' ),
				'top-right'    => esc_html__( 'Top Right', 'wedding-widget' ),
				'top-left'     => esc_html__( 'Top Left', 'wedding-widget' ),
				'inline'       => esc_html__( 'Inline', 'wedding-widget' ),
			),
		) );

		$this->end_controls_section();

		/* ---- Icons ---- */
		$this->start_controls_section(
			'section_icons',
			array( 'label' => esc_html__( 'Icons', 'wedding-widget' ) )
		);

		$this->add_control( 'play_icon', array(
			'label'       => esc_html__( 'Play Icon (paused state)', 'wedding-widget' ),
			'type'        => Controls_Manager::ICONS,
			'description' => esc_html__( 'Shown when the music is stopped.', 'wedding-widget' ),
			'default'     => array(
				'value'   => 'fas fa-play',
				'library' => 'fa-solid',
			),
		) );

		$this->add_control( 'pause_icon', array(
			'label'       => esc_html__( 'Pause Icon (playing state)', 'wedding-widget' ),
			'type'        => Controls_Manager::ICONS,
			'description' => esc_html__( 'Shown while the music is playing.', 'wedding-widget' ),
			'default'     => array(
				'value'   => 'fas fa-pause',
				'library' => 'fa-solid',
			),
		) );

		$this->end_controls_section();

		/* ---- Style: button ---- */
		$this->start_controls_section(
			'section_style',
			array(
				'label' => esc_html__( 'Button', 'wedding-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control( 'icon_color', array(
			'label'     => esc_html__( 'Icon Color', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#ffffff',
			'selectors' => array( '{{WRAPPER}} .ww-music__btn' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'btn_bg', array(
			'label'     => esc_html__( 'Background', 'wedding-widget' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#b08968',
			'selectors' => array( '{{WRAPPER}} .ww-music__btn' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_control( 'btn_size', array(
			'label'      => esc_html__( 'Button Size', 'wedding-widget' ),
			'type'       => Controls_Manager::SLIDER,
			'range'      => array( 'px' => array( 'min' => 32, 'max' => 96 ) ),
			'default'    => array( 'size' => 52, 'unit' => 'px' ),
			'selectors'  => array(
				'{{WRAPPER}} .ww-music__btn' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
			),
		) );

		$this->add_control( 'icon_size', array(
			'label'      => esc_html__( 'Icon Size', 'wedding-widget' ),
			'type'       => Controls_Manager::SLIDER,
			'range'      => array( 'px' => array( 'min' => 10, 'max' => 60 ) ),
			'default'    => array( 'size' => 22, 'unit' => 'px' ),
			'selectors'  => array(
				'{{WRAPPER}} .ww-music__icon' => 'font-size: {{SIZE}}{{UNIT}};',
			),
		) );

		$this->end_controls_section();

		/* ---- Style: animation ---- */
		$this->start_controls_section(
			'section_animation',
			array(
				'label' => esc_html__( 'Animation', 'wedding-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control( 'enable_spin', array(
			'label'        => esc_html__( 'Spin While Playing', 'wedding-widget' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'spin_speed', array(
			'label'      => esc_html__( 'Spin Speed (seconds)', 'wedding-widget' ),
			'type'       => Controls_Manager::SLIDER,
			'range'      => array( 's' => array( 'min' => 1, 'max' => 20, 'step' => 0.5 ) ),
			'default'    => array( 'size' => 5, 'unit' => 's' ),
			'condition'  => array( 'enable_spin' => 'yes' ),
		) );

		$this->add_control( 'hover_scale', array(
			'label'        => esc_html__( 'Grow On Hover', 'wedding-widget' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->end_controls_section();
	}

	/**
	 * Render an icon, falling back to a built-in SVG when none is chosen.
	 *
	 * @param array  $icon         Elementor icon control value.
	 * @param string $fallback_svg Fallback SVG markup.
	 * @return string
	 */
	private function icon_html( $icon, $fallback_svg ) {
		if ( ! empty( $icon['value'] ) ) {
			ob_start();
			Icons_Manager::render_icon( $icon, array( 'aria-hidden' => 'true' ) );
			$html = ob_get_clean();
			if ( $html ) {
				return $html;
			}
		}
		return $fallback_svg;
	}

	private function play_svg() {
		return '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>';
	}

	private function pause_svg() {
		return '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6 5h4v14H6zM14 5h4v14h-4z"/></svg>';
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$source   = $settings['source'] ?? 'upload';

		$audio_url = '';
		$youtube   = '';
		if ( 'upload' === $source ) {
			$audio_url = $settings['audio_upload']['url'] ?? '';
		} elseif ( 'link' === $source ) {
			$audio_url = $settings['audio_link']['url'] ?? '';
		} else {
			$youtube = $settings['youtube_url'] ?? '';
		}

		if ( '' === $audio_url && '' === $youtube ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="ww-music__placeholder">' . esc_html__( 'Select an audio source for the Music widget.', 'wedding-widget' ) . '</div>';
			}
			return;
		}

		$autoplay = ( 'yes' === ( $settings['autoplay'] ?? 'yes' ) ) ? '1' : '0';
		$loop     = ( 'yes' === ( $settings['loop'] ?? 'yes' ) ) ? '1' : '0';
		$position = $settings['position'] ?? 'bottom-right';
		$spin     = ( 'yes' === ( $settings['enable_spin'] ?? 'yes' ) );
		$hover    = ( 'yes' === ( $settings['hover_scale'] ?? 'yes' ) );
		$speed    = isset( $settings['spin_speed']['size'] ) ? floatval( $settings['spin_speed']['size'] ) : 5;
		if ( $speed <= 0 ) {
			$speed = 5;
		}

		$classes = array( 'ww-music', 'ww-music--' . $position );
		if ( $spin ) {
			$classes[] = 'ww-music--spin';
		}
		if ( $hover ) {
			$classes[] = 'ww-music--hover';
		}

		$play  = $this->icon_html( $settings['play_icon'] ?? array(), $this->play_svg() );
		$pause = $this->icon_html( $settings['pause_icon'] ?? array(), $this->pause_svg() );
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			data-ww-music
			data-autoplay="<?php echo esc_attr( $autoplay ); ?>"
			data-loop="<?php echo esc_attr( $loop ); ?>"
			style="--ww-spin-speed: <?php echo esc_attr( $speed ); ?>s;">

			<?php if ( '' !== $youtube ) : ?>
				<div class="ww-music__yt" data-video="<?php echo esc_url( $youtube ); ?>"></div>
			<?php else :
				$arr = explode( '.', strtok( $audio_url, '?' ) );
				$ext = strtolower( end( $arr ) );
				?>
				<audio class="ww-music__audio" preload="auto"<?php echo ( '1' === $loop ) ? ' loop' : ''; ?>>
					<source src="<?php echo esc_url( $audio_url ); ?>" type="audio/<?php echo esc_attr( $ext ? $ext : 'mpeg' ); ?>">
				</audio>
			<?php endif; ?>

			<button type="button" class="ww-music__btn" data-ww-music-btn aria-label="<?php esc_attr_e( 'Toggle music', 'wedding-widget' ); ?>">
				<span class="ww-music__icon ww-music__icon--play"><?php echo $play; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor icon / static SVG. ?></span>
				<span class="ww-music__icon ww-music__icon--pause"><?php echo $pause; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor icon / static SVG. ?></span>
			</button>
		</div>
		<?php
	}
}
