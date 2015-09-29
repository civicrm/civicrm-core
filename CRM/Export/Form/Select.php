<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */

/**
 * This class gets the name of the file to upload
 */
class CRM_Export_Form_Select extends CRM_Core_Form {

  /**
   * Various Contact types.
   */
  const
    EXPORT_ALL = 1,
    EXPORT_SELECTED = 2,
    EXPORT_MERGE_DO_NOT_MERGE = 0,
    EXPORT_MERGE_SAME_ADDRESS = 1,
    EXPORT_MERGE_HOUSEHOLD = 2;

  /**
   * Export modes.
   */
  const
    CONTACT_EXPORT = 1,
    CONTRIBUTE_EXPORT = 2,
    MEMBER_EXPORT = 3,
    EVENT_EXPORT = 4,
    PLEDGE_EXPORT = 5,
    CASE_EXPORT = 6,
    GRANT_EXPORT = 7,
    ACTIVITY_EXPORT = 8;

  /**
   * Current export mode.
   *
   * @var int
   */
  public $_exportMode;

  public $_componentTable;

  /**
   * Build all the data structures needed to build the form.
   *
   * @param
   *
   * @return void
   */
  public function preProcess() {
    //special case for custom search, directly give option to download csv file
    $customSearchID = $this->get('customSearchID');
    if ($customSearchID) {
      CRM_Export_BAO_Export::exportCustom($this->get('customSearchClass'),
        $this->get('formValues'),
        $this->get(CRM_Utils_Sort::SORT_ORDER)
      );
    }

    $this->_selectAll = FALSE;
    $this->_exportMode = self::CONTACT_EXPORT;
    $this->_componentIds = array();
    $this->_componentClause = NULL;

    // get the submitted values based on search
    if ($this->_action == CRM_Core_Action::ADVANCED) {
      $values = $this->controller->exportValues('Advanced');
    }
    elseif ($this->_action == CRM_Core_Action::PROFILE) {
      $values = $this->controller->exportValues('Builder');
    }
    elseif ($this->_action == CRM_Core_Action::COPY) {
      $values = $this->controller->exportValues('Custom');
    }
    else {
      // we need to determine component export
      $stateMachine = $this->controller->getStateMachine();

      $formName = CRM_Utils_System::getClassName($stateMachine);
      $componentName = explode('_', $formName);
      $components = array('Contribute', 'Member', 'Event', 'Pledge', 'Case', 'Grant', 'Activity');

      if (in_array($componentName[1], $components)) {
        switch ($componentName[1]) {
          case 'Contribute':
            $this->_exportMode = self::CONTRIBUTE_EXPORT;
            break;

          case 'Member':
            $this->_exportMode = self::MEMBER_EXPORT;
            break;

          case 'Event':
            $this->_exportMode = self::EVENT_EXPORT;
            break;

          case 'Pledge':
            $this->_exportMode = self::PLEDGE_EXPORT;
            break;

          case 'Case':
            $this->_exportMode = self::CASE_EXPORT;
            break;

          case 'Grant':
            $this->_exportMode = self::GRANT_EXPORT;
            break;

          case 'Activity':
            $this->_exportMode = self::ACTIVITY_EXPORT;
            break;
        }

        $className = "CRM_{$componentName[1]}_Form_Task";
        $className::preProcessCommon($this, TRUE);
        $values = $this->controller->exportValues('Search');
      }
      else {
        $values = $this->controller->exportValues('Basic');
      }
    }

    $count = 0;
    $this->_matchingContacts = FALSE;
    if (CRM_Utils_Array::value('radio_ts', $values) == 'ts_sel') {
      foreach ($values as $key => $value) {
        if (strstr($key, 'mark_x')) {
          $count++;
        }
        if ($count > 2) {
          $this->_matchingContacts = TRUE;
          break;
        }
      }
    }

    $componentMode = $this->get('component_mode');
    switch ($componentMode) {
      case 2:
        CRM_Contribute_Form_Task::preProcessCommon($this, TRUE);
        $this->_exportMode = self::CONTRIBUTE_EXPORT;
        $componentName = array('', 'Contribute');
        break;

      case 3:
        CRM_Event_Form_Task::preProcessCommon($this, TRUE);
        $this->_exportMode = self::EVENT_EXPORT;
        $componentName = array('', 'Event');
        break;

      case 4:
        CRM_Activity_Form_Task::preProcessCommon($this, TRUE);
        $this->_exportMode = self::ACTIVITY_EXPORT;
        $componentName = array('', 'Activity');
        break;

      case 5:
        CRM_Member_Form_Task::preProcessCommon($this, TRUE);
        $this->_exportMode = self::MEMBER_EXPORT;
        $componentName = array('', 'Member');
        break;

      case 6:
        CRM_Case_Form_Task::preProcessCommon($this, TRUE);
        $this->_exportMode = self::CASE_EXPORT;
        $componentName = array('', 'Case');
        break;
    }

    $this->_task = $values['task'];
    if ($this->_exportMode == self::CONTACT_EXPORT) {
      $contactTasks = CRM_Contact_Task::taskTitles();
      $taskName = $contactTasks[$this->_task];
      $component = FALSE;
      CRM_Contact_Form_Task::preProcessCommon($this, TRUE);
    }
    else {
      $this->assign('taskName', "Export $componentName[1]");
      $className = "CRM_{$componentName[1]}_Task";
      $componentTasks = $className::tasks();
      $taskName = $componentTasks[$this->_task];
      $component = TRUE;
    }

    if ($this->_componentTable) {
      $query = "
SELECT count(*)
FROM   {$this->_componentTable}
";
      $totalSelectedRecords = CRM_Core_DAO::singleValueQuery($query);
    }
    else {
      $totalSelectedRecords = count($this->_componentIds);
    }
    $this->assign('totalSelectedRecords', $totalSelectedRecords);
    $this->assign('taskName', $taskName);
    $this->assign('component', $component);
    // all records actions = save a search
    if (($values['radio_ts'] == 'ts_all') || ($this->_task == CRM_Contact_Task::SAVE_SEARCH)) {
      $this->_selectAll = TRUE;
      $rowCount = $this->get('rowCount');
      if ($rowCount > 2) {
        $this->_matchingContacts = TRUE;
      }
      $this->assign('totalSelectedRecords', $rowCount);
    }

    $this->assign('matchingContacts', $this->_matchingContacts);
    $this->set('componentIds', $this->_componentIds);
    $this->set('selectAll', $this->_selectAll);
    $this->set('exportMode', $this->_exportMode);
    $this->set('componentClause', $this->_componentClause);
    $this->set('componentTable', $this->_componentTable);
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    //export option
    $exportOptions = $mergeOptions = $postalMailing = array();
    $exportOptions[] = $this->createElement('radio',
      NULL, NULL,
      ts('Export PRIMARY fields'),
      self::EXPORT_ALL,
      array('onClick' => 'showMappingOption( );')
    );
    $exportOptions[] = $this->createElement('radio',
      NULL, NULL,
      ts('Select fields for export'),
      self::EXPORT_SELECTED,
      array('onClick' => 'showMappingOption( );')
    );

    $mergeOptions[] = $this->createElement('radio',
      NULL, NULL,
      ts('Do not merge'),
      self::EXPORT_MERGE_DO_NOT_MERGE,
      array('onclick' => 'showGreetingOptions( );')
    );
    $mergeOptions[] = $this->createElement('radio',
      NULL, NULL,
      ts('Merge All Contacts with the Same Address'),
      self::EXPORT_MERGE_SAME_ADDRESS,
      array('onclick' => 'showGreetingOptions( );')
    );
    $mergeOptions[] = $this->createElement('radio',
      NULL, NULL,
      ts('Merge Household Members into their Households'),
      self::EXPORT_MERGE_HOUSEHOLD,
      array('onclick' => 'showGreetingOptions( );')
    );

    $postalMailing[] = $this->createElement('advcheckbox',
      'postal_mailing_export',
      NULL,
      NULL
    );

    $this->addGroup($exportOptions, 'exportOption', ts('Export Type'), '<br/>');

    if ($this->_matchingContacts) {
      $this->_greetingOptions = self::getGreetingOptions();

      foreach ($this->_greetingOptions as $key => $value) {
        $fieldLabel = ts('%1 (merging > 2 contacts)', array(1 => ucwords(str_replace('_', ' ', $key))));
        $this->addElement('select', $key, $fieldLabel,
          $value, array('onchange' => "showOther(this);")
        );
        $this->addElement('text', "{$key}_other", '');
      }
    }

    if ($this->_exportMode == self::CONTACT_EXPORT) {
      $this->addGroup($mergeOptions, 'mergeOption', ts('Merge Options'), '<br/>');
      $this->addGroup($postalMailing, 'postal_mailing_export', ts('Postal Mailing Export'), '<br/>');

      $this->addElement('select', 'additional_group', ts('Additional Group for Export'),
        array('' => ts('- select group -')) + CRM_Core_PseudoConstant::nestedGroup(),
        array('class' => 'crm-select2 huge')
      );
    }

    $this->buildMapping();

    $this->setDefaults(array(
      'exportOption' => self::EXPORT_ALL,
      'mergeOption' => self::EXPORT_MERGE_DO_NOT_MERGE,
    ));

    $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => ts('Continue'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );

    $this->addFormRule(array('CRM_Export_Form_Select', 'formRule'), $this);
  }

  /**
   * Validation.
   *
   * @param array $params
   *   (ref.) an assoc array of name/value pairs.
   *
   * @param $files
   * @param $self
   *
   * @return bool|array
   *   mixed true or array of errors
   */
  static public function formRule($params, $files, $self) {
    $errors = array();

    if (CRM_Utils_Array::value('mergeOption', $params) == self::EXPORT_MERGE_SAME_ADDRESS &&
      $self->_matchingContacts
    ) {
      $greetings = array(
        'postal_greeting' => 'postal_greeting_other',
        'addressee' => 'addressee_other',
      );

      foreach ($greetings as $key => $value) {
        $otherOption = CRM_Utils_Array::value($key, $params);

        if ((CRM_Utils_Array::value($otherOption, $self->_greetingOptions[$key]) == ts('Other')) && empty($params[$value])) {

          $label = ucwords(str_replace('_', ' ', $key));
          $errors[$value] = ts('Please enter a value for %1 (merging > 2 contacts), or select a pre-configured option from the list.', array(1 => $label));
        }
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the uploaded file.
   *
   * @return void
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $exportOption = $params['exportOption'];
    $mergeSameAddress = CRM_Utils_Array::value('mergeOption', $params) == self::EXPORT_MERGE_SAME_ADDRESS ? 1 : 0;
    $mergeSameHousehold = CRM_Utils_Array::value('mergeOption', $params) == self::EXPORT_MERGE_HOUSEHOLD ? 1 : 0;

    $this->set('mergeSameAddress', $mergeSameAddress);
    $this->set('mergeSameHousehold', $mergeSameHousehold);

    // instead of increasing the number of arguments to exportComponents function, we
    // will send $exportParams as another argument, which is an array and suppose to contain
    // all submitted options or any other argument
    $exportParams = $params;

    if (!empty($this->_greetingOptions)) {
      foreach ($this->_greetingOptions as $key => $value) {
        if ($option = CRM_Utils_Array::value($key, $exportParams)) {
          if ($this->_greetingOptions[$key][$option] == ts('Other')) {
            $exportParams[$key] = $exportParams["{$key}_other"];
          }
          elseif ($this->_greetingOptions[$key][$option] == ts('List of names')) {
            $exportParams[$key] = '';
          }
          else {
            $exportParams[$key] = $this->_greetingOptions[$key][$option];
          }
        }
      }
    }

    $mappingId = CRM_Utils_Array::value('mapping', $params);
    if ($mappingId) {
      $this->set('mappingId', $mappingId);
    }
    else {
      $this->set('mappingId', NULL);
    }

    if ($exportOption == self::EXPORT_ALL) {
      CRM_Export_BAO_Export::exportComponents($this->_selectAll,
        $this->_componentIds,
        $this->get('queryParams'),
        $this->get(CRM_Utils_Sort::SORT_ORDER),
        NULL,
        $this->get('returnProperties'),
        $this->_exportMode,
        $this->_componentClause,
        $this->_componentTable,
        $mergeSameAddress,
        $mergeSameHousehold,
        $exportParams,
        $this->get('queryOperator')
      );
    }

    //reset map page
    $this->controller->resetPage('Map');
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Export All or Selected Fields');
  }

  /**
   * Build mapping form element.
   */
  public function buildMapping() {
    switch ($this->_exportMode) {
      case CRM_Export_Form_Select::CONTACT_EXPORT:
        $exportType = 'Export Contact';
        break;

      case CRM_Export_Form_Select::CONTRIBUTE_EXPORT:
        $exportType = 'Export Contribution';
        break;

      case CRM_Export_Form_Select::MEMBER_EXPORT:
        $exportType = 'Export Membership';
        break;

      case CRM_Export_Form_Select::EVENT_EXPORT:
        $exportType = 'Export Participant';
        break;

      case CRM_Export_Form_Select::PLEDGE_EXPORT:
        $exportType = 'Export Pledge';
        break;

      case CRM_Export_Form_Select::CASE_EXPORT:
        $exportType = 'Export Case';
        break;

      case CRM_Export_Form_Select::GRANT_EXPORT:
        $exportType = 'Export Grant';
        break;

      case CRM_Export_Form_Select::ACTIVITY_EXPORT:
        $exportType = 'Export Activity';
        break;
    }

    $mappingTypeId = CRM_Core_OptionGroup::getValue('mapping_type', $exportType, 'name');
    $this->set('mappingTypeId', $mappingTypeId);

    $mappings = CRM_Core_BAO_Mapping::getMappings($mappingTypeId);
    if (!empty($mappings)) {
      $this->add('select', 'mapping', ts('Use Saved Field Mapping'), array('' => '-select-') + $mappings);
    }
  }

  /**
   * @return array
   */
  public static function getGreetingOptions() {
    $options = array();
    $greetings = array(
      'postal_greeting' => 'postal_greeting_other',
      'addressee' => 'addressee_other',
    );

    foreach ($greetings as $key => $value) {
      $params = array();
      $optionGroupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', $key, 'id', 'name');

      CRM_Core_DAO::commonRetrieveAll('CRM_Core_DAO_OptionValue', 'option_group_id', $optionGroupId,
        $params, array('label', 'filter')
      );

      $greetingCount = 1;
      $options[$key] = array("$greetingCount" => ts('List of names'));

      foreach ($params as $id => $field) {
        if (CRM_Utils_Array::value('filter', $field) == 4) {
          $options[$key][++$greetingCount] = $field['label'];
        }
      }

      $options[$key][++$greetingCount] = ts('Other');
    }

    return $options;
  }

}
