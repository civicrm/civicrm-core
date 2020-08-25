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
 */


namespace Civi\Api4\Service\Spec;

use Civi\Api4\Utils\CoreUtil;

class FieldSpec {
  /**
   * @var mixed
   */
  protected $defaultValue;

  /**
   * @var string
   */
  protected $name;

  /**
   * @var string
   */
  protected $label;

  /**
   * @var string
   */
  protected $title;

  /**
   * @var string
   */
  protected $entity;

  /**
   * @var string
   */
  protected $description;

  /**
   * @var bool
   */
  protected $required = FALSE;

  /**
   * @var bool
   */
  protected $requiredIf;

  /**
   * @var array|bool
   */
  protected $options;

  /**
   * @var string
   */
  protected $dataType;

  /**
   * @var string
   */
  protected $inputType;

  /**
   * @var array
   */
  protected $inputAttrs = [];

  /**
   * @var string
   */
  protected $fkEntity;

  /**
   * @var int
   */
  protected $serialize;

  /**
   * @var string
   */
  protected $helpPre;

  /**
   * @var string
   */
  protected $helpPost;

  /**
   * @var array
   */
  protected $permission;

  /**
   * @var string
   */
  protected $columnName;

  /**
   * Aliases for the valid data types
   *
   * @var array
   */
  public static $typeAliases = [
    'Int' => 'Integer',
    'Link' => 'Url',
    'Memo' => 'Text',
  ];

  /**
   * @param string $name
   * @param string $entity
   * @param string $dataType
   */
  public function __construct($name, $entity, $dataType = 'String') {
    $this->entity = $entity;
    $this->name = $this->columnName = $name;
    $this->setDataType($dataType);
  }

  /**
   * @return mixed
   */
  public function getDefaultValue() {
    return $this->defaultValue;
  }

  /**
   * @param mixed $defaultValue
   *
   * @return $this
   */
  public function setDefaultValue($defaultValue) {
    $this->defaultValue = $defaultValue;

    return $this;
  }

  /**
   * @return string
   */
  public function getName() {
    return $this->name;
  }

  /**
   * @param string $name
   *
   * @return $this
   */
  public function setName($name) {
    $this->name = $name;

    return $this;
  }

  /**
   * @return string
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * @param string $label
   *
   * @return $this
   */
  public function setLabel($label) {
    $this->label = $label;

    return $this;
  }

  /**
   * @return string
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * @param string $title
   *
   * @return $this
   */
  public function setTitle($title) {
    $this->title = $title;

    return $this;
  }

  /**
   * @return string
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * @return string
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * @param string $description
   *
   * @return $this
   */
  public function setDescription($description) {
    $this->description = $description;

    return $this;
  }

  /**
   * @return bool
   */
  public function isRequired() {
    return $this->required;
  }

  /**
   * @param bool $required
   *
   * @return $this
   */
  public function setRequired($required) {
    $this->required = $required;

    return $this;
  }

  /**
   * @return bool
   */
  public function getRequiredIf() {
    return $this->requiredIf;
  }

  /**
   * @param bool $requiredIf
   *
   * @return $this
   */
  public function setRequiredIf($requiredIf) {
    $this->requiredIf = $requiredIf;

    return $this;
  }

  /**
   * @return string
   */
  public function getDataType() {
    return $this->dataType;
  }

  /**
   * @param $dataType
   *
   * @return $this
   * @throws \Exception
   */
  public function setDataType($dataType) {
    if (array_key_exists($dataType, self::$typeAliases)) {
      $dataType = self::$typeAliases[$dataType];
    }

    if (!in_array($dataType, $this->getValidDataTypes())) {
      throw new \Exception(sprintf('Invalid data type "%s', $dataType));
    }

    $this->dataType = $dataType;

    return $this;
  }

  /**
   * @return int
   */
  public function getSerialize() {
    return $this->serialize;
  }

  /**
   * @param int|null $serialize
   * @return $this
   */
  public function setSerialize($serialize) {
    $this->serialize = $serialize;

    return $this;
  }

  /**
   * @param array $permission
   * @return $this
   */
  public function setPermission($permission) {
    $this->permission = $permission;
    return $this;
  }

  /**
   * @return array
   */
  public function getPermission() {
    return $this->permission;
  }

  /**
   * @return string
   */
  public function getInputType() {
    return $this->inputType;
  }

  /**
   * @param string $inputType
   * @return $this
   */
  public function setInputType($inputType) {
    $this->inputType = $inputType;

    return $this;
  }

  /**
   * @return array
   */
  public function getInputAttrs() {
    return $this->inputAttrs;
  }

  /**
   * @param array $inputAttrs
   * @return $this
   */
  public function setInputAttrs($inputAttrs) {
    $this->inputAttrs = $inputAttrs;

    return $this;
  }

  /**
   * @return string|NULL
   */
  public function getHelpPre() {
    return $this->helpPre;
  }

  /**
   * @param string|NULL $helpPre
   */
  public function setHelpPre($helpPre) {
    $this->helpPre = is_string($helpPre) && strlen($helpPre) ? $helpPre : NULL;
  }

  /**
   * @return string|NULL
   */
  public function getHelpPost() {
    return $this->helpPost;
  }

  /**
   * @param string|NULL $helpPost
   */
  public function setHelpPost($helpPost) {
    $this->helpPost = is_string($helpPost) && strlen($helpPost) ? $helpPost : NULL;
  }

  /**
   * Add valid types that are not not part of \CRM_Utils_Type::dataTypes
   *
   * @return array
   */
  private function getValidDataTypes() {
    $extraTypes = ['Boolean', 'Text', 'Float', 'Url', 'Array'];
    $extraTypes = array_combine($extraTypes, $extraTypes);

    return array_merge(\CRM_Utils_Type::dataTypes(), $extraTypes);
  }

  /**
   * @param array $values
   * @param array|bool $return
   * @return array
   */
  public function getOptions($values = [], $return = TRUE) {
    if (!isset($this->options) || $this->options === TRUE) {
      $fieldName = $this->getName();

      if ($this instanceof CustomFieldSpec) {
        // buildOptions relies on the custom_* type of field names
        $fieldName = sprintf('custom_%d', $this->getCustomFieldId());
      }

      // BAO::buildOptions returns a single-dimensional list, we call that first because of the hook contract,
      // @see CRM_Utils_Hook::fieldOptions
      // We then supplement the data with additional properties if requested.
      $bao = CoreUtil::getBAOFromApiName($this->getEntity());
      $optionLabels = $bao::buildOptions($fieldName, NULL, $values);

      if (!is_array($optionLabels) || !$optionLabels) {
        $this->options = FALSE;
      }
      else {
        $this->options = \CRM_Utils_Array::makeNonAssociative($optionLabels, 'id', 'label');
        if (is_array($return)) {
          self::addOptionProps($bao, $fieldName, $values, $return);
        }
      }
    }
    return $this->options;
  }

  /**
   * Supplement the data from
   *
   * @param \CRM_Core_DAO $baoName
   * @param string $fieldName
   * @param array $values
   * @param array $return
   */
  private function addOptionProps($baoName, $fieldName, $values, $return) {
    // FIXME: For now, call the buildOptions function again and then combine the arrays. Not an ideal approach.
    // TODO: Teach CRM_Core_Pseudoconstant to always load multidimensional option lists so we can get more properties like 'color' and 'icon',
    // however that might require a change to the hook_civicrm_fieldOptions signature so that's a bit tricky.
    if (in_array('name', $return)) {
      $props['name'] = $baoName::buildOptions($fieldName, 'validate', $values);
    }
    $return = array_diff($return, ['id', 'name', 'label']);
    // CRM_Core_Pseudoconstant doesn't know how to fetch extra stuff like icon, description, color, etc., so we have to invent that wheel here...
    if ($return) {
      $optionIds = implode(',', array_column($this->options, 'id'));
      $optionIndex = array_flip(array_column($this->options, 'id'));
      if ($this instanceof CustomFieldSpec) {
        $optionGroupId = \CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $this->getCustomFieldId(), 'option_group_id');
      }
      else {
        $dao = new $baoName();
        $fieldSpec = $dao->getFieldSpec($fieldName);
        $pseudoconstant = $fieldSpec['pseudoconstant'] ?? NULL;
        $optionGroupName = $pseudoconstant['optionGroupName'] ?? NULL;
        $optionGroupId = $optionGroupName ? \CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', $optionGroupName, 'id', 'name') : NULL;
      }
      if (!empty($optionGroupId)) {
        $extraStuff = \CRM_Core_BAO_OptionValue::getOptionValuesArray($optionGroupId);
        $keyColumn = $pseudoconstant['keyColumn'] ?? 'value';
        foreach ($extraStuff as $item) {
          if (isset($optionIndex[$item[$keyColumn]])) {
            foreach ($return as $ret) {
              $this->options[$optionIndex[$item[$keyColumn]]][$ret] = $item[$ret] ?? NULL;
            }
          }
        }
      }
      else {
        // Fetch the abbr if requested using context: abbreviate
        if (in_array('abbr', $return)) {
          $props['abbr'] = $baoName::buildOptions($fieldName, 'abbreviate', $values);
          $return = array_diff($return, ['abbr']);
        }
        // Fetch anything else (color, icon, description)
        if ($return && !empty($pseudoconstant['table']) && \CRM_Utils_Rule::commaSeparatedIntegers($optionIds)) {
          $sql = "SELECT * FROM {$pseudoconstant['table']} WHERE id IN (%1)";
          $query = \CRM_Core_DAO::executeQuery($sql, [1 => [$optionIds, 'CommaSeparatedIntegers']]);
          while ($query->fetch()) {
            foreach ($return as $ret) {
              if (property_exists($query, $ret)) {
                $this->options[$optionIndex[$query->id]][$ret] = $query->$ret;
              }
            }
          }
        }
      }
    }
    if (isset($props)) {
      foreach ($this->options as &$option) {
        foreach ($props as $name => $prop) {
          $option[$name] = $prop[$option['id']] ?? NULL;
        }
      }
    }
  }

  /**
   * @param array|bool $options
   *
   * @return $this
   */
  public function setOptions($options) {
    $this->options = $options;
    return $this;
  }

  /**
   * @return string
   */
  public function getFkEntity() {
    return $this->fkEntity;
  }

  /**
   * @param string $fkEntity
   *
   * @return $this
   */
  public function setFkEntity($fkEntity) {
    $this->fkEntity = $fkEntity;

    return $this;
  }

  /**
   * @return string
   */
  public function getColumnName() {
    return $this->columnName;
  }

  /**
   * @param string $columnName
   *
   * @return $this
   */
  public function setColumnName($columnName) {
    $this->columnName = $columnName;
    return $this;
  }

  /**
   * @param array $values
   * @return array
   */
  public function toArray($values = []) {
    $ret = [];
    foreach (get_object_vars($this) as $key => $val) {
      $key = strtolower(preg_replace('/(?=[A-Z])/', '_$0', $key));
      if (!$values || in_array($key, $values)) {
        $ret[$key] = $val;
      }
    }
    return $ret;
  }

}
