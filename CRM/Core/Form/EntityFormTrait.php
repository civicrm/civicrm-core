<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 5                                                  |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

trait CRM_Core_Form_EntityFormTrait {

  /**
   * The entity subtype ID (eg. for Relationship / Activity)
   *
   * @var int
   */
  protected $_entitySubTypeId;

  /**
   * Get entity fields for the entity to be added to the form.
   *
   * @return array
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
   * @return string
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
   * Get the entity subtype ID being edited
   *
   * @param $subTypeId
   *
   * @return int|null
   */
  public function getEntitySubTypeId($subTypeId) {
    if ($subTypeId) {
      return $subTypeId;
    }
    return $this->_entitySubTypeId;
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
    if ($this->isDeleteContext()) {
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

    if ($this->isViewContext()) {
      $this->freeze();
    }
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
    if ($this->isViewContext() || $this->_action & CRM_Core_Action::PREVIEW) {
      $this->addButtons([
          [
            'type' => 'cancel',
            'name' => ts('Done'),
            'isDefault' => TRUE,
          ],
        ]
      );
    }
    else {
      $this->addButtons([
          [
            'type' => 'next',
            'name' => $this->isDeleteContext() ? ts('Delete') : ts('Save'),
            'isDefault' => TRUE,
          ],
          [
            'type' => 'cancel',
            'name' => ts('Cancel'),
          ],
        ]
      );
    }
  }

  /**
   * Get the defaults for the entity.
   */
  protected function getEntityDefaults() {
    $defaults = $moneyFields = [];

    if (!$this->isDeleteContext() &&
      $this->getEntityId()
    ) {
      $params = ['id' => $this->getEntityId()];
      $baoName = $this->_BAOName;
      $baoName::retrieve($params, $defaults);
    }
    foreach ($this->entityFields as $entityFieldName => $fieldSpec) {
      $value = CRM_Utils_Request::retrieveValue($fieldSpec['name'], $this->getValidationTypeForField($fieldSpec['name']));
      if ($value !== FALSE && $value !== NULL) {
        $defaults[$fieldSpec['name']] = $value;
      }
      // Store a list of fields with money formatters
      if (CRM_Utils_Array::value('formatter', $fieldSpec) == 'crmMoney') {
        $moneyFields[] = $entityFieldName;
      }
    }
    if (!empty($defaults['currency'])) {
      // If we have a money formatter we need to pass the specified currency or it will render as the default
      foreach ($moneyFields as $entityFieldName) {
        $this->entityFields[$entityFieldName]['formatterParam'] = $defaults['currency'];
      }
    }

    // Assign again as we may have modified above
    $this->assign('entityFields', $this->entityFields);
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
        $element = $this->addField($fieldSpec['name'], [], CRM_Utils_Array::value('required', $fieldSpec), FALSE);
        if (!empty($fieldSpec['is_freeze'])) {
          $element->freeze();
        }
      }
    }
  }

  /**
   * Is the form being used in the context of a deletion.
   *
   * (For some reason rather than having separate forms Civi overloads one form).
   *
   * @return bool
   */
  protected function isDeleteContext() {
    return ($this->_action & CRM_Core_Action::DELETE);
  }

  /**
   * Is the form being used in the context of a view.
   *
   * @return bool
   */
  protected function isViewContext() {
    return ($this->_action & CRM_Core_Action::VIEW);
  }

  protected function setEntityFieldsMetadata() {
    foreach ($this->entityFields as $field => &$props) {
      if (!empty($props['not-auto-addable'])) {
        // We can't load this field using metadata
        continue;
      }
      if ($field != 'id' && $this->isDeleteContext()) {
        // Delete forms don't generally present any fields to edit
        continue;
      }
      // Resolve action.
      if (empty($props['action'])) {
        $props['action'] = $this->getApiAction();
      }
      $fieldSpec = civicrm_api3($this->getDefaultEntity(), 'getfield', $props);
      $fieldSpec = $fieldSpec['values'];
      if (!isset($props['description']) && isset($fieldSpec['description'])) {
        $props['description'] = $fieldSpec['description'];
      }
    }
  }

}
