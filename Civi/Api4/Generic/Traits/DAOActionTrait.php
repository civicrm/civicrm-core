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

namespace Civi\Api4\Generic\Traits;

use Civi\Api4\Utils\FormattingUtil;
use Civi\Api4\Utils\CoreUtil;
use Civi\Api4\Utils\ReflectionUtils;

/**
 * Common properties and helper-methods used for DB-oriented actions.
 */
trait DAOActionTrait {

  /**
   * @var array
   */
  private $_maxWeights = [];

  /**
   * @return \CRM_Core_DAO|string
   */
  protected function getBaoName() {
    return CoreUtil::getBAOFromApiName($this->getEntityName());
  }

  /**
   * Convert saved object to array
   *
   * Used by create, update & save actions
   *
   * @param \CRM_Core_DAO $bao
   * @param array $input
   * @return array
   */
  public function baoToArray($bao, $input) {
    $entityFields = array_column($bao->fields(), 'name');
    $inputFields = array_map(function($key) {
      return explode(':', $key)[0];
    }, array_keys($input));
    $combinedFields = array_unique(array_merge($entityFields, $inputFields));
    if (!empty($this->reload)) {
      $bao->find(TRUE);
    }
    else {
      // Convert 'null' input to true null
      foreach ($inputFields as $key) {
        if (($bao->$key ?? NULL) === 'null') {
          $bao->$key = NULL;
        }
      }
    }
    $values = [];
    foreach ($combinedFields as $field) {
      if (isset($bao->$field) || in_array($field, $inputFields) || (!empty($this->reload) && in_array($field, $entityFields))) {
        $values[$field] = $bao->$field ?? NULL;
      }
    }
    return $values;
  }

  /**
   * Fill field defaults which were declared by the api.
   *
   * Note: default values from core are ignored because the BAO or database layer will supply them.
   *
   * @param array $params
   */
  protected function fillDefaults(&$params) {
    $fields = $this->entityFields();
    $bao = $this->getBaoName();
    $coreFields = array_column($bao::fields(), NULL, 'name');

    foreach ($fields as $name => $field) {
      // If a default value in the api field is different than in core, the api should override it.
      if (!isset($params[$name]) && !empty($field['default_value']) && $field['default_value'] != \CRM_Utils_Array::pathGet($coreFields, [$name, 'default'])) {
        $params[$name] = $field['default_value'];
      }
    }
  }

  /**
   * Write bao objects as part of a create/update/save action.
   *
   * @param array $items
   *   The records to write to the DB.
   *
   * @return array
   *   The records after being written to the DB (e.g. including newly assigned "id").
   * @throws \CRM_Core_Exception
   */
  protected function writeObjects($items) {
    $updateWeights = FALSE;
    // Adjust weights for sortable entities
    if (CoreUtil::isType($this->getEntityName(), 'SortableEntity')) {
      $weightField = CoreUtil::getInfoItem($this->getEntityName(), 'order_by');
      // Only take action if updating a single record, or if no weights are specified in any record
      // This avoids messing up a bulk update with multiple recalculations
      if (count($items) === 1 || !array_filter(array_column($items, $weightField))) {
        $updateWeights = TRUE;
      }
    }

    // Values specified by entity definition (e.g. 'Individual', 'Organization', 'Household' pseudo-entities specify `contact_type`)
    $presetValues = CoreUtil::getInfoItem($this->getEntityName(), 'where') ?? [];

    $result = [];
    $idField = CoreUtil::getIdFieldName($this->getEntityName());

    foreach ($items as &$item) {
      $entityId = $item[$idField] ?? NULL;
      if (CoreUtil::isContact($this->getEntityName())) {
        $entityId = FormattingUtil::resolveContactID($idField, $entityId);
      }
      FormattingUtil::formatWriteParams($item, $this->entityFields());
      $this->formatCustomParams($item, $entityId);

      if (!$entityId) {
        $item = $presetValues + $item;
      }

      // Adjust weights for sortable entities
      if ($updateWeights) {
        $this->updateWeight($item);
      }

      $item['check_permissions'] = $this->getCheckPermissions();
    }

    // Ensure array keys start at 0
    $items = array_values($items);

    foreach ($this->write($items) as $index => $dao) {
      if (!$dao) {
        $errMessage = sprintf('%s write operation failed', $this->getEntityName());
        throw new \CRM_Core_Exception($errMessage);
      }
      $result[] = $this->baoToArray($dao, $items[$index]);
    }

    \CRM_Utils_API_HTMLInputCoder::singleton()->decodeRows($result);
    foreach ($result as &$row) {
      FormattingUtil::formatOutputValues($row, $this->entityFields());
    }
    return $result;
  }

  /**
   * Overrideable function to save items using the appropriate BAO function
   *
   * @param array[] $items
   *   Items already formatted by self::writeObjects
   * @return \CRM_Core_DAO[]
   *   Array of saved DAO records
   */
  protected function write(array $items) {
    $saved = [];
    $baoName = $this->getBaoName();

    $method = method_exists($baoName, 'create') ? 'create' : (method_exists($baoName, 'add') ? 'add' : NULL);
    // Use BAO create or add method if not deprecated
    if ($method && !ReflectionUtils::isMethodDeprecated($baoName, $method)) {
      foreach ($items as $item) {
        $saved[] = $baoName::$method($item);
      }
    }
    else {
      $saved = $baoName::writeRecords($items);
    }
    return $saved;
  }

  /**
   * @inheritDoc
   */
  protected function formatWriteValues(&$record) {
    $this->resolveFKValues($record);
    parent::formatWriteValues($record);
  }

  /**
   * Looks up an id based on some other property of an fk entity
   *
   * @param array $record
   */
  protected function resolveFKValues(array &$record): void {
    // Resolve domain id first
    uksort($record, function($a, $b) {
      return substr($a, 0, 9) == 'domain_id' ? -1 : 1;
    });
    foreach ($record as $key => $value) {
      if (!$value || substr_count($key, '.') !== 1) {
        continue;
      }
      [$fieldName, $fkField] = explode('.', $key);
      $field = $this->entityFields()[$fieldName] ?? NULL;
      if (!$field || empty($field['fk_entity'])) {
        continue;
      }
      $fkDao = CoreUtil::getBAOFromApiName($field['fk_entity']);
      // Constrain search to the domain of the current entity
      $domainConstraint = NULL;
      if (isset($fkDao::getSupportedFields()['domain_id'])) {
        if (!empty($record['domain_id'])) {
          $domainConstraint = $record['domain_id'] === 'current_domain' ? \CRM_Core_Config::domainID() : $record['domain_id'];
        }
        elseif (!empty($record['id']) && isset($this->entityFields()['domain_id'])) {
          $domainConstraint = \CRM_Core_DAO::getFieldValue($this->getBaoName(), $record['id'], 'domain_id');
        }
      }
      if ($domainConstraint) {
        $fkSearch = new $fkDao();
        $fkSearch->domain_id = $domainConstraint;
        $fkSearch->$fkField = $value;
        $fkSearch->find(TRUE);
        $record[$fieldName] = $fkSearch->id;
      }
      // Simple lookup without all the fuss about domains
      else {
        $record[$fieldName] = \CRM_Core_DAO::getFieldValue($fkDao, $value, 'id', $fkField);
      }
      unset($record[$key]);
    }
  }

  /**
   * Converts params from flat array e.g. ['GroupName.Fieldname' => value] to the
   * hierarchy expected by the BAO, nested within $params['custom'].
   *
   * @param array $params
   * @param int $entityId
   *
   * @throws \CRM_Core_Exception
   */
  protected function formatCustomParams(&$params, $entityId) {
    $customParams = [];

    foreach ($params as $name => $value) {
      $field = $this->getCustomFieldInfo($name);
      if (!$field) {
        continue;
      }

      if ($field['suffix']) {
        $options = FormattingUtil::getPseudoconstantList($field, $name, $params, $this->getActionName());
        $value = FormattingUtil::replacePseudoconstant($options, $value, TRUE);
      }

      // Null and empty string are interchangeable as far as the custom bao understands
      if (NULL === $value) {
        $value = '';
      }

      // Uglify checkbox values for the sake of CustomField::formatCustomField()
      if ($field['html_type'] === 'CheckBox' && is_array($value)) {
        $value = array_fill_keys($value, TRUE);
      }

      // Match contact id to strings like "user_contact_id"
      // FIXME handle arrays for multi-value contact reference fields, etc.
      if (in_array($field['data_type'], ['ContactReference', 'EntityReference']) && is_string($value)) {
        $value = FormattingUtil::resolveContactID($field['name'], $value);
      }

      \CRM_Core_BAO_CustomField::formatCustomField(
        $field['id'],
        $customParams,
        $value,
        $field['extends'],
        // todo check when this is needed
        NULL,
        $entityId,
        FALSE,
        $this->getCheckPermissions(),
        TRUE
      );
    }

    $params['custom'] = $customParams ?: NULL;
  }

  /**
   * Gets field info needed to save custom data
   *
   * @param string $fieldExpr
   *   Field identifier with possible suffix, e.g. MyCustomGroup.MyField1:label
   * @return array{id: int, name: string, entity: string, suffix: string, html_type: string, data_type: string, extends: string, table_name: string}|NULL
   */
  protected function getCustomFieldInfo(string $fieldExpr): ?array {
    if (!str_contains($fieldExpr, '.')) {
      return NULL;
    }
    [$groupName, $fieldName] = explode('.', $fieldExpr);
    [$fieldName, $suffix] = array_pad(explode(':', $fieldName), 2, NULL);
    foreach (\CRM_Core_BAO_CustomGroup::getAll() as $customGroup) {
      if ($customGroup['name'] === $groupName) {
        foreach ($customGroup['fields'] as $field) {
          if ($field['name'] === $fieldName) {
            $field['custom_field_id'] = $field['id'];
            $field['table_name'] = $customGroup['table_name'];
            $field['extends'] = $customGroup['extends'];
            $field['name'] = "$groupName.$fieldName";
            $field['entity'] = \CRM_Core_BAO_CustomGroup::getEntityFromExtends($customGroup['extends']);
            $field['suffix'] = $suffix;
            return $field;
          }
        }
      }
    }
    return NULL;
  }

  /**
   * Update weights when inserting or updating a sortable entity
   * @param array $record
   * @see SortableEntity
   */
  protected function updateWeight(array &$record) {
    /** @var \CRM_Core_DAO|string $daoName */
    $daoName = CoreUtil::getInfoItem($this->getEntityName(), 'dao');
    $weightField = CoreUtil::getInfoItem($this->getEntityName(), 'order_by');
    $grouping = CoreUtil::getInfoItem($this->getEntityName(), 'group_weights_by');
    $idField = CoreUtil::getIdFieldName($this->getEntityName());
    // If updating an existing record without changing weight, do nothing
    if (!isset($record[$weightField]) && !empty($record[$idField])) {
      return;
    }
    $newWeight = $record[$weightField] ?? NULL;

    $filters = [];
    foreach ($grouping ?? [] as $filter) {
      if (array_key_exists($filter, $record)) {
        $filters[$filter] = $record[$filter];
      }
      elseif (!empty($record[$idField])) {
        $filters[$filter] = $daoName::getDbVal($filter, $record[$idField]);
      }
    }
    // Supply default weight for new record
    if (!isset($record[$weightField]) && empty($record[$idField])) {
      $max = $this->getMaxWeight($daoName, $filters, $weightField);
      $record[$weightField] = $max;
    }
    else {
      $oldWeight = NULL;
      // Look up the old weight using filters (it's only relevant if this record is still within the same filter grouping)
      if (!empty($record[$idField])) {
        $where = [[$idField, '=', $record[$idField]]];
        foreach ($filters as $filter => $value) {
          $where[] = [$filter, '=', $value];
        }
        $oldWeight = civicrm_api4($this->getEntityName(), 'get', [
          'select' => [$weightField],
          'where' => $where,
        ])[0][$weightField] ?? NULL;
      }
      $record[$weightField] = \CRM_Utils_Weight::updateOtherWeights($daoName, $oldWeight, $newWeight, $filters, $weightField);
    }
  }

  /**
   * Looks up max weight for a set of sortable entities
   *
   * Keeps it in memory in case this operation is writing more than one record
   *
   * @param $daoName
   * @param $filters
   * @param $weightField
   * @return int|mixed
   */
  private function getMaxWeight($daoName, $filters, $weightField) {
    ksort($filters);
    $key = $daoName . json_encode($filters);
    if (!isset($this->_maxWeights[$key])) {
      $this->_maxWeights[$key] = \CRM_Utils_Weight::getMax($daoName, $filters, $weightField) + 1;
    }
    else {
      ++$this->_maxWeights[$key];
    }
    return $this->_maxWeights[$key];
  }

}
