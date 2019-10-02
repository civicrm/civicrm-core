<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */


namespace Civi\Api4;

use api\v4\Mock\MockEntityDataStorage;

/**
 * MockBasicEntity entity.
 *
 * @package Civi\Api4
 */
class MockBasicEntity extends Generic\AbstractEntity {

  /**
   * @return Generic\BasicGetFieldsAction
   */
  public static function getFields() {
    return new Generic\BasicGetFieldsAction(static::class, __FUNCTION__, function() {
      return [
        [
          'name' => 'id',
          'type' => 'Integer',
        ],
        [
          'name' => 'group',
          'options' => [
            'one' => 'One',
            'two' => 'Two',
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
          'name' => 'weight',
        ],
      ];
    });
  }

  /**
   * @return Generic\BasicGetAction
   */
  public static function get() {
    return new Generic\BasicGetAction('MockBasicEntity', __FUNCTION__, [MockEntityDataStorage::CLASS, 'get']);
  }

  /**
   * @return Generic\BasicCreateAction
   */
  public static function create() {
    return new Generic\BasicCreateAction(static::class, __FUNCTION__, [MockEntityDataStorage::CLASS, 'write']);
  }

  /**
   * @return Generic\BasicSaveAction
   */
  public static function save() {
    return new Generic\BasicSaveAction(self::getEntityName(), __FUNCTION__, 'id', [MockEntityDataStorage::CLASS, 'write']);
  }

  /**
   * @return Generic\BasicUpdateAction
   */
  public static function update() {
    return new Generic\BasicUpdateAction(self::getEntityName(), __FUNCTION__, 'id', [MockEntityDataStorage::CLASS, 'write']);
  }

  /**
   * @return Generic\BasicBatchAction
   */
  public static function delete() {
    return new Generic\BasicBatchAction('MockBasicEntity', __FUNCTION__, 'id', [MockEntityDataStorage::CLASS, 'delete']);
  }

  /**
   * @return Generic\BasicBatchAction
   */
  public static function batchFrobnicate() {
    return new Generic\BasicBatchAction('MockBasicEntity', __FUNCTION__, ['id', 'number'], function ($item) {
      return [
        'id' => $item['id'],
        'frobnication' => $item['number'] * $item['number'],
      ];
    });
  }

  /**
   * @return Generic\BasicReplaceAction
   */
  public static function replace() {
    return new Generic\BasicReplaceAction('MockBasicEntity', __FUNCTION__);
  }

}
