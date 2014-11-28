<?php
/* ----------------------------------------------------------------------

   MyOOS [Shopsystem]
   http://www.oos-shop.de/

   Copyright (c) 2003 - 2014 by the MyOOS Development Team.
   ----------------------------------------------------------------------
   Based on:

   File: default.php,v 1.2 2003/01/09 09:40:07 elarifr
   orig: default.php,v 1.81 2003/02/13 04:23:23 hpdl 
   ----------------------------------------------------------------------
   osCommerce, Open Source E-Commerce Solutions
   http://www.oscommerce.com

   Copyright (c) 2003 osCommerce
   ----------------------------------------------------------------------
   Released under the GNU General Public License
   ---------------------------------------------------------------------- */

/** ensure this file is being included by a parent file */
defined( 'OOS_VALID_MOD' ) or die( 'Direct Access to this location is not allowed.' );

require_once MYOOS_INCLUDE_PATH . '/includes/languages/' . $sLanguage . '/main.php';
require_once MYOOS_INCLUDE_PATH . '/includes/functions/function_default.php';

// default


$aTemplate['page'] = $sTheme . '/page/main.html';
$aTemplate['page_heading'] = $sTheme . '/heading/page_heading.html';
if ($oEvent->installed_plugin('spezials')) $aTemplate['new_spezials'] = $sTheme . '/page/products/new_spezials.html';
if ($oEvent->installed_plugin('featured')) $aTemplate['featured'] = $sTheme . '/page/products/featured.html';
if ($oEvent->installed_plugin('manufacturers')) $aTemplate['mod_manufacturers'] = $sTheme . '/page/products/manufacturers.html';
$aTemplate['new_products'] = $sTheme . '/page/products/new_products.html';
$aTemplate['upcoming_products'] = $sTheme . '/page/products/upcoming_products.html';

$nPageType = OOS_PAGE_TYPE_MAINPAGE;

require_once MYOOS_INCLUDE_PATH . '/includes/oos_system.php';
if (!isset($option)) {
	require_once MYOOS_INCLUDE_PATH . '/includes/info_message.php';
	require_once MYOOS_INCLUDE_PATH . '/includes/oos_blocks.php';
}


$heading_title = $aLang['heading_title'];


// assign Smarty variables;
$smarty->assign(
	array(
		'oos_breadcrumb'	=> $oBreadcrumb->trail(BREADCRUMB_SEPARATOR),
		'oos_heading_title' => $heading_title,
		'canonical'			=> $current_domain
	)
);

if ( (USE_CACHE == 'true') && (!isset($_SESSION)) ) {
	$smarty->setCaching(Smarty::CACHING_LIFETIME_CURRENT);
}



if ($oEvent->installed_plugin('spezials')) {
	if (!$smarty->isCached($aTemplate['new_spezials'], $oos_modules_cache_id)) {
		require_once MYOOS_INCLUDE_PATH . '/includes/modules/new_spezials.php';
	}
	$smarty->assign('new_spezials', $smarty->fetch($aTemplate['new_spezials'], $oos_modules_cache_id));
}
 
if ($oEvent->installed_plugin('featured')) {
	if (!$smarty->isCached($aTemplate['featured'], $oos_modules_cache_id)) {
		require_once MYOOS_INCLUDE_PATH . '/includes/modules/featured.php';
	}
	$smarty->assign('featured', $smarty->fetch($aTemplate['featured'], $oos_modules_cache_id));
}

if (!$smarty->isCached($aTemplate['new_products'], $oos_modules_cache_id)) {
	require_once MYOOS_INCLUDE_PATH . '/includes/modules/new_products.php';
}
$smarty->assign('new_products', $smarty->fetch($aTemplate['new_products'], $oos_modules_cache_id));

if ($oEvent->installed_plugin('manufacturers')) {
	if (!$smarty->isCached($aTemplate['mod_manufacturers'], $oos_modules_cache_id)) {
		require_once MYOOS_INCLUDE_PATH . '/includes/modules/mod_manufacturers.php';
	}
    $smarty->assign('mod_manufacturers', $smarty->fetch($aTemplate['mod_manufacturers'], $oos_modules_cache_id));
}

if (!$smarty->isCached($aTemplate['upcoming_products'], $oos_modules_cache_id)) {
	require_once MYOOS_INCLUDE_PATH . '/includes/modules/upcoming_products.php';
}
$smarty->assign('upcoming_products', $smarty->fetch($aTemplate['upcoming_products'], $oos_modules_cache_id));

$smarty->setCaching(false);

$smarty->assign('oosPageHeading', $smarty->fetch($aTemplate['page_heading']));
# 


// display the template
$smarty->display($aTemplate['page']);
