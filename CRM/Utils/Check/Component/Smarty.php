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
  public function checkSmarty3(): array {
    $messages = [];
    if (!defined('CIVICRM_SMARTY3_AUTOLOAD_PATH')) {
      $smartyPath = rtrim(\Civi::paths()->getPath('[civicrm.packages]/'), '/') . DIRECTORY_SEPARATOR . 'smarty3/vendor/autoload.php';
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('We are in the process of migrating from Smarty2 onto Smarty3 and then Smarty4 for performance and security reasons. '
          . 'As of CiviCRM 5.69 switching to Smarty3 is optional but recommended.'
          . ' In order to switch you need to add the following to your civicrm.settings.php file: ')
        . "<pre>define('CIVICRM_SMARTY3_AUTOLOAD_PATH', '$smartyPath');</pre>"
        . ts('In some cases your extensions will not be compatible with Smarty 3 and will not have released compatible versions. '
          . ' Smarty2 will be supported in the ESR programme for at least another 6 months
          '),
        ts('Smarty3 recommended'),
        LogLevel::WARNING,
        'fa-lock'
      );
    }
    return $messages;
  }

}
