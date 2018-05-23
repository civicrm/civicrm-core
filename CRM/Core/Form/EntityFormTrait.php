<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.7                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2018                                |
  +--------------------------------------------------------------------+
  | This file is a part of CiviCRM.                                    |
  |                                                                    |
  | CiviCRM is free software; you can copy, modify, and distribute it  |
  | under the terms of the GNU Affero General Public License           |
  | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
  |                                                                    |
  | CiviCRM is distributed in the hope that it will be useful, but     |
  | WITHOUT ANY WARRANTY; without even the implied warranty of         |
  | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
  | See the GNU Affero General Public License for more details.        |
  |                                                                    |
  | You should have received a copy of the GNU Affero General Public   |
  | License and the CiviCRM Licensing Exception along                  |
  | with this program; if not, contact CiviCRM LLC                     |
  | at info[AT]civicrm[DOT]org. If you have questions about the        |
  | GNU Affero General Public License or the licensing of CiviCRM,     |
  | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
  +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 */

trait CRM_Core_Form_EntityFormTrait {
  /**
   * Get entity fields for the entity to be added to the form.
   *
   * @var array
   */
  public function getEntityFields() {
    return $this->entityFields;
  }

  /**
   * Explicitly declare the form context.
   */
  public function getDefaultContext() {
    return 'create';
  }

  /**
   * Get entity fields for the entity to be added to the form.
   *
   * @var array
   */
  public function getDeleteMessage() {
    return $this->deleteMessage;
  }

  /**
   * Get the entity id being edited.
   *
   * @return int|null
   */
  public function getEntityId() {
    return $this->_id;
  }
  /**
   * If the custom data is in the submitted data (eg. added via ajax loaded form) add to form.
   */
  public function addCustomDataToForm() {
    $customisableEntities = CRM_Core_SelectValues::customGroupExtends();
    if (isset($customisableEntities[$this->getDefaultEntity()])) {
      CRM_Custom_Form_CustomData::addToForm($this);
    }
  }

  /**
   * Build the form object.
   */
  public function buildQuickEntityForm() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      $this->buildDeleteForm();
      return;
    }
    $this->applyFilter('__ALL__', 'trim');
    $this->addEntityFieldsToTemplate();
    $this->assign('entityFields', $this->entityFields);
    $this->assign('entityID', $this->getEntityId());
    $this->assign('entityInClassFormat', strtolower(str_replace('_', '-', $this->getDefaultEntity())));
    $this->assign('entityTable', CRM_Core_DAO_AllCoreTables::getTableForClass(CRM_Core_DAO_AllCoreTables::getFullName($this->getDefaultEntity())));
    $this->addCustomDataToForm();
    $this->addFormButtons();
  }

  /**
   * Build the form for any deletion.
   */
  protected function buildDeleteForm() {
    $this->assign('deleteMessage', $this->getDeleteMessage());
    $this->addFormButtons();
  }

  /**
   * Add relevant buttons to the form.
   */
  protected function addFormButtons() {
    if ($this->_action & CRM_Core_Action::VIEW || $this->_action & CRM_Core_Action::PREVIEW) {
      $this->addButtons(array(
          array(
            'type' => 'cancel',
            'name' => ts('Done'),
            'isDefault' => TRUE,
          ),
        )
      );
    }
    else {
      $this->addButtons(array(
          array(
            'type' => 'next',
            'name' => $this->_action & CRM_Core_Action::DELETE ? ts('Delete') : ts('Save'),
            'isDefault' => TRUE,
          ),
          array(
            'type' => 'cancel',
            'name' => ts('Cancel'),
          ),
        )
      );
    }
  }

  /**
   * Get the defaults for the entity.
   */
  protected function getEntityDefaults() {
    $defaults = [];
    if ($this->_action != CRM_Core_Action::DELETE &&
      $this->getEntityId()
    ) {
      $params = ['id' => $this->getEntityId()];
      $baoName = $this->_BAOName;
      $baoName::retrieve($params, $defaults);
    }
    foreach ($this->entityFields as $fieldSpec) {
      $value = CRM_Utils_Request::retrieveValue($fieldSpec['name'], $this->getValidationTypeForField($fieldSpec['name']));
      if ($value !== FALSE) {
        $defaults[$fieldSpec['name']] = $value;
      }
    }
    return $defaults;
  }

  /**
   * Get the validation rule to apply to a function.
   *
   * Alphanumeric is designed to always be safe & for now we just return
   * that but in future we can use tighter rules for types like int, bool etc.
   *
   * @param string $fieldName
   *
   * @return string|int|bool
   */
  protected function getValidationTypeForField($fieldName) {
    switch ($this->metadata[$fieldName]['type']) {
      case CRM_Utils_Type::T_BOOLEAN:
        return 'Boolean';

      default:
        return 'Alphanumeric';
    }
  }

  /**
   * Set translated fields.
   *
   * This function is called from the class constructor, allowing us to set
   * fields on the class that can't be set as properties due to need for
   * translation or other non-input specific handling.
   */
  protected function setTranslatedFields() {
    $this->setEntityFields();
    $this->setDeleteMessage();
    $metadata = civicrm_api3($this->getDefaultEntity(), 'getfields', ['action' => 'create']);
    $this->metadata = $metadata['values'];
    foreach ($this->metadata as $fieldName => $spec) {
      if (isset($this->entityFields[$fieldName])) {
        if ($spec['localizable']) {
          $this->entityFields[$fieldName]['is_add_translate_dialog'] = TRUE;
        }
        if (empty($spec['html'])) {
          $this->entityFields[$fieldName]['not-auto-addable'] = TRUE;
        }
      }
    }
  }

  /**
   * Add defined entity field to template.
   */
  protected function addEntityFieldsToTemplate() {
    foreach ($this->getEntityFields() as $fieldSpec) {
      if (empty($fieldSpec['not-auto-addable'])) {
        $element = $this->addField($fieldSpec['name'], [], CRM_Utils_Array::value('required', $fieldSpec));
        if (!empty($fieldSpec['is_freeze'])) {
          $element->freeze();
        }
      }
    }
  }

}
