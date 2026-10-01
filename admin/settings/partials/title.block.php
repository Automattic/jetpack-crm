<?php
/*
 * Admin Page Partial: Settings: Title Block
 */

// stop direct access
if ( ! defined( 'ZEROBSCRM_PATH' ) ) {
	exit( 0 );
}

?>
<h2 class="jpcrm-settings__title"><?php echo esc_html( $title ); ?></h2>
<?php
// Optional notice under the title, e.g. which module a page belongs to.
if ( is_array( $settings_rightfloated_notice ) ) {
	?>
	<p class="jpcrm-settings__title-notice"><?php echo wp_kses_post( $settings_rightfloated_notice['body'] ); ?></p>
	<?php
}
