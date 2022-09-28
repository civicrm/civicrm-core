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
 * This class generates form components for Options.
 */
class CRM_Admin_Form_Options extends CRM_Admin_Form {

  /**
   * The option group name.
   *
   * @var array
   */
  protected $_gName;

  /**
   * The option group name in display format (capitalized, without underscores...etc)
   *
   * @var array
   */
  protected $_gLabel;

  /**
   * Is this Option Group Domain Specific
   * @var bool
   */
  protected $_domainSpecific = FALSE;

  /**
   * @var bool
   */
  public $submitOnce = TRUE;

  /**
   * Pre-process
   */
  public function preProcess() {
    parent::preProcess();
    $session = CRM_Core_Session::singleton();
    if (!$this->_gName && !empty($this->urlPath[3])) {
      $this->_gName = $this->urlPath[3];
    }
    if (!$this->_gName && !empty($_GET['gid'])) {
      $this->_gName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', (int) $_GET['gid'], 'name');
    }
    if ($this->_gName) {
      $this->set('gName', $this->_gName);
    }
    else {
      $this->_gName = $this->get('gName');
    }
    $this->_gid = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup',
      $this->_gName,
      'id',
      'name'
    );
    $this->_gLabel = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', $this->_gid, 'title');
    $this->_domainSpecific = in_array($this->_gName, CRM_Core_OptionGroup::$_domainIDGroups);
    $url = "civicrm/admin/options/{$this->_gName}";
    $params = "reset=1";

    if (($this->_action & CRM_Core_Action::DELETE) &&
      in_array($this->_gName, ['email_greeting', 'postal_greeting', 'addressee'])
    ) {
      // Don't allow delete if the option value belongs to addressee, postal or email greetings and is in use.
      $findValue = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $this->_id, 'value');
      $queryParam = [1 => [$findValue, 'Integer']];
      $columnName = $this->_gName . "_id";
      $sql = "SELECT count(id) FROM civicrm_contact WHERE " . $columnName . " = %1";
      $isInUse = CRM_Core_DAO::singleValueQuery($sql, $queryParam);
      if ($isInUse) {
        $scriptURL = "<a href='" . CRM_Utils_System::docURL2('Update Greetings and Address Data for Contacts', TRUE, NULL, NULL, NULL, "wiki") . "'>" . ts('Learn more about a script that can automatically update contact addressee and greeting options.') . "</a>";
        CRM_Core_Session::setStatus(ts('The selected %1 option has <strong>not been deleted</strong> because it is currently in use. Please update these contacts to use a different format before deleting this option. %2', [
          1 => $this->_gLabel,
          2 => $scriptURL,
        ]), ts('Sorry'), 'error');
        $redirect = CRM_Utils_System::url($url, $params);
        CRM_Utils_System::redirect($redirect);
      }
    }

    $session->pushUserContext(CRM_Utils_System::url($url, $params));
    $this->assign('id', $this->_id);

    if ($this->_id && in_array($this->_gName, CRM_Core_OptionGroup::$_domainIDGroups)) {
      $domainID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $this->_id, 'domain_id', 'id');
      if (CRM_Core_Config::domainID() != $domainID) {
        CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
      }
    }
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    // Default weight & value
    $fieldValues = ['option_group_id' => $this->_gid];
    foreach (['weight', 'value'] as $field) {
      if (empty($defaults[$field])) {
        $defaults[$field] = CRM_Utils_Weight::getDefaultWeight('CRM_Core_DAO_OptionValue', $fieldValues, $field);
      }
    }

    // setDefault of contact types for email greeting, postal greeting, addressee, CRM-4575
    if (in_array($this->_gName, [
      'email_greeting',
      'postal_greeting',
      'addressee',
    ])) {
      $defaults['contactOptions'] = (CRM_Utils_Array::value('filter', $defaults)) ? $defaults['filter'] : NULL;
    }
    // CRM-11516
    if ($this->_gName == 'payment_instrument' && $this->_id) {
      $defaults['financial_account_id'] = CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($this->_id, NULL, 'civicrm_option_value');
    }
    if (empty($this->_id) || !CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $this->_id, 'color')) {
      $defaults['color'] = '#ffffff';
    }
    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    $this->setPageTitle(ts('%1 Option', [1 => $this->_gLabel]));

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $this->applyFilter('__ALL__', 'trim');

    $isReserved = FALSE;
    if ($this->_id) {
      $isReserved = (bool) CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $this->_id, 'is_reserved');
    }

    $this->add('text',
      'label',
      ts('Label'),
      CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'label'),
      TRUE
    );

    if ($this->_gName != 'activity_type') {
      $this->add('text',
        'value',
        ts('Value'),
        CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'value'),
        TRUE
      );
      $this->addRule('value',
        ts('This Value already exists in the database for this option group. Please select a different Value.'),
        'optionExists',
        ['CRM_Core_DAO_OptionValue', $this->_id, $this->_gid, 'value', $this->_domainSpecific]
      );
    }
    else {
      $this->add('text', 'icon', ts('Icon'), ['class' => 'crm-icon-picker', 'title' => ts('Choose Icon'), 'allowClear' => TRUE]);
    }

    if (in_array($this->_gName, ['activity_status', 'case_status'])) {
      $this->add('color', 'color', ts('Color'));
    }

    if (!in_array($this->_gName, ['email_greeting', 'postal_greeting', 'addressee'])
      && !$isReserved
    ) {
      $this->addRule('label',
        ts('This Label already exists in the database for this option group. Please select a different Label.'),
        'optionExists',
        ['CRM_Core_DAO_OptionValue', $this->_id, $this->_gid, 'label', $this->_domainSpecific]
      );
    }

    if ($this->_gName == 'case_status') {
      $classes = [
        'Opened' => ts('Opened'),
        'Closed' => ts('Closed'),
      ];

      $grouping = $this->add('select',
        'grouping',
        ts('Status Class'),
        $classes
      );
      if ($isReserved) {
        $grouping->freeze();
      }
    }
    // CRM-11516
    if ($this->_gName == 'payment_instrument') {
      $accountType = CRM_Core_PseudoConstant::accountOptionValues('financial_account_type', NULL, " AND v.name = 'Asset' ");
      $financialAccount = CRM_Contribute_PseudoConstant::financialAccount(NULL, key($accountType));

      $this->add('select', 'financial_account_id', ts('Financial Account'),
        ['' => ts('- select -')] + $financialAccount,
        TRUE
      );
    }

    if ($this->_gName == 'activity_status') {
      $this->add('select',
        'filter',
        ts('Status Type'),
        [
          CRM_Activity_BAO_Activity::INCOMPLETE => ts('Incomplete'),
          CRM_Activity_BAO_Activity::COMPLETED => ts('Completed'),
          CRM_Activity_BAO_Activity::CANCELLED => ts('Cancelled'),
        ]
      );
    }
    if ($this->_gName == 'redaction_rule') {
      $this->add('checkbox',
        'filter',
        ts('Regular Expression?')
      );
    }
    if ($this->_gName == 'participant_listing') {
      $this->add('text',
        'description',
        ts('Description'),
        CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'description')
      );
    }
    else {
      // Hard-coding attributes here since description is still stored as varchar and not text in the schema. dgg
      $this->add('wysiwyg', 'description',
        ts('Description'),
        ['rows' => 4, 'cols' => 80],
        $this->_gName == 'custom_search'
      );
    }

    if ($this->_gName == 'event_badge') {
      $this->add('text',
        'name',
        ts('Class Name'),
        CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'name')
      );
    }

    $this->add('number',
      'weight',
      ts('Order'),
      CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'weight'),
      TRUE
    );
    $this->addRule('weight', ts('is a numeric field'), 'numeric');

    // If CiviCase enabled AND "Add" mode OR "edit" mode for non-reserved activities, only allow user to pick Core or CiviCase component.
    // FIXME: Each component should define whether adding new activity types is allowed.
    $config = CRM_Core_Config::singleton();
    if ($this->_gName == 'activity_type' && in_array("CiviCase", $config->enableComponents) &&
      (($this->_action & CRM_Core_Action::ADD) || !$isReserved)
    ) {
      $caseID = CRM_Core_Component::getComponentID('CiviCase');
      $components = ['' => ts('Contacts AND Cases'), $caseID => ts('Cases Only')];
      $this->add('select',
        'component_id',
        ts('Component'),
        $components, FALSE
      );
    }

    $enabled = $this->add('checkbox', 'is_active', ts('Enabled?'));

    if ($isReserved) {
      $enabled->freeze();
    }

    // fix for CRM-3552, CRM-4575
    $showIsDefaultGroups = [
      'email_greeting',
      'postal_greeting',
      'addressee',
      'from_email_address',
      'case_status',
      'encounter_medium',
      'case_type',
      'payment_instrument',
      'communication_style',
      'soft_credit_type',
      'website_type',
    ];

    if (in_array($this->_gName, $showIsDefaultGroups)) {
      $this->assign('showDefault', TRUE);
      $this->add('checkbox', 'is_default', ts('Default Option?'));
    }

    // get contact type for which user want to create a new greeting/addressee type, CRM-4575
    if (in_array($this->_gName, ['email_greeting', 'postal_greeting', 'addressee'])
      && !$isReserved
    ) {
      $values = [
        1 => ts('Individual'),
        2 => ts('Household'),
        3 => ts('Organization'),
        4 => ts('Multiple Contact Merge'),
      ];
      $this->add('select', 'contactOptions', ts('Contact Type'), ['' => '-select-'] + $values, TRUE);
      $this->assign('showContactFilter', TRUE);
    }

    if ($this->_gName == 'participant_status') {
      // For Participant Status options, expose the 'filter' field to track which statuses are "Counted", and the Visibility field
      $this->add('checkbox', 'filter', ts('Counted?'));
      $this->add('select', 'visibility_id', ts('Visibility'), CRM_Core_PseudoConstant::visibility());
    }
    if ($this->_gName == 'participant_role') {
      // For Participant Role options, expose the 'filter' field to track which statuses are "Counted"
      $this->add('checkbox', 'filter', ts('Counted?'));
    }

    $this->addFormRule(['CRM_Admin_Form_Options', 'formRule'], $this);
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param array $self
   *   Current form object.
   *
   * @return array
   *   array of errors / empty array.
   * @throws \CRM_Core_Exception
   */
  public static function formRule($fields, $files, $self) {
    $errors = [];
    if ($self->_gName == 'case_status' && empty($fields['grouping'])) {
      $errors['grouping'] = ts('Status class is a required field');
    }

    if (in_array($self->_gName, ['email_greeting', 'postal_greeting', 'addressee'])
      && empty($self->_defaultValues['is_reserved'])
    ) {
      $label = $fields['label'];
      $condition = " AND v.label = '{$label}' ";
      $values = CRM_Core_OptionGroup::values($self->_gName, FALSE, FALSE, FALSE, $condition, 'filter');
      $checkContactOptions = TRUE;

      if ($self->_id && ($self->_defaultValues['contactOptions'] == $fields['contactOptions'])) {
        $checkContactOptions = FALSE;
      }

      if ($checkContactOptions && in_array($fields['contactOptions'], $values)) {
        $errors['label'] = ts('This Label already exists in the database for the selected contact type.');
      }
    }

    if ($self->_gName == 'from_email_address') {
      $formEmail = CRM_Utils_Mail::pluckEmailFromHeader($fields['label']);
      if (!CRM_Utils_Rule::email($formEmail)) {
        $errors['label'] = ts('Please enter a valid email address.');
      }

      $formName = explode('"', $fields['label']);
      if (empty($formName[1]) || count($formName) != 3) {
        $errors['label'] = ts('Please follow the proper format for From Email Address');
      }
    }

    $dataType = self::getOptionGroupDataType($self->_gName);
    if ($dataType && $self->_gName !== 'activity_type') {
      $validate = CRM_Utils_Type::validate($fields['value'], $dataType, FALSE);
      if ($validate === FALSE) {
        CRM_Core_Session::setStatus(
          ts('Data Type of the value field for this option value does not match %1.', [1 => $dataType]),
          ts('Value field Data Type mismatch'));
      }
    }
    return $errors;
  }

  /**
   * Get the DataType for a specified Option Group.
   *
   * @param string $optionGroupName name of the option group
   *
   * @return string|null
   */
  public static function getOptionGroupDataType($optionGroupName) {
    $optionGroupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', $optionGroupName, 'id', 'name');

    $dataType = CRM_Core_BAO_OptionGroup::getDataType($optionGroupId);
    return $dataType;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      $fieldValues = ['option_group_id' => $this->_gid];
      CRM_Utils_Weight::delWeight('CRM_Core_DAO_OptionValue', $this->_id, $fieldValues);

      if (CRM_Core_BAO_OptionValue::del($this->_id)) {
        if ($this->_gName == 'phone_type') {
          CRM_Core_BAO_Phone::setOptionToNull(CRM_Utils_Array::value('value', $this->_defaultValues));
        }

        CRM_Core_Session::setStatus(ts('Selected %1 type has been deleted.', [1 => $this->_gLabel]), ts('Record Deleted'), 'success');
      }
      else {
        CRM_Core_Session::setStatus(ts('Selected %1 type has not been deleted.', [1 => $this->_gLabel]), ts('Sorry'), 'error');
        CRM_Utils_Weight::correctDuplicateWeights('CRM_Core_DAO_OptionValue', $fieldValues);
      }
    }
    else {
      $params = $this->exportValues();

      // allow multiple defaults within group.
      $allowMultiDefaults = ['email_greeting', 'postal_greeting', 'addressee', 'from_email_address'];
      if (in_array($this->_gName, $allowMultiDefaults)) {
        if ($this->_gName == 'from_email_address') {
          $params['reset_default_for'] = ['domain_id' => CRM_Core_Config::domainID()];
        }
        elseif ($filter = CRM_Utils_Array::value('contactOptions', $params)) {
          $params['filter'] = $filter;
          $params['reset_default_for'] = ['filter' => "0, " . $params['filter']];
        }

        //make sure we only have a single space, CRM-6977 and dev/mail/15
        if ($this->_gName == 'from_email_address') {
          $params['label'] = $this->sanitizeFromEmailAddress($params['label']);
        }
      }

      // set value of filter if not present in params
      if ($this->_id && !array_key_exists('filter', $params)) {
        if ($this->_gName == 'participant_role') {
          $params['filter'] = 0;
        }
        else {
          $params['filter'] = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $this->_id, 'filter', 'id');
        }
      }

      if (isset($params['color']) && strtolower($params['color']) == '#ffffff') {
        $params['color'] = 'null';
      }

      $optionValue = CRM_Core_OptionValue::addOptionValue($params, $this->_gName, $this->_action, $this->_id);

      CRM_Core_Session::setStatus(ts('The %1 \'%2\' has been saved.', [
        1 => $this->_gLabel,
        2 => $optionValue->label,
      ]), ts('Saved'), 'success');

      $this->ajaxResponse['optionValue'] = $optionValue->toArray();
    }
  }

  public function sanitizeFromEmailAddress($email) {
    preg_match("/^\"(.*)\" *<([^@>]*@[^@>]*)>$/", $email, $parts);
    return "\"{$parts[1]}\" <$parts[2]>";
  }

}
