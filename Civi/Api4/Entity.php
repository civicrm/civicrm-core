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

/**
 * Retrieves information about all Api4 entities.
 *
 * @see \Civi\Api4\Generic\AbstractEntity
 *
 * @package Civi\Api4
 */
class Entity extends Generic\AbstractEntity {

  /**
   * @param bool $checkPermissions
   * @return Action\Entity\Get
   */
  public static function get($checkPermissions = TRUE) {
    return (new Action\Entity\Get('Entity', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new Generic\BasicGetFieldsAction('Entity', __FUNCTION__, function() {
      return [
        [
          'name' => 'name',
          'description' => 'Entity name',
        ],
        [
          'name' => 'title',
          'description' => 'Localized title (singular)',
        ],
        [
          'name' => 'title_plural',
          'description' => 'Localized title (plural)',
        ],
        [
          'name' => 'type',
          'description' => 'Base class for this entity',
          'options' => ['DAOEntity' => 'DAOEntity', 'BasicEntity' => 'BasicEntity', 'BridgeEntity' => 'BridgeEntity', 'AbstractEntity' => 'AbstractEntity'],
        ],
        [
          'name' => 'description',
          'description' => 'Description from docblock',
        ],
        [
          'name' => 'comment',
          'description' => 'Comments from docblock',
        ],
        [
          'name' => 'icon',
          'description' => 'crm-i icon class associated with this entity',
        ],
        [
          'name' => 'dao',
          'description' => 'Class name for dao-based entities',
        ],
        [
          'name' => 'paths',
          'data_type' => 'Array',
          'description' => 'System paths for accessing this entity',
        ],
        [
          'name' => 'see',
          'data_type' => 'Array',
          'description' => 'Any @see annotations from docblock',
        ],
      ];
    }))->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Entity\GetLinks
   */
  public static function getLinks($checkPermissions = TRUE) {
    return (new Action\Entity\GetLinks('Entity', __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
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
