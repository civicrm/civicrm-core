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

if (!class_exists('Smarty_Security')) {
  return;
}
/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

class CRM_Core_Smarty_Security extends Smarty_Security {

  /**
   * @param Smarty $smarty
   */
  public function __construct($smarty) {
    $this->smarty = $smarty;
    $phpFunctions = [
      'array',
      'list',
      'isset',
      'empty',
      'count',
      'sizeof',
      'in_array',
      'is_array',
      'true',
      'false',
    ];
    $modifiers = [
      'escape',
      'count',
      'sizeof',
      'nl2br',
    ];
    if ($smarty->getVersion() === 5) {
      $this->allow_constants = FALSE;
      foreach ($phpFunctions as $phpFunction) {
        $smarty->registerPlugin('modifier', $phpFunction, $phpFunction);
      }
      foreach ($modifiers as $modifier) {
        $smarty->registerPlugin('modifier', $modifier, $modifier);
      }
    }
    else {
      $this->php_functions = $phpFunctions;
      $this->php_modifiers = $modifiers;
    }
  }

}
