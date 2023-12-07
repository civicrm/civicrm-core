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

use Civi\API\Exception\NotImplementedException;
use Civi\Api4\Utils\CoreUtil;

/**
 * Export $ENTITY to civicrm_managed format.
 *
 * This action generates an exportable array suitable for use in a .mgd.php file.
 * The array will include any other entities that reference the $ENTITY.
 *
 * @method $this setId(int $id)
 * @method int getId()
 * @method $this setMatch(array $match) Specify fields to match for update.
 * @method array getMatch()
 * @method $this setCleanup(string $cleanup)
 * @method string getCleanup()
 * @method $this setUpdate(string $update)
 * @method string getUpdate()
 */
class ExportAction extends AbstractAction {

  /**
   * Id of $ENTITY to export
   * @var int
   * @required
   */
  protected $id;

  /**
   * Fields to match when managed records are being reconciled.
   *
   * By default this will be set automatically based on the entity's unique fields.
   *
   * @var array
   * @optionsCallback getMatchFields
   */
  protected $match;

  /**
   * Specify rule for auto-updating managed entity
   * @var string
   * @options never,always,unmodified
   */
  protected $update = 'unmodified';

  /**
   * Specify rule for auto-deleting managed entity
   * @var string
   * @options never,always,unused
   */
  protected $cleanup = 'unused';

  /**
   * Used to prevent circular references
   * @var array
   */
  private $exportedEntities = [];

  /**
   * @param \Civi\Api4\Generic\Result $result
   */
  public function _run(Result $result) {
    $this->exportRecord($this->getEntityName(), $this->id, $result);
  }

  /**
   * @param string $entityType
   * @param int $entityId
   * @param \Civi\Api4\Generic\Result $result
   * @param string $parentName
   * @param array $excludeFields
   */
  private function exportRecord(string $entityType, int $entityId, Result $result, $parentName = NULL, $excludeFields = []) {
    if (isset($this->exportedEntities[$entityType][$entityId])) {
      throw new \CRM_Core_Exception("Circular reference detected: attempted to export $entityType id $entityId multiple times.");
    }
    $this->exportedEntities[$entityType][$entityId] = TRUE;
    $select = $pseudofields = [];
    $allFields = $this->getFieldsForExport($entityType, TRUE, $excludeFields);
    foreach ($allFields as $field) {
      // Use implicit join syntax but only if the fk entity has a `name` field
      if (!empty($field['fk_entity']) && array_key_exists('name', $this->getFieldsForExport($field['fk_entity']))) {
        $select[] = $field['name'];
        $select[] = $field['name'] . '.name';
        $pseudofields[$field['name'] . '.name'] = $field['name'];
      }
      // Use pseudoconstant syntax if appropriate
      elseif ($this->shouldUsePseudoconstant($entityType, $field)) {
        $select[] = $field['name'];
        $select[] = $field['name'] . ':name';
        $pseudofields[$field['name'] . ':name'] = $field['name'];
      }
      elseif (empty($field['fk_entity'])) {
        $select[] = $field['name'];
      }
    }
    $record = civicrm_api4($entityType, 'get', [
      'checkPermissions' => $this->checkPermissions,
      'select' => $select,
      'where' => [['id', '=', $entityId]],
    ])->first();
    if (!$record) {
      return;
    }
    // The get api always returns ID, but it should not be included in an export
    unset($record['id']);
    $name = ($parentName ?? '') . $entityType . '_' . ($record['name'] ?? count($this->exportedEntities[$entityType]));
    // Ensure safe characters, max length.
    // This is used for the value of `civicrm_managed.name` which has a maxlength of 255, but is also used
    // to generate a file by civix, and many filesystems have a maxlength of 255 including the suffix, so
    // 255 - strlen('.mgd.php') = 247
    $name = \CRM_Utils_String::munge($name, '_', 247);
    // Include option group with custom field
    if ($entityType === 'CustomField') {
      if (
        !empty($record['option_group_id.name']) &&
        // Sometimes fields share an option group; only export it once.
        empty($this->exportedEntities['OptionGroup'][$record['option_group_id']])
      ) {
        $this->exportRecord('OptionGroup', $record['option_group_id'], $result);
      }
    }
    // Don't use joins/pseudoconstants if null or if it has the same value as the original
    foreach ($pseudofields as $alias => $fieldName) {
      if (!isset($record[$alias]) || $record[$alias] == ($record[$fieldName] ?? NULL)) {
        unset($record[$alias]);
      }
      else {
        unset($record[$fieldName]);
      }
    }
    // Unset values that match the default
    foreach ($allFields as $fieldName => $field) {
      if (($record[$fieldName] ?? NULL) === $field['default_value']) {
        unset($record[$fieldName]);
      }
    }
    $export = [
      'name' => $name,
      'entity' => $entityType,
      'cleanup' => $this->cleanup,
      'update' => $this->update,
      'params' => [
        'version' => 4,
        'values' => $record,
      ],
    ];
    $matchFields = $this->match;
    // Calculate $match param if not passed explicitly
    if (!isset($matchFields)) {
      $matchFields = (array) CoreUtil::getInfoItem($entityType, 'match_fields');
    }
    if ($matchFields) {
      $export['params']['match'] = $matchFields;
    }
    $result[] = $export;
    // Export entities that reference this one
    $daoName = CoreUtil::getInfoItem($entityType, 'dao');
    if ($daoName) {
      /** @var \CRM_Core_DAO $dao */
      $dao = $daoName::findById($entityId);
      // Collect references into arrays keyed by entity type
      $references = [];
      foreach ($dao->findReferences() as $reference) {
        $refEntity = \CRM_Utils_Array::first($reference::fields())['entity'] ?? '';
        // Custom fields don't really "belong" to option groups despite the reference
        if ($refEntity === 'CustomField' && $entityType === 'OptionGroup') {
          continue;
        }
        $references[$refEntity][] = $reference;
      }
      foreach ($references as $refEntity => $records) {
        $refApiType = CoreUtil::getInfoItem($refEntity, 'type') ?? [];
        // Reference must be a ManagedEntity
        if (!in_array('ManagedEntity', $refApiType, TRUE)) {
          continue;
        }
        $exclude = [];
        // For sortable entities, order by weight and exclude weight from the export (it will be auto-managed)
        if (in_array('SortableEntity', $refApiType, TRUE)) {
          $exclude[] = $weightCol = CoreUtil::getInfoItem($refEntity, 'order_by');
          usort($records, function ($a, $b) use ($weightCol) {
            if (!isset($a->$weightCol)) {
              $a->find(TRUE);
            }
            if (!isset($b->$weightCol)) {
              $b->find(TRUE);
            }
            return $a->$weightCol < $b->$weightCol ? -1 : 1;
          });
        }
        foreach ($records as $record) {
          $this->exportRecord($refEntity, $record->id, $result, $name . '_', $exclude);
        }
      }
    }
  }

  /**
   * If a field has a pseudoconstant list, determine whether it would be better
   * to use pseudoconstant (field:name) syntax vs plain value.
   *
   * @param string $entityType
   * @param array $field
   * @return bool
   */
  private function shouldUsePseudoconstant(string $entityType, array $field) {
    if (empty($field['options'])) {
      return FALSE;
    }
    $daoName = CoreUtil::getInfoItem($entityType, 'dao');
    // Exception for Profile.field_name
    if ($entityType === 'UFField' && $field['name'] === 'field_name') {
      return TRUE;
    }
    // Options generated by a callback function tend to be stable,
    // and the :name property may not be reliable. Use plain value.
    if ($daoName && !empty($daoName::getSupportedFields()[$field['name']]['pseudoconstant']['callback'])) {
      return FALSE;
    }
    // Options with numeric keys probably refer to auto-increment keys
    // which vary across different databases. Use :name syntax.
    $numericKeys = array_filter(array_keys($field['options']), 'is_numeric');
    return count($numericKeys) === count($field['options']);
  }

  /**
   * @param $entityType
   * @param bool $loadOptions
   * @param array $excludeFields
   * @return array
   */
  private function getFieldsForExport($entityType, $loadOptions = FALSE, $excludeFields = []): array {
    $conditions = [
      ['type', 'IN', ['Field', 'Custom']],
      ['readonly', '!=', TRUE],
    ];
    // Domains are handled automatically
    $excludeFields[] = 'domain_id';
    if ($excludeFields) {
      $conditions[] = ['name', 'NOT IN', $excludeFields];
    }
    try {
      return (array) civicrm_api4($entityType, 'getFields', [
        'action' => 'create',
        'where' => $conditions,
        'loadOptions' => $loadOptions,
        'checkPermissions' => $this->checkPermissions,
      ])->indexBy('name');
    }
    catch (NotImplementedException $e) {
      return [];
    }
  }

  /**
   * Options callback for $this->match
   * @return array
   */
  protected function getMatchFields() {
    return (array) civicrm_api4($this->getEntityName(), 'getFields', [
      'checkPermissions' => FALSE,
      'action' => 'get',
      'where' => [
        ['type', 'IN', ['Field']],
        ['readonly', '!=', TRUE],
      ],
    ], ['name']);
  }

}
