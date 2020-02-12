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
    $this->setName($name);
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
   * @return array
   */
  public function getOptions($values = []) {
    if (!isset($this->options) || $this->options === TRUE) {
      $fieldName = $this->getName();

      if ($this instanceof CustomFieldSpec) {
        // buildOptions relies on the custom_* type of field names
        $fieldName = sprintf('custom_%d', $this->getCustomFieldId());
      }

      $bao = CoreUtil::getBAOFromApiName($this->getEntity());
      $options = $bao::buildOptions($fieldName, NULL, $values);

      if (!is_array($options) || !$options) {
        $options = FALSE;
      }

      $this->setOptions($options);
    }
    return $this->options;
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
