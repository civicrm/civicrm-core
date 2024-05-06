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
    $sorter = new AfformEntitySortEvent($this->_afform, $this->_formDataModel, $this);
    \Civi::dispatcher()->dispatch('civi.afform.sort.prefill', $sorter);
    $sortedEntities = $sorter->getSortedEnties();

    // if submission id is passed then we should display the submission data
    if (!empty($this->args['sid'])) {
      $this->prePopulateSubmissionData($sortedEntities);
      return;
    }

    foreach ($sortedEntities as $entityName) {
      $entity = $this->_formDataModel->getEntity($entityName);
      $this->_entityIds[$entityName] = [];
      $matchField = $this->matchField ?? CoreUtil::getIdFieldName($entity['type']);
      $matchFieldDefn = $this->_formDataModel->getField($entity['type'], $matchField, 'create');
      if (!empty($entity['actions'][$matchFieldDefn['input_attrs']['autofill']])) {
        if (
          !empty($this->args[$entityName]) &&
          (!empty($entity['url-autofill']) || isset($entity['fields'][$matchField]))
        ) {
          $ids = (array) $this->args[$entityName];
          $this->loadEntity($entity, $ids);
        }
      }
      $event = new AfformPrefillEvent($this->_afform, $this->_formDataModel, $this, $entity['type'], $entityName, $this->_entityIds);
      \Civi::dispatcher()->dispatch('civi.afform.prefill', $event);
    }
  }

  /**
   * Load the data from submission table
   */
  protected function prePopulateSubmissionData($sortedEntities) {
    // if submission id is passed then get the data from submission
    // we should prepopulate only pending submissions
    $afformSubmissionData = \Civi\Api4\AfformSubmission::get(FALSE)
      ->addSelect('data')
      ->addWhere('id', '=', $this->args['sid'])
      ->addWhere('afform_name', '=', $this->name)
      ->execute()->first();

    // do nothing and return early
    if (empty($afformSubmissionData)) {
      return;
    }

    foreach ($sortedEntities as $entityName) {
      foreach ($afformSubmissionData['data'] as $entity => $data) {
        if ($entity == $entityName) {
          $this->_entityValues[$entityName] = $data;
        }
      }
    }
  }

  /**
   * Fetch all data needed to display a given entity on this form
   *
   * @param array $entity
   * @param array $ids
   */
  public function loadEntity(array $entity, array $ids) {
    // Limit number of records based on af-repeat settings
    // If 'min' is set then it is repeatable, and max will either be a number or NULL for unlimited.
    if (isset($entity['min']) && isset($entity['max'])) {
      foreach (array_keys($ids) as $index) {
        if ($index >= $entity['max']) {
          unset($ids[$index]);
        }
      }
    }

    $api4 = $this->_formDataModel->getSecureApi4($entity['name']);
    $idField = CoreUtil::getIdFieldName($entity['type']);
    if ($ids && !empty($entity['fields'][$idField]['defn']['saved_search'])) {
      $ids = $this->validateBySavedSearch($entity, $ids);
    }
    if (!$ids) {
      return;
    }
    $result = $this->apiGet($api4, $entity['type'], $entity['fields'], [
      'where' => [[$idField, 'IN', $ids]],
    ]);
    foreach ($ids as $index => $id) {
      $this->_entityIds[$entity['name']][$index] = [
        $idField => isset($result[$id]) ? $id : NULL,
        'joins' => [],
      ];
      if (isset($result[$id])) {
        $data = ['fields' => $result[$id]];
        foreach ($entity['joins'] ?? [] as $joinEntity => $join) {
          $joinIdField = CoreUtil::getIdFieldName($joinEntity);
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
          $where = self::getJoinWhereClause($this->_formDataModel, $entity['name'], $joinEntity, $id);
          if ($where) {
            $joinResult = $this->apiGet($api4, $joinEntity, $join['fields'] + ($join['data'] ?? []), [
              'where' => $where,
              'limit' => $limit,
              'orderBy' => self::getEntityField($joinEntity, 'is_primary') ? ['is_primary' => 'DESC'] : [],
            ]);
          }
          else {
            $joinResult = [];
          }
          // Sort into multiple location blocks
          if ($multipleLocationBlocks) {
            $items = array_column($joinResult, NULL, 'location_type_id');
            $joinResult = [];
            foreach ($join['data']['location_type_id'] as $locationType) {
              $joinResult[] = $items[$locationType] ?? [];
            }
          }
          $data['joins'][$joinEntity] = array_values($joinResult);
          $this->_entityIds[$entity['name']][$index]['joins'][$joinEntity] = \CRM_Utils_Array::filterColumns($joinResult, [$joinIdField]);
        }
        $this->_entityValues[$entity['name']][$index] = $data;
      }
    }
  }

  /**
   * Delegated by loadEntity to call API.get and fill in additioal info
   *
   * @param $api4
   * @param $entityName
   * @param $entityFields
   * @param $params
   * @return array
   */
  private function apiGet($api4, $entityName, $entityFields, $params) {
    $idField = CoreUtil::getIdFieldName($entityName);
    $params['select'] = array_unique(array_merge([$idField], array_keys($entityFields)));
    $result = (array) $api4($entityName, 'get', $params)->indexBy($idField);
    // Fill additional info about file fields
    $fileFields = $this->getFileFields($entityName, $entityFields);
    foreach ($fileFields as $fieldName => $fieldDefn) {
      foreach ($result as &$item) {
        if (!empty($item[$fieldName])) {
          $fileInfo = File::get(FALSE)
            ->addSelect('file_name', 'icon')
            ->addWhere('id', '=', $item[$fieldName])
            ->execute()->first();
          $item[$fieldName] = $fileInfo;
        }
      }
    }
    return $result;
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
   * @param $entity
   * @param array $ids
   * @return array
   * @throws \CRM_Core_Exception
   */
  private function validateBySavedSearch($entity, array $ids) {
    $idField = CoreUtil::getIdFieldName($entity['type']);
    $fetched = civicrm_api4($entity['type'], 'autocomplete', [
      'ids' => $ids,
      'formName' => 'afform:' . $this->name,
      'fieldName' => $entity['name'] . ':' . $idField,
    ])->indexBy($idField);
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
    foreach (self::getEntityFields($mainEntity) as $field) {
      if ($field['type'] === 'Field' && empty($field['custom_field_id']) &&
        ($field['fk_entity'] === $otherEntity || in_array($otherEntity, $field['dfk_entities'] ?? [], TRUE))
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
        $item = array_merge(($val ?? []), $idData);
        $combined[$name][$idx] = $item;
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
      $fileFields = $this->getFileFields($entity['type'], $entity['fields']);
      // Gather submitted field values from $values['fields'] and sub-entities from $values['joins']
      foreach ($submittedValues[$entityName] ?? [] as $values) {
        // Only accept values from fields on the form
        $values['fields'] = array_intersect_key($values['fields'] ?? [], $entity['fields']);
        // Unset prefilled file fields
        foreach ($fileFields as $fileFieldName) {
          if (isset($values['fields'][$fileFieldName]) && is_array($values['fields'][$fileFieldName])) {
            // File was unchanged
            if (isset($values['fields'][$fileFieldName]['file_name'])) {
              unset($values['fields'][$fileFieldName]);
            }
            // File was deleted
            elseif (array_key_exists('file_name', $values['fields'][$fileFieldName])) {
              $values['fields'][$fileFieldName] = '';
            }
          }
        }
        // Only accept joins set on the form
        $values['joins'] = array_intersect_key($values['joins'] ?? [], $entity['joins']);
        foreach ($values['joins'] as $joinEntity => &$joinValues) {
          // Only accept values from join fields on the form
          $idField = CoreUtil::getIdFieldName($joinEntity);
          $allowedFields = $entity['joins'][$joinEntity]['fields'] ?? [];
          $allowedFields[$idField] = TRUE;
          $fileFields = $this->getFileFields($joinEntity, $allowedFields);
          // Enforce the limit set by join[max]
          $joinValues = array_slice($joinValues, 0, $entity['joins'][$joinEntity]['max'] ?? NULL);
          foreach ($joinValues as $index => $vals) {
            $joinValues[$index] = array_intersect_key($vals, $allowedFields);
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
            $joinValues[$index] = array_merge($joinValues[$index], $entity['joins'][$joinEntity]['data'] ?? []);
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

}
