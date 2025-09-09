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

use Psr\Log\LogLevel;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_Check_Component_Smarty extends CRM_Utils_Check_Component {

  /**
   * Check if Smarty3 has been enabled.
   *
   * @return CRM_Utils_Check_Message[]
   */
  public function checkSmartyVersion(): array {
    $messages = [];
    $settingNames = ['CIVICRM_SMARTY_AUTOLOAD_PATH', 'CIVICRM_SMARTY3_AUTOLOAD_PATH'];
    foreach ($settingNames as $settingName) {
      if (CRM_Utils_Constant::value($settingName)) {
        $pathSetting = CRM_Utils_Constant::value($settingName);
        break;
      }
    }
    if ($pathSetting && !str_ends_with($pathSetting, 'smarty5/Smarty.php')) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        '<p>' . (ts('The site settings are overriding the Smarty path with an older version of Smarty. CiviCRM only officially supports Smarty version 5.')) . '</p>'
        . '<p>' . (ts("It's recommended to remove this override ASAP by deleting lines with <code>%1</code> from the <code>civicrm.settings.php</code> file.", [1 => $settingName])) . '</p>'
        . '<p>' . (ts('CiviCRM <a %1>v6.4-ESR</a> provides extended support for Smarty v2, v3, & v4. To learn more and discuss, see the <a %2>Smarty transition page</a>.' . '</p>', [
          1 => 'target="_blank" href="' . htmlentities('https://civicrm.org/esr') . '"',
          2 => 'target="_blank" href="' . htmlentities('https://civicrm.org/redirect/smarty-v3') . '"',
        ])),
        ts('Unsupported Smarty Version'),
        LogLevel::ERROR,
        'fa-lock'
      );
    }
    return $messages;
  }

}
