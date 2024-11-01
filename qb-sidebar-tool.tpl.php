<?php
/**
 * Sidebar tool template module
 *
 * @package qbeats for WordPress
 */

// These global variables will be used below.
global $post, $qb_auth;
$story = new QB_Story( $post->ID );
$token = get_option( $qb_auth::AUTH_TOKEN_OPTION_NAME );
?>
<script type="text/javascript" class="temporary">
	SIDEBAR_DATA = {
		story_id: <?php if ( $story->story_id ) { echo "'" . sanitize_text_field( $story->story_id ) . "'"; // WPCS: XSS OK.
} else { echo 'null';}?>,
		draft_id: <?php if ( $story->draft_id ) { echo "'" . sanitize_text_field( $story->draft_id ) . "'"; // WPCS: XSS OK.
} else { echo 'null';}?>,
		token: <?php if ( $token ) { echo "'" . sanitize_text_field( $token ) . "'"; // WPCS: XSS OK.
} else { echo 'null';}?>,
		permalink: <?php echo "'" . get_post_permalink( $post->ID ) . "'"; // WPCS: XSS OK.
			?>
	};
</script>

<?php
global $qb_auth;
if ( ! $qb_auth->is_plugin_authorized() ) {
	$link = '/wp-admin/admin.php?page=' . plugin_basename( __DIR__ ) . '/settings.tpl.php';
	?>
	<h3>Oops! Looks like you still need to connect your qbeats account.</h3>
	<h2 class="qb-connect-button"><a href="<?php echo esc_attr( $link ); ?>">CONNECT QBEATS</a></h2>
<?php
} else {
	?>
	<input type="hidden" name="qb-draft-id" id="qb-draft-id" value="<?php echo esc_js( $story->draft_id ); ?>"/>
	<input type="hidden" name="qb-story-id" id="qb-story-id" value="<?php echo esc_js( $story->story_id ); ?>"/>
	<input type="hidden" name="qb-public-content" id="qb-public-content" value="<?php echo esc_js( $story->public_content ); ?>"/>
	<input type="checkbox"
		   class="qb-js-publish-with-qbeats-enabled qb-content-padding"
		   name="qb-publish-with-qbeats-enabled" <?php if ( $story->is_publishing_enabled() ) { echo 'checked'; } ?>
		/>
	<label for="qb-publish-with-qbeats-enabled">Publish with qbeats</label>
	<div class="qb-js-sidebar-panel">
		<qp-sidebar-panel qp-sidebar-ready="onSidebarLoaded"></qp-sidebar-panel>
	</div>
<?php
}
?>
