<?php
/**
 * Initialization file for the SemanticXML extension.
 *
 * @file SemanticXML.php
 * @ingroup SemanticXML
 * @package SemanticXML
 *
 * @licence GNU GPL v3
 * @author Wikivote llc < http://wikivote.ru >
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

if ( version_compare( $wgVersion, '1.17', '<' ) ) {
	die( '<b>Error:</b> This version of SemanticXML requires MediaWiki 1.17 or above.' );
}

global $wgSemanticXML;
$wgSemanticXMLDir = dirname( __FILE__ );

$extensionName = 'SemanticQueryInterface';

if (isset($wgInternalURLLink)) {
		$extensionUrl=$wgInternalURLLink.$extensionName;
}
else {
		$extensionUrl="http://hq.wikivote.ru/index.php/Extension:" . $extensionName;
}

$wgExtensionCredits['specialpage'][] = array(
		'path' => __FILE__,
		'name' => $extensionName,
		'version' => '0.1',
		'author' => 'WikiVote!',
		'url' => $extensionUrl,
		'descriptionmsg' => strtolower($extensionName).'-credits',
);

/* Resource modules */
$wgResourceModules['ext.SemanticQueryInterface.main'] = array(
    'localBasePath' => dirname( __FILE__ ) . '/',
    'remoteExtPath' => 'SemanticQueryInterface/',
    'group' => 'ext.SemanticQueryInterface',
    'scripts' => '',
    'styles' => ''
);

/* Message Files */
$wgExtensionMessagesFiles['SemanticQueryInterface'] = dirname( __FILE__ ) . '/SQI.i18n.php';

/* Autoload classes */
$wgAutoloadClasses['SemanticQueryInterface'] = dirname( __FILE__ ) . '/SQI.class.php';
$wgAutoloadClasses['SemanticQueryInterfaceSpecial'] = dirname( __FILE__ ) . '/SQISpecial.class.php';
$wgAutoloadClasses['SemanticQueryInterfaceHooks'] = dirname( __FILE__ ) . '/SQI.hooks.php';

$wgAutoloadClasses['SQI\SemanticQueryInterface'] = dirname( __FILE__ ) . '/includes/SemanticQueryInterface.php';
$wgAutoloadClasses['SQI\SemanticUtils'] = dirname( __FILE__ ) . '/includes/SemanticUtils.php';

/* ORM,MODELS */
#$wgAutoloadClasses['SemanticXML_Model_'] = dirname( __FILE__ ) . '/includes/SemanticXML_Model_.php';

/* ORM,PAGES */
#$wgAutoloadClasses['SemanticXMLSpecial'] = dirname( __FILE__ ) . '/pages/SemanticXMLSpecial/SemanticXMLSpecial.php';

/* Rights */
$wgAvailableRights[] = 'usesemanticxml';

/* Permissions */
$wgGroupPermissions['sysop']['usesemanticxml'] = true;

/* Special Pages */
$wgSpecialPages['SemanticQueryInterface'] = 'SemanticQueryInterfaceSpecial';

/* Hooks */
#$wgHooks['example_hook'][] = 'SemanticXMLHooks::onExampleHook';

/* Unit Tests */
$wgHooks['UnitTestsList'][] = 'SemanticQueryInterfaceHooks::onUnitTestsList';
