<?php
/**
 * Module of qbeats story.
 *
 * @package qbeats for WordPress
 *
 * @property $wp_post_id
 * @property $story_id
 * @property $draft_id
 * @property $qb_publish_enabled
 * @property $allow_print
 * @property $allow_copy
 * @property $publish_error
 * @property $pricing_enabled
 */

/**
 * Functions for managing qbeats story properties as metadata of WP post
 */
class QB_Story {
	/**
	 * Id of WP post corresponding to story
	 *
	 * @var string
	 */
	protected $wp_post_id;
	/**
	 * Id of story in qbeats
	 *
	 * @var string
	 */
	protected $story_id;
	/**
	 * Id of draft in qbeats
	 *
	 * @var string
	 */
	protected $draft_id;
	/**
	 * Enabled publish with qbeats
	 *
	 * @var bool
	 */
	protected $qb_publish_enabled;
	/**
	 * Allow printing
	 *
	 * @var bool
	 */
	protected $allow_print;
	/**
	 * Allow copying
	 *
	 * @var bool
	 */
	protected $allow_copy;
	/**
	 * Publishing error
	 *
	 * @var int
	 */
	protected $publish_error;
	/**
	 * Enabled pricing. Obsolete parameter is used to support old drafts and stories
	 *
	 * @var bool
	 */
	protected $pricing_enabled;
	/**
	 * Public part of the content returned from qbeats
	 *
	 * @var string
	 */
	protected $public_content;

	/**
	 * Construct and initialize qbeats story
	 *
	 * @param string $wp_post_id id of WP post.
	 */
	public function __construct( $wp_post_id ) {
		$this->wp_post_id = $wp_post_id;
	}

	/**
	 * Get post id by the given story id
	 *
	 * @param string $story_id id of qbeats story.
	 *
	 * @return string wp post id.
	 */
	public static function get_post_id_by_story_id( $story_id ) {
		$args = array(
			'meta_key' => 'qb_story_id',
			'meta_value' => $story_id,
			'posts_per_page' => 1,
		);
		$posts = get_posts( $args );
		if ( 0 === count( $posts ) ) {
			$post_id = null;
		} else {
			$post = $posts[0];
			$post_id = $post->ID;
		}
		return $post_id;
	}

	/**
	 * Get instance of qbeats story by qbeats story id
	 *
	 * @param string $story_id id of qbeats story.
	 *
	 * @return object instance of WP post.
	 */
	public static function from_story_id( $story_id ) {
		$post_id = QB_Story::get_post_id_by_story_id( $story_id );
		if ( strlen( $post_id ) === 0 ) {
			$instance = null;
		} else {
			$instance = new self( $post_id );
		}
		return $instance;
	}

	/**
	 * Check if story is published
	 *
	 * @return bool true if story is published, else false.
	 */
	public function is_published() {
		return strlen( $this->__get( 'story_id' ) ) > 0;
	}

	/**
	 * Check if story is protected by qbeats (published to qbeats and pricing enabled)
	 *
	 * @return bool true if story is protected by qbeats, else false.
	 */
	public function is_protected_by_qbeats() {
		return $this->is_published() && ( $this->__get( 'qb_publish_enabled' )  ||  $this->__get( 'pricing_enabled' ) );
	}

	/**
	 * Check if publishing story to qbeats is enabled
	 *
	 * @return bool true publishing story to qbeats is enabled, else false.
	 */
	public function is_publishing_enabled() {
		return $this->__get( 'qb_publish_enabled' ) || $this->is_published() && $this->__get( 'pricing_enabled' );
	}

	/**
	 * Getter of qbeats story property
	 *
	 * @param string $property name of property field.
	 *
	 * @return string value of the given property.
	 */
	public function __get( $property ) {
		if ( 'wp_post_id' === $property ) {
			return $this->wp_post_id;
		}
		if ( property_exists( $this, $property ) ) {
			$this->$property = get_post_meta( $this->wp_post_id, 'qb_' . $property, true );
		}

		return $this->$property ? $this->$property : null;
	}

	/**
	 * Setter of qbeats story property
	 *
	 * @param string $property name of property field.
	 * @param string $value value of property field.
	 *
	 * @return object qbeats story instance.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			$this->$property = $value;
			$this->save_post_meta( $this->wp_post_id, 'qb_' . $property, $value );
		}

		return $this;
	}

	/**
	 * Save value of the given meta field
	 *
	 * @param string $post_id id of WP post.
	 * @param string $meta_key name of meta field.
	 * @param string $new_meta_value value of meta field.
	 */
	private function save_post_meta( $post_id, $meta_key, $new_meta_value ) {
		$meta_value = get_post_meta( $post_id, $meta_key, true );
		if ( ( '' === $new_meta_value || ! isset( $new_meta_value ) )  && '' !== $meta_value ) {
			delete_post_meta( $post_id, $meta_key, $meta_value );
		} else {
			update_post_meta( $post_id, $meta_key, $new_meta_value );
		}
	}
}

/**
 * Wrapper for constants of qbeats publishing error
 */
class QBStoryError {
	/**
	 * Empty title error constant
	 *
	 * @var string
	 */
	static $empty_title = 'empty_title';
	/**
	 * Empty content error constant
	 *
	 * @var string
	 */
	static $empty_content = 'empty_content';
}
