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
 * @package CiviCRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_Monaco {

  /**
   * Get a list of JS variables (`CRM.crmMonaco`) to provide to the browser.
   *
   * @return array
   * @see CRM_Utils_Hook::alterAngular()
   */
  public static function getSettings() {
    return [
      'paths' => [
        'vs' => Civi::paths()->getUrl('[civicrm.bower]/monaco-editor/min/vs'),
      ],
    ];
  }

}
