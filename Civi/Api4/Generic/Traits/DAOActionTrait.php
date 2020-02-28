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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id$
 *
 */

namespace Civi\Api4\Generic\Traits;

use Civi\Api4\Utils\FormattingUtil;
use Civi\Api4\Query\Api4SelectQuery;

/**
 * @method string getLanguage()
 * @method setLanguage(string $language)
 */
trait DAOActionTrait {

  /**
   * Specify the language to use if this is a multi-lingual environment.
   *
   * E.g. "en_US" or "fr_CA"
   *
   * @var string
   */
  protected $language;

  /**
   * @return \CRM_Core_DAO|string
   */
  protected function getBaoName() {
    require_once 'api/v3/utils.php';
    return \_civicrm_api3_get_BAO($this->getEntityName());
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
    $allFields = array_column($bao->fields(), 'name');
    if (!empty($this->reload)) {
      $inputFields = $allFields;
      $bao->find(TRUE);
    }
    else {
      $inputFields = array_keys($input);
      // Convert 'null' input to true null
      foreach ($input as $key => $val) {
        if ($val === 'null') {
          $bao->$key = NULL;
        }
      }
    }
    $values = [];
    foreach ($allFields as $field) {
      if (isset($bao->$field) || in_array($field, $inputFields)) {
        $values[$field] = $bao->$field ?? NULL;
      }
    }
    return $values;
  }

  /**
   * @return array|int
   */
  protected function getObjects() {
    $query = new Api4SelectQuery($this->getEntityName(), $this->getCheckPermissions(), $this->entityFields());
    $query->select = $this->getSelect();
    $query->where = $this->getWhere();
    $query->orderBy = $this->getOrderBy();
    $query->limit = $this->getLimit();
    $query->offset = $this->getOffset();
    if ($this->getDebug()) {
      $query->debugOutput =& $this->_debugOutput;
    }
    $result = $query->run();
    if (is_array($result)) {
      \CRM_Utils_API_HTMLInputCoder::singleton()->decodeRows($result);
    }
    return $result;
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
   * Write bao objects as part of a create/update action.
   *
   * @param array $items
   *   The records to write to the DB.
   * @return array
   *   The records after being written to the DB (e.g. including newly assigned "id").
   * @throws \API_Exception
   */
  protected function writeObjects($items) {
    $baoName = $this->getBaoName();

    // Some BAOs are weird and don't support a straightforward "create" method.
    $oddballs = [
      'EntityTag' => 'add',
      'GroupContact' => 'add',
      'Website' => 'add',
    ];
    $method = $oddballs[$this->getEntityName()] ?? 'create';
    if (!method_exists($baoName, $method)) {
      $method = 'add';
    }

    $result = [];

    foreach ($items as $item) {
      $entityId = $item['id'] ?? NULL;
      FormattingUtil::formatWriteParams($item, $this->getEntityName(), $this->entityFields());
      $this->formatCustomParams($item, $entityId);
      $item['check_permissions'] = $this->getCheckPermissions();

      // For some reason the contact bao requires this
      if ($entityId && $this->getEntityName() == 'Contact') {
        $item['contact_id'] = $entityId;
      }

      if ($this->getCheckPermissions()) {
        $this->checkContactPermissions($baoName, $item);
      }

      if ($this->getEntityName() == 'Address') {
        $createResult = $baoName::add($item, $this->fixAddress);
      }
      elseif (method_exists($baoName, $method)) {
        $createResult = $baoName::$method($item);
      }
      else {
        $createResult = $this->genericCreateMethod($item);
      }

      if (!$createResult) {
        $errMessage = sprintf('%s write operation failed', $this->getEntityName());
        throw new \API_Exception($errMessage);
      }

      $result[] = $this->baoToArray($createResult, $item);
    }
    FormattingUtil::formatOutputValues($result, $this->entityFields(), $this->getEntityName());
    return $result;
  }

  /**
   * Fallback when a BAO does not contain create or add functions
   *
   * @param $params
   * @return mixed
   */
  private function genericCreateMethod($params) {
    $baoName = $this->getBaoName();
    $hook = empty($params['id']) ? 'create' : 'edit';

    \CRM_Utils_Hook::pre($hook, $this->getEntityName(), $params['id'] ?? NULL, $params);
    /** @var \CRM_Core_DAO $instance */
    $instance = new $baoName();
    $instance->copyValues($params);
    $instance->save();
    \CRM_Utils_Hook::post($hook, $this->getEntityName(), $instance->id, $instance);

    return $instance;
  }

  /**
   * @param array $params
   * @param int $entityId
   * @return mixed
   */
  protected function formatCustomParams(&$params, $entityId) {
    $customParams = [];

    // $customValueID is the ID of the custom value in the custom table for this
    // entity (i guess this assumes it's not a multi value entity)
    foreach ($params as $name => $value) {
      if (strpos($name, '.') === FALSE) {
        continue;
      }

      list($customGroup, $customField) = explode('.', $name);

      $customFieldId = \CRM_Core_BAO_CustomField::getFieldValue(
        \CRM_Core_DAO_CustomField::class,
        $customField,
        'id',
        'name'
      );
      $customFieldType = \CRM_Core_BAO_CustomField::getFieldValue(
        \CRM_Core_DAO_CustomField::class,
        $customField,
        'html_type',
        'name'
      );
      $customFieldExtends = \CRM_Core_BAO_CustomGroup::getFieldValue(
        \CRM_Core_DAO_CustomGroup::class,
        $customGroup,
        'extends',
        'name'
      );

      // todo are we sure we don't want to allow setting to NULL? need to test
      if ($customFieldId && NULL !== $value) {

        if ($customFieldType == 'CheckBox') {
          // this function should be part of a class
          formatCheckBoxField($value, 'custom_' . $customFieldId, $this->getEntityName());
        }

        \CRM_Core_BAO_CustomField::formatCustomField(
          $customFieldId,
          $customParams,
          $value,
          $customFieldExtends,
          // todo check when this is needed
          NULL,
          $entityId,
          FALSE,
          FALSE,
          TRUE
        );
      }
    }

    if ($customParams) {
      $params['custom'] = $customParams;
    }
  }

  /**
   * Check edit/delete permissions for contacts and related entities.
   *
   * @param $baoName
   * @param $item
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function checkContactPermissions($baoName, $item) {
    if ($baoName == 'CRM_Contact_BAO_Contact' && !empty($item['id'])) {
      $permission = $this->getActionName() == 'delete' ? \CRM_Core_Permission::DELETE : \CRM_Core_Permission::EDIT;
      if (!\CRM_Contact_BAO_Contact_Permission::allow($item['id'], $permission)) {
        throw new \Civi\API\Exception\UnauthorizedException('Permission denied to modify contact record');
      }
    }
    else {
      // Fixme: decouple from v3
      require_once 'api/v3/utils.php';
      _civicrm_api3_check_edit_permissions($baoName, ['check_permissions' => 1] + $item);
    }
  }

}
