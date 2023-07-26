<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit06c775433a83ed276f0a1d8ac25f93ba_crmⓥ6_2_0_alpha
{
    public static $files = array (
        '3773ef3f09c37da5478d578e32b03a4b' => __DIR__ . '/../..' . '/jetpack_vendor/automattic/jetpack-assets/actions.php',
    );

    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Svg\\' => 4,
            'Sabberworm\\CSS\\' => 15,
        ),
        'M' => 
        array (
            'Masterminds\\' => 12,
        ),
        'F' => 
        array (
            'FontLib\\' => 8,
        ),
        'D' => 
        array (
            'Dompdf\\' => 7,
        ),
        'A' => 
        array (
            'Automattic\\WooCommerce\\' => 23,
            'Automattic\\Jetpack\\Autoloader\\' => 30,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Svg\\' => 
        array (
            0 => __DIR__ . '/..' . '/phenx/php-svg-lib/src/Svg',
        ),
        'Sabberworm\\CSS\\' => 
        array (
            0 => __DIR__ . '/..' . '/sabberworm/php-css-parser/src',
        ),
        'Masterminds\\' => 
        array (
            0 => __DIR__ . '/..' . '/masterminds/html5/src',
        ),
        'FontLib\\' => 
        array (
            0 => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib',
        ),
        'Dompdf\\' => 
        array (
            0 => __DIR__ . '/..' . '/dompdf/dompdf/src',
        ),
        'Automattic\\WooCommerce\\' => 
        array (
            0 => __DIR__ . '/..' . '/automattic/woocommerce/src/WooCommerce',
        ),
        'Automattic\\Jetpack\\Autoloader\\' => 
        array (
            0 => __DIR__ . '/..' . '/automattic/jetpack-autoloader/src',
        ),
    );

    public static $classMap = array (
        'Automattic\\Jetpack\\Assets' => __DIR__ . '/../..' . '/jetpack_vendor/automattic/jetpack-assets/src/class-assets.php',
        'Automattic\\Jetpack\\Assets\\Semver' => __DIR__ . '/../..' . '/jetpack_vendor/automattic/jetpack-assets/src/class-semver.php',
        'Automattic\\Jetpack\\Autoloader\\AutoloadFileWriter' => __DIR__ . '/..' . '/automattic/jetpack-autoloader/src/AutoloadFileWriter.php',
        'Automattic\\Jetpack\\Autoloader\\AutoloadGenerator' => __DIR__ . '/..' . '/automattic/jetpack-autoloader/src/AutoloadGenerator.php',
        'Automattic\\Jetpack\\Autoloader\\AutoloadProcessor' => __DIR__ . '/..' . '/automattic/jetpack-autoloader/src/AutoloadProcessor.php',
        'Automattic\\Jetpack\\Autoloader\\CustomAutoloaderPlugin' => __DIR__ . '/..' . '/automattic/jetpack-autoloader/src/CustomAutoloaderPlugin.php',
        'Automattic\\Jetpack\\Autoloader\\ManifestGenerator' => __DIR__ . '/..' . '/automattic/jetpack-autoloader/src/ManifestGenerator.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Action' => __DIR__ . '/../..' . '/src/automation/interface-action.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Actions\\Add_Contact_Log' => __DIR__ . '/../..' . '/src/automation/commons/actions/contacts/class-add-contact-log.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Actions\\Add_Remove_Contact_Tag' => __DIR__ . '/../..' . '/src/automation/commons/actions/contacts/class-add-remove-contact-tag.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Actions\\Delete_Contact' => __DIR__ . '/../..' . '/src/automation/commons/actions/contacts/class-delete-contact.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Actions\\New_Contact' => __DIR__ . '/../..' . '/src/automation/commons/actions/contacts/class-new-contact.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Actions\\Update_Contact' => __DIR__ . '/../..' . '/src/automation/commons/actions/contacts/class-update-contact.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Actions\\Update_Contact_Status' => __DIR__ . '/../..' . '/src/automation/commons/actions/contacts/class-update-contact-status.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Automation_Engine' => __DIR__ . '/../..' . '/src/automation/class-automation-engine.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Automation_Exception' => __DIR__ . '/../..' . '/src/automation/class-automation-exception.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Automation_Logger' => __DIR__ . '/../..' . '/src/automation/class-automation-logger.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Automation_Workflow' => __DIR__ . '/../..' . '/src/automation/class-automation-workflow.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Base_Action' => __DIR__ . '/../..' . '/src/automation/class-base-action.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Base_Condition' => __DIR__ . '/../..' . '/src/automation/class-base-condition.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Base_Step' => __DIR__ . '/../..' . '/src/automation/class-base-step.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Base_Trigger' => __DIR__ . '/../..' . '/src/automation/class-base-trigger.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Condition' => __DIR__ . '/../..' . '/src/automation/interface-condition.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Step' => __DIR__ . '/../..' . '/src/automation/interface-step.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Step_Exception' => __DIR__ . '/../..' . '/src/automation/class-step-exception.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Trigger' => __DIR__ . '/../..' . '/src/automation/interface-trigger.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Triggers\\Company_Deleted' => __DIR__ . '/../..' . '/src/automation/commons/triggers/companies/class-company-deleted.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Triggers\\Company_New' => __DIR__ . '/../..' . '/src/automation/commons/triggers/companies/class-company-new.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Triggers\\Company_Status_Updated' => __DIR__ . '/../..' . '/src/automation/commons/triggers/companies/class-company-status-updated.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Triggers\\Company_Updated' => __DIR__ . '/../..' . '/src/automation/commons/triggers/companies/class-company-updated.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Triggers\\Contact_Before_Deleted' => __DIR__ . '/../..' . '/src/automation/commons/triggers/contacts/class-contact-before-deleted.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Triggers\\Contact_Deleted' => __DIR__ . '/../..' . '/src/automation/commons/triggers/contacts/class-contact-deleted.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Triggers\\Contact_Email_Updated' => __DIR__ . '/../..' . '/src/automation/commons/triggers/contacts/class-contact-email-updated.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Triggers\\Contact_New' => __DIR__ . '/../..' . '/src/automation/commons/triggers/contacts/class-contact-new.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Triggers\\Contact_Status_Updated' => __DIR__ . '/../..' . '/src/automation/commons/triggers/contacts/class-contact-status-updated.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Triggers\\Contact_Updated' => __DIR__ . '/../..' . '/src/automation/commons/triggers/contacts/class-contact-updated.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Triggers\\Invoice_Deleted' => __DIR__ . '/../..' . '/src/automation/commons/triggers/invoices/class-invoice-deleted.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Triggers\\Invoice_New' => __DIR__ . '/../..' . '/src/automation/commons/triggers/invoices/class-invoice-new.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Triggers\\Invoice_Status_Updated' => __DIR__ . '/../..' . '/src/automation/commons/triggers/invoices/class-invoice-status-updated.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Triggers\\Invoice_Updated' => __DIR__ . '/../..' . '/src/automation/commons/triggers/invoices/class-invoice-updated.php',
        'Automattic\\Jetpack\\CRM\\Automation\\Workflow_Exception' => __DIR__ . '/../..' . '/src/automation/class-workflow-exception.php',
        'Automattic\\Jetpack\\CRM\\REST_API\\V4\\REST_Base_Controller' => __DIR__ . '/../..' . '/src/rest-api/v4/class-rest-base-controller.php',
        'Automattic\\Jetpack\\CRM\\REST_API\\V4\\REST_Base_Objects_Controller' => __DIR__ . '/../..' . '/src/rest-api/v4/class-rest-base-objects-controller.php',
        'Automattic\\Jetpack\\CRM\\REST_API\\V4\\REST_Contacts_Controller' => __DIR__ . '/../..' . '/src/rest-api/v4/class-rest-contacts-controller.php',
        'Automattic\\Jetpack\\Composer\\Manager' => __DIR__ . '/..' . '/automattic/jetpack-composer-plugin/src/class-manager.php',
        'Automattic\\Jetpack\\Composer\\Plugin' => __DIR__ . '/..' . '/automattic/jetpack-composer-plugin/src/class-plugin.php',
        'Automattic\\Jetpack\\Constants' => __DIR__ . '/../..' . '/jetpack_vendor/automattic/jetpack-constants/src/class-constants.php',
        'Automattic\\Jetpack_CRM\\Onboarding_Wizard\\Bootstrap' => __DIR__ . '/../..' . '/src/onboarding-wizard/class-bootstrap.php',
        'Automattic\\WooCommerce\\Client' => __DIR__ . '/..' . '/automattic/woocommerce/src/WooCommerce/Client.php',
        'Automattic\\WooCommerce\\HttpClient\\BasicAuth' => __DIR__ . '/..' . '/automattic/woocommerce/src/WooCommerce/HttpClient/BasicAuth.php',
        'Automattic\\WooCommerce\\HttpClient\\HttpClient' => __DIR__ . '/..' . '/automattic/woocommerce/src/WooCommerce/HttpClient/HttpClient.php',
        'Automattic\\WooCommerce\\HttpClient\\HttpClientException' => __DIR__ . '/..' . '/automattic/woocommerce/src/WooCommerce/HttpClient/HttpClientException.php',
        'Automattic\\WooCommerce\\HttpClient\\OAuth' => __DIR__ . '/..' . '/automattic/woocommerce/src/WooCommerce/HttpClient/OAuth.php',
        'Automattic\\WooCommerce\\HttpClient\\Options' => __DIR__ . '/..' . '/automattic/woocommerce/src/WooCommerce/HttpClient/Options.php',
        'Automattic\\WooCommerce\\HttpClient\\Request' => __DIR__ . '/..' . '/automattic/woocommerce/src/WooCommerce/HttpClient/Request.php',
        'Automattic\\WooCommerce\\HttpClient\\Response' => __DIR__ . '/..' . '/automattic/woocommerce/src/WooCommerce/HttpClient/Response.php',
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'Dompdf\\Adapter\\CPDF' => __DIR__ . '/..' . '/dompdf/dompdf/src/Adapter/CPDF.php',
        'Dompdf\\Adapter\\GD' => __DIR__ . '/..' . '/dompdf/dompdf/src/Adapter/GD.php',
        'Dompdf\\Adapter\\PDFLib' => __DIR__ . '/..' . '/dompdf/dompdf/src/Adapter/PDFLib.php',
        'Dompdf\\Canvas' => __DIR__ . '/..' . '/dompdf/dompdf/src/Canvas.php',
        'Dompdf\\CanvasFactory' => __DIR__ . '/..' . '/dompdf/dompdf/src/CanvasFactory.php',
        'Dompdf\\Cellmap' => __DIR__ . '/..' . '/dompdf/dompdf/src/Cellmap.php',
        'Dompdf\\Cpdf' => __DIR__ . '/..' . '/dompdf/dompdf/lib/Cpdf.php',
        'Dompdf\\Css\\AttributeTranslator' => __DIR__ . '/..' . '/dompdf/dompdf/src/Css/AttributeTranslator.php',
        'Dompdf\\Css\\Color' => __DIR__ . '/..' . '/dompdf/dompdf/src/Css/Color.php',
        'Dompdf\\Css\\Style' => __DIR__ . '/..' . '/dompdf/dompdf/src/Css/Style.php',
        'Dompdf\\Css\\Stylesheet' => __DIR__ . '/..' . '/dompdf/dompdf/src/Css/Stylesheet.php',
        'Dompdf\\Dompdf' => __DIR__ . '/..' . '/dompdf/dompdf/src/Dompdf.php',
        'Dompdf\\Exception' => __DIR__ . '/..' . '/dompdf/dompdf/src/Exception.php',
        'Dompdf\\Exception\\ImageException' => __DIR__ . '/..' . '/dompdf/dompdf/src/Exception/ImageException.php',
        'Dompdf\\FontMetrics' => __DIR__ . '/..' . '/dompdf/dompdf/src/FontMetrics.php',
        'Dompdf\\Frame' => __DIR__ . '/..' . '/dompdf/dompdf/src/Frame.php',
        'Dompdf\\FrameDecorator\\AbstractFrameDecorator' => __DIR__ . '/..' . '/dompdf/dompdf/src/FrameDecorator/AbstractFrameDecorator.php',
        'Dompdf\\FrameDecorator\\Block' => __DIR__ . '/..' . '/dompdf/dompdf/src/FrameDecorator/Block.php',
        'Dompdf\\FrameDecorator\\Image' => __DIR__ . '/..' . '/dompdf/dompdf/src/FrameDecorator/Image.php',
        'Dompdf\\FrameDecorator\\Inline' => __DIR__ . '/..' . '/dompdf/dompdf/src/FrameDecorator/Inline.php',
        'Dompdf\\FrameDecorator\\ListBullet' => __DIR__ . '/..' . '/dompdf/dompdf/src/FrameDecorator/ListBullet.php',
        'Dompdf\\FrameDecorator\\ListBulletImage' => __DIR__ . '/..' . '/dompdf/dompdf/src/FrameDecorator/ListBulletImage.php',
        'Dompdf\\FrameDecorator\\NullFrameDecorator' => __DIR__ . '/..' . '/dompdf/dompdf/src/FrameDecorator/NullFrameDecorator.php',
        'Dompdf\\FrameDecorator\\Page' => __DIR__ . '/..' . '/dompdf/dompdf/src/FrameDecorator/Page.php',
        'Dompdf\\FrameDecorator\\Table' => __DIR__ . '/..' . '/dompdf/dompdf/src/FrameDecorator/Table.php',
        'Dompdf\\FrameDecorator\\TableCell' => __DIR__ . '/..' . '/dompdf/dompdf/src/FrameDecorator/TableCell.php',
        'Dompdf\\FrameDecorator\\TableRow' => __DIR__ . '/..' . '/dompdf/dompdf/src/FrameDecorator/TableRow.php',
        'Dompdf\\FrameDecorator\\TableRowGroup' => __DIR__ . '/..' . '/dompdf/dompdf/src/FrameDecorator/TableRowGroup.php',
        'Dompdf\\FrameDecorator\\Text' => __DIR__ . '/..' . '/dompdf/dompdf/src/FrameDecorator/Text.php',
        'Dompdf\\FrameReflower\\AbstractFrameReflower' => __DIR__ . '/..' . '/dompdf/dompdf/src/FrameReflower/AbstractFrameReflower.php',
        'Dompdf\\FrameReflower\\Block' => __DIR__ . '/..' . '/dompdf/dompdf/src/FrameReflower/Block.php',
        'Dompdf\\FrameReflower\\Image' => __DIR__ . '/..' . '/dompdf/dompdf/src/FrameReflower/Image.php',
        'Dompdf\\FrameReflower\\Inline' => __DIR__ . '/..' . '/dompdf/dompdf/src/FrameReflower/Inline.php',
        'Dompdf\\FrameReflower\\ListBullet' => __DIR__ . '/..' . '/dompdf/dompdf/src/FrameReflower/ListBullet.php',
        'Dompdf\\FrameReflower\\NullFrameReflower' => __DIR__ . '/..' . '/dompdf/dompdf/src/FrameReflower/NullFrameReflower.php',
        'Dompdf\\FrameReflower\\Page' => __DIR__ . '/..' . '/dompdf/dompdf/src/FrameReflower/Page.php',
        'Dompdf\\FrameReflower\\Table' => __DIR__ . '/..' . '/dompdf/dompdf/src/FrameReflower/Table.php',
        'Dompdf\\FrameReflower\\TableCell' => __DIR__ . '/..' . '/dompdf/dompdf/src/FrameReflower/TableCell.php',
        'Dompdf\\FrameReflower\\TableRow' => __DIR__ . '/..' . '/dompdf/dompdf/src/FrameReflower/TableRow.php',
        'Dompdf\\FrameReflower\\TableRowGroup' => __DIR__ . '/..' . '/dompdf/dompdf/src/FrameReflower/TableRowGroup.php',
        'Dompdf\\FrameReflower\\Text' => __DIR__ . '/..' . '/dompdf/dompdf/src/FrameReflower/Text.php',
        'Dompdf\\Frame\\Factory' => __DIR__ . '/..' . '/dompdf/dompdf/src/Frame/Factory.php',
        'Dompdf\\Frame\\FrameListIterator' => __DIR__ . '/..' . '/dompdf/dompdf/src/Frame/FrameListIterator.php',
        'Dompdf\\Frame\\FrameTree' => __DIR__ . '/..' . '/dompdf/dompdf/src/Frame/FrameTree.php',
        'Dompdf\\Frame\\FrameTreeIterator' => __DIR__ . '/..' . '/dompdf/dompdf/src/Frame/FrameTreeIterator.php',
        'Dompdf\\Helpers' => __DIR__ . '/..' . '/dompdf/dompdf/src/Helpers.php',
        'Dompdf\\Image\\Cache' => __DIR__ . '/..' . '/dompdf/dompdf/src/Image/Cache.php',
        'Dompdf\\JavascriptEmbedder' => __DIR__ . '/..' . '/dompdf/dompdf/src/JavascriptEmbedder.php',
        'Dompdf\\LineBox' => __DIR__ . '/..' . '/dompdf/dompdf/src/LineBox.php',
        'Dompdf\\Options' => __DIR__ . '/..' . '/dompdf/dompdf/src/Options.php',
        'Dompdf\\PhpEvaluator' => __DIR__ . '/..' . '/dompdf/dompdf/src/PhpEvaluator.php',
        'Dompdf\\Positioner\\Absolute' => __DIR__ . '/..' . '/dompdf/dompdf/src/Positioner/Absolute.php',
        'Dompdf\\Positioner\\AbstractPositioner' => __DIR__ . '/..' . '/dompdf/dompdf/src/Positioner/AbstractPositioner.php',
        'Dompdf\\Positioner\\Block' => __DIR__ . '/..' . '/dompdf/dompdf/src/Positioner/Block.php',
        'Dompdf\\Positioner\\Fixed' => __DIR__ . '/..' . '/dompdf/dompdf/src/Positioner/Fixed.php',
        'Dompdf\\Positioner\\Inline' => __DIR__ . '/..' . '/dompdf/dompdf/src/Positioner/Inline.php',
        'Dompdf\\Positioner\\ListBullet' => __DIR__ . '/..' . '/dompdf/dompdf/src/Positioner/ListBullet.php',
        'Dompdf\\Positioner\\NullPositioner' => __DIR__ . '/..' . '/dompdf/dompdf/src/Positioner/NullPositioner.php',
        'Dompdf\\Positioner\\TableCell' => __DIR__ . '/..' . '/dompdf/dompdf/src/Positioner/TableCell.php',
        'Dompdf\\Positioner\\TableRow' => __DIR__ . '/..' . '/dompdf/dompdf/src/Positioner/TableRow.php',
        'Dompdf\\Renderer' => __DIR__ . '/..' . '/dompdf/dompdf/src/Renderer.php',
        'Dompdf\\Renderer\\AbstractRenderer' => __DIR__ . '/..' . '/dompdf/dompdf/src/Renderer/AbstractRenderer.php',
        'Dompdf\\Renderer\\Block' => __DIR__ . '/..' . '/dompdf/dompdf/src/Renderer/Block.php',
        'Dompdf\\Renderer\\Image' => __DIR__ . '/..' . '/dompdf/dompdf/src/Renderer/Image.php',
        'Dompdf\\Renderer\\Inline' => __DIR__ . '/..' . '/dompdf/dompdf/src/Renderer/Inline.php',
        'Dompdf\\Renderer\\ListBullet' => __DIR__ . '/..' . '/dompdf/dompdf/src/Renderer/ListBullet.php',
        'Dompdf\\Renderer\\TableCell' => __DIR__ . '/..' . '/dompdf/dompdf/src/Renderer/TableCell.php',
        'Dompdf\\Renderer\\TableRowGroup' => __DIR__ . '/..' . '/dompdf/dompdf/src/Renderer/TableRowGroup.php',
        'Dompdf\\Renderer\\Text' => __DIR__ . '/..' . '/dompdf/dompdf/src/Renderer/Text.php',
        'FontLib\\AdobeFontMetrics' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/AdobeFontMetrics.php',
        'FontLib\\Autoloader' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/Autoloader.php',
        'FontLib\\BinaryStream' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/BinaryStream.php',
        'FontLib\\EOT\\File' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/EOT/File.php',
        'FontLib\\EOT\\Header' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/EOT/Header.php',
        'FontLib\\EncodingMap' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/EncodingMap.php',
        'FontLib\\Exception\\FontNotFoundException' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/Exception/FontNotFoundException.php',
        'FontLib\\Font' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/Font.php',
        'FontLib\\Glyph\\Outline' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/Glyph/Outline.php',
        'FontLib\\Glyph\\OutlineComponent' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/Glyph/OutlineComponent.php',
        'FontLib\\Glyph\\OutlineComposite' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/Glyph/OutlineComposite.php',
        'FontLib\\Glyph\\OutlineSimple' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/Glyph/OutlineSimple.php',
        'FontLib\\Header' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/Header.php',
        'FontLib\\OpenType\\File' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/OpenType/File.php',
        'FontLib\\OpenType\\TableDirectoryEntry' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/OpenType/TableDirectoryEntry.php',
        'FontLib\\Table\\DirectoryEntry' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/Table/DirectoryEntry.php',
        'FontLib\\Table\\Table' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/Table/Table.php',
        'FontLib\\Table\\Type\\cmap' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/Table/Type/cmap.php',
        'FontLib\\Table\\Type\\glyf' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/Table/Type/glyf.php',
        'FontLib\\Table\\Type\\head' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/Table/Type/head.php',
        'FontLib\\Table\\Type\\hhea' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/Table/Type/hhea.php',
        'FontLib\\Table\\Type\\hmtx' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/Table/Type/hmtx.php',
        'FontLib\\Table\\Type\\kern' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/Table/Type/kern.php',
        'FontLib\\Table\\Type\\loca' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/Table/Type/loca.php',
        'FontLib\\Table\\Type\\maxp' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/Table/Type/maxp.php',
        'FontLib\\Table\\Type\\name' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/Table/Type/name.php',
        'FontLib\\Table\\Type\\nameRecord' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/Table/Type/nameRecord.php',
        'FontLib\\Table\\Type\\os2' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/Table/Type/os2.php',
        'FontLib\\Table\\Type\\post' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/Table/Type/post.php',
        'FontLib\\TrueType\\Collection' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/TrueType/Collection.php',
        'FontLib\\TrueType\\File' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/TrueType/File.php',
        'FontLib\\TrueType\\Header' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/TrueType/Header.php',
        'FontLib\\TrueType\\TableDirectoryEntry' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/TrueType/TableDirectoryEntry.php',
        'FontLib\\WOFF\\File' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/WOFF/File.php',
        'FontLib\\WOFF\\Header' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/WOFF/Header.php',
        'FontLib\\WOFF\\TableDirectoryEntry' => __DIR__ . '/..' . '/phenx/php-font-lib/src/FontLib/WOFF/TableDirectoryEntry.php',
        'Masterminds\\HTML5' => __DIR__ . '/..' . '/masterminds/html5/src/HTML5.php',
        'Masterminds\\HTML5\\Elements' => __DIR__ . '/..' . '/masterminds/html5/src/HTML5/Elements.php',
        'Masterminds\\HTML5\\Entities' => __DIR__ . '/..' . '/masterminds/html5/src/HTML5/Entities.php',
        'Masterminds\\HTML5\\Exception' => __DIR__ . '/..' . '/masterminds/html5/src/HTML5/Exception.php',
        'Masterminds\\HTML5\\InstructionProcessor' => __DIR__ . '/..' . '/masterminds/html5/src/HTML5/InstructionProcessor.php',
        'Masterminds\\HTML5\\Parser\\CharacterReference' => __DIR__ . '/..' . '/masterminds/html5/src/HTML5/Parser/CharacterReference.php',
        'Masterminds\\HTML5\\Parser\\DOMTreeBuilder' => __DIR__ . '/..' . '/masterminds/html5/src/HTML5/Parser/DOMTreeBuilder.php',
        'Masterminds\\HTML5\\Parser\\EventHandler' => __DIR__ . '/..' . '/masterminds/html5/src/HTML5/Parser/EventHandler.php',
        'Masterminds\\HTML5\\Parser\\FileInputStream' => __DIR__ . '/..' . '/masterminds/html5/src/HTML5/Parser/FileInputStream.php',
        'Masterminds\\HTML5\\Parser\\InputStream' => __DIR__ . '/..' . '/masterminds/html5/src/HTML5/Parser/InputStream.php',
        'Masterminds\\HTML5\\Parser\\ParseError' => __DIR__ . '/..' . '/masterminds/html5/src/HTML5/Parser/ParseError.php',
        'Masterminds\\HTML5\\Parser\\Scanner' => __DIR__ . '/..' . '/masterminds/html5/src/HTML5/Parser/Scanner.php',
        'Masterminds\\HTML5\\Parser\\StringInputStream' => __DIR__ . '/..' . '/masterminds/html5/src/HTML5/Parser/StringInputStream.php',
        'Masterminds\\HTML5\\Parser\\Tokenizer' => __DIR__ . '/..' . '/masterminds/html5/src/HTML5/Parser/Tokenizer.php',
        'Masterminds\\HTML5\\Parser\\TreeBuildingRules' => __DIR__ . '/..' . '/masterminds/html5/src/HTML5/Parser/TreeBuildingRules.php',
        'Masterminds\\HTML5\\Parser\\UTF8Utils' => __DIR__ . '/..' . '/masterminds/html5/src/HTML5/Parser/UTF8Utils.php',
        'Masterminds\\HTML5\\Serializer\\HTML5Entities' => __DIR__ . '/..' . '/masterminds/html5/src/HTML5/Serializer/HTML5Entities.php',
        'Masterminds\\HTML5\\Serializer\\OutputRules' => __DIR__ . '/..' . '/masterminds/html5/src/HTML5/Serializer/OutputRules.php',
        'Masterminds\\HTML5\\Serializer\\RulesInterface' => __DIR__ . '/..' . '/masterminds/html5/src/HTML5/Serializer/RulesInterface.php',
        'Masterminds\\HTML5\\Serializer\\Traverser' => __DIR__ . '/..' . '/masterminds/html5/src/HTML5/Serializer/Traverser.php',
        'Sabberworm\\CSS\\CSSList\\AtRuleBlockList' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/CSSList/AtRuleBlockList.php',
        'Sabberworm\\CSS\\CSSList\\CSSBlockList' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/CSSList/CSSBlockList.php',
        'Sabberworm\\CSS\\CSSList\\CSSList' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/CSSList/CSSList.php',
        'Sabberworm\\CSS\\CSSList\\Document' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/CSSList/Document.php',
        'Sabberworm\\CSS\\CSSList\\KeyFrame' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/CSSList/KeyFrame.php',
        'Sabberworm\\CSS\\Comment\\Comment' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/Comment/Comment.php',
        'Sabberworm\\CSS\\Comment\\Commentable' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/Comment/Commentable.php',
        'Sabberworm\\CSS\\OutputFormat' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/OutputFormat.php',
        'Sabberworm\\CSS\\OutputFormatter' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/OutputFormatter.php',
        'Sabberworm\\CSS\\Parser' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/Parser.php',
        'Sabberworm\\CSS\\Parsing\\OutputException' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/Parsing/OutputException.php',
        'Sabberworm\\CSS\\Parsing\\ParserState' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/Parsing/ParserState.php',
        'Sabberworm\\CSS\\Parsing\\SourceException' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/Parsing/SourceException.php',
        'Sabberworm\\CSS\\Parsing\\UnexpectedEOFException' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/Parsing/UnexpectedEOFException.php',
        'Sabberworm\\CSS\\Parsing\\UnexpectedTokenException' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/Parsing/UnexpectedTokenException.php',
        'Sabberworm\\CSS\\Property\\AtRule' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/Property/AtRule.php',
        'Sabberworm\\CSS\\Property\\CSSNamespace' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/Property/CSSNamespace.php',
        'Sabberworm\\CSS\\Property\\Charset' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/Property/Charset.php',
        'Sabberworm\\CSS\\Property\\Import' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/Property/Import.php',
        'Sabberworm\\CSS\\Property\\KeyframeSelector' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/Property/KeyframeSelector.php',
        'Sabberworm\\CSS\\Property\\Selector' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/Property/Selector.php',
        'Sabberworm\\CSS\\Renderable' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/Renderable.php',
        'Sabberworm\\CSS\\RuleSet\\AtRuleSet' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/RuleSet/AtRuleSet.php',
        'Sabberworm\\CSS\\RuleSet\\DeclarationBlock' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/RuleSet/DeclarationBlock.php',
        'Sabberworm\\CSS\\RuleSet\\RuleSet' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/RuleSet/RuleSet.php',
        'Sabberworm\\CSS\\Rule\\Rule' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/Rule/Rule.php',
        'Sabberworm\\CSS\\Settings' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/Settings.php',
        'Sabberworm\\CSS\\Value\\CSSFunction' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/Value/CSSFunction.php',
        'Sabberworm\\CSS\\Value\\CSSString' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/Value/CSSString.php',
        'Sabberworm\\CSS\\Value\\CalcFunction' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/Value/CalcFunction.php',
        'Sabberworm\\CSS\\Value\\CalcRuleValueList' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/Value/CalcRuleValueList.php',
        'Sabberworm\\CSS\\Value\\Color' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/Value/Color.php',
        'Sabberworm\\CSS\\Value\\LineName' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/Value/LineName.php',
        'Sabberworm\\CSS\\Value\\PrimitiveValue' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/Value/PrimitiveValue.php',
        'Sabberworm\\CSS\\Value\\RuleValueList' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/Value/RuleValueList.php',
        'Sabberworm\\CSS\\Value\\Size' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/Value/Size.php',
        'Sabberworm\\CSS\\Value\\URL' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/Value/URL.php',
        'Sabberworm\\CSS\\Value\\Value' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/Value/Value.php',
        'Sabberworm\\CSS\\Value\\ValueList' => __DIR__ . '/..' . '/sabberworm/php-css-parser/src/Value/ValueList.php',
        'Svg\\CssLength' => __DIR__ . '/..' . '/phenx/php-svg-lib/src/Svg/CssLength.php',
        'Svg\\DefaultStyle' => __DIR__ . '/..' . '/phenx/php-svg-lib/src/Svg/DefaultStyle.php',
        'Svg\\Document' => __DIR__ . '/..' . '/phenx/php-svg-lib/src/Svg/Document.php',
        'Svg\\Gradient\\Stop' => __DIR__ . '/..' . '/phenx/php-svg-lib/src/Svg/Gradient/Stop.php',
        'Svg\\Style' => __DIR__ . '/..' . '/phenx/php-svg-lib/src/Svg/Style.php',
        'Svg\\Surface\\CPdf' => __DIR__ . '/..' . '/phenx/php-svg-lib/src/Svg/Surface/CPdf.php',
        'Svg\\Surface\\SurfaceCpdf' => __DIR__ . '/..' . '/phenx/php-svg-lib/src/Svg/Surface/SurfaceCpdf.php',
        'Svg\\Surface\\SurfaceInterface' => __DIR__ . '/..' . '/phenx/php-svg-lib/src/Svg/Surface/SurfaceInterface.php',
        'Svg\\Surface\\SurfacePDFLib' => __DIR__ . '/..' . '/phenx/php-svg-lib/src/Svg/Surface/SurfacePDFLib.php',
        'Svg\\Tag\\AbstractTag' => __DIR__ . '/..' . '/phenx/php-svg-lib/src/Svg/Tag/AbstractTag.php',
        'Svg\\Tag\\Anchor' => __DIR__ . '/..' . '/phenx/php-svg-lib/src/Svg/Tag/Anchor.php',
        'Svg\\Tag\\Circle' => __DIR__ . '/..' . '/phenx/php-svg-lib/src/Svg/Tag/Circle.php',
        'Svg\\Tag\\ClipPath' => __DIR__ . '/..' . '/phenx/php-svg-lib/src/Svg/Tag/ClipPath.php',
        'Svg\\Tag\\Ellipse' => __DIR__ . '/..' . '/phenx/php-svg-lib/src/Svg/Tag/Ellipse.php',
        'Svg\\Tag\\Group' => __DIR__ . '/..' . '/phenx/php-svg-lib/src/Svg/Tag/Group.php',
        'Svg\\Tag\\Image' => __DIR__ . '/..' . '/phenx/php-svg-lib/src/Svg/Tag/Image.php',
        'Svg\\Tag\\Line' => __DIR__ . '/..' . '/phenx/php-svg-lib/src/Svg/Tag/Line.php',
        'Svg\\Tag\\LinearGradient' => __DIR__ . '/..' . '/phenx/php-svg-lib/src/Svg/Tag/LinearGradient.php',
        'Svg\\Tag\\Path' => __DIR__ . '/..' . '/phenx/php-svg-lib/src/Svg/Tag/Path.php',
        'Svg\\Tag\\Polygon' => __DIR__ . '/..' . '/phenx/php-svg-lib/src/Svg/Tag/Polygon.php',
        'Svg\\Tag\\Polyline' => __DIR__ . '/..' . '/phenx/php-svg-lib/src/Svg/Tag/Polyline.php',
        'Svg\\Tag\\RadialGradient' => __DIR__ . '/..' . '/phenx/php-svg-lib/src/Svg/Tag/RadialGradient.php',
        'Svg\\Tag\\Rect' => __DIR__ . '/..' . '/phenx/php-svg-lib/src/Svg/Tag/Rect.php',
        'Svg\\Tag\\Shape' => __DIR__ . '/..' . '/phenx/php-svg-lib/src/Svg/Tag/Shape.php',
        'Svg\\Tag\\Stop' => __DIR__ . '/..' . '/phenx/php-svg-lib/src/Svg/Tag/Stop.php',
        'Svg\\Tag\\StyleTag' => __DIR__ . '/..' . '/phenx/php-svg-lib/src/Svg/Tag/StyleTag.php',
        'Svg\\Tag\\Text' => __DIR__ . '/..' . '/phenx/php-svg-lib/src/Svg/Tag/Text.php',
        'Svg\\Tag\\UseTag' => __DIR__ . '/..' . '/phenx/php-svg-lib/src/Svg/Tag/UseTag.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit06c775433a83ed276f0a1d8ac25f93ba_crmⓥ6_2_0_alpha::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit06c775433a83ed276f0a1d8ac25f93ba_crmⓥ6_2_0_alpha::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit06c775433a83ed276f0a1d8ac25f93ba_crmⓥ6_2_0_alpha::$classMap;

        }, null, ClassLoader::class);
    }
}
