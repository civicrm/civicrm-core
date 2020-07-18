<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright TTTP
 *
 */

/**
 * Display the CiviCRM version
 *
 * ```
 * The version is {crmVersion}.
 *
 * {crmVersion redact=auto assign=ver}The version is {$ver}.
 * ```
 *
 * @param $params
 * @param $smarty
 *
 * @return string
 */
function smarty_function_crmVersion($params, &$smarty) {
  $version = CRM_Utils_System::version();

  if (!CRM_Core_Permission::check('access CiviCRM')) {
    $version = CRM_Utils_System::majorVersion();
  }

  if (isset($params['assign'])) {
    $smarty->assign($params['assign'], $version);
  }
  else {
    return $version;
  }
}
