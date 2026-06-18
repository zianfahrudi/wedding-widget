<?php
/**
 * Admin Comments screen enhancements for Wedding Widget entries.
 *
 * - Shows an attendance icon next to the author name (green check = Attending,
 *   red X = Not Attending, question mark = Maybe).
 * - Shows an initials avatar placeholder for our comment authors (who have no
 *   email/Gravatar).
 * - The author IP address is shown by WordPress core once it is stored on
 *   submission (handled in WW_Loader).
 *
 * @package WeddingWidget
 */

namespace WeddingWidget;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WW_Admin {

	/**
	 * Comment types handled by this plugin.
	 *
	 * @var string[]
	 */
	private $types = array( 'ww_wish', 'ww_rsvp' );

	public function __construct() {
		add_filter( 'comment_author', array( $this, 'append_attendance_icon' ), 20, 3 );
		add_filter( 'pre_get_avatar', array( $this, 'initials_avatar' ), 20, 3 );
	}

	/**
	 * Resolve a comment object from a filter's mixed arguments.
	 *
	 * @param int|string|object $comment_id Comment ID.
	 * @param object|null       $comment    Comment object (if provided).
	 * @return \WP_Comment|null
	 */
	private function resolve_comment( $comment_id, $comment ) {
		if ( $comment instanceof \WP_Comment ) {
			return $comment;
		}
		if ( $comment_id ) {
			$resolved = get_comment( $comment_id );
			return $resolved instanceof \WP_Comment ? $resolved : null;
		}
		return null;
	}

	/**
	 * Whether a comment belongs to this plugin.
	 *
	 * @param \WP_Comment|null $comment Comment.
	 * @return bool
	 */
	private function is_ours( $comment ) {
		return $comment instanceof \WP_Comment && in_array( $comment->comment_type, $this->types, true );
	}

	/**
	 * Append the attendance icon after the author name (admin only).
	 *
	 * @param string     $author     Author name.
	 * @param int|string $comment_id Comment ID.
	 * @param object     $comment    Comment object.
	 * @return string
	 */
	public function append_attendance_icon( $author, $comment_id = 0, $comment = null ) {
		if ( ! is_admin() ) {
			return $author;
		}

		$comment = $this->resolve_comment( $comment_id, $comment );
		if ( ! $this->is_ours( $comment ) ) {
			return $author;
		}

		$attendance = get_comment_meta( $comment->comment_ID, 'ww_attendance', true );
		$icon       = $this->attendance_icon_html( $attendance );

		return $icon ? $author . ' ' . $icon : $author;
	}

	/**
	 * Build the dashicon markup for an attendance status.
	 *
	 * @param string $attendance Attendance key.
	 * @return string
	 */
	private function attendance_icon_html( $attendance ) {
		$map = array(
			'attending'     => array( 'dashicons-yes-alt', '#16a34a', __( 'Attending', 'wedding-widget' ) ),
			'not_attending' => array( 'dashicons-dismiss', '#dc2626', __( 'Not Attending', 'wedding-widget' ) ),
			'maybe'         => array( 'dashicons-editor-help', '#ca8a04', __( 'Maybe', 'wedding-widget' ) ),
		);

		if ( ! isset( $map[ $attendance ] ) ) {
			return '';
		}

		list( $icon, $color, $label ) = $map[ $attendance ];

		return sprintf(
			'<span class="dashicons %1$s" style="color:%2$s;vertical-align:text-bottom;" title="%3$s" aria-label="%3$s"></span>',
			esc_attr( $icon ),
			esc_attr( $color ),
			esc_attr( $label )
		);
	}

	/**
	 * Replace the avatar with an initials placeholder for our authors (admin only).
	 *
	 * @param string|null $avatar      Pre-computed avatar HTML (null by default).
	 * @param mixed       $id_or_email Avatar identifier (may be a WP_Comment).
	 * @param array       $args        Avatar args.
	 * @return string|null
	 */
	public function initials_avatar( $avatar, $id_or_email, $args ) {
		if ( ! is_admin() ) {
			return $avatar;
		}

		$comment = null;
		if ( $id_or_email instanceof \WP_Comment ) {
			$comment = $id_or_email;
		} elseif ( is_object( $id_or_email ) && isset( $id_or_email->comment_ID ) ) {
			$comment = get_comment( $id_or_email->comment_ID );
		}

		if ( ! $this->is_ours( $comment ) ) {
			return $avatar;
		}

		$size     = isset( $args['size'] ) ? (int) $args['size'] : 48;
		$size     = max( 16, $size );
		$font     = max( 9, (int) round( $size * 0.42 ) );
		$name     = $comment->comment_author;
		$initials = WW_Loader::initials( $name );
		$color    = WW_Loader::avatar_color( $name );

		return sprintf(
			'<span class="ww-admin-avatar avatar" style="display:inline-flex;align-items:center;justify-content:center;width:%1$dpx;height:%1$dpx;border-radius:50%%;background:%2$s;color:#fff;font-size:%3$dpx;font-weight:700;line-height:1;text-transform:uppercase;overflow:hidden;vertical-align:middle;">%4$s</span>',
			$size,
			esc_attr( $color ),
			$font,
			esc_html( $initials )
		);
	}
}
