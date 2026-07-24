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

use Civi\Core\Event\GenericHookEvent;
use Civi\Core\HookInterface;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Financial_BAO_Currency extends CRM_Financial_DAO_Currency implements HookInterface {

  /**
   * Provide default SearchDisplay for Currency autocompletes
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  public static function on_civi_search_defaultDisplay(GenericHookEvent $e) {
    if ($e->display['settings'] || $e->display['type'] !== 'autocomplete' || $e->savedSearch['api_entity'] !== 'Currency') {
      return;
    }
    $e->display['settings'] = [
      'sort' => [
        ['full_name', 'ASC'],
      ],
      'columns' => [
        [
          'type' => 'field',
          'key' => 'full_name',
        ],
        [
          'type' => 'field',
          'key' => 'name',
          'rewrite' => '[symbol] [name]',
        ],
      ],
    ];
  }

}
