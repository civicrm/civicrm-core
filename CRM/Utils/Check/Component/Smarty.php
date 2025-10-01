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
   * Check if the Smarty version has been overridden.
   *
   * @return CRM_Utils_Check_Message[]
   */
  public function checkSmartyVersion(): array {
    $messages = [];
    $settingNames = ['CIVICRM_SMARTY_AUTOLOAD_PATH', 'CIVICRM_SMARTY3_AUTOLOAD_PATH'];
    $pathSetting = '';
    foreach ($settingNames as $settingName) {
      if (CRM_Utils_Constant::value($settingName)) {
        $pathSetting = CRM_Utils_Constant::value($settingName);
        break;
      }
    }
    if ($pathSetting && !str_ends_with($pathSetting, 'smarty5/Smarty.php')) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
      "
        <p>" . (ts("CiviCRM recommends Smarty version 5. This site is overriding that default with an older version of Smarty.")) . "</p>
        <p>" . (ts("CiviCRM will support such overrides until version 6.10. By v6.11, Smarty 5 will be required. You can try out Smarty 5 before then by deleting the line starting with <code>%1</code> from the <code>%2</code> file.", [1 => "define($settingName", 2 => 'civicrm.settings.php'])) . "</p>
        <p>" . (ts('CiviCRM <a %1>v6.10-ESR</a> provides extended support for Smarty v2 and v4. To learn more and discuss, see the <a %2>Smarty transition page</a>.' . '</p>', [
          1 => 'target="_blank" href="' . htmlentities('https://civicrm.org/esr') . '"',
          2 => 'target="_blank" href="' . htmlentities('https://civicrm.org/redirect/smarty-v3') . '"',
        ])),
        ts('Smarty Version Override'),
        LogLevel::WARNING,
        'fa-lock'
      );
    }
    return $messages;
  }

}
