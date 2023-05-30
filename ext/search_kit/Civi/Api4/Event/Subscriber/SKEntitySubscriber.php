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

namespace Civi\Api4\Event\Subscriber;

use Civi\Api4\Generic\Traits\SavedSearchInspectorTrait;
use Civi\Api4\Job;
use Civi\Api4\SKEntity;
use Civi\Api4\Utils\CoreUtil;
use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Event\PostEvent;
use Civi\Core\Event\PreEvent;
use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Manages tables and API entities created from search displays of type "entity"
 * @service
 * @internal
 */
class SKEntitySubscriber extends AutoService implements EventSubscriberInterface {

  use SavedSearchInspectorTrait;

  /**
   * @return array
   */
  public static function getSubscribedEvents(): array {
    return [
      'civi.api4.entityTypes' => 'on_civi_api4_entityTypes',
      'hook_civicrm_pre' => 'onPreSaveDisplay',
      'hook_civicrm_post' => 'onPostSaveDisplay',
    ];
  }

  /**
   * Register SearchDisplays of type 'entity'
   *
   * @param \Civi\Core\Event\GenericHookEvent $event
   */
  public static function on_civi_api4_entityTypes(GenericHookEvent $event): void {
    // Can't use the API to fetch search displays because this hook is called when the API boots
    foreach (_getSearchKitEntityDisplays() as $display) {
      $event->entities[$display['entityName']] = [
        'name' => $display['entityName'],
        'title' => $display['label'],
        'title_plural' => $display['label'],
        'description' => $display['settings']['description'] ?? NULL,
        'primary_key' => ['_row'],
        'type' => ['SavedSearch'],
        'table_name' => $display['tableName'],
        'class_args' => [$display['name']],
        'label_field' => NULL,
        'searchable' => 'secondary',
        'class' => SKEntity::class,
        'icon' => 'fa-search-plus',
      ];
    }
  }

  /**
   * @param \Civi\Core\Event\PreEvent $event
   */
  public function onPreSaveDisplay(PreEvent $event): void {
    if (!$this->applies($event)) {
      return;
    }
    $oldName = $event->id ? \CRM_Core_DAO::getFieldValue('CRM_Search_DAO_SearchDisplay', $event->id) : NULL;
    $newName = $event->params['name'] ?? $oldName;
    $newSettings = $event->params['settings'] ?? NULL;
    // No changes made, nothing to do
    if (!$newSettings && $oldName === $newName && $event->action !== 'delete') {
      return;
    }
    // Drop the old table if it exists
    if ($oldName) {
      \CRM_Core_BAO_SchemaHandler::dropTable(_getSearchKitDisplayTableName($oldName));
    }
    if ($event->action === 'delete') {
      // Delete scheduled jobs when deleting entity
      Job::delete(FALSE)
        ->addWhere('api_entity', '=', 'SK_' . $oldName)
        ->execute();
      return;
    }
    // Build the new table
    $savedSearchID = $event->params['saved_search_id'] ?? \CRM_Core_DAO::getFieldValue('CRM_Search_DAO_SearchDisplay', $event->id, 'saved_search_id');
    $this->loadSavedSearch($savedSearchID);
    $table = [
      'name' => _getSearchKitDisplayTableName($newName),
      'is_multiple' => FALSE,
      'attributes' => 'ENGINE=InnoDB',
      'fields' => [],
    ];
    // Primary key field
    $table['fields'][] = [
      'name' => '_row',
      'type' => 'int unsigned',
      'primary' => TRUE,
      'required' => TRUE,
      'attributes' => 'AUTO_INCREMENT',
      'comment' => 'Row number',
    ];
    foreach ($newSettings['columns'] as &$column) {
      $expr = $this->getSelectExpression($column['key']);
      if (!$expr) {
        continue;
      }
      $column['spec'] = $this->formatFieldSpec($column, $expr);
      $table['fields'][] = $this->formatSQLSpec($column, $expr);
    }
    // Store new settings with added column spec
    $event->params['settings'] = $newSettings;
    $sql = \CRM_Core_BAO_SchemaHandler::buildTableSQL($table);
    // do not i18n-rewrite
    \CRM_Core_DAO::executeQuery($sql, [], TRUE, NULL, FALSE, FALSE);
  }

  /**
   * @param array $column
   * @param array{fields: array, expr: SqlExpression, dataType: string} $expr
   * @return array
   */
  private function formatFieldSpec(array $column, array $expr): array {
    // Strip the pseuoconstant suffix
    [$name, $suffix] = array_pad(explode(':', $column['key']), 2, NULL);
    // Sanitize the name
    $name = \CRM_Utils_String::munge($name, '_', 255);
    $spec = [
      'name' => $name,
      'data_type' => $expr['dataType'],
      'suffixes' => $suffix ? ['id', $suffix] : NULL,
      'options' => FALSE,
    ];
    if ($expr['expr']->getType() === 'SqlField') {
      $field = \CRM_Utils_Array::first($expr['fields']);
      $spec['fk_entity'] = $field['fk_entity'] ?? NULL;
      $spec['original_field_name'] = $field['name'];
      $spec['original_field_entity'] = $field['entity'];
      if ($suffix) {
        // Options will be looked up by SKEntitySpecProvider::getOptionsForSKEntityField
        $spec['options'] = TRUE;
      }
    }
    elseif ($expr['expr']->getType() === 'SqlFunction') {
      if ($suffix) {
        $spec['options'] = CoreUtil::formatOptionList($expr['expr']::getOptions(), $spec['suffixes']);
      }
    }
    return $spec;
  }

  /**
   * @param array $column
   * @param array{fields: array, expr: SqlExpression, dataType: string} $expr
   * @return array
   */
  private function formatSQLSpec(array $column, array $expr): array {
    // Try to use the exact sql column type as the original field
    $field = \CRM_Utils_Array::first($expr['fields']);
    if (!empty($field['column_name']) && !empty($field['table_name'])) {
      $columns = \CRM_Core_DAO::executeQuery("DESCRIBE `{$field['table_name']}`")
        ->fetchMap('Field', 'Type');
      $type = $columns[$field['column_name']] ?? NULL;
    }
    // If we can't get the exact data type from the column, take an educated guess
    if (empty($type) ||
      ($expr['expr']->getType() !== 'SqlField' && $field['data_type'] !== $expr['dataType'])
    ) {
      $map = [
        'Array' => 'text',
        'Boolean' => 'tinyint',
        'Date' => 'date',
        'Float' => 'double',
        'Integer' => 'int',
        'String' => 'text',
        'Text' => 'text',
        'Timestamp' => 'datetime',
      ];
      $type = $map[$expr['dataType']] ?? $type;
    }
    $defn = [
      'name' => $column['spec']['name'],
      'type' => $type,
      // Adds an index to non-fk fields
      'searchable' => TRUE,
    ];
    // Add FK indexes
    if ($expr['expr']->getType() === 'SqlField' && !empty($field['fk_entity'])) {
      $defn['fk_table_name'] = CoreUtil::getTableName($field['fk_entity']);
      // FIXME look up fk_field_name from schema, don't assume it's always "id"
      $defn['fk_field_name'] = 'id';
      $defn['fk_attributes'] = ' ON DELETE SET NULL';
    }
    return $defn;
  }

  /**
   * @param \Civi\Core\Event\PostEvent $event
   */
  public function onPostSaveDisplay(PostEvent $event): void {
    if ($this->applies($event)) {
      \CRM_Core_DAO_AllCoreTables::flush();
      \Civi::cache('metadata')->clear();
    }
  }

  /**
   * Check if pre/post hook applies to a SearchDisplay type 'entity'
   *
   * @param \Civi\Core\Event\PreEvent|\Civi\Core\Event\PostEvent $event
   * @return bool
   */
  private function applies(GenericHookEvent $event): bool {
    if ($event->entity !== 'SearchDisplay') {
      return FALSE;
    }
    $type = $event->params['type'] ?? $event->object->type ?? \CRM_Core_DAO::getFieldValue('CRM_Search_DAO_SearchDisplay', $event->id, 'type');
    return $type === 'entity';
  }

}
