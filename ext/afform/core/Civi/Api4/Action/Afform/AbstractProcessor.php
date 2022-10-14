<?php

namespace Civi\Api4\Action\Afform;

use Civi\Afform\FormDataModel;
use Civi\Api4\Generic\Result;
use Civi\Api4\Utils\CoreUtil;

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
   * Used by prefill action to indicate if the entire form or just one entity is being filled.
   * @var string
   * @options form,entity
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
    // This will throw an exception if the form doesn't exist or user lacks permission
    $this->_afform = (array) civicrm_api4('Afform', 'get', ['where' => [['name', '=', $this->name]]], 0);
    $this->_formDataModel = new FormDataModel($this->_afform['layout']);
    $this->loadEntities();
    $result->exchangeArray($this->processForm());
  }

  /**
   * Load all entities
   */
  protected function loadEntities() {
    foreach ($this->_formDataModel->getEntities() as $entityName => $entity) {
      $this->_entityIds[$entityName] = [];
      $idField = CoreUtil::getIdFieldName($entity['type']);
      if (!empty($entity['actions']['update'])) {
        if (
          !empty($this->args[$entityName]) &&
          (!empty($entity['url-autofill']) || isset($entity['fields'][$idField]))
        ) {
          $ids = (array) $this->args[$entityName];
          // Limit number of records to 1 unless using af-repeat
          $ids = array_slice($ids, 0, !empty($entity['af-repeat']) ? $entity['max'] ?? NULL : 1);
          $this->loadEntity($entity, $ids);
        }
        elseif (!empty($entity['autofill']) && $this->fillMode !== 'entity') {
          $this->autofillEntity($entity, $entity['autofill']);
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
  private function loadEntity(array $entity, array $ids) {
    $api4 = $this->_formDataModel->getSecureApi4($entity['name']);
    $idField = CoreUtil::getIdFieldName($entity['type']);
    if (!empty($entity['fields'][$idField]['saved_search'])) {
      $ids = $this->validateBySavedSearch($entity, $ids);
    }
    if (!$ids) {
      return;
    }
    $result = $api4($entity['type'], 'get', [
      'where' => [['id', 'IN', $ids]],
      'select' => array_keys($entity['fields']),
    ])->indexBy($idField);
    foreach ($ids as $index => $id) {
      $this->_entityIds[$entity['name']][$index] = [
        $idField => isset($result[$id]) ? $id : NULL,
        '_joins' => [],
      ];
      if (isset($result[$id])) {
        $data = ['fields' => $result[$id]];
        foreach ($entity['joins'] ?? [] as $joinEntity => $join) {
          $joinIdField = CoreUtil::getIdFieldName($joinEntity);
          $data['joins'][$joinEntity] = (array) $api4($joinEntity, 'get', [
            'where' => self::getJoinWhereClause($this->_formDataModel, $entity['name'], $joinEntity, $id),
            'limit' => !empty($join['af-repeat']) ? $join['max'] ?? 0 : 1,
            'select' => array_unique(array_merge([$joinIdField], array_keys($join['fields']))),
            'orderBy' => self::getEntityField($joinEntity, 'is_primary') ? ['is_primary' => 'DESC'] : [],
          ]);
          $this->_entityIds[$entity['name']][$index]['_joins'][$joinEntity] = \CRM_Utils_Array::filterColumns($data['joins'][$joinEntity], [$joinIdField]);
        }
        $this->_entityValues[$entity['name']][$index] = $data;
      }
    }
  }

  /**
   * Fetch an entity based on its autofill settings
   *
   * @param $entity
   * @param $mode
   */
  private function autoFillEntity($entity, $mode) {
    $id = NULL;
    if ($entity['type'] == 'Contact') {
      if ($mode == 'user') {
        $id = \CRM_Core_Session::getLoggedInContactID();
      }
    }
    if ($id) {
      $this->loadEntity($entity, [$id]);
    }
  }

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
