<?php
defined( 'ZEROBSCRM_PATH' ) || exit( 0 );

/**
 *
 * The JPCRM_FeatureSniffer class lets core detect installed
 * plugins for which we already have integrations
 */
class JPCRM_FeatureSniffer {

	/**
	 * An array of plugins installed.
	 *
	 * @var array
	 */
	public $all_plugins = array();

	public function __construct() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$this->all_plugins = get_plugins();
	}

	/**
	 *
	 * Checks if there is an unused CRM integration
	 * with installed plugins
	 *
	 * $args may look something like this:
	 *  array(
	 *    'feature_slug'    => 'feature_slug',
	 *    'plugin_slug'     => 'plugin.php',
	 *    'more_info_link'  => 'https://kb.jetpackcrm.com/some_link_to_docs'
	 *  )
	 *
	 * @param array $args params passed to check, e.g.
	 * @param bool  $is_silent Unused. Kept for callers that still pass it.
	 *
	 * @return bool
	 */
	public function sniff_for_plugin( $args = array(), $is_silent = false ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Kept for backward compatibility.

		if (
			// bad params
			empty( $args )
			|| ! isset( $args['feature_slug'] )
			|| ! isset( $args['plugin_slug'] )
			|| ! isset( $args['more_info_link'] )
			// target plugin isn't active
			|| ! is_plugin_active( $args['plugin_slug'] )
			// feature is already enabled
			|| zeroBSCRM_isExtensionInstalled( $args['feature_slug'] )
		) {
			return false;
		}

		return true;
	}
}
