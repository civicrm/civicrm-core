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
      throw new \API_Exception("Circular reference detected: attempted to export $entityType id $entityId multiple times.");
    }
    $this->exportedEntities[$entityType][$entityId] = TRUE;
    $select = $pseudofields = [];
    $allFields = $this->getFieldsForExport($entityType, TRUE, $excludeFields);
    foreach ($allFields as $field) {
      // Use implicit join syntax but only if the fk entity has a `name` field
      if (!empty($field['fk_entity']) && array_key_exists('name', $this->getFieldsForExport($field['fk_entity']))) {
        $select[] = $field['name'] . '.name';
        $pseudofields[$field['name'] . '.name'] = $field['name'];
      }
      // Use pseudoconstant syntax if appropriate
      elseif ($this->shouldUsePseudoconstant($entityType, $field)) {
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
    // Null fields should not use joins/pseudoconstants
    foreach ($pseudofields as $alias => $fieldName) {
      if (is_null($record[$alias])) {
        unset($record[$alias]);
        $record[$fieldName] = NULL;
      }
    }
    // Should references be limited to the current domain?
    $limitRefsByDomain = $entityType === 'OptionGroup' && \CRM_Core_OptionGroup::isDomainOptionGroup($record['name']) ? \CRM_Core_BAO_Domain::getDomain()->id : FALSE;
    foreach ($allFields as $fieldName => $field) {
      if (($field['fk_entity'] ?? NULL) === 'Domain') {
        $alias = $fieldName . '.name';
        if (isset($record[$alias])) {
          // If this entity is for a specific domain, limit references to that same domain
          if ($fieldName === 'domain_id') {
            $limitRefsByDomain = \CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Domain', $record[$alias], 'id', 'name');
          }
          // Swap current domain for special API keyword
          if ($record[$alias] === \CRM_Core_BAO_Domain::getDomain()->name) {
            unset($record[$alias]);
            $record[$fieldName] = 'current_domain';
          }
        }
      }
    }
    $name = ($parentName ?? '') . $entityType . '_' . ($record['name'] ?? count($this->exportedEntities[$entityType]));
    $result[] = [
      'name' => $name,
      'entity' => $entityType,
      'cleanup' => $this->cleanup,
      'update' => $this->update,
      'params' => [
        'version' => 4,
        'values' => $record,
      ],
    ];
    // Export entities that reference this one
    $daoName = CoreUtil::getInfoItem($entityType, 'dao');
    if ($daoName) {
      /** @var \CRM_Core_DAO $dao */
      $dao = new $daoName();
      $dao->id = $entityId;
      // Collect references into arrays keyed by entity type
      $references = [];
      foreach ($dao->findReferences() as $reference) {
        $refEntity = \CRM_Utils_Array::first($reference::fields())['entity'] ?? '';
        // Limit references by domain
        if (property_exists($reference, 'domain_id')) {
          if (!isset($reference->domain_id)) {
            $reference->find(TRUE);
          }
          if (isset($reference->domain_id) && $reference->domain_id != $limitRefsByDomain) {
            continue;
          }
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

}
