<?php
/*
 * Admin Page Partial: Settings: Menu Block
 * This outputs the left hand menu for settings pages
 */

// phpcs:disable Squiz.Commenting.InlineComment.WrongStyle -- The ##WLREMOVE markers below are read by the white-label build.

// stop direct access
if ( ! defined( 'ZEROBSCRM_PATH' ) ) {
	exit( 0 );
}

	global $zbs;

	// } Default
	$tabs    = array( 'settings' => __( 'General', 'zero-bs-crm' ) );
	$tabsNew = array(); // slugs included in here will get a new flag

	// } Get Settings
	$settings = $zbs->settings->getAll();

	// } Add hard-typed:
	$tabs['bizinfo']      = __( 'Business Info', 'zero-bs-crm' );
	$tabs['customfields'] = __( 'Custom Fields', 'zero-bs-crm' );
	$tabs['fieldsorts']   = __( 'Field Sorts', 'zero-bs-crm' );
	$tabs['fieldoptions'] = __( 'Field Options', 'zero-bs-crm' );
	$tabs['locale']       = __( 'Locale', 'zero-bs-crm' );
	$tabs['listview']     = __( 'List View', 'zero-bs-crm' );
	$tabs['tax']          = __( 'Tax', 'zero-bs-crm' );
	$tabs['license']      = __( 'CRM License', 'zero-bs-crm' );

if ( $settings['companylevelcustomers'] == 1 ) {
	$tabs['companies'] = __( 'Companies', 'zero-bs-crm' );
}

	// } Load them from proper list :)
	global $zeroBSCRM_extensionsInstalledList;

	// } This will cycle through "installed" extensions and display them as tabs, using their custom funcs to get names, and falling back to a capitalised version of their perma
if ( isset( $zeroBSCRM_extensionsInstalledList ) && is_array( $zeroBSCRM_extensionsInstalledList ) ) {
	foreach ( $zeroBSCRM_extensionsInstalledList as $installedExt ) {

		// } Ignore pages for a min
		global $zbsExtensionsExcludeFromSettings;

		if ( ! in_array( $installedExt, $zbsExtensionsExcludeFromSettings ) ) {

			// } Got name func?
			if ( function_exists( 'zeroBSCRM_extension_name_' . $installedExt ) ) {

				// } Fire it to generate name :)
				$extNameFunc = 'zeroBSCRM_extension_name_' . $installedExt;

				// additional check, that there's actually a settings func to run :)
				if ( function_exists( 'zeroBSCRM_extensionhtml_settings_' . $installedExt ) ) {

					$tabs[ $installedExt ] = call_user_func( $extNameFunc );

				}
			} else {

					// } Fallback to capitalised ver of perm
					// Don't even show, as of 10/1/19
					// if func doesn't exist, screw it
					// ... came ultimately to check for the page setting:
				if ( function_exists( 'zeroBSCRM_extensionhtml_settings_' . $installedExt ) ) {

					$tabs[ $installedExt ] = ucwords( $installedExt );

				}
			}
		}
	}
}

	// Optional:
if ( $settings['feat_transactions'] == 1 ) {
	$tabs['transactions'] = __( 'Transactions', 'zero-bs-crm' );
}
if ( $settings['feat_forms'] == 1 ) {
	$tabs['forms'] = __( 'Forms', 'zero-bs-crm' );
}
if ( $settings['feat_portal'] == 1 ) {
	$tabs['clients'] = __( 'Client Portal', 'zero-bs-crm' );
}
if ( $settings['feat_api'] == 1 ) {
	$tabs['api'] = 'API';
}

	// Base hard-typed:
	$tabs['mail']          = __( 'Mail', 'zero-bs-crm' );
	$tabs['maildelivery']  = __( 'Mail Delivery', 'zero-bs-crm' );
	$tabs['mailtemplates'] = __( 'Mail Templates', 'zero-bs-crm' );
	$tabs['oauth']         = __( 'OAuth Connection', 'zero-bs-crm' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

	// make these filterable for the extensions..
	$tabs = apply_filters( 'zbs_settings_tabs', $tabs );

	// hacky rewrite to add submenu under general/mail, needs genericifying so can use submenus throughout
	$sortedTabs   = array();
	$underGeneral = array( 'customfields', 'fieldsorts', 'fieldoptions', 'locale', 'listview', 'bizinfo', 'tax', 'license' );
	$underMail    = array( 'maildelivery', 'mailtemplates', 'mailcampaigns' );

	// WH this shows $tabs has ones which we excluded above. Possibly due to load order timings?
	global $zbsExtensionsExcludeFromSettings;

	$tabs = apply_filters( 'zbs_settings_tabs', $tabs );

foreach ( $tabs as $tab => $name ) {

	// double check as the above zbs_write_log of $tabs outputs
	/*
	[23-Aug-2018 23:43:05 UTC] Array
	(
		[settings] => General
		[bizinfo] => Business Info
		[customfields] => Custom Fields
		[transactions] => Transactions
		[fieldsorts] => Field Sorts
		[listview] => List View
		[forms] => Front-end Forms
		[clients] => Client Portal
		[api] => API
		[quotebuilder] => Quote Builder
		[invbuilder] => Invoice Builder
		[systememailspro] => System Emails Pro
		[mail] => Mail
		[maildelivery] => Mail Delivery
		[mailtemplates] => Mail Templates
		[bulktag] => Bulk Tagger
	)
	*/

	if ( in_array( $tab, $zbsExtensionsExcludeFromSettings ) ) {
		continue;
	}

	if ( is_array( $name ) && isset( $name['submenu'] ) ) {
		$sortedTabs[ $tab ] = $name;
		continue;
	}

	if ( ! isset( $sortedTabs[ $tab ] ) ) {
		$sortedTabs[ $tab ] = array();
	}
	$sortedTabs[ $tab ]['name'] = $name;
	$sortedTabs[ $tab ]['ico']  = ''; // nothing yet

	if ( in_array( $tab, $underGeneral ) ) {
		if ( ! isset( $sortedTabs['settings'] ) ) {
			$sortedTabs['settings'] = array();
		}
		if ( ! isset( $sortedTabs['settings']['submenu'] ) ) {
			$sortedTabs['settings']['submenu'] = array();
		}
		$sortedTabs['settings']['submenu'][ $tab ] = array(
			'name' => $name,
			'ico'  => '',
		);

		// unset this - hacky
		unset( $sortedTabs[ $tab ] );
	}

	if ( in_array( $tab, $underMail ) ) {
		if ( ! isset( $sortedTabs['mail'] ) ) {
			$sortedTabs['mail'] = array();
		}
		if ( ! isset( $sortedTabs['mail']['submenu'] ) ) {
			$sortedTabs['mail']['submenu'] = array();
		}
		$sortedTabs['mail']['submenu'][ $tab ] = array(
			'name' => $name,
			'ico'  => '',
		);

		// unset this - hacky
		unset( $sortedTabs[ $tab ] );
	}
}

?>
<?php
// Every page the menu links to, flattened, so the phone-width jump menu and the
// list below are built from the same entries.
$jpcrm_settings_url = function ( $tab ) use ( $zbs ) {
	// Mail templates live on their own admin page.
	if ( $tab === 'mailtemplates' ) {
		return admin_url( 'admin.php?page=' . $zbs->slugs['email-templates'] );
	}
	return admin_url( 'admin.php?page=' . $zbs->slugs['settings'] . '&tab=' . $tab );
};
?>
<div class="jpcrm-settings-menu" id="zbs-settings-menu">
	<h2 class="jpcrm-settings-menu__title" id="zbs-settings-head-tour"><?php esc_html_e( 'CRM Settings', 'zero-bs-crm' ); ?></h2>

	<label class="screen-reader-text" for="jpcrm-settings-jump"><?php esc_html_e( 'Settings page', 'zero-bs-crm' ); ?></label>
	<select class="jpcrm-settings-menu__jump" id="jpcrm-settings-jump">
		<?php foreach ( $sortedTabs as $tab => $tab_arr ) : // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase ?>
			<option value="<?php echo esc_url( $jpcrm_settings_url( $tab ) ); ?>" <?php selected( $tab, $current ); ?>><?php echo esc_html( $tab_arr['name'] ?? '' ); ?></option>
			<?php if ( ! empty( $tab_arr['submenu'] ) ) : ?>
				<?php foreach ( $tab_arr['submenu'] as $sub_tab => $sub_tab_arr ) : ?>
					<option value="<?php echo esc_url( $jpcrm_settings_url( $sub_tab ) ); ?>" <?php selected( $sub_tab, $current ); ?>>&nbsp;&nbsp;&nbsp;<?php echo esc_html( $sub_tab_arr['name'] ); ?></option>
				<?php endforeach; ?>
			<?php endif; ?>
		<?php endforeach; ?>
	</select>

	<ul class="jpcrm-settings-menu__list">
		<?php foreach ( $sortedTabs as $tab => $tab_arr ) : // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase ?>
			<li class="jpcrm-settings-menu__group">
				<a class="jpcrm-settings-menu__item<?php echo $tab === $current ? ' is-active' : ''; ?>" href="<?php echo esc_url( $jpcrm_settings_url( $tab ) ); ?>"<?php echo $tab === $current ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $tab_arr['name'] ?? '' ); ?></a>
				<?php if ( ! empty( $tab_arr['submenu'] ) ) : ?>
					<ul class="jpcrm-settings-menu__sub">
						<?php foreach ( $tab_arr['submenu'] as $sub_tab => $sub_tab_arr ) : ?>
							<li><a class="jpcrm-settings-menu__item<?php echo $sub_tab === $current ? ' is-active' : ''; ?>" href="<?php echo esc_url( $jpcrm_settings_url( $sub_tab ) ); ?>"<?php echo $sub_tab === $current ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $sub_tab_arr['name'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<ul class="jpcrm-settings-menu__list jpcrm-settings-menu__footer">
		<?php ##WLREMOVE ?>
		<li><a class="jpcrm-settings-menu__item" href="<?php echo jpcrm_esc_link( $zbs->slugs['extensions'] ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>"><?php esc_html_e( 'Extensions', 'zero-bs-crm' ); ?></a></li>
		<?php ##/WLREMOVE ?>
		<li><a class="jpcrm-settings-menu__item is-destructive" href="<?php echo jpcrm_esc_link( wp_nonce_url( $zbs->slugs['settings'] . '&resetsettings=1' ) ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>"><?php esc_html_e( 'Restore default settings', 'zero-bs-crm' ); ?></a></li>
	</ul>
</div>
