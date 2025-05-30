<?php

namespace Civi\Api4\Action\Afform;

use Civi\Afform\Event\AfformEntitySortEvent;
use Civi\Afform\Event\AfformPrefillEvent;
use Civi\Afform\Event\AfformSubmitEvent;
use Civi\Afform\FormDataModel;
use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\File;
use Civi\Api4\Generic\Result;
use Civi\Api4\Utils\CoreUtil;
use CRM_Afform_ExtensionUtil as E;

/**
 * Shared functionality for form submission pre & post processing.
 * @package Civi\Api4\Action\Afform
 *
 * @method $this setFillMode(string $fillMode) Set entity/form fill mode.
 * @method string getFillMode()
 */
abstract class AbstractProcessor extends \Civi\Api4\Generic\AbstractAction {

  /**
   * Form name
   * @var string
   * @required
   */
  protected $name;

  /**
   * Arguments present when loading the form
   * @var array
   */
  protected $args = [];

  /**
   * Mode indicates what is being prefilled.
   *
   * Either the entire form, or a specific entity, or a join for an entity.
   *
   * @var string
   * @options form,entity,join
   */
  protected $fillMode = 'form';

  /**
   * @var array
   */
  protected $_afform;

  /**
   * @var \Civi\Afform\FormDataModel
   *   List of entities declared by this form.
   */
  protected $_formDataModel;

  /**
   * Ids of each autoloaded entity.
   *
   * Each key in the array corresponds to the name of an entity,
   * and the value is an array of arrays
   * (because of `<af-repeat>` all entities are treated as if they may be multi)
   * E.g. $entityIds['Individual1'] = [['id' => 1, 'joins' => ['Email' => [['id' => 1], ['id' => 2]]];
   *
   * @var array
   */
  protected $_entityIds = [];

  protected $_entityValues = [];

  /**
   * @param \Civi\Api4\Generic\Result $result
   * @throws \CRM_Core_Exception
   */
  public function _run(Result $result) {
    $this->_afform = civicrm_api4('Afform', 'get', [
      'select' => ['*', 'submit_currently_open'],
      'where' => [['name', '=', $this->name]],
    ])->first();
    // Either the form doesn't exist or user lacks permission
    if (!$this->_afform) {
      throw new UnauthorizedException(E::ts('You do not have permission to submit this form'), ['show_detailed_error' => TRUE]);
    }
    if (empty($this->_afform['submit_currently_open'])) {
      throw new UnauthorizedException(E::ts('This form is not currently open for submissions.'), ['show_detailed_error' => TRUE]);
    }
    $this->_formDataModel = new FormDataModel($this->_afform['layout']);
    $this->loadEntities();
    $result->exchangeArray($this->processForm());
  }

  /**
   * Load all entities
   */
  protected function loadEntities() {
    // When loading a single join for an entity, only that entity needs to be processed
    if ($this->fillMode === 'join') {
      $entityNames = array_keys(array_intersect_key($this->args, $this->_formDataModel->getEntities()));
    }
    // When loading the whole form, process every entity in order of dependencies.
    // also when filling a single entity from an autocomplete, as that may affect other entities.
    else {
      $sorter = new AfformEntitySortEvent($this->_afform, $this->_formDataModel, $this);
      \Civi::dispatcher()->dispatch('civi.afform.sort.prefill', $sorter);
      $entityNames = $sorter->getSortedEnties();
    }

    foreach ($entityNames as $entityName) {
      $ids = (array) ($this->args[$entityName] ?? []);

      $entity = $this->_formDataModel->getEntity($entityName);
      $this->_entityIds[$entityName] = [];
      $idField = CoreUtil::getIdFieldName($entity['type']);

      foreach ($ids as $num => $id) {
        // Url args may be scalar - convert to array format
        if (is_scalar($id)) {
          $ids[$num] = [$idField => $id];
        }
      }
      if ($ids) {
        // Load entity join via autocomplete e.g. Address.id
        if ($this->fillMode === 'join') {
          $this->loadJoin($entity, $ids);
        }
        // Load entity via url arg or autocomplete input
        else {
          $matchField = self::getNestedKey($ids) ?: $idField;
          $matchFieldDefn = $this->_formDataModel->getField($entity['type'], $matchField, 'create');
          $autofillMode = $matchFieldDefn['input_attrs']['autofill'] ?? NULL;
          // If 'update' (or 'create' in special cases like 'template_id') is allowed, load entity.
          if (!empty($entity['actions'][$autofillMode])) {
            if (!empty($entity['url-autofill']) || isset($entity['fields'][$matchField])) {
              $this->loadEntity($entity, $ids, $autofillMode);
            }
          }
        }
      }
      $event = new AfformPrefillEvent($this->_afform, $this->_formDataModel, $this, $entity['type'], $entityName, $this->_entityIds);
      \Civi::dispatcher()->dispatch('civi.afform.prefill', $event);
    }
  }

  /**
   * Fetch all data needed to display a given entity on this form
   *
   * @param array $entity
   *   Afform entity definition
   * @param array[] $values
   *   Array of value arrays. Each must be the primary key, e.g.
   *   ```
   *   [
   *     ['id' => 123],
   *     ['id' => 456],
   *   ]
   *   ```
   *   In theory we could include other stuff in the values, but it's not currently supported.
   * @param string $mode
   *   'update' or 'create' ('create' is only used in special cases like `Event.template_id`)
   */
  public function loadEntity(array $entity, array $values, string $mode = 'update'): void {
    // Backward-compat, prior to 5.78 $values was an array of ids
    if (isset($values[0]) && is_scalar($values[0])) {
      \CRM_Core_Error::deprecatedWarning("Afform.loadEntity should be called with an array of values (array of ids was provided for {$entity['type']})");
      $idField = CoreUtil::getIdFieldName($entity['type']);
      foreach ($values as $key => $value) {
        $values[$key] = [$idField => $value];
      }
    }

    // Limit number of records based on af-repeat settings
    // If 'min' is set then it is repeatable, and max will either be a number or NULL for unlimited.
    if (isset($entity['min']) && isset($entity['max'])) {
      foreach (array_keys($values) as $count => $index) {
        if ($count >= $entity['max']) {
          unset($values[$index]);
        }
      }
    }
    $matchField = self::getNestedKey($values);
    if (!$matchField) {
      return;
    }
    $keys = array_combine(array_keys($values), array_column($values, $matchField));
    // In create mode, use id as the key
    $keyField = $mode === 'create' ? CoreUtil::getIdFieldName($entity['name']) : $matchField;

    if ($keys && !empty($entity['fields'][$keyField]['defn']['saved_search'])) {
      $keys = $this->validateBySavedSearch($entity['name'], $entity['type'], $keys, $matchField);
    }
    if (!$keys) {
      return;
    }
    $result = $this->apiGet($entity['name'], $entity['type'], $entity['fields'], $keyField, [
      'where' => [[$keyField, 'IN', $keys]],
    ]);
    $idField = CoreUtil::getIdFieldName($entity['type']);
    foreach ($keys as $index => $key) {
      $entityId = $result[$key][$idField] ?? NULL;
      // In create mode, swap id with matchField
      if ($mode === 'create') {
        if (isset($result[$key][$idField])) {
          $result[$key][$matchField] = $result[$key][$idField];
          unset($result[$key][$idField]);
        }
      }
      else {
        $this->_entityIds[$entity['name']][$index] = [
          $idField => $entityId,
          'joins' => [],
        ];
      }
      if (!empty($result[$key])) {
        $data = ['fields' => $result[$key]];
        foreach ($entity['joins'] ?? [] as $joinEntity => $join) {
          $joinAllowedAction = self::getJoinAllowedAction($entity, $joinEntity);
          if ($joinAllowedAction['update']) {
            $data['joins'][$joinEntity] = $this->loadJoins($joinEntity, $entity, $entityId, $index);
          }
        }
        $this->_entityValues[$entity['name']][$index] = $data;
      }
    }
  }

  /**
   * Finds all joins after loading an entity.
   */
  public function loadJoins(string $joinEntity, array $afEntity, $entityId, $index): array {
    $joinIdField = CoreUtil::getIdFieldName($joinEntity);
    $join = $afEntity['joins'][$joinEntity];
    $multipleLocationBlocks = is_array($join['data']['location_type_id'] ?? NULL);
    $limit = 1;
    // Repeating blocks - set limit according to `max`, if set, otherwise 0 for unlimited
    if (!empty($join['af-repeat'])) {
      $limit = $join['max'] ?? 0;
    }
    // Remove limit when handling multiple location blocks
    if ($multipleLocationBlocks) {
      $limit = 0;
    }
    $where = self::getJoinWhereClause($this->_formDataModel, $afEntity['name'], $joinEntity, $entityId);
    if ($where) {
      $joinResult = $this->getJoinResult($afEntity, $joinEntity, $join, $where, $limit);
    }
    else {
      $joinResult = [];
    }
    // Sort into multiple location blocks
    if ($multipleLocationBlocks) {
      $items = array_column($joinResult, NULL, 'location_type_id');
      $joinResult = [];
      foreach ($join['data']['location_type_id'] as $locationType) {
        $joinResult[] = $items[$locationType] ?? NULL;
      }
    }
    $this->_entityIds[$afEntity['name']][$index]['joins'][$joinEntity] = \CRM_Utils_Array::filterColumns($joinResult, [$joinIdField]);
    return array_values($joinResult);
  }

  /**
   * Directly loads a join entity e.g. from an autocomplete field in the join block.
   */
  private function loadJoin(array $afEntity, array $values): array {
    $joinResult = [];
    foreach ($values as $entityIndex => $value) {
      foreach ($value['joins'] as $joinEntity => $joins) {
        $joinIdField = CoreUtil::getIdFieldName($joinEntity);
        $joinInfo = $afEntity['joins'][$joinEntity] ?? [];
        foreach ($joins as $joinIndex => $join) {
          foreach ($join as $fieldName => $fieldValue) {
            if (!empty($joinInfo['fields'][$fieldName])) {
              $where = [[$fieldName, '=', $fieldValue]];
              $joinResult = $this->getJoinResult($afEntity, $joinEntity, $joinInfo, $where, 1);
              $this->_entityIds[$afEntity['name']][$entityIndex]['joins'][$joinEntity] = \CRM_Utils_Array::filterColumns($joinResult, [$joinIdField]);
              $this->_entityValues[$afEntity['name']][$entityIndex]['joins'][$joinEntity] = array_values($joinResult);
            }
          }
        }
      }
    }
    return array_values($joinResult);
  }

  public function getJoinResult(array $afEntity, string $joinEntity, array $join, array $where, int $limit): array {
    $joinIdField = CoreUtil::getIdFieldName($joinEntity);
    $joinResult = $this->apiGet($afEntity['name'], $joinEntity, $join['fields'] + ($join['data'] ?? []), $joinIdField, [
      'where' => $where,
      'limit' => $limit,
      'orderBy' => self::getEntityField($joinEntity, 'is_primary') ? ['is_primary' => 'DESC'] : [],
    ]);
    // Validate autocomplete fields
    if ($joinResult && !empty($entity['joins'][$joinEntity]['fields'][$joinIdField]['defn']['saved_search'])) {
      $keys = array_combine(array_keys($joinResult), array_column($joinResult, $joinIdField));
      $keys = $this->validateBySavedSearch($entity['name'], $joinEntity, $keys, $joinIdField);
      $joinResult = array_intersect_key($joinResult, $keys);
    }
    return $joinResult;
  }

  /**
   * Delegated by loadEntity to call API.get and fill in additional info
   *
   * @param string $afEntityName
   *   e.g. Individual1
   * @param string $apiEntityName
   *   Not necessarily the api of the afEntity, in the case of joins it will be different.
   * @param array $entityFields
   * @param string $keyField
   * @param array $params
   * @return array
   */
  private function apiGet($afEntityName, $apiEntityName, $entityFields, string $keyField, $params) {
    $api4 = $this->_formDataModel->getSecureApi4($afEntityName);
    $idField = CoreUtil::getIdFieldName($apiEntityName);
    // Ensure 'id' is selected
    $params['select'] = array_unique(array_merge([$idField], array_keys($entityFields)));
    $result = (array) $api4($apiEntityName, 'get', $params)->indexBy($keyField);
    // Fill additional info about file fields
    $fileFields = $this->getFileFields($apiEntityName, $entityFields);
    foreach ($fileFields as $fieldName => $fieldDefn) {
      foreach ($result as &$item) {
        if (!empty($item[$fieldName])) {
          $fileInfo = $this->getFileInfo($item[$fieldName], $afEntityName);
          $item[$fieldName] = $fileInfo;
        }
      }
    }
    return $result;
  }

  protected function getFileInfo(int $fileId, string $afEntityName):? array {
    $select = ['id', 'file_name', 'icon'];
    if ($this->canViewFileAttachments($afEntityName)) {
      $select[] = 'url';
    }
    return File::get(FALSE)
      ->setSelect($select)
      ->addWhere('id', '=', $fileId)
      ->execute()->first();
  }

  private function canViewFileAttachments(string $afEntityName): bool {
    $afEntity = $this->_formDataModel->getEntity($afEntityName);
    return ($afEntity['security'] === 'FBAC' || \CRM_Core_Permission::check('access uploaded files'));
  }

  protected static function getFileFields($entityName, $entityFields): array {
    if (!$entityFields) {
      return [];
    }
    return civicrm_api4($entityName, 'getFields', [
      'checkPermissions' => FALSE,
      'action' => 'create',
      'select' => ['name'],
      'where' => [['name', 'IN', array_keys($entityFields)], ['fk_entity', '=', 'File']],
    ])->column('name', 'name');
  }

  /**
   * Validate that given id(s) are actually returned by the Autocomplete API
   *
   * @param string $afEntityName
   * @param string $apiEntity
   * @param array $ids
   * @param string $matchField
   * @return array
   * @throws \CRM_Core_Exception
   */
  private function validateBySavedSearch(string $afEntityName, string $apiEntity, array $ids, string $matchField) {
    $fetched = civicrm_api4($apiEntity, 'autocomplete', [
      'ids' => $ids,
      'formName' => 'afform:' . $this->name,
      'fieldName' => $afEntityName . ':' . $matchField,
    ])->indexBy($matchField);
    $validIds = [];
    // Preserve keys
    foreach ($ids as $index => $id) {
      if (isset($fetched[$id])) {
        $validIds[$index] = $id;
      }
    }
    return $validIds;
  }

  /**
   * @return array
   */
  abstract protected function processForm();

  /**
   * Gets the clause for looking up join entities, or NULL if not available.
   *
   * Joins can come in two styles:
   *  - Forward FK e.g. Event.loc_block_id => LocBlock
   *  - Reverse FK e.g. Contact <= Email.contact_id
   *
   * @param \Civi\Afform\FormDataModel $formDataModel
   * @param string $mainEntityName
   * @param string $joinEntityType
   * @param int|string $mainEntityId
   * @return array[]
   * @throws \CRM_Core_Exception
   */
  protected static function getJoinWhereClause(FormDataModel $formDataModel, string $mainEntityName, string $joinEntityType, $mainEntityId): ?array {
    $entity = $formDataModel->getEntity($mainEntityName);
    $mainEntityType = CoreUtil::isContact($entity['type']) ? 'Contact' : $entity['type'];
    $params = [];

    // Forward FK e.g. Event.loc_block_id => LocBlock
    $forwardFkField = self::getFkField($mainEntityType, $joinEntityType);
    if ($forwardFkField) {
      $joinIdField = $forwardFkField['fk_column'];
      $mainEntityJoinValue = civicrm_api4($mainEntityType, 'get', [
        'checkPermissions' => FALSE,
        'where' => [['id', '=', $mainEntityId]],
        'select' => [$forwardFkField['name']],
      ])->first();
      if (!empty($mainEntityJoinValue[$forwardFkField['name']])) {
        $params[] = [$joinIdField, '=', $mainEntityJoinValue[$forwardFkField['name']] ?? 0];
        return $params;
      }
      return NULL;
    }

    // Reverse FK e.g. Contact <= Email.contact_id
    // Add data as clauses e.g. `is_primary: true`
    foreach ($entity['joins'][$joinEntityType]['data'] ?? [] as $key => $val) {
      $op = is_array($val) ? 'IN' : '=';
      $params[] = [$key, $op, $val];
    }

    $reverseFkField = self::getFkField($joinEntityType, $mainEntityType);
    if ($reverseFkField) {
      $params[] = [$reverseFkField['name'], '=', $mainEntityId];
      // Handle dynamic foreign keys e.g. `entity_table` + `entity_id`
      if (!empty($reverseFkField['dfk_entities'])) {
        $params[] = [$reverseFkField['input_attrs']['control_field'], '=', array_search($mainEntityType, $reverseFkField['dfk_entities'])];
      }
    }
    return $params;
  }

  protected static function getFkField($mainEntity, $otherEntity): ?array {
    $fkEntities = [$otherEntity];
    if ($otherEntity !== 'Contact' && CoreUtil::isContact($otherEntity)) {
      $fkEntities[] = 'Contact';
    }
    foreach (self::getEntityFields($mainEntity) as $field) {
      if ($field['type'] === 'Field' && empty($field['custom_field_id']) &&
        (in_array($field['fk_entity'], $fkEntities, TRUE) || array_intersect($fkEntities, $field['dfk_entities'] ?? []))
      ) {
        return $field;
      }
    }
    return NULL;
  }

  /**
   * Get field definition for a given entity
   *
   * @param string $entityName
   * @param string $fieldName
   * @return array|null
   * @throws \CRM_Core_Exception
   */
  public static function getEntityField(string $entityName, string $fieldName) {
    return self::getEntityFields($entityName)[$fieldName] ?? NULL;
  }

  /**
   * Get all fields for a given entity
   *
   * @param string $entityName
   * @return array
   * @throws \CRM_Core_Exception
   */
  public static function getEntityFields(string $entityName): array {
    \Civi::$statics[__CLASS__][__FUNCTION__][$entityName] ??= (array) civicrm_api4($entityName, 'getFields', [
      'checkPermissions' => FALSE,
      'action' => 'create',
    ])->indexBy('name');
    return \Civi::$statics[__CLASS__][__FUNCTION__][$entityName];
  }

  /**
   * @return array
   */
  public function getArgs():array {
    return $this->args;
  }

  /**
   * @param array $args
   * @return $this
   */
  public function setArgs(array $args) {
    $this->args = $args;
    return $this;
  }

  /**
   * @return string
   */
  public function getName():string {
    return $this->name;
  }

  /**
   * @param string $name
   * @return $this
   */
  public function setName(string $name) {
    $this->name = $name;
    return $this;
  }

  /**
   * Replace Entity reference fields with the id of the referenced entity.
   * @param string $entityName
   * @param $records
   */
  protected function replaceReferences($entityName, $records) {
    $entityNames = array_diff(array_keys($this->_entityIds), [$entityName]);
    $entityType = $this->_formDataModel->getEntity($entityName)['type'];
    foreach ($records as $key => $record) {
      foreach ($record['fields'] as $field => $value) {
        if (array_intersect($entityNames, (array) $value) && $this->getEntityField($entityType, $field)['input_type'] === 'EntityRef') {
          if (is_array($value)) {
            foreach ($value as $val) {
              if (in_array($val, $entityNames, TRUE)) {
                $refIds = array_filter(array_column($this->_entityIds[$val], 'id'));
                // replace the reference element in the field value array with found id(s)
                $refPosition = array_search($val, $records[$key]['fields'][$field]);
                array_splice($records[$key]['fields'][$field], $refPosition, 1, $refIds);
              }
            }
          }
          else {
            $records[$key]['fields'][$field] = $this->_entityIds[$value][0]['id'] ?? NULL;
          }
        }
      }
    }
    return $records;
  }

  /**
   * @param array $records
   * @param string $entityName
   */
  protected function fillIdFields(array &$records, string $entityName): void {
    foreach ($records as $index => &$record) {
      if (empty($record['fields']['id']) && !empty($this->_entityIds[$entityName][$index]['id'])) {
        $record['fields']['id'] = $this->_entityIds[$entityName][$index]['id'];
      }
    }
  }

  /**
   * Recursively add entity IDs to the values.
   */
  protected function combineValuesAndIds($values, $ids, $isJoin = FALSE) {
    $combined = [];
    $values += array_fill_keys(array_keys($ids), []);
    foreach ($values as $name => $value) {
      foreach ($value as $idx => $val) {
        $idData = $ids[$name][$idx] ?? [];
        if (!$isJoin) {
          $idData['joins'] = $this->combineValuesAndIds($val['joins'] ?? [], $idData['joins'] ?? [], TRUE);
        }
        // $item = array_merge($isJoin ? $val : ($val['fields'] ?? []), $idData);
        if (is_array($val) || empty($val)) {
          $item = array_merge(($val ?? []), $idData);
          $combined[$name][$idx] = $item;
        }
      }
    }
    return $combined;
  }

  /**
   * Preprocess submitted values
   */
  public function preprocessSubmittedValues(array $submittedValues) {
    $entityValues = [];
    foreach ($this->_formDataModel->getEntities() as $entityName => $entity) {
      $entityValues[$entityName] = [];
      // Gather submitted field values from $values['fields'] and sub-entities from $values['joins']
      $submittableFields = $this->getSubmittableFields($entity['fields']);
      $fileFields = $this->getFileFields($entity['type'], $submittableFields);
      foreach ($submittedValues[$entityName] ?? [] as $values) {
        // Use default values from DisplayOnly fields + submittable fields on the form
        $values['fields'] = $this->getForcedDefaultValues($entity['fields']) +
          array_intersect_key($values['fields'] ?? [], $submittableFields);
        // Special handling for file fields
        foreach ($fileFields as $fileFieldName) {
          if (isset($values['fields'][$fileFieldName]) && is_array($values['fields'][$fileFieldName])) {
            // Keep file id
            if (isset($values['fields'][$fileFieldName]['id'])) {
              $values['fields'][$fileFieldName] = $values['fields'][$fileFieldName]['id'];
            }
            // File was deleted
            elseif (array_key_exists('id', $values['fields'][$fileFieldName])) {
              $values['fields'][$fileFieldName] = '';
            }
          }
        }
        // Only accept joins set on the form
        $values['joins'] = array_intersect_key($values['joins'] ?? [], $entity['joins']);
        foreach ($values['joins'] as $joinEntity => &$joinValues) {
          // Only accept values from join fields on the form
          $idField = CoreUtil::getIdFieldName($joinEntity);
          $allowedFields = $this->getSubmittableFields($entity['joins'][$joinEntity]['fields'] ?? []);
          $allowedFields[$idField] = TRUE;
          $fileFields = $this->getFileFields($joinEntity, $allowedFields);
          // Enforce the limit set by join[max]
          $joinValues = array_slice($joinValues, 0, $entity['joins'][$joinEntity]['max'] ?? NULL);
          foreach ($joinValues as $index => $vals) {
            // $vals could be NULL when a join is in a repeating group.
            // Then $joinValues[0] = null and $joinValues[1] = array
            if ($vals === NULL) {
              unset($joinValues[$index]);
              continue;
            }
            // As with the main entity, use default values from DisplayOnly fields + values from submittable fields
            $joinValues[$index] = $this->getForcedDefaultValues($entity['joins'][$joinEntity]['fields'] ?? []);
            $joinValues[$index] += array_intersect_key($vals, $allowedFields);
            // Unset prefilled file fields
            foreach ($fileFields as $fileFieldName) {
              if (isset($joinValues[$index][$fileFieldName]) && is_array($joinValues[$index][$fileFieldName])) {
                // File was unchanged
                if (isset($joinValues[$index][$fileFieldName]['file_name'])) {
                  unset($joinValues[$index][$fileFieldName]);
                }
                // File was deleted
                elseif (array_key_exists('file_name', $joinValues[$index][$fileFieldName])) {
                  $joinValues[$index][$fileFieldName] = '';
                }
              }
              // Creating new record, add placeholder value so the file upload will have an id
              if (empty($joinValues[$index][$idField])) {
                $joinValues[$index][$fileFieldName] = '';
              }
            }

            // Merge in pre-set data
            foreach ($entity['joins'][$joinEntity]['data'] ?? [] as $dataKey => $dataVal) {
              // For multiple location blocks, values will be in an array (see FormDataModel::parseFields)
              if (is_array($dataVal) && array_key_exists($index, $dataVal)) {
                $joinValues[$index][$dataKey] = $dataVal[$index];
              }
              else {
                $joinValues[$index][$dataKey] = $dataVal;
              }
            }
          }
        }
        $entityValues[$entityName][] = $values;
      }
      if (!empty($entity['data'])) {
        // If no submitted values but data exists, fill the minimum number of records
        for ($index = 0; $index < $entity['min']; $index++) {
          $entityValues[$entityName][$index] ??= ['fields' => []];
        }
        // Predetermined values override submitted values
        foreach ($entityValues[$entityName] as $index => $vals) {
          $entityValues[$entityName][$index]['fields'] = $entity['data'] + $vals['fields'];
        }
      }
    }

    return $entityValues;
  }

  /**
   * Return names of submittable fields (not DisplayOnly)
   *
   * TODO: Should filter out conditionally hidden fields too.
   *
   * @param array $fields
   * @return array
   */
  protected function getSubmittableFields(array $fields): array {
    return array_filter($fields, function ($field) {
      $inputType = $field['defn']['input_type'] ?? NULL;
      return $inputType !== 'DisplayOnly';
    });
  }

  /**
   * Get default values from DisplayOnly fields
   *
   * @param array $fields
   * @return array
   */
  protected function getForcedDefaultValues(array $fields): array {
    $values = [];
    foreach ($fields as $field) {
      $inputType = $field['defn']['input_type'] ?? NULL;
      if ($inputType === 'DisplayOnly' && isset($field['defn']['afform_default'])) {
        $values[$field['name']] = $field['defn']['afform_default'];
      }
    }
    return $values;
  }

  /**
   * Process form data
   */
  public function processFormData(array $entityValues) {
    $entityWeights = \Civi\Afform\Utils::getEntityWeights($this->_formDataModel->getEntities(), $entityValues);
    foreach ($entityWeights as $entityName) {
      $entityType = $this->_formDataModel->getEntity($entityName)['type'];
      $records = $this->replaceReferences($entityName, $entityValues[$entityName]);
      $this->fillIdFields($records, $entityName);
      $event = new AfformSubmitEvent($this->_afform, $this->_formDataModel, $this, $records, $entityType, $entityName, $this->_entityIds);
      \Civi::dispatcher()->dispatch('civi.afform.submit', $event);
    }
  }

  /**
   * Given a nested array like `[0 => ['id' => 123]]`,
   * this returns the first key from the inner array, e.g. `'id'`.
   * @param array $values
   * @return int|string|null
   */
  protected static function getNestedKey(array $values) {
    $firstValue = \CRM_Utils_Array::first(array_filter($values));
    return is_array($firstValue) && $firstValue ? array_keys($firstValue)[0] : NULL;
  }

  /**
   * Function to get allowed action of a join entity
   *
   * @param array $mainEntity
   * @param string $joinEntityName
   *
   * @return array{update: bool, delete: bool}
   */
  public static function getJoinAllowedAction(array $mainEntity, string $joinEntityName) {
    $actions = ["update" => TRUE, "delete" => TRUE];
    if (array_key_exists('actions', $mainEntity['joins'][$joinEntityName])) {
      $actions = array_merge($actions, $mainEntity['joins'][$joinEntityName]['actions']);
    }

    return $actions;
  }

}
