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
trait CRM_Core_Form_EntityFormTrait {
  use CRM_Custom_Form_CustomDataTrait;

  /**
   * The id of the object being edited / created.
   *
   * @var int
   */
  public $_id;

  /**
   * The entity subtype ID (eg. for Relationship / Activity)
   *
   * @var int
   */
  protected $_entitySubTypeId = NULL;

  /**
   * Deletion message to be assigned to the form.
   *
   * Depending on the screen, the deletionMessage may be plain-text (`{$deletionMessage|escape}`)
   * or HTML (`{$deletionMessage nofilter}`). Be sure your controller+template agree.
   *
   * @var string
   */
  protected $deleteMessage;

  /**
   * Fields for the entity to be assigned to the template.
   *
   * Fields may have keys
   *  - name (required to show in tpl from the array)
   *  - description (optional, will appear below the field)
   *  - not-auto-addable - this class will not attempt to add the field using addField.
   *    (this will be automatically set if the field does not have html in it's metadata
   *    or is not a core field on the form's entity).
   *  - help (option) add help to the field - e.g ['id' => 'id-source', 'file' => 'CRM/Contact/Form/Contact']]
   *  - template - use a field specific template to render this field
   *  - required
   *  - is_freeze (field should be frozen).
   *
   * @var array
   */
  protected $entityFields = [];

  /**
   * Metadata from getfields API call for the current entity.
   *
   * @var array
   */
  protected $metadata = [];

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
   * Set the delete message.
   *
   * We do this from the constructor in order to do a translation.
   */
  public function setDeleteMessage() {
  }

  /**
   * Set entity fields to be assigned to the form.
   */
  protected function setEntityFields() {
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
   * Set the entity ID
   *
   * @param int $id The entity ID
   */
  public function setEntityId($id) {
    $this->_id = $id;
  }

  /**
   * Should custom data be suppressed on this form.
   *
   * @return bool
   */
  protected function isSuppressCustomData() {
    return FALSE;
  }

  /**
   * Get the entity subtype ID being edited
   *
   * @return int|null
   */
  public function getEntitySubTypeId() {
    return $this->_entitySubTypeId;
  }

  /**
   * Set the entity subtype ID being edited
   *
   * @param $subTypeId
   */
  public function setEntitySubTypeId($subTypeId) {
    $this->_entitySubTypeId = $subTypeId;
  }

  /**
   * If the custom data is in the submitted data (eg. added via ajax loaded form) add to form.
   */
  public function addCustomDataToForm() {
    if ($this->isSuppressCustomData()) {
      return TRUE;
    }

    /*
    @todo - this would be the preferred code here to better support
    php8.2 & take advantage of code improvements
    Note in this example an additional filter (membership_type_id)
    is relevant although for most entities it isn't.
    if ($this->isSubmitted()) {
    // The custom data fields are added to the form by an ajax form.
    // However, if they are not present in the element index they will
    // not be available from `$this->getSubmittedValue()` in post process.
    // We do not have to set defaults or otherwise render - just add to the element index.
    $this->addCustomDataFieldsToForm($this->getDefaultEntity(), array_filter([
    'id' => $this->getEntityId(),
    'membership_type_id' => $this->getSubmittedValue('membership_type_id')
    ]));
    }
     */
    $this->assign('customDataType', $this->getDefaultEntity());
    $this->assign('customDataSubType', $this->getEntitySubTypeId());
    $this->assign('entityID', $this->getEntityId());
    $this->assign('cid', NULL);
    if ($this->isSubmitted()) {
      $customisableEntities = CRM_Core_SelectValues::customGroupExtends();
      if (isset($customisableEntities[$this->getDefaultEntity()])) {
        if ($this->getEntitySubTypeId()) {
          // Supporting entity subtypes form the EntityFormTrait is not
          // used in core & is likely not used anywhere / was a good idea that
          // didn't fully happen. If anyone is winding up here they should override
          // the entire addCustomDataToForm function - e.g like the backoffice membership
          // forms do
          // @todo - add some noisy deprecation at some point.
          CRM_Custom_Form_CustomData::addToForm($this, $this->getEntitySubTypeId());
        }
        else {
          $this->addCustomDataFieldsToForm($this->getDefaultEntity(), array_filter([
            'id' => $this->getEntityId(),
          ]));
        }
      }
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
    foreach ($this->entityFields as $index => $fields) {
      $this->entityFields[$index] = array_replace_recursive([
        'template' => '',
        'post_html_text' => '',
        'description' => '',
        'documentation_link' => ['page' => '', 'resource' => ''],
      ], $fields);
    }
    $this->assign('entityFields', $this->entityFields);
    $this->assign('entityID', $this->getEntityId());
    $this->assign('entityInClassFormat', strtolower(str_replace('_', '-', $this->getDefaultEntity())));
    $this->assign('entityTable', CRM_Core_DAO_AllCoreTables::getTableForClass(CRM_Core_DAO_AllCoreTables::getDAONameForEntity($this->getDefaultEntity())));
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
      ]);
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
      ]);
    }
  }

  /**
   * Get the defaults for the entity.
   */
  protected function getEntityDefaults() {
    $defaults = $moneyFields = [];

    if (!$this->isDeleteContext() &&
      $this->getEntityId()) {
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
      if (($fieldSpec['formatter'] ?? NULL) == 'crmMoney') {
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
        if (empty($spec['html']['type'])) {
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
        $element = $this->addField($fieldSpec['name'], [], $fieldSpec['required'] ?? FALSE, FALSE);
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
