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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class provides the functionality for batch profile update
 */
class CRM_Core_Form_Task_Batch extends CRM_Core_Form_Task {

  /**
   * The title of the group.
   *
   * @var string
   */
  protected $_title;

  /**
   * Maximum profile fields that will be displayed.
   * @var int
   */
  protected $_maxFields = 9;

  /**
   * Fields that belong to this UF Group.
   *
   * @var array
   */
  protected $_fields;

  /**
   * Must be set to entity table name (eg. civicrm_participant) by child class
   * @var string
   */
  public static $tableName = NULL;
  /**
   * Must be set to entity shortname (eg. event)
   * @var string
   */
  public static $entityShortname = NULL;

  /**
   * Build all the data structures needed to build the form.
   *
   * @return void
   */
  public function preProcess() {
    // initialize the task and row fields
    parent::preProcess();

    // get the contact read only fields to display.
    $readOnlyFields = array_merge(['sort_name' => ts('Name')],
      CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'contact_autocomplete_options',
        TRUE, NULL, FALSE, 'name', TRUE
      )
    );
    // get the read only field data.
    $returnProperties = array_fill_keys(array_keys($readOnlyFields), 1);
    $contactDetails = CRM_Contact_BAO_Contact_Utils::contactDetails($this->_entityIds,
      'Civi' . ucfirst($this::$entityShortname), $returnProperties
    );

    $this->assign('contactDetails', $contactDetails);
    $this->assign('readOnlyFields', $readOnlyFields);
  }

  /**
   * Build the form object.
   *
   *
   * @return void
   */
  public function buildQuickForm() {
    $ufGroupId = $this->get('ufGroupId');

    if (!$ufGroupId) {
      throw new InvalidArgumentException('ufGroupId is missing');
    }
    $this->_title = ts("Update multiple %1s", [1 => $this::$entityShortname]) . ' - ' . CRM_Core_BAO_UFGroup::getTitle($ufGroupId);
    $this->setTitle($this->_title);

    $this->addDefaultButtons(ts('Save'));
    $this->_fields = CRM_Core_BAO_UFGroup::getFields($ufGroupId, FALSE, CRM_Core_Action::VIEW);

    // remove file type field and then limit fields
    $suppressFields = FALSE;
    $removeHtmlTypes = ['File'];
    foreach ($this->_fields as $name => $field) {
      if ($cfID = CRM_Core_BAO_CustomField::getKeyID($name) &&
        in_array($this->_fields[$name]['html_type'], $removeHtmlTypes)
      ) {
        $suppressFields = TRUE;
        unset($this->_fields[$name]);
      }

      //fix to reduce size as we are using this field in grid
      if (is_array($field['attributes']) && !empty($this->_fields[$name]['attributes']['size']) && $this->_fields[$name]['attributes']['size'] > 19) {
        //shrink class to "form-text-medium"
        $this->_fields[$name]['attributes']['size'] = 19;
      }
    }

    $this->_fields = array_slice($this->_fields, 0, $this->_maxFields);

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => ts('Update'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);

    $this->assign('profileTitle', $this->_title);
    $this->assign('componentIds', $this->_entityIds);

    $customFields = CRM_Core_BAO_CustomField::getFields(ucfirst($this::$entityShortname));
    foreach ($this->_entityIds as $entityId) {
      $typeId = CRM_Core_DAO::getFieldValue('CRM_' . ucfirst($this::$entityShortname) . '_DAO_' . ucfirst($this::$entityShortname), $entityId, $this::$entityShortname . '_type_id');
      foreach ($this->_fields as $name => $field) {
        if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($name)) {
          $customValue = $customFields[$customFieldID] ?? NULL;
          $entityColumnValue = [];
          if (!empty($customValue['extends_entity_column_value'])) {
            $entityColumnValue = explode(CRM_Core_DAO::VALUE_SEPARATOR,
              $customValue['extends_entity_column_value']
            );
          }
          if (!empty($entityColumnValue[$typeId]) ||
            CRM_Utils_System::isNull($entityColumnValue[$typeId])
          ) {
            CRM_Core_BAO_UFGroup::buildProfile($this, $field, NULL, $entityId);
          }
        }
        else {
          // handle non custom fields
          CRM_Core_BAO_UFGroup::buildProfile($this, $field, NULL, $entityId);
        }
      }
    }

    $this->assign('fields', $this->_fields);

    // don't set the status message when form is submitted.
    $buttonName = $this->controller->getButtonName('submit');
    if ($suppressFields && $buttonName != '_qf_Batch_next') {
      CRM_Core_Session::setStatus(ts("File type fields in the selected profile are not supported for Update multiple %1s", [1 => $this::$entityShortname]), ts('Unsupported Field Type'), 'error');
    }

    $this->addDefaultButtons(ts('Update'));

    $taskComponent['lc'] = $this::$entityShortname;
    $taskComponent['ucfirst'] = ucfirst($this::$entityShortname);
    $this->assign('taskComponent', $taskComponent);
  }

  /**
   * Set default values for the form.
   *
   * @return array $defaults
   */
  public function setDefaultValues() {
    if (empty($this->_fields)) {
      return [];
    }

    $defaults = [];
    foreach ($this->_entityIds as $entityId) {
      CRM_Core_BAO_UFGroup::setProfileDefaults(NULL, $this->_fields, $defaults, FALSE, $entityId, ucfirst($this::$entityShortname));
    }

    return $defaults;
  }

  /**
   * Process the form after the input has been submitted and validated.
   * Normally the child class will override this
   *
   * @return void
   */
  public function postProcess() {
    $params = $this->exportValues();

    if (!isset($params['field'])) {
      CRM_Core_Session::setStatus(ts("No updates have been saved."), ts('Not Saved'), 'alert');
      return;
    }
  }

}
