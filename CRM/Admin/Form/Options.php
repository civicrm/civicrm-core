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

use Civi\Api4\OptionGroup;
use Civi\Api4\OptionValue;

/**
 * This class generates form components for Options.
 */
class CRM_Admin_Form_Options extends CRM_Admin_Form {

  use CRM_Core_Form_EntityFormTrait;

  /**
   * The option group name.
   *
   * @var string
   */
  protected $_gName;

  /**
   * The option group name in display format (capitalized, without underscores...etc)
   *
   * @var array
   */
  protected $_gLabel;

  /**
   * @var bool
   */
  public $submitOnce = TRUE;

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'OptionValue';
  }

  /**
   * The Option Group ID.
   * @var int
   * @internal
   */
  protected $_gid;

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
    $this->setDeleteMessage();
    if ($this->isSubmitted()) {
      // The custom data fields are added to the form by an ajax form.
      // However, if they are not present in the element index they will
      // not be available from `$this->getSubmittedValue()` in post process.
      // We do not have to set defaults or otherwise render - just add to the element index.
      $this->addCustomDataFieldsToForm('OptionValue', array_filter([
        'id' => $this->_id,
        'option_group_id' => $this->_gid,
      ]));
    }
  }

  /**
   * Get the form-specific delete message.
   */
  public function setDeleteMessage(): void {
    $this->deleteMessage = ts('WARNING: Deleting this option will result in the loss of all %1 related records which use the option.', [1 => $this->_gLabel]) . ' ' . ts('This may mean the loss of a substantial amount of data, and the action cannot be undone.') . ' ' . ts('Do you want to continue?');
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    // Default weight & value
    $fieldValues = ['option_group_id' => $this->_gid];
    foreach (['weight', 'value'] as $field) {
      if (!isset($defaults[$field]) || $defaults[$field] === '') {
        $defaults[$field] = CRM_Utils_Weight::getDefaultWeight('CRM_Core_DAO_OptionValue', $fieldValues, $field);
      }
    }

    // setDefault of contact types for email greeting, postal greeting, addressee, CRM-4575
    if (in_array($this->_gName, [
      'email_greeting',
      'postal_greeting',
      'addressee',
    ])) {
      $defaults['contact_type_id'] = !empty($defaults['filter']) ? $defaults['filter'] : NULL;
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
   * @return string
   */
  public function getOptionGroupName() : string {
    return $this->_gName;
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {
    parent::buildQuickForm();
    $this->setPageTitle(ts('%1 Option', [1 => $this->_gLabel]));

    if ($this->_action & CRM_Core_Action::DELETE) {
      $this->buildDeleteForm();
      return;
    }

    $optionGroup = OptionGroup::get(FALSE)
      ->addWhere('id', '=', $this->_gid)
      ->execute()->first();

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
        ['CRM_Core_DAO_OptionValue', $this->_id, $this->_gid, 'value']
      );
    }

    // Add icon & color if this option group supports it.
    if ($optionGroup['option_value_fields'] && in_array('icon', $optionGroup['option_value_fields'])) {
      $this->add('text', 'icon', ts('Icon'), ['class' => 'crm-icon-picker', 'title' => ts('Choose Icon'), 'allowClear' => TRUE]);
    }
    if ($optionGroup['option_value_fields'] && in_array('color', $optionGroup['option_value_fields'])) {
      $this->add('color', 'color', ts('Color'));
    }

    if (!in_array($this->_gName, ['email_greeting', 'postal_greeting', 'addressee'])
      && !$isReserved
    ) {
      $this->addRule('label',
        ts('This Label already exists in the database for this option group. Please select a different Label.'),
        'optionExists',
        ['CRM_Core_DAO_OptionValue', $this->_id, $this->_gid, 'label']
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
      $financialAccount = \Civi\Api4\FinancialAccount::get()
        ->addSelect('id', 'label')
        ->addWhere('financial_account_type_id', '=', key($accountType))
        ->addWhere('is_active', '=', TRUE)
        ->addOrderBy('label')
        ->execute()
        ->column('label', 'id');

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
    if ($this->_gName == 'activity_type' && CRM_Core_Component::isEnabled("CiviCase") &&
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
    if (in_array($optionGroup['name'], ['email_greeting', 'postal_greeting', 'addressee'], TRUE)
      && !$isReserved
    ) {
      $values = [
        1 => ts('Individual'),
        2 => ts('Household'),
        3 => ts('Organization'),
      ];
      if ($optionGroup['name'] !== 'email_greeting') {
        // This isn't really a contact type - but it becomes available when exporting
        // if 'Merge All Contacts with the Same Address' is selected.
        $values[4] = ts('Multiple Contact Merge during Export');
      }
      $this->add('select', 'contact_type_id', ts('Contact Type'), ['' => '-select-'] + $values, TRUE);
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
    //need to assign subtype to the template
    $this->assign('customDataSubType', $this->_gid);
    $this->assign('entityID', $this->_id);

    if (($this->_action & CRM_Core_Action::ADD) || ($this->_action & CRM_Core_Action::UPDATE)) {
      $this->addButtons([
        [
          'type' => 'upload',
          'name' => ts('Save'),
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
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param self $self
   *   Current form object.
   *
   * @return array
   *   array of errors / empty array.
   * @throws \CRM_Core_Exception
   */
  public static function formRule($fields, $files, $self) {
    $errors = [];
    $optionGroupName = $self->_gName;
    if ($optionGroupName === 'case_status' && empty($fields['grouping'])) {
      $errors['grouping'] = ts('Status class is a required field');
    }

    if (
      // We are checking no other option value exists for this label+contact type combo.
      // @todo - bypassing reserved is historical - why would we not do this check for reserved options?
      empty($self->_defaultValues['is_reserved'])
      && in_array($optionGroupName, ['email_greeting', 'postal_greeting', 'addressee'], TRUE)
    ) {
      if (self::greetingExists($self->_id, $fields['label'], $fields['contact_type_id'], $optionGroupName)) {
        $errors['label'] = ts('This Label already exists in the database for the selected contact type.');
      }

    }

    $dataType = self::getOptionGroupDataType($optionGroupName);
    if ($dataType && $optionGroupName !== 'activity_type') {
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
   * Does an existing option already have this label for this contact type.
   *
   * @param int|null $id
   * @param string $label
   * @param int $contactTypeID
   * @param string $optionGroupName
   *
   * @return bool
   *
   * @throws \CRM_Core_Exception
   */
  protected static function greetingExists(?int $id, string $label, int $contactTypeID, string $optionGroupName): bool {
    $query = OptionValue::get(FALSE)
      ->addWhere('label', '=', $label)
      ->addWhere('option_group_id.name', '=', $optionGroupName)
      ->addWhere('is_active', '=', TRUE)
      ->addWhere('filter', '=', (int) $contactTypeID)
      ->addSelect('rowCount');
    if ($id) {
      $query->addWhere('id', '<>', $id);
    }
    return (bool) $query->execute()->count();
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

      if (CRM_Core_BAO_OptionValue::deleteRecord(['id' => $this->_id])) {
        if ($this->_gName == 'phone_type') {
          CRM_Core_BAO_Phone::setOptionToNull($this->_defaultValues['value'] ?? NULL);
        }

        CRM_Core_Session::setStatus(ts('Selected %1 type has been deleted.', [1 => $this->_gLabel]), ts('Record Deleted'), 'success');
      }
      else {
        CRM_Core_Session::setStatus(ts('Selected %1 type has not been deleted.', [1 => $this->_gLabel]), ts('Sorry'), 'error');
        CRM_Utils_Weight::correctDuplicateWeights('CRM_Core_DAO_OptionValue', $fieldValues);
      }
    }
    else {
      $params = $this->getSubmittedValues();
      if ($this->isGreetingOptionGroup()) {
        $params['filter'] = $params['contact_type_id'];
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
      $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params,
        $this->_id,
        'OptionValue'
      );
      $optionValue = CRM_Core_OptionValue::addOptionValue($params, $this->_gName, $this->_action, $this->_id);

      CRM_Core_Session::setStatus(ts('The %1 \'%2\' has been saved.', [
        1 => $this->_gLabel,
        2 => $optionValue->label,
      ]), ts('Saved'), 'success');

      $this->ajaxResponse['optionValue'] = $optionValue->toArray();
    }
  }

  /**
   * Is the option group one of our greetings.
   *
   * @return bool
   */
  protected function isGreetingOptionGroup(): bool {
    return in_array($this->getOptionGroupName(), ['email_greeting', 'postal_greeting', 'addressee'], TRUE);
  }

  /**
   * Override
   * @return array
   */
  protected function getFieldsToExcludeFromPurification(): array {
    return [];
  }

}
