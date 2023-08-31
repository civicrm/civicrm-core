<?php

namespace Civi\Api4\Action\Afform;

use Civi\Afform\Event\AfformEntitySortEvent;
use Civi\Afform\Event\AfformPrefillEvent;
use Civi\Afform\FormDataModel;
use Civi\API\Exception\UnauthorizedException;
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
   * E.g. $entityIds['Individual1'] = [['id' => 1, '_joins' => ['Email' => [['id' => 1], ['id' => 2]]];
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
      'where' => [['name', '=', $this->name], ['submit_currently_open', '=', TRUE]],
    ])->first();
    if (!$this->_afform) {
      // Either the form doesn't exist, user lacks permission,
      // or submit_currently_open = false.
      throw new UnauthorizedException(E::ts('You do not have permission to submit this form'));
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
      'where' => [['id', 'IN', $ids]],
    ]);
    foreach ($ids as $index => $id) {
      $this->_entityIds[$entity['name']][$index] = [
        $idField => isset($result[$id]) ? $id : NULL,
        '_joins' => [],
      ];
      if (isset($result[$id])) {
        $data = ['fields' => $result[$id]];
        foreach ($entity['joins'] ?? [] as $joinEntity => $join) {
          $joinIdField = CoreUtil::getIdFieldName($joinEntity);
          $data['joins'][$joinEntity] = array_values($this->apiGet($api4, $joinEntity, $join['fields'], [
            'where' => self::getJoinWhereClause($this->_formDataModel, $entity['name'], $joinEntity, $id),
            'limit' => !empty($join['af-repeat']) ? $join['max'] ?? 0 : 1,
            'orderBy' => self::getEntityField($joinEntity, 'is_primary') ? ['is_primary' => 'DESC'] : [],
          ]));
          $this->_entityIds[$entity['name']][$index]['_joins'][$joinEntity] = \CRM_Utils_Array::filterColumns($data['joins'][$joinEntity], [$joinIdField]);
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
    // Check for file fields
    $fieldInfo = civicrm_api4($entityName, 'getFields', [
      'checkPermissions' => FALSE,
      'action' => 'create',
      'select' => ['name', 'fk_entity'],
      'where' => [['name', 'IN', array_keys($entityFields)]],
    ])->indexBy('name');
    // Fill additional info about file fields
    foreach ($fieldInfo as $fieldName => $fieldDefn) {
      if ($fieldDefn['fk_entity'] === 'File') {
        foreach ($result as &$item) {
          if (!empty($item[$fieldName])) {
            // Fall back on APIv3 until we have an attachment API for v4.
            $fileInfo = \CRM_Utils_Array::filterColumns(civicrm_api3('Attachment', 'get', [
              'id' => $item[$fieldName],
            ])['values'], ['name', 'icon']);
            $item[$fieldName] = \CRM_Utils_Array::first($fileInfo);
          }
        }
      }
    }
    return $result;
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
   * @param \Civi\Afform\FormDataModel $formDataModel
   * @param string $mainEntityName
   * @param string $joinEntityType
   * @param int|string $mainEntityId
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected static function getJoinWhereClause(FormDataModel $formDataModel, string $mainEntityName, string $joinEntityType, $mainEntityId) {
    $entity = $formDataModel->getEntity($mainEntityName);
    $mainEntityType = $entity['type'];
    $params = [];

    // Add data as clauses e.g. `is_primary: true`
    foreach ($entity['joins'][$joinEntityType]['data'] ?? [] as $key => $val) {
      $params[] = [$key, '=', $val];
    }

    // Figure out the FK field between the join entity and the main entity
    if (self::getEntityField($joinEntityType, 'entity_id')) {
      $params[] = ['entity_id', '=', $mainEntityId];
      if (self::getEntityField($joinEntityType, 'entity_table')) {
        $params[] = ['entity_table', '=', CoreUtil::getTableName($mainEntityType)];
      }
    }
    else {
      $mainEntityField = \CRM_Core_DAO_AllCoreTables::convertEntityNameToLower($mainEntityType) . '_id';
      $params[] = [$mainEntityField, '=', $mainEntityId];
    }
    return $params;
  }

  /**
   * Get field definition for a given entity
   *
   * @param $entityName
   * @param $fieldName
   * @return array|null
   * @throws \CRM_Core_Exception
   */
  public static function getEntityField($entityName, $fieldName) {
    if (!isset(\Civi::$statics[__CLASS__][__FUNCTION__][$entityName])) {
      $fields = civicrm_api4($entityName, 'getFields', [
        'checkPermissions' => FALSE,
        'action' => 'create',
      ]);
      \Civi::$statics[__CLASS__][__FUNCTION__][$entityName] = $fields->indexBy('name');
    }
    return \Civi::$statics[__CLASS__][__FUNCTION__][$entityName][$fieldName] ?? NULL;
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

}
