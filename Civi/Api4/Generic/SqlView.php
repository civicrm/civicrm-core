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

namespace Civi\Api4\Generic;

use Civi\Api4\Event\SchemaMapBuildEvent;
use Civi\Api4\Service\Schema\Joinable\Joinable;
use Civi\Api4\Service\Schema\Table;
use Civi\Api4\Utils\CoreUtil;
use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Service\AutoServiceInterface;
use Civi\Core\Service\AutoServiceTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Base class for SQL Views.
 *
 * To-date this is not used in core but is provided for extensions to use.
 *
 * Inheritors of this class must define viewSelect() and viewFrom() which are used to generate the View and the API.
 * Recommended: implement the getEntityTitle() method.
 * Optional: override the viewName() function to customize the table name of the view.
 * Optional: class annotations can be added to customize the API:
 * e.g. @description, @icon, @labelField, @orderBy, @searchable, @searchFields, @since.
 *
 * @service
 * @internal
 */
abstract class SqlView extends AbstractEntity implements EventSubscriberInterface, AutoServiceInterface {
  use AutoServiceTrait;

  /**
   * Defines the SELECT clause of the view and also the output of Api4 getFields.
   *
   * Return values can include anything supported by Api4 getFields, plus a "select" key which defines the SQL expression.
   * The "name" key defines the field alias in the view, and the name of the Api4 field.
   *
   * If selecting an existing database column, specify "original_field" (concatenating the api entity name with the field name e.g. 'Contact.id').
   * This allows the api to reuse the existing metadata for the field, including FKs and pseudoconstants.
   *
   * Example:
   * ```php
   * return [
   *   // Will be rendered as `SELECT CONCAT(first_name, ' ', last_name) AS full_name`
   *   [
   *     'select' => 'CONCAT(civicrm_contact.first_name, " ", civicrm_contact.last_name)',
   *     'name' => 'full_name',
   *     'data_type' => 'String',
   *   ],
   *   // Using the 'original_field' key will establish a FK to civicrm_contact.
   *   [
   *     'select' => 'civicrm_email.email',
   *     'name' => 'email',
   *     'original_field' => 'Email.email',
   *   ],
   *   ... more columns ...
   * ];
   * ```
   */
  abstract protected static function viewSelect(): array;

  /**
   * Defines the body of the view.
   *
   * Should return a string containing the entire view SQL starting with the word "FROM".
   *
   * Example:
   * ```php
   * return 'FROM civicrm_contact WHERE contact_type = "Individual"';
   * ```
   */
  abstract protected static function viewFrom(): string;

  /**
   * Defines the table name of the view.
   *
   * In most cases this does not need to be overridden; the default is civicrm_view_{entity_name}.
   */
  protected static function viewName(): string {
    return 'civicrm_view_' . \CRM_Utils_String::convertStringToSnakeCase(static::getEntityName());
  }

  /**
   * @param bool $checkPermissions
   * @return DAOGetAction
   */
  public static function get($checkPermissions = TRUE): DAOGetAction {
    return (new DAOGetAction(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return AutocompleteAction
   */
  public static function autocomplete($checkPermissions = TRUE): AutocompleteAction {
    return (new AutocompleteAction(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  public static function getFields($checkPermissions = TRUE): BasicGetFieldsAction {
    return (new BasicGetFieldsAction(static::getEntityName(), __FUNCTION__, [static::class, '_getFieldsFromViewSelect']))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @inheritDoc
   */
  public static function getInfo(): array {
    $info = parent::getInfo();
    $info['table_name'] = static::viewName();
    $info['primary_key'] ??= [];
    $info['description'] ??= ts('View of %1', [1 => $info['title']]);
    $info['dao'] = 'Civi\Core\DAO\SqlView';
    $info['type'] = ['SqlView', 'DAOEntity'];
    return $info;
  }

  /**
   * @internal
   */
  public static function getSubscribedEvents(): array {
    return [
      'civi.api4.entityTypes' => '_on_civi_api4_entityTypes',
      'api.schema_map.build' => '_on_schema_map_build',
    ];
  }

  /**
   * Helper for getFields.
   */
  public static function _getFieldsFromViewSelect(): array {
    $fields = static::viewSelect();
    foreach ($fields as &$field) {
      $field['column_name'] = $field['name'];
      $field['table_name'] = static::viewName();
      $field['entity'] = static::getEntityName();
      $originalDefn = self::getOriginalDefinition($field);
      if ($originalDefn) {
        // Fetch original options
        if (isset($originalDefn['options_callback']) || isset($originalDefn['pseudoconstant'])) {
          $originalDefn['pseudoconstant'] = [
            'callback' => [static::class, '_getOptions'],
          ];
        }
        // Set FK to original entity id
        if ($originalDefn['name'] === CoreUtil::getIdFieldName($originalDefn['entity'])) {
          $field['fk_entity'] = $originalDefn['entity'];
          $field['fk_column'] = $originalDefn['name'];
          $field['input_type'] = 'EntityRef';
        }
        $field += $originalDefn;
      }
      unset($field['select'], $field['options_callback']);
    }
    return $fields;
  }

  /**
   * Callback rebuilds the view whenever the Api4 entityTypes cache is rebuilt.
   *
   * @internal
   */
  public static function _on_civi_api4_entityTypes(GenericHookEvent $event): void {
    $viewName = static::viewName();
    \CRM_Core_DAO::executeQuery("DROP VIEW IF EXISTS `$viewName`");
    $selects = [];
    foreach (static::viewSelect() as $field) {
      $selects[] = "{$field['select']} AS `{$field['name']}`";
    }
    $select = implode(', ', $selects);
    $from = static::viewFrom();
    \CRM_Core_DAO::executeQuery("CREATE VIEW `$viewName` AS SELECT $select $from");
  }

  /**
   * Callback to register FK joins for the view.
   *
   * This makes it possible to use implicit join syntax in Api.get.
   *
   * @internal
   */
  public static function _on_schema_map_build(SchemaMapBuildEvent $event): void {
    $schema = $event->getSchemaMap();
    foreach (static::_getFieldsFromViewSelect() as $field) {
      if (isset($field['fk_entity'])) {
        $fkTable = CoreUtil::getTableName($field['fk_entity']);
        if ($fkTable) {
          $table = $schema->getTableByName($field['table_name']) ?? (new Table($field['table_name']));
          $link = new Joinable($fkTable, $field['fk_column'] ?? 'id', $field['name']);
          $link->setBaseTable($field['table_name']);
          $link->setJoinType(Joinable::JOIN_TYPE_ONE_TO_MANY);
          $table->addTableLink($field['name'], $link);
          $schema->addTable($table);
        }
      }
    }
  }

  /**
   * Fetch the original definition of a field from the entity it belongs to.
   */
  protected static function getOriginalDefinition(array $field, $loadOptions = FALSE): ?array {
    if (!isset($field['original_field'])) {
      return NULL;
    }
    [$originalEntity, $originalField] = explode('.', $field['original_field'], 2);
    return civicrm_api4($originalEntity, 'getfields', [
      'checkPermissions' => FALSE,
      'action' => 'get',
      'where' => [['name', '=', $originalField]],
      'loadOptions' => $loadOptions,
    ])->first();
  }

  /**
   * Pseudoconstant callback.
   */
  public static function _getOptions($fieldName): array {
    foreach (static::viewSelect() as $field) {
      if ($field['name'] === $fieldName) {
        $originalField = static::getOriginalDefinition($field, array_merge(['id'], array_keys(\CRM_Core_SelectValues::optionAttributes())));
        return $originalField['options'] ?: [];
      }
    }
    return [];
  }

}
