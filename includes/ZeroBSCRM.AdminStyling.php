<?php
/*
 * Jetpack CRM
 * https://jetpackcrm.com
 * V1.20
 *
 * Copyright 2020 Automattic
 *
 * Date: 01/11/16
 */

/*
======================================================
	Breaking Checks ( stops direct access )
	====================================================== */
if ( ! defined( 'ZEROBSCRM_PATH' ) ) {
	exit( 0 );
}
/*
======================================================
	/ Breaking Checks
	====================================================== */

/*
======================================================
	Jetpack CRM Closers - WH - these would be better as transients IMO
	These allow you to "x" things in ZBS and persist the state
	====================================================== */

	// } Returns a timestamp or false, depending on if a "closer" has been logged
	// } see zeroBSCRM_AJAX_logClose
function zeroBSCRM_getCloseState( $key = '' ) {

	if ( ! empty( $key ) ) {
		return get_option( 'zbs_closers_' . $key, false );
	}

	return false;
}
	// } Removes close state
function zeroBSCRM_clearCloseState( $key = '' ) {

	if ( ! empty( $key ) ) {
		return delete_option( 'zbs_closers_' . $key );
	}

	return false;
}
/*
======================================================
	/ Jetpack CRM Closers
	====================================================== */

/*
======================================================
	WordPress Button/Text Overides (could end up in language inc?)
	====================================================== */

	// } WH10 http://wordpress.stackexchange.com/questions/15357/edit-the-post-updated-view-post-link
	// use: add_filter('post_updated_messages', 'zeroBSCRM_improvedPostMsgsBookings'); on init
function zeroBSCRM_improvedPostMsgsCustomers( $messages ) {

	$messages['post'] = array(
		0  => '', // Unused. Messages start at index 1.
		1  => sprintf(
			/* Translators: %s: link to the main Contacts page */
			__( 'Contact updated. <a href="%s">Back to Contacts</a>', 'zero-bs-crm' ),
			esc_url( 'edit.php?post_type=zerobs_customer&page=manage-customers' )
		),
		2  => __( 'Contact updated.', 'zero-bs-crm' ),
		3  => __( 'Contact field deleted.', 'zero-bs-crm' ),
		4  => __( 'Contact updated.', 'zero-bs-crm' ),
		/* translators: %s: date and time of the revision */
		5  => isset( $_GET['revision'] ) ? sprintf( __( 'Contact restored to revision from %s', 'zero-bs-crm' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		6  => sprintf(
			/* Translators: %s: link to the main Contacts page */
			__( 'Contact added. <a href="%s">Back to Contacts</a>', 'zero-bs-crm' ),
			esc_url( 'edit.php?post_type=zerobs_customer&page=manage-customers' )
		),
		7  => __( 'Contact saved.', 'zero-bs-crm' ),
		8  => sprintf(
			/* Translators: %s: link to the main Contacts page */
			__( 'Contact submitted. <a target="_blank" href="%s">Back to Contacts</a>', 'zero-bs-crm' ),
			esc_url( 'edit.php?post_type=zerobs_customer&page=manage-customers' )
		),
		9  => '',
		10 => sprintf(
			/* Translators: %s: link to the main Contacts page */
			__( 'Contact draft updated. <a target="_blank" href="%s">Back to Contacts</a>', 'zero-bs-crm' ),
			esc_url( 'edit.php?post_type=zerobs_customer&page=manage-customers' )
		),
	);

	return $messages;
}
function zeroBSCRM_improvedPostMsgsCompanies( $messages ) {

	$messages['post'] = array(
		0  => '', // Unused. Messages start at index 1.
		1  => sprintf( __( jpcrm_label_company() . ' updated. <a href="%s">Back to ' . jpcrm_label_company( true ) . '</a>', 'zero-bs-crm' ), esc_url( 'edit.php?post_type=zerobs_company&page=manage-companies' ) ), // get_permalink($post_ID) ) ),
		2  => __( jpcrm_label_company() . ' updated.', 'zero-bs-crm' ),
		3  => __( jpcrm_label_company() . ' field deleted.', 'zero-bs-crm' ),
		4  => __( jpcrm_label_company() . ' updated.', 'zero-bs-crm' ),
		/* translators: %s: date and time of the revision */
		5  => isset( $_GET['revision'] ) ? sprintf( __( jpcrm_label_company() . ' restored to revision from %s', 'zero-bs-crm' ), wp_post_revision_title( (int) sanitize_text_field( $_GET['revision'] ), false ) ) : false,
		6  => sprintf( __( jpcrm_label_company() . ' added. <a href="%s">Back to ' . jpcrm_label_company( true ) . '</a>', 'zero-bs-crm' ), esc_url( 'edit.php?post_type=zerobs_company&page=manage-companies' ) ), // get_permalink($post_ID) ) ),//esc_url( get_permalink($post_ID) ) ),
		7  => __( jpcrm_label_company() . ' saved.', 'zero-bs-crm' ),
		8  => sprintf( __( jpcrm_label_company() . ' submitted. <a target="_blank" href="%s">Back to ' . jpcrm_label_company( true ) . '</a>', 'zero-bs-crm' ), esc_url( 'edit.php?post_type=zerobs_company&page=manage-companies' ) ), // esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		9  => '',
		10 => sprintf( __( jpcrm_label_company() . ' draft updated. <a target="_blank" href="%s">Back to ' . jpcrm_label_company( true ) . '</a>', 'zero-bs-crm' ), esc_url( 'edit.php?post_type=zerobs_customer&page=manage-companies' ) ), // get_permalink($post_ID) ) ),//esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
	);

	return $messages;
}
function zeroBSCRM_improvedPostMsgsInvoices( $messages ) {

	$messages['post'] = array(
		0  => '', // Unused. Messages start at index 1.
		1  => sprintf( __( 'Invoice updated. <a href="%s">Back to Invoices</a>', 'zero-bs-crm' ), esc_url( 'edit.php?post_type=zerobs_invoice&page=manage-invoices' ) ), // get_permalink($post_ID) ) ),
		2  => __( 'Invoice updated.', 'zero-bs-crm' ),
		3  => __( 'Invoice field deleted.', 'zero-bs-crm' ),
		4  => __( 'Invoice updated.', 'zero-bs-crm' ),
		/* translators: %s: date and time of the revision */
		5  => isset( $_GET['revision'] ) ? sprintf( __( 'Invoice restored to revision from %s', 'zero-bs-crm' ), wp_post_revision_title( (int) sanitize_text_field( $_GET['revision'] ), false ) ) : false,
		6  => sprintf( __( 'Invoice added. <a href="%s">Back to Invoices</a>', 'zero-bs-crm' ), esc_url( 'edit.php?post_type=zerobs_invoice&page=manage-invoices' ) ), // get_permalink($post_ID) ) ),//esc_url( get_permalink($post_ID) ) ),
		7  => __( 'Invoice saved.', 'zero-bs-crm' ),
		8  => sprintf( __( 'Invoice submitted. <a target="_blank" href="%s">Back to Invoices</a>', 'zero-bs-crm' ), esc_url( 'edit.php?post_type=zerobs_invoice&page=manage-invoices' ) ), // esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		9  => '',
		10 => sprintf( __( 'Invoice draft updated. <a target="_blank" href="%s">Back to Invoices</a>', 'zero-bs-crm' ), esc_url( 'edit.php?post_type=zerobs_invoice&page=manage-invoices' ) ), // get_permalink($post_ID) ) ),//esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
	);

	return $messages;
}
function zeroBSCRM_improvedPostMsgsQuotes( $messages ) {

	$messages['post'] = array(
		0  => '', // Unused. Messages start at index 1.
		1  => sprintf( __( 'Quote updated. <a href="%s">Back to Quotes</a>', 'zero-bs-crm' ), esc_url( 'edit.php?post_type=zerobs_quote&page=manage-quotes' ) ), // get_permalink($post_ID) ) ),
		2  => __( 'Quote updated.', 'zero-bs-crm' ),
		3  => __( 'Quote field deleted.', 'zero-bs-crm' ),
		4  => __( 'Quote updated.', 'zero-bs-crm' ),
		/* translators: %s: date and time of the revision */
		5  => isset( $_GET['revision'] ) ? sprintf( __( 'Quote restored to revision from %s', 'zero-bs-crm' ), wp_post_revision_title( (int) sanitize_text_field( $_GET['revision'] ), false ) ) : false,
		6  => sprintf( __( 'Quote added. <a href="%s">Back to Quotes</a>', 'zero-bs-crm' ), esc_url( 'edit.php?post_type=zerobs_quote&page=manage-quotes' ) ), // get_permalink($post_ID) ) ),//esc_url( get_permalink($post_ID) ) ),
		7  => __( 'Quote saved.', 'zero-bs-crm' ),
		8  => sprintf( __( 'Quote submitted. <a target="_blank" href="%s">Back to Quotes</a>', 'zero-bs-crm' ), esc_url( 'edit.php?post_type=zerobs_quote&page=manage-quotes' ) ), // esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		9  => '',
		10 => sprintf( __( 'Quote draft updated. <a target="_blank" href="%s">Back to Quotes</a>', 'zero-bs-crm' ), esc_url( 'edit.php?post_type=zerobs_quote&page=manage-quotes' ) ), // get_permalink($post_ID) ) ),//esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
	);

	return $messages;
}
function zeroBSCRM_improvedPostMsgsTransactions( $messages ) {

	global $zbs;

	$messages['post'] = array(
		0  => '', // Unused. Messages start at index 1.
		1  => sprintf( __( 'Transaction updated. <a href="%s">Back to Transactions</a>', 'zero-bs-crm' ), esc_url( 'admin.php?page=' . $zbs->slugs['managetransactions'] ) ), // get_permalink($post_ID) ) ),
		2  => __( 'Transaction updated.', 'zero-bs-crm' ),
		3  => __( 'Transaction field deleted.', 'zero-bs-crm' ),
		4  => __( 'Transaction updated.', 'zero-bs-crm' ),
		/* translators: %s: date and time of the revision */
		5  => isset( $_GET['revision'] ) ? sprintf( __( 'Transaction restored to revision from %s', 'zero-bs-crm' ), wp_post_revision_title( (int) sanitize_text_field( $_GET['revision'] ), false ) ) : false,
		6  => sprintf( __( 'Transaction added. <a href="%s">Back to Transactions</a>', 'zero-bs-crm' ), esc_url( 'admin.php?&page=' . $zbs->slugs['managetransactions'] ) ), // get_permalink($post_ID) ) ),//esc_url( get_permalink($post_ID) ) ),
		7  => __( 'Transaction saved.', 'zero-bs-crm' ),
		8  => sprintf( __( 'Transaction submitted. <a target="_blank" href="%s">Back to Transactions</a>', 'zero-bs-crm' ), esc_url( 'edit.php?post_type=zerobs_transaction' ) ), // esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		9  => '',
		10 => sprintf( __( 'Transaction draft updated. <a target="_blank" href="%s">Back to Transactions</a>', 'zero-bs-crm' ), esc_url( 'edit.php?post_type=zerobs_transaction' ) ), // get_permalink($post_ID) ) ),//esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
	);

	return $messages;
}

/*
======================================================
	/ WordPress Button Overides
	====================================================== */

/*
======================================================
	WordPress Footer Msg
	====================================================== */

// } Footer Text (left) - Jetpack logo + wordmark, matching the unified Jetpack admin footer
function jpcrm_footer_credit_thanks( $content ) {

	// return original text if not on a CRM page
	if ( ! zeroBSCRM_isAdminPage() ) {
		return $content;
	}

	##WLREMOVE
	global $zbs;
	$showpoweredby_admin = $zbs->settings->get( 'showpoweredby_admin' ) === 1 ? true : false;
	if ( $showpoweredby_admin ) {
		$jetpack_logo = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="16" height="16" aria-hidden="true" focusable="false"><path fill="#069e08" d="M16,0C7.2,0,0,7.2,0,16s7.2,16,16,16s16-7.2,16-16S24.8,0,16,0z M15,19H7l8-16V19z M17,29V13h8L17,29z"/></svg>';
		return '<span class="jetpack-footer__logo">' . $jetpack_logo . '<span class="jetpack-footer__logo-text">Jetpack</span></span>';
	}
	##/WLREMOVE

	// return blank if disabled or white label
	return '';
}
add_filter( 'admin_footer_text', 'jpcrm_footer_credit_thanks' );

// } Footer Text (right) - Automattic "by line" logo, matching the unified Jetpack admin footer
function jpcrm_footer_credit_version( $content ) {

	// return original text if not on a CRM page
	if ( ! zeroBSCRM_isAdminPage() ) {
		return $content;
	}

	##WLREMOVE
	global $zbs;
	$showpoweredby_admin = $zbs->settings->get( 'showpoweredby_admin' ) === 1 ? true : false;
	if ( $showpoweredby_admin ) {
		$byline_title = esc_attr__( 'An Automattic Airline', 'zero-bs-crm' );
		$byline_svg   = '<svg role="img" x="0" y="0" viewBox="0 0 935 38.2" height="8" aria-hidden="true" focusable="false" class="jp-automattic-byline-logo"><path d="M317.1 38.2c-12.6 0-20.7-9.1-20.7-18.5v-1.2c0-9.6 8.2-18.5 20.7-18.5 12.6 0 20.8 8.9 20.8 18.5v1.2C337.9 29.1 329.7 38.2 317.1 38.2zM331.2 18.6c0-6.9-5-13-14.1-13s-14 6.1-14 13v0.9c0 6.9 5 13.1 14 13.1s14.1-6.2 14.1-13.1V18.6zM175 36.8l-4.7-8.8h-20.9l-4.5 8.8h-7L157 1.3h5.5L182 36.8H175zM159.7 8.2L152 23.1h15.7L159.7 8.2zM212.4 38.2c-12.7 0-18.7-6.9-18.7-16.2V1.3h6.6v20.9c0 6.6 4.3 10.5 12.5 10.5 8.4 0 11.9-3.9 11.9-10.5V1.3h6.7V22C231.4 30.8 225.8 38.2 212.4 38.2zM268.6 6.8v30h-6.7v-30h-15.5V1.3h37.7v5.5H268.6zM397.3 36.8V8.7l-1.8 3.1 -14.9 25h-3.3l-14.7-25 -1.8-3.1v28.1h-6.5V1.3h9.2l14 24.4 1.7 3 1.7-3 13.9-24.4h9.1v35.5H397.3zM454.4 36.8l-4.7-8.8h-20.9l-4.5 8.8h-7l19.2-35.5h5.5l19.5 35.5H454.4zM439.1 8.2l-7.7 14.9h15.7L439.1 8.2zM488.4 6.8v30h-6.7v-30h-15.5V1.3h37.7v5.5H488.4zM537.3 6.8v30h-6.7v-30h-15.5V1.3h37.7v5.5H537.3zM569.3 36.8V4.6c2.7 0 3.7-1.4 3.7-3.4h2.8v35.5L569.3 36.8 569.3 36.8zM628 11.3c-3.2-2.9-7.9-5.7-14.2-5.7 -9.5 0-14.8 6.5-14.8 13.3v0.7c0 6.7 5.4 13 15.3 13 5.9 0 10.8-2.8 13.9-5.7l4 4.2c-3.9 3.8-10.5 7.1-18.3 7.1 -13.4 0-21.6-8.7-21.6-18.3v-1.2c0-9.6 8.9-18.7 21.9-18.7 7.5 0 14.3 3.1 18 7.1L628 11.3zM321.5 12.4c1.2 0.8 1.5 2.4 0.8 3.6l-6.1 9.4c-0.8 1.2-2.4 1.6-3.6 0.8l0 0c-1.2-0.8-1.5-2.4-0.8-3.6l6.1-9.4C318.7 11.9 320.3 11.6 321.5 12.4L321.5 12.4z"/><path d="M37.5 36.7l-4.7-8.9H11.7l-4.6 8.9H0L19.4 0.8H25l19.7 35.9H37.5zM22 7.8l-7.8 15.1h15.9L22 7.8zM82.8 36.7l-23.3-24 -2.3-2.5v26.6h-6.7v-36H57l22.6 24 2.3 2.6V0.8h6.7v35.9H82.8z"/><path d="M719.9 37l-4.8-8.9H694l-4.6 8.9h-7.1l19.5-36h5.6l19.8 36H719.9zM704.4 8l-7.8 15.1h15.9L704.4 8zM733 37V1h6.8v36H733zM781 37c-1.8 0-2.6-2.5-2.9-5.8l-0.2-3.7c-0.2-3.6-1.7-5.1-8.4-5.1h-12.8V37H750V1h19.6c10.8 0 15.7 4.3 15.7 9.9 0 3.9-2 7.7-9 9 7 0.5 8.5 3.7 8.6 7.9l0.1 3c0.1 2.5 0.5 4.3 2.2 6.1V37H781zM778.5 11.8c0-2.6-2.1-5.1-7.9-5.1h-13.8v10.8h14.4c5 0 7.3-2.4 7.3-5.2V11.8zM794.8 37V1h6.8v30.4h28.2V37H794.8zM836.7 37V1h6.8v36H836.7zM886.2 37l-23.4-24.1 -2.3-2.5V37h-6.8V1h6.5l22.7 24.1 2.3 2.6V1h6.8v36H886.2zM902.3 37V1H935v5.6h-26v9.2h20v5.5h-20v10.1h26V37H902.3z"/></svg>';
		return '<a class="jetpack-footer__a8c" href="https://jetpack.com/redirect/?source=a8c-about" target="_blank" rel="noopener noreferrer" aria-label="' . $byline_title . '">' . $byline_svg . '</a>';
	}
	##/WLREMOVE

	// return blank if disabled or white label
	return '';
}
add_filter( 'update_footer', 'jpcrm_footer_credit_version', 11 );

/*
======================================================
	/ WordPress Footer Msg
	====================================================== */

/*
======================================================
	Color Grabber Admin Colour Schemes
	====================================================== */

// } Admin Colour Schemes
add_action( 'admin_head', 'zbs_color_grabber' );
function zbs_color_grabber() {
	// } Information here to get the colors
	global $_wp_admin_css_colors, $zbsadmincolors;
	$current_color = get_user_option( 'admin_color' );
	echo '<script type="text/javascript">var zbsJS_admcolours = ' . wp_json_encode( $_wp_admin_css_colors[ $current_color ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP ) . ';</script>';
	echo '<script type="text/javascript">var zbsJS_unpaid = "' . esc_html__( 'unpaid', 'zero-bs-crm' ) . '";</script>';
	$zbsadmincolors = $_wp_admin_css_colors[ $current_color ]->colors;
	?>
	<style>
		.max_this{
			color: <?php echo esc_html( $zbsadmincolors[0] ); ?> !important;
		}
		.zerobs_customer{
			background-color: <?php echo esc_html( $zbsadmincolors[0] ); ?> !important;
		}
		.users-php .zerobs_customer {
				background: none !important; 
			}
		.zerobs_transaction{
			background-color: <?php echo esc_html( $zbsadmincolors[2] ); ?> !important;
		}
		.zerobs_invoice{
			background-color: <?php echo esc_html( $zbsadmincolors[1] ); ?> !important;
		}
		.zerobs_quote{
			background-color: <?php echo esc_html( $zbsadmincolors[3] ?? $zbsadmincolors[2] ); ?> !important;
		}
		.graph-box .view-me, .rev{
			color: <?php echo esc_html( $zbsadmincolors[0] ); ?> !important;
		}
		.toplevel_page_sales-dash .sales-graph-wrappers .area, .sales-dashboard_page_gross-revenue .sales-graph-wrappers .area, .sales-dashboard_page_net-revenue .sales-graph-wrappers .area, .sales-dashboard_page_discounts .sales-graph-wrappers .area, .sales-dashboard_page_fees .sales-graph-wrappers .area, .sales-dashboard_page_average-rev .sales-graph-wrappers .area, .sales-dashboard_page_new-customers .sales-graph-wrappers .area, .sales-dashboard_page_total-customers .sales-graph-wrappers .area{
			fill: <?php echo esc_html( $zbsadmincolors[0] ); ?> !important;
		}
		.bar{
			fill: <?php echo esc_html( $zbsadmincolors[0] ); ?> !important;
		}
	</style>
	<?php
}

/*
======================================================
	/ Color Grabber Admin Colour Schemes
	====================================================== */

/*
======================================================
	WP Override specifically
	====================================================== */

function zeroBSCRM_stopFrontEnd() {

	// } Harsh redir!
	global $zbs;

	if ( ! zeroBSCRM_isAPIRequest() && ! zeroBSCRM_isClientPortalPage() ) {

		if ( is_user_logged_in() ) {
			// } No need here :)
			header( 'Location: ' . admin_url( 'admin.php?page=' . $zbs->slugs['managecontacts'] ) );
			exit( 0 );

		} else {
			// } No need here :)
			header( 'Location: ' . wp_login_url() );
			exit( 0 );
		}
	}
}

function zeroBSCRM_catchDashboard() {

		// } Only if not API / Client portal
	if ( ! zeroBSCRM_isAPIRequest() && ! zeroBSCRM_isClientPortalPage() ) {

		// } Admin side, zbs users

		// this is quick hack code and doesn't work!

		if ( is_admin() && zeroBSCRM_permsIsZBSUser() ) {

			// } Doesnt work:
			// require_once(ABSPATH . 'wp-admin/includes/screen.php');
			// $screen = get_current_screen();

			// } Does:
			global $pagenow, $zbs;

			if ( $pagenow == 'profile.php' || $pagenow == 'index.php' ) {// $screen->base == 'dashboard' ) {

				// } Customers quotes or invs?
				// this forwards non-wp users :) from dash/profile to their corresponding page
				if ( ! zeroBSCRM_permsWPEditPosts() ) {

						$sent = false;
					if ( zeroBSCRM_permsCustomers() ) {
						wp_redirect( jpcrm_esc_link( $zbs->slugs['managecontacts'] ) );
						$sent = 1;
					}
					if ( ! $sent && zeroBSCRM_permsQuotes() ) {
						wp_redirect( jpcrm_esc_link( $zbs->slugs['managequotes'] ) );
						$sent = 1;
					}
					if ( ! $sent && zeroBSCRM_permsInvoices() ) {
						wp_redirect( jpcrm_esc_link( $zbs->slugs['manageinvoices'] ) );
						$sent = 1;
					}
					if ( ! $sent && zeroBSCRM_permsTransactions() ) {
						wp_redirect( jpcrm_esc_link( $zbs->slugs['managetransactions'] ) );
						$sent = 1;
					}
					if ( ! $sent ) {

						// ?
						wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'zero-bs-crm' ) );
					}
				}
			}
		}
	} // / if API request / portal
}

function zeroBSCRM_CustomisedLogin_Header() {

	$loginLogo = zeroBSCRM_getSetting( 'loginlogourl' );
	if ( ! empty( $loginLogo ) ) {
		?>
		<style type="text/css">

			.login h1 a {

				background-image: url( <?php echo esc_html( $loginLogo ); ?> );
				background-size: contain;
				width: auto;
				max-width: 90%;

			}

		</style>
		<?php
	}
}
add_action( 'login_head', 'zeroBSCRM_CustomisedLogin_Header' );

// changes wordpress.org to site.com :)
function zeroBSCRM_CustomisedLogin_logo_url() {

	return site_url();
}
add_filter( 'login_headerurl', 'zeroBSCRM_CustomisedLogin_logo_url' );

// changes the title :) (Powered by WordPress) to site title :)
function zeroBSCRM_CustomisedLogin_logo_url_title() {

	return get_bloginfo( 'name' );
}
add_filter( 'login_headertext', 'zeroBSCRM_CustomisedLogin_logo_url_title' );

##WLREMOVE
// add powered by Jetpack CRM to WP login footer if "override all" mode and public credits are enabled
function jpcrm_wplogin_footer() {

	global $zbs;
	$wptakeovermodeforall = $zbs->settings->get( 'wptakeovermodeforall' ) === 1 ? true : false;
	$showpoweredby_public = $zbs->settings->get( 'showpoweredby_public' ) === 1 ? true : false;

	if ( $wptakeovermodeforall && $showpoweredby_public ) {
		echo '<div style="text-align:center;margin-top:1em;font-size:12px"><a href="' . esc_url( $zbs->urls['home'] ) . '" title="' . esc_attr__( 'Powered by Jetpack CRM', 'zero-bs-crm' ) . '" target="_blank">' . esc_html__( 'Powered by Jetpack CRM', 'zero-bs-crm' ) . '</a></div>';
	}
}
add_action( 'login_footer', 'jpcrm_wplogin_footer' );
##/WLREMOVE

// } For (if shown mobile) - restrict things shown
add_action( 'admin_bar_menu', 'remove_wp_items', 100 );
function remove_wp_items( $wp_admin_bar ) {

	global $zbs;

	// } Retrieve setting
	$customheadertext = $zbs->settings->get( 'customheadertext' );

	// } Only for zbs custom user role users or all if flagged
	$takeoverModeAll = $zbs->settings->get( 'wptakeovermodeforall' );
	$takeoverModeZBS = $zbs->settings->get( 'wptakeovermode' );

	$takeoverMode = false;

	if ( $takeoverModeAll || ( zeroBSCRM_permsIsZBSUser() && $takeoverModeZBS ) ) {
		$takeoverMode = true;
	}

	if ( $takeoverMode ) {

		$wp_admin_bar->remove_menu( 'wp-logo' );
		$wp_admin_bar->remove_menu( 'site-name' );
		$wp_admin_bar->remove_menu( 'comments' );
		$wp_admin_bar->remove_menu( 'new-content' );
		$wp_admin_bar->remove_menu( 'my-account' );
		// $wp_admin_bar->remove_menu('top-secondary');

		if ( ! empty( $customheadertext ) ) {

			// https://codex.wordpress.org/Class_Reference/WP_Admin_Bar/add_menu
			// https://codex.wordpress.org/Function_Reference/add_node
			$wp_admin_bar->add_node(
				array(

					'id'    => 'zbshead',
					'title' => '<div class="wp-menu-image dashicons-before dashicons-groups" style="display: inline-block;margin-right: 6px;"><br></div>' . $customheadertext,
					'href'  => zeroBSCRM_getAdminURL( $zbs->slugs['dash'] ),
					'meta'  => array(
						// 'class' => 'wp-menu-image dashicons-before dashicons-groups'
					),

				)
			);

		}
	}
}

/*
======================================================
	/ WP Override specifically
	====================================================== */

/*
======================================================
	Thumbnails
	====================================================== */

// } Can you even do this via plugin?
function zeroBSCRM_addThemeThumbnails() {

	if ( function_exists( 'add_theme_support' ) ) {
		add_theme_support( 'post-thumbnails' );
	}
}

/*
======================================================
	/ Thumbnails
	====================================================== */
