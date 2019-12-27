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
  protected $args;

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
    // This will throw an exception if the form doesn't exist
    $this->_afform = (array) civicrm_api4('Afform', 'get', ['checkPermissions' => FALSE, 'where' => [['name', '=', $this->name]]], 0);
    if ($this->getCheckPermissions()) {
      if (!\CRM_Core_Permission::check("@afform:" . $this->_afform['name'])) {
        throw new \Civi\API\Exception\UnauthorizedException("Authorization failed: Cannot process form " . $this->_afform['name']);
      }
    }

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
    $joinMeta = \Civi::$statics[__CLASS__][__FUNCTION__][$joinEntityName] ?? NULL;
    $params = [];
    if (!$joinMeta) {
      $joinMeta = civicrm_api4($joinEntityName, 'getFields', ['checkPermissions' => FALSE, 'action' => 'create', 'select' => ['name']])->column('name');
      \Civi::$statics[__CLASS__][__FUNCTION__][$joinEntityName] = $joinMeta;
    }
    if (in_array('entity_id', $joinMeta)) {
      $params[] = ['entity_id', '=', $mainEntityId];
      if (in_array('entity_table', $joinMeta)) {
        $params[] = ['entity_table', '=', 'civicrm_' . _civicrm_api_get_entity_name_from_camel($mainEntityName)];
      }
    }
    else {
      $mainEntityField = _civicrm_api_get_entity_name_from_camel($mainEntityName) . '_id';
      $params[] = [$mainEntityField, '=', $mainEntityId];
    }
    return $params;
  }

}
