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

/**
 * Retrieves information about all Api4 entities.
 *
 * @package Civi\Api4
 */
class Entity extends Generic\AbstractEntity {

  /**
   * @return Action\Entity\Get
   */
  public static function get() {
    return new Action\Entity\Get('Entity', __FUNCTION__);
  }

  /**
   * @return \Civi\Api4\Generic\BasicGetFieldsAction
   */
  public static function getFields() {
    return new \Civi\Api4\Generic\BasicGetFieldsAction('Entity', __FUNCTION__, function() {
      return [
        ['name' => 'name'],
        ['name' => 'description'],
        ['name' => 'comment'],
      ];
    });
  }

  /**
   * @return Action\Entity\GetLinks
   */
  public static function getLinks() {
    return new Action\Entity\GetLinks('Entity', __FUNCTION__);
  }

  /**
   * @return array
   */
  public static function permissions() {
    return [
      'default' => ['access CiviCRM'],
    ];
  }

}
