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

namespace Civi\Search;

/**
 * Class Display
 * @package Civi\Search
 */
class Display {

  /**
   * @return array
   */
  public static function getPageSettings():array {
    return [
      'displayTypes' => self::getDisplayTypes(['name']),
    ];
  }

  /**
   * @param array $props
   * @return array
   */
  public static function getDisplayTypes(array $props):array {
    try {
      return \Civi\Api4\SearchDisplay::getFields(FALSE)
        ->setLoadOptions($props)
        ->addWhere('name', '=', 'type')
        ->execute()
        ->first()['options'];
    }
    catch (\Exception $e) {
      return [];
    }
  }

}
