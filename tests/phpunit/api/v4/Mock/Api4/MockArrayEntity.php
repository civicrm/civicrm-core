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


namespace Civi\Api4;

use Civi\Api4\Generic\BasicGetFieldsAction;

/**
 * MockArrayEntity entity.
 *
 * @method Generic\BasicGetAction get()
 *
 * @package Civi\Api4
 */
class MockArrayEntity extends Generic\AbstractEntity {

  public static function getFields() {
    return new BasicGetFieldsAction(static::class, __FUNCTION__, function() {
      return [
        ['name' => 'field1'],
        ['name' => 'field2'],
        ['name' => 'field3'],
        ['name' => 'field4'],
        ['name' => 'field5'],
        ['name' => 'field6'],
      ];
    });
  }

}
