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
    $p = function($text) {
      return '<p>' . $text . '</p>';
    };

    $messages = [];
    if (!defined('CIVICRM_SMARTY_AUTOLOAD_PATH') && !defined('CIVICRM_SMARTY3_AUTOLOAD_PATH')) {
      $smartyPath = \Civi::paths()->getPath('[civicrm.packages]/smarty3/vendor/autoload.php');
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        $p(ts('CiviCRM is updating a major library (<em>Smarty</em>) to improve performance and security. In CiviCRM v5.69, the update is optional, but you should try it now.'))
        . $p(ts('To apply the update, add this statement to <code>civicrm.settings.php</code>:'))
        . sprintf("<pre>  define('CIVICRM_SMARTY_AUTOLOAD_PATH',\n    %s);</pre>", htmlentities(var_export($smartyPath, 1)))
        . $p('Some extensions may not work yet with Smarty v3. If you encounter problems, then simply remove the statement.')
        . $p(ts('Upcoming versions will standardize on Smarty v3. CiviCRM <a %1>v5.69-ESR</a> will provide extended support for Smarty v2. To learn more and discuss, see the <a %2>Smarty transition page</a>.', [
          1 => 'target="_blank" href="' . htmlentities('https://civicrm.org/esr') . '"',
          2 => 'target="_blank" href="' . htmlentities('https://civicrm.org/redirect/smarty-v3') . '"',
        ])),
        ts('Smarty Update (v2 => v3)'),
        LogLevel::WARNING,
        'fa-lock'
      );
    }
    return $messages;
  }

}
