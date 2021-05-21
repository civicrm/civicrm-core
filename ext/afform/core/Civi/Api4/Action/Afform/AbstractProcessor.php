<?php

namespace Civi\Api4\Action\Afform;

use Civi\Afform\FormDataModel;
use Civi\Api4\Generic\Result;

/**
 * Shared functionality for form submission processing.
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

  protected $_afform;

  /**
   * @var \Civi\Afform\FormDataModel
   *   List of entities declared by this form.
   */
  protected $_formDataModel;

  /**
   * @param \Civi\Api4\Generic\Result $result
   * @throws \API_Exception
   */
  public function _run(Result $result) {
    // This will throw an exception if the form doesn't exist or user lacks permission
    $this->_afform = (array) civicrm_api4('Afform', 'get', ['where' => [['name', '=', $this->name]]], 0);
    $this->_formDataModel = new FormDataModel($this->_afform['layout']);
    $this->validateArgs();
    $result->exchangeArray($this->processForm());
  }

  /**
   * Strip out arguments that are not allowed on this form
   */
  protected function validateArgs() {
    $rawArgs = $this->args;
    $entities = $this->_formDataModel->getEntities();
    $this->args = [];
    foreach ($rawArgs as $arg => $val) {
      if (!empty($entities[$arg]['url-autofill'])) {
        $this->args[$arg] = $val;
      }
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
        $params[] = ['entity_table', '=', 'civicrm_' . \CRM_Core_DAO_AllCoreTables::convertEntityNameToLower($mainEntityName)];
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
