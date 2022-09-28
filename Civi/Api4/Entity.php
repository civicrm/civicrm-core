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
namespace Civi\Api4;

/**
 * Retrieves information about all Api4 entities.
 *
 * @see \Civi\Api4\Generic\AbstractEntity
 *
 * @searchable none
 * @since 5.19
 * @package Civi\Api4
 */
class Entity extends Generic\AbstractEntity {

  /**
   * @var array[]
   */
  public static $entityFields = [
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
      'data_type' => 'Array',
      'description' => 'Base class for this entity',
      'pseudoconstant' => ['callback' => ['Civi\Api4\Utils\CoreUtil', 'getEntityTypes']],
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
      'name' => 'table_name',
      'description' => 'Name of sql table, if applicable',
    ],
    [
      'name' => 'primary_key',
      'data_type' => 'Array',
      'description' => 'Name of unique identifier field(s) (e.g. [id])',
    ],
    [
      'name' => 'label_field',
      'description' => 'Field to show when displaying a record',
    ],
    [
      'name' => 'icon_field',
      'data_type' => 'Array',
      'description' => 'Field(s) which contain the icon for a record, listed in order of precedence',
    ],
    [
      'name' => 'order_by',
      'description' => 'Default column to sort results',
    ],
    [
      'name' => 'searchable',
      'description' => 'How should this entity be presented in search UIs',
      'pseudoconstant' => ['callback' => ['Civi\Api4\Utils\CoreUtil', 'getSearchableOptions']],
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
    [
      'name' => 'since',
      'data_type' => 'String',
      'description' => 'Version this API entity was added',
    ],
    [
      'name' => 'class',
      'data_type' => 'String',
      'description' => 'PHP class name',
    ],
    [
      'name' => 'class_args',
      'data_type' => 'Array',
      'description' => 'Arguments needed by php action factory functions (used when multiple entities share a class, e.g. CustomValue).',
    ],
    [
      'name' => 'bridge',
      'data_type' => 'Array',
      'description' => 'Connecting fields for EntityBridge types',
    ],
    [
      'name' => 'ui_join_filters',
      'data_type' => 'Array',
      'description' => 'When joining entities in the UI, which fields should be presented by default in the ON clause',
    ],
    [
      'name' => 'group_weights_by',
      'data_type' => 'Array',
      'description' => 'For sortable entities, what field groupings are used to order by weight',
    ],
  ];

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
    return (new Generic\BasicGetFieldsAction('Entity', __FUNCTION__, function(Generic\BasicGetFieldsAction $getFields) {
      return Entity::$entityFields;
    }))->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @deprecated
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
