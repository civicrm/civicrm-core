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
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id$
 *
 */


namespace Civi\Api4\Action\MockArrayEntity;

/**
 * This class demonstrates how the getRecords method of Basic\Get can be overridden.
 */
class Get extends \Civi\Api4\Generic\BasicGetAction {

  public function getRecords() {
    return [
      [
        'field1' => 1,
        'field2' => 'zebra',
        'field3' => NULL,
        'field4' => [1, 2, 3],
        'field5' => 'apple',
      ],
      [
        'field1' => 2,
        'field2' => 'yack',
        'field3' => 0,
        'field4' => [2, 3, 4],
        'field5' => 'banana',
        'field6' => '',
      ],
      [
        'field1' => 3,
        'field2' => 'x ray',
        'field4' => [3, 4, 5],
        'field5' => 'banana',
        'field6' => 0,
      ],
      [
        'field1' => 4,
        'field2' => 'wildebeest',
        'field3' => 1,
        'field4' => [4, 5, 6],
        'field5' => 'apple',
        'field6' => '0',
      ],
      [
        'field1' => 5,
        'field2' => 'vole',
        'field3' => 1,
        'field4' => [4, 5, 6],
        'field5' => 'apple',
        'field6' => 0,
      ],
    ];
  }

}
