<?php
/**
 * Module for premium post filters.
 *
 * @package qbeats for WordPress
 */

/**
 * Functions related to premium post filters
 */
class QB_PremiumPostFilter {
	/**
	 * Construct and initialize premium post filters
	 */
	public function __construct() {
		add_filter( 'the_title',   array( $this, 'filter_post_title' ), 10, 2 );
		add_filter( 'the_content', array( $this, 'filter_post_content' ), 10, 1 );
		add_filter( 'the_excerpt', array( $this, 'filter_post_excerpt' ), 10, 1 );
	}

	/**
	 * Check if we need to close premium content from user.
	 */
	private function should_apply_premium_filter() {
		global $qb_auth;
		return $qb_auth->is_plugin_authorized() && ! current_user_can( 'manage_options' );
	}

	/**
	 * Get resulting title of the given post
	 *
	 * @param string $title current title post value.
	 * @param int    $post_id the post ID.
	 *
	 * @return string resulting title of the given post.
	 */
	public function filter_post_title( $title, $post_id ) {
		if ( ! $this->should_apply_premium_filter() ) {
			return $title;
		}
		if ( ! is_null( $post_id ) ) {
			$wp_post = get_post( $post_id );
		}
		if ( ! empty( $wp_post ) && $this->is_post_premium_for_user( $wp_post ) && ! is_single() ) {
			if ( $this->need_show_price_label() ) {
				$qb_post = new QB_Story( $wp_post->ID );
				$title .= " <qp-price story-id=\"{$qb_post->story_id}\"";
				$widget_settings = $this->price_widget_settings();
				if ( $widget_settings['trend_arrow'] ) {
					$title .= ' trend-arrow="true"';
				}
				if ( $widget_settings['trend_in_price'] ) {
					$title .= ' trend-in-price="true"';
				}
				if ( $widget_settings['qmv_icon'] ) {
					$title .= ' qmv-icon="true"';
				}
				$title .= '></qp-price>';
			} elseif ( $this->need_show_mini_paywindow() ) {
				$qb_post = new QB_Story( $wp_post->ID );
				$paywindow_size = $this->current_paywindow_size();
				$post_url = get_post_permalink( $qb_post->wp_post_id );
				$title .= " <qb-winks story-id=\"{$qb_post->story_id}\"";
				$title .= ' theme="mini" blink="true" skip-fake-text';
				$title .= " size=\"$paywindow_size\"";
				$title .= " callback-url=\"$post_url\"";
				$title .= '></qb-winks>';
			} elseif ( ! $this->need_show_full_paywindow() ) {
				$title .= '<qp-icon></qp-icon>';
			}
		}

		return $title;
	}

	/**
	 * Get resulting content of the given post
	 *
	 * @param string $content current content post value.
	 *
	 * @return string resulting content of the given post.
	 */
	public function filter_post_content( $content ) {
		if ( ! $this->should_apply_premium_filter() ) {
			return $content;
		}
		global $post;
		if ( $this->is_post_premium_for_user( $post, is_single() ) ) {
			$qb_post = new QB_Story( $post->ID );

			$content = $this->available_content( $content, $qb_post );
			if ( $this->need_show_full_paywindow() || is_single() ) {
				$content = $this->append_buy_button( $content, $qb_post, is_single() );
			}
		}

		return $content;
	}

	/**
	 * Get resulting excerpt for the given post
	 *
	 * @param string $excerpt current excerpt post value.
	 *
	 * @return string resulting excerpt of the given post.
	 */
	public function filter_post_excerpt( $excerpt ) {
		if ( ! $this->should_apply_premium_filter() ) {
			return $excerpt;
		}
		global $post;
		if ( $this->is_post_premium_for_user( $post ) ) {
			$qb_post = new QB_Story( $post->ID );
			// Supposed that publisher defines visible free content
			// with 'Excerpts' https://en.support.wordpress.com/splitting-content/excerpts/.
			$excerpt = $this->available_content( $excerpt, $qb_post );
			if ( $this->need_show_full_paywindow() || is_single() ) {
				$excerpt = $this->append_buy_button( $excerpt, $qb_post, is_single() );
			}
		}

		return $excerpt;
	}

	/**
	 * Check if post is premium for a user.
	 *
	 * @param object $post wp post.
	 * @return bool true if post is premium for a user.
	 */
	private function is_post_premium_for_user( $post ) {
		if ( is_null( $post ) || ! is_object( $post ) || ! $this->should_apply_premium_filter() ) {
			return false;
		}

		$qb_post = new QB_Story( $post->ID );
		$user    = wp_get_current_user();

		return ( $qb_post &&
		         $qb_post->is_protected_by_qbeats() &&
		         $post->post_author !== $user->ID );
	}

	/**
	 * Get powerup type options.
	 *
	 * @return string Current option for the powerup type.
	 */
	private function current_powerup_type() {
		$powerup_type = QB_PowerupType::$full;
		$options = get_option( 'plugin_options' );
		if ( isset( $options['powerup_type'] ) ) {
			$powerup_type = $options['powerup_type'];
		}
		return $powerup_type;
	}

	/**
	 * Check if powerup panel should be shown.
	 *
	 * @return bool True if powerup panel should be shown.
	 */
	private function need_show_full_paywindow() {
		return QB_PowerupType::$full === $this->current_powerup_type();
	}

	/**
	 * Check if mini paywindow should be shown.
	 *
	 * @return bool True if mini paywindow should be shown.
	 */
	private function need_show_mini_paywindow() {
		return QB_PowerupType::$mini === $this->current_powerup_type();
	}

	/**
	 * Check if price label should be shown.
	 *
	 * @return bool True if price label should be shown.
	 */
	private function need_show_price_label() {
		return QB_PowerupType::$price === $this->current_powerup_type();
	}

	/**
	 * Get pay window size options.
	 *
	 * @return string Current option for the pay window size.
	 */
	private function current_paywindow_size() {
		$paywindow_size = QB_PayWindowSize::$medium;
		$options = get_option( 'plugin_options' );
		if ( isset( $options['paywindow_size'] ) ) {
			$paywindow_size = $options['paywindow_size'];
		}
		return $paywindow_size;
	}

	/**
	 * Get price widget settings.
	 *
	 * @return array Object that contains the peice widget settings.
	 */
	private function price_widget_settings() {
		$widget_settings = array(
			'trend_arrow' => QB_PriceWidget::$trend_arrow,
			'trend_in_price' => QB_PriceWidget::$trend_in_price,
			'qmv_icon' => QB_PriceWidget::$qmv_icon,
		);
		$options = get_option( 'plugin_options' );
		if ( isset( $options['price_widget_options'] ) ) {
			array_replace( $widget_settings, $options['price_widget_options'] );
		}
		return $widget_settings;
	}

	/**
	 * Add powerup panel to a post
	 *
	 * @param string $content post content.
	 * @param object $qb_post qbeats post.
	 * @param bool   $single_post_page show if page contains.
	 *
	 * @return string resulting content for the given post.
	 */
	private function append_buy_button( $content, $qb_post, $single_post_page ) {
		global $qb_auth;
		$post_url = get_post_permalink( $qb_post->wp_post_id );
		$content .= "<qb-winks
				story-id=\"{$qb_post->story_id}\"
				callback-url=\"$post_url\"></qb-winks>";
		// Put subscribe panel only on single post pages.
		if ( $single_post_page ) {
			$content .= "<qb-subscribe
					story-id=\"{$qb_post->story_id}\"
					callback-url=\"$post_url\"></qb-subscribe>";
		}
		return $content;
	}

	/**
	 * Get publicly available content for the given post
	 *
	 * @param string $text content of the post.
	 * @param object $qb_post qbeats post.
	 *
	 * @return string excerpt of the content.
	 */
	private function available_content( $text, $qb_post ) {
		$the_excerpt = $qb_post->public_content;
		// Left for backwards compatibility.
		if ( empty( $the_excerpt ) ) {
			$excerpt_length = 30;
			$the_excerpt    = wp_strip_all_tags( strip_shortcodes( $text ) );
			$words          = explode( ' ', $the_excerpt, $excerpt_length + 1 );
			if ( count( $words ) > $excerpt_length ) {
				array_pop( $words );
				array_push( $words, '...' );
				$the_excerpt = implode( ' ', $words );
			}
			$the_excerpt = '<p>' . $the_excerpt . '</p>';
			$the_excerpt  = "<qb-protected-part uid=\"{$qb_post->story_id}\">" . $the_excerpt . '</qb-protected-part>';
		}
		return $the_excerpt;
	}
}
