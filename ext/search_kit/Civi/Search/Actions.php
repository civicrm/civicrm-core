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

use CRM_Search_ExtensionUtil as E;

/**
 * Class Tasks
 * @package Civi\Search
 */
class Actions {

  /**
   * @return array
   */
  public static function getActionSettings():array {
    return [
      'dateRanges' => \CRM_Utils_Array::makeNonAssociative(\CRM_Core_OptionGroup::values('relative_date_filters'), 'id', 'text'),
    ];
  }

}
