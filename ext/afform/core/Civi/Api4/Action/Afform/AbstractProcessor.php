<?php

namespace Civi\Api4\Action\Afform;

use Civi\Afform\FormDataModel;
use Civi\Api4\Generic\Result;
use Civi\Api4\Utils\CoreUtil;

/**
 * Shared functionality for form submission pre & post processing.
 * @package Civi\Api4\Action\Afform
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
   * E.g. $entityIds['Individual1'] = [['id' => 1, 'joins' => ['Email' => [1,2,3]]];
   *
   * @var array
   */
  protected $_entityIds = [];

  protected $_entityValues = [];

  /**
   * @param \Civi\Api4\Generic\Result $result
   * @throws \API_Exception
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
      if (!empty($entity['actions']['update'])) {
        if (!empty($this->args[$entityName]) && !empty($entity['url-autofill'])) {
          $ids = array_map('trim', explode(',', $this->args[$entityName]));
          // Limit number of records to 1 unless using af-repeat
          $ids = array_slice($ids, 0, !empty($entity['af-repeat']) ? $entity['max'] ?? NULL : 1);
          $this->loadEntity($entity, $ids);
        }
        elseif (!empty($entity['autofill'])) {
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
    $result = $api4($entity['type'], 'get', [
      'where' => [['id', 'IN', $ids]],
      'select' => array_keys($entity['fields']),
    ])->indexBy('id');
    foreach ($ids as $index => $id) {
      $this->_entityIds[$entity['name']][$index] = [
        'id' => isset($result[$id]) ? $id : NULL,
        'joins' => [],
      ];
      if (isset($result[$id])) {
        $data = ['fields' => $result[$id]];
        foreach ($entity['joins'] ?? [] as $joinEntity => $join) {
          $data['joins'][$joinEntity] = (array) $api4($joinEntity, 'get', [
            'where' => self::getJoinWhereClause($entity['type'], $joinEntity, $id),
            'limit' => !empty($join['af-repeat']) ? $join['max'] ?? 0 : 1,
            'select' => array_keys($join['fields']),
            'orderBy' => self::getEntityField($joinEntity, 'is_primary') ? ['is_primary' => 'DESC'] : [],
          ]);
          $this->_entityIds[$entity['name']][$index]['joins'][$joinEntity] = array_column($data['joins'][$joinEntity], 'id');
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

  /**
   * @return array
   */
  abstract protected function processForm();

  /**
   * @param $mainEntityName
   * @param $joinEntityName
   * @param $mainEntityId
   * @return array
   * @throws \API_Exception
   */
  protected static function getJoinWhereClause($mainEntityName, $joinEntityName, $mainEntityId) {
    $params = [];
    if (self::getEntityField($joinEntityName, 'entity_id')) {
      $params[] = ['entity_id', '=', $mainEntityId];
      if (self::getEntityField($joinEntityName, 'entity_table')) {
        $params[] = ['entity_table', '=', CoreUtil::getTableName($mainEntityName)];
      }
    }
    else {
      $mainEntityField = \CRM_Core_DAO_AllCoreTables::convertEntityNameToLower($mainEntityName) . '_id';
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
   * @throws \API_Exception
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
