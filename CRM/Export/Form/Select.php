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

/**
 * This class gets the name of the file to upload
 */
class CRM_Export_Form_Select extends CRM_Core_Form_Task {

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
   * @var bool
   * @internal
   */
  public $_selectAll;

  /**
   * @var bool
   * @internal
   */
  public $_matchingContacts;

  /**
   * @var array
   * @internal
   */
  public $_greetingOptions;

  /**
   * Use the form name to create the tpl file name.
   *
   * @return string
   */
  public function getTemplateFileName() {
    return 'CRM/Export/Form/Select.tpl';
  }

  /**
   * Build all the data structures needed to build the form.
   *
   * @return void
   */
  public function preProcess() {
    $this->preventAjaxSubmit();
    $this->_selectAll = FALSE;
    $this->_exportMode = self::CONTACT_EXPORT;
    $this->_componentIds = [];
    $this->_componentClause = NULL;

    // FIXME: This should use a modified version of CRM_Contact_Form_Search::getModeValue but it doesn't have all the contexts
    // FIXME: Or better still, use CRM_Core_DAO_AllCoreTables::getEntityNameForClass($daoName) to get the $entityShortName
    $entityShortname = $this->getEntityShortName();

    if (!in_array($entityShortname, ['Contact', 'Contribute', 'Member', 'Event', 'Pledge', 'Case', 'Grant', 'Activity'], TRUE)) {
      // This is never reached - the exception here is just to clarify that entityShortName MUST be one of the above
      // to save future refactorers & reviewers from asking that question.
      throw new CRM_Core_Exception('Unreachable code');
    }
    $this->_exportMode = constant('CRM_Export_Form_Select::' . strtoupper($entityShortname) . '_EXPORT');

    $this::$entityShortname = strtolower($entityShortname);
    $values = $this->getSearchFormValues();

    $count = 0;
    $this->_matchingContacts = FALSE;
    if (($values['radio_ts'] ?? NULL) == 'ts_sel') {
      foreach ($values as $key => $value) {
        if (str_contains($key, 'mark_x')) {
          $count++;
        }
        if ($count > 2) {
          $this->_matchingContacts = TRUE;
          break;
        }
      }
    }

    $this->callPreProcessing();

    // $component is used on CRM/Export/Form/Select.tpl to display extra information for contact export
    ($this->_exportMode == self::CONTACT_EXPORT) ? $component = FALSE : $component = TRUE;
    $this->assign('component', $component);

    $this->assign('isShowMergeOptions', $this->isShowContactMergeOptions());

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
    $this->set('componentTable', $this->getTableName());
  }

  /**
   * Get the name of the table for the relevant entity.
   */
  public function getTableName() {
    throw new CRM_Core_Exception('should be over-riden');
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    //export option
    $exportOptions = $exportOptionsJS = $mergeOptions = $mergeOptionsJS = $postalMailing = [];
    $exportOptions[self::EXPORT_ALL] = ts('Export PRIMARY fields');
    $exportOptions[self::EXPORT_SELECTED] = ts('Select fields for export');
    $mergeOptions[self::EXPORT_MERGE_DO_NOT_MERGE] = ts('Do not merge');
    $mergeOptions[self::EXPORT_MERGE_SAME_ADDRESS] = ts('Merge All Contacts with the Same Address');
    $mergeOptions[self::EXPORT_MERGE_HOUSEHOLD] = ts('Merge Household Members into their Households');
    foreach (array_keys($exportOptions) as $key) {
      $exportOptionsJS[$key] = ['onClick' => 'showMappingOption( );'];
    }
    foreach (array_keys($mergeOptions) as $key) {
      $mergeOptionsJS[$key] = ['onclick' => 'showGreetingOptions( );'];
    }
    $this->addRadio('exportOption', ts('Export Type'), $exportOptions, [], '<br/>', FALSE, $exportOptionsJS);
    $postalMailing[] = $this->createElement('advcheckbox',
      'postal_mailing_export',
      NULL,
      NULL
    );

    if ($this->_matchingContacts) {
      $this->_greetingOptions = self::getGreetingOptions();

      foreach ($this->_greetingOptions as $key => $value) {
        $fieldLabel = ts('%1 (when merging contacts)', [1 => ucwords(str_replace('_', ' ', $key))]);
        $this->addElement('select', $key, $fieldLabel,
          $value, ['onchange' => "showOther(this);"]
        );
        $this->addElement('text', "{$key}_other", '');
      }
    }

    if ($this->_exportMode == self::CONTACT_EXPORT) {
      $this->addRadio('mergeOption', ts('Merge Options'), $mergeOptions, [], '<br/>', FALSE, $mergeOptionsJS);
      $this->addGroup($postalMailing, 'postal_mailing_export', ts('Postal Mailing Export'), '<br/>');
    }

    $this->buildMapping();

    $this->setDefaults([
      'exportOption' => self::EXPORT_ALL,
      'mergeOption' => self::EXPORT_MERGE_DO_NOT_MERGE,
    ]);

    $this->addButtons([
      [
        'type' => 'next',
        'name' => ts('Continue'),
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);

    $this->addFormRule(['CRM_Export_Form_Select', 'formRule'], $this);
  }

  /**
   * Validation.
   *
   * @param array $params
   *   (ref.) an assoc array of name/value pairs.
   *
   * @param $files
   * @param self $self
   *
   * @return bool|array
   *   mixed true or array of errors
   */
  public static function formRule($params, $files, $self) {
    $errors = [];

    if (($params['mergeOption'] ?? NULL) == self::EXPORT_MERGE_SAME_ADDRESS &&
      $self->_matchingContacts
    ) {
      $greetings = [
        'postal_greeting' => 'postal_greeting_other',
        'addressee' => 'addressee_other',
      ];

      foreach ($greetings as $key => $value) {
        $otherOption = $params[$key] ?? NULL;

        if ((($self->_greetingOptions[$key][$otherOption] ?? NULL) == ts('Other')) && empty($params[$value])) {

          $label = ucwords(str_replace('_', ' ', $key));
          $errors[$value] = ts('Please enter a value for %1 (when merging contacts), or select a pre-configured option from the list.', [1 => $label]);
        }
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the uploaded file.
   *
   * @throws \CRM_Core_Exception
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $exportOption = $params['exportOption'];
    $mergeSameAddress = ($params['mergeOption'] ?? NULL) == self::EXPORT_MERGE_SAME_ADDRESS ? 1 : 0;
    $mergeSameHousehold = ($params['mergeOption'] ?? NULL) == self::EXPORT_MERGE_HOUSEHOLD ? 1 : 0;

    $this->set('mergeSameAddress', $mergeSameAddress);
    $this->set('mergeSameHousehold', $mergeSameHousehold);

    // instead of increasing the number of arguments to exportComponents function, we
    // will send $exportParams as another argument, which is an array and suppose to contain
    // all submitted options or any other argument
    $exportParams = $params;

    $mappingId = $params['mapping'] ?? NULL;
    if ($mappingId) {
      $this->set('mappingId', $mappingId);
    }
    else {
      $this->set('mappingId', NULL);
    }

    if ($exportOption == self::EXPORT_ALL) {
      CRM_Export_BAO_Export::exportComponents($this->_selectAll,
        $this->_componentIds,
        (array) $this->get('queryParams'),
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
    return ts('Export Options');
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

    $this->set('mappingTypeId', CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Mapping', 'mapping_type_id', $exportType));

    $mappings = CRM_Core_BAO_Mapping::getMappings($exportType, TRUE);
    if (!empty($mappings)) {
      $this->add('select2', 'mapping', ts('Use Saved Field Mapping'), $mappings, FALSE, ['placeholder' => ts('- select -')]);
    }
  }

  /**
   * @return array
   */
  public static function getGreetingOptions() {
    $options = [];
    $greetings = [
      'postal_greeting' => 'postal_greeting_other',
      'addressee' => 'addressee_other',
    ];

    foreach ($greetings as $key => $value) {
      $params = [];
      $optionGroupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', $key, 'id', 'name');

      CRM_Core_DAO::commonRetrieveAll('CRM_Core_DAO_OptionValue', 'option_group_id', $optionGroupId,
        $params, ['label', 'filter']
      );

      $greetingCount = 1;
      $options[$key] = ["$greetingCount" => ts('List of names')];

      foreach ($params as $id => $field) {
        if (($field['filter'] ?? NULL) == 4) {
          $options[$key][++$greetingCount] = $field['label'];
        }
      }

      $options[$key][++$greetingCount] = ts('Other');
    }

    return $options;
  }

  /**
   * Get the query mode (eg. CRM_Contact_BAO_Query::MODE_CASE)
   *
   * @return int
   */
  public function getQueryMode() {
    return (int) ($this->queryMode ?: $this->controller->get('component_mode'));
  }

  /**
   * Call the pre-processing function.
   */
  protected function callPreProcessing(): void {
    throw new CRM_Core_Exception('This must be over-ridden');
  }

  /**
   * Assign the title of the task to the tpl.
   */
  protected function isShowContactMergeOptions() {
    throw new CRM_Core_Exception('This must be over-ridden');
  }

  /**
   * Get the name of the component.
   *
   * @return string
   */
  protected function getComponentName(): string {
    // CRM_Export_Controller_Standalone has this method
    if (method_exists($this->controller, 'getComponent')) {
      return $this->controller->getComponent();
    }
    // For others, just guess based on the name of the controller
    $formName = CRM_Utils_System::getClassName($this->controller->getStateMachine());
    $componentName = explode('_', $formName);
    return $componentName[1];
  }

  /**
   * Get the DAO name for the given export.
   *
   * @return string
   */
  protected function getDAOName(): string {
    switch ($this->getQueryMode()) {
      case CRM_Contact_BAO_Query::MODE_CONTRIBUTE:
        return 'Contribute';

      case CRM_Contact_BAO_Query::MODE_MEMBER:
        return 'Membership';

      case CRM_Contact_BAO_Query::MODE_EVENT:
        return 'Event';

      case CRM_Contact_BAO_Query::MODE_PLEDGE:
        return 'Pledge';

      case CRM_Contact_BAO_Query::MODE_CASE:
        return 'Case';

      case CRM_Contact_BAO_Query::MODE_GRANT:
        return 'Grant';

      case CRM_Contact_BAO_Query::MODE_ACTIVITY:
        return 'Activity';

      default:
        return $this->controller->get('entity') ?? $this->getComponentName();
    }
  }

  /**
   * Get the entity short name for a given export.
   *
   * @return string
   */
  protected function getEntityShortName(): string {
    switch ($this->getQueryMode()) {
      case CRM_Contact_BAO_Query::MODE_CONTRIBUTE:
        return 'Contribute';

      case CRM_Contact_BAO_Query::MODE_MEMBER:
        return 'Member';

      case CRM_Contact_BAO_Query::MODE_EVENT:
        return 'Event';

      case CRM_Contact_BAO_Query::MODE_PLEDGE:
        return 'Pledge';

      case CRM_Contact_BAO_Query::MODE_CASE:
        return 'Case';

      case CRM_Contact_BAO_Query::MODE_GRANT:
        return 'Grant';

      case CRM_Contact_BAO_Query::MODE_ACTIVITY:
        return 'Activity';

      default:
        return $this->getComponentName();
    }
  }

}
