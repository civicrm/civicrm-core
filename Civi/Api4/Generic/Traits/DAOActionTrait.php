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

use Civi\Api4\CustomField;
use Civi\Api4\Service\Schema\Joinable\CustomGroupJoinable;
use Civi\Api4\Utils\FormattingUtil;
use Civi\Api4\Utils\CoreUtil;

/**
 * @method string getLanguage()
 * @method $this setLanguage(string $language)
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
   *
   * @return array
   *   The records after being written to the DB (e.g. including newly assigned "id").
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  protected function writeObjects(&$items) {
    $baoName = $this->getBaoName();

    // TODO: Opt-in more entities to use the new writeRecords BAO method.
    $functionNames = [
      'Address' => 'add',
      'CustomField' => 'writeRecords',
      'EntityTag' => 'add',
      'GroupContact' => 'add',
    ];
    $method = $functionNames[$this->getEntityName()] ?? NULL;
    if (!isset($method)) {
      $method = method_exists($baoName, 'create') ? 'create' : (method_exists($baoName, 'add') ? 'add' : 'writeRecords');
    }

    $result = [];

    foreach ($items as &$item) {
      $entityId = $item['id'] ?? NULL;
      FormattingUtil::formatWriteParams($item, $this->entityFields());
      $this->formatCustomParams($item, $entityId);

      // Skip individual processing if using writeRecords
      if ($method === 'writeRecords') {
        continue;
      }
      $item['check_permissions'] = $this->getCheckPermissions();

      // For some reason the contact bao requires this
      if ($entityId && $this->getEntityName() === 'Contact') {
        $item['contact_id'] = $entityId;
      }

      if ($this->getEntityName() === 'Address') {
        $createResult = $baoName::$method($item, $this->fixAddress);
      }
      else {
        $createResult = $baoName::$method($item);
      }

      if (!$createResult) {
        $errMessage = sprintf('%s write operation failed', $this->getEntityName());
        throw new \API_Exception($errMessage);
      }

      $result[] = $this->baoToArray($createResult, $item);
    }

    // Use bulk `writeRecords` method if the BAO doesn't have a create or add method
    // TODO: reverse this from opt-in to opt-out and default to using `writeRecords` for all BAOs
    if ($method === 'writeRecords') {
      $items = array_values($items);
      foreach ($baoName::writeRecords($items) as $i => $createResult) {
        $result[] = $this->baoToArray($createResult, $items[$i]);
      }
    }

    FormattingUtil::formatOutputValues($result, $this->entityFields(), $this->getEntityName());
    return $result;
  }

  /**
   * @param array $params
   * @param int $entityId
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  protected function formatCustomParams(&$params, $entityId) {
    $customParams = [];

    // $customValueID is the ID of the custom value in the custom table for this
    // entity (i guess this assumes it's not a multi value entity)
    foreach ($params as $name => $value) {
      $field = $this->getCustomFieldInfo($name);
      if (!$field) {
        continue;
      }

      // todo are we sure we don't want to allow setting to NULL? need to test
      if (NULL !== $value) {

        if ($field['suffix']) {
          $options = FormattingUtil::getPseudoconstantList($field, $field['suffix'], $params, $this->getActionName());
          $value = FormattingUtil::replacePseudoconstant($options, $value, TRUE);
        }

        if ($field['html_type'] === 'CheckBox') {
          // this function should be part of a class
          formatCheckBoxField($value, 'custom_' . $field['id'], $this->getEntityName());
        }

        if ($field['data_type'] === 'ContactReference' && !is_numeric($value)) {
          require_once 'api/v3/utils.php';
          $value = \_civicrm_api3_resolve_contactID($value);
          if ('unknown-user' === $value) {
            throw new \API_Exception("\"{$field['name']}\" \"{$value}\" cannot be resolved to a contact ID", 2002, ['error_field' => $field['name'], "type" => "integer"]);
          }
        }

        \CRM_Core_BAO_CustomField::formatCustomField(
          $field['id'],
          $customParams,
          $value,
          $field['custom_group.extends'],
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
   * Gets field info needed to save custom data
   *
   * @param string $fieldExpr
   *   Field identifier with possible suffix, e.g. MyCustomGroup.MyField1:label
   * @return array|NULL
   */
  protected function getCustomFieldInfo(string $fieldExpr) {
    if (strpos($fieldExpr, '.') === FALSE) {
      return NULL;
    }
    list($groupName, $fieldName) = explode('.', $fieldExpr);
    list($fieldName, $suffix) = array_pad(explode(':', $fieldName), 2, NULL);
    $cacheKey = "APIv4_Custom_Fields-$groupName";
    $info = \Civi::cache('metadata')->get($cacheKey);
    if (!isset($info[$fieldName])) {
      $info = [];
      $fields = CustomField::get(FALSE)
        ->addSelect('id', 'name', 'html_type', 'data_type', 'custom_group.extends')
        ->addWhere('custom_group.name', '=', $groupName)
        ->execute()->indexBy('name');
      foreach ($fields as $name => $field) {
        $field['custom_field_id'] = $field['id'];
        $field['name'] = $groupName . '.' . $name;
        $field['entity'] = CustomGroupJoinable::getEntityFromExtends($field['custom_group.extends']);
        $info[$name] = $field;
      }
      \Civi::cache('metadata')->set($cacheKey, $info);
    }
    return isset($info[$fieldName]) ? ['suffix' => $suffix] + $info[$fieldName] : NULL;
  }

}
