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
 */


namespace Civi\Api4;

use api\v4\Mock\MockEntityDataStorage;

/**
 * MockBasicEntity entity.
 *
 * @package Civi\Api4
 */
class MockBasicEntity extends Generic\BasicEntity {

  protected static $idField = 'identifier';

  protected static $getter = [MockEntityDataStorage::CLASS, 'get'];
  protected static $setter = [MockEntityDataStorage::CLASS, 'write'];
  protected static $deleter = [MockEntityDataStorage::CLASS, 'delete'];

  /**
   * @inheritDoc
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new Generic\BasicGetFieldsAction(__CLASS__, __FUNCTION__, function() {
      return [
        [
          'name' => 'identifier',
          'data_type' => 'Integer',
        ],
        [
          'name' => 'group',
          'options' => [
            'one' => 'First',
            'two' => 'Second',
          ],
        ],
        [
          'name' => 'color',
        ],
        [
          'name' => 'shape',
        ],
        [
          'name' => 'size',
        ],
        [
          'name' => 'foo',
        ],
        [
          'name' => 'weight',
          'data_type' => 'Integer',
        ],
        [
          'name' => 'fruit',
          'options' => [
            [
              'id' => 1,
              'name' => 'apple',
              'label' => 'Apple',
              'color' => 'red',
            ],
            [
              'id' => 2,
              'name' => 'pear',
              'label' => 'Pear',
              'color' => 'green',
            ],
            [
              'id' => 3,
              'name' => 'banana',
              'label' => 'Banana',
              'color' => 'yellow',
            ],
          ],
        ],
      ];
    }))->setCheckPermissions(TRUE);
  }

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicBatchAction
   */
  public static function batchFrobnicate($checkPermissions = TRUE) {
    return (new Action\MockBasicEntity\BatchFrobnicate(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

}
