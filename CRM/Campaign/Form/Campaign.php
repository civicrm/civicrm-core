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
 * This class generates form components for processing a campaign.
 */
class CRM_Campaign_Form_Campaign extends CRM_Core_Form {
  use CRM_Custom_Form_CustomDataTrait;
  use CRM_Campaign_Form_CampaignFormTrait;

  /**
   * Fields for the entity to be assigned to the template.
   *
   * Note this form is not implementing the EntityFormTrait but
   * is following it's syntax for consistency.
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
  protected array $entityFields = [];

  /**
   * Action
   *
   * @var int
   */
  public $_action;

  /**
   * Context
   *
   * @var string
   */
  protected $_context;

  /**
   * The id of the campaign we are processing
   *
   * @var int
   *
   * @deprecated use getCampaignID()
   */
  protected $_campaignId;

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity(): string {
    return 'Campaign';
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function preProcess(): void {
    if (!CRM_Campaign_BAO_Campaign::accessCampaign()) {
      CRM_Utils_System::permissionDenied();
    }

    $this->setEntityFields();
    $this->_context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);

    $this->assign('context', $this->_context);

    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this);
    $this->_campaignId = CRM_Utils_Request::retrieve('id', 'Positive');

    $title = NULL;
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $title = ts('Edit Campaign');
    }
    if ($this->_action & CRM_Core_Action::DELETE) {
      $title = ts('Delete Campaign');
    }
    if ($title) {
      $this->setTitle($title);
    }

    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=campaign'));
    $this->assign('action', $this->_action);

    if ($this->isSubmitted()) {
      // The custom data fields are added to the form by an ajax form.
      // However, if they are not present in the element index they will
      // not be available from `$this->getSubmittedValue()` in post process.
      // We do not have to set defaults or otherwise render - just add to the element index.
      $this->addCustomDataFieldsToForm('Campaign', array_filter([
        'id' => $this->getCampaignID(),
        'campaign_type_id' => $this->getSubmittedValue('campaign_type_id'),
      ]));
    }
  }

  /**
   * Set entity fields to be assigned to the form.
   */
  protected function setEntityFields(): void {
    $this->entityFields = [
      'title' => ['name' => 'title'],
      'description' => ['name' => 'description'],
      'start_date' => ['name' => 'start_date', 'default' => date('Y-m-d H:i:s')],
      'end_date' => ['name' => 'end_date'],
      'campaign_type_id' => ['name' => 'campaign_type_id'],
      'status_id' => ['name' => 'status_id'],
      'parent_id' => ['name' => 'parent_id'],
      'goal_general' => ['name' => 'goal_general'],
      'goal_revenue' => ['name' => 'goal_revenue'],
      'external_identifier' => ['name' => 'external_identifier'],
      'is_active' => ['name' => 'is_active', 'default' => 1],
    ];
  }

  /**
   * Set default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function setDefaultValues(): array {
    $defaults = [];
    foreach ($this->entityFields as $field) {
      $defaults[$field['name']] = $this->getCampaignValue($field['name']) ?? ($field['default'] ?? '');
    }

    if (!$this->getCampaignID()) {
      return $defaults;
    }

    $dao = new CRM_Campaign_DAO_CampaignGroup();

    $campaignGroups = [];
    $dao->campaign_id = $this->getCampaignID();
    $dao->find();

    while ($dao->fetch()) {
      $campaignGroups[$dao->entity_table][$dao->group_type][] = $dao->entity_id;
    }

    if (!empty($campaignGroups)) {
      $defaults['includeGroups'] = $campaignGroups['civicrm_group']['Include'];
    }
    return $defaults;
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {
    $this->add('hidden', 'id', $this->getCampaignID());
    if ($this->_action & CRM_Core_Action::DELETE) {

      $this->addButtons([
        [
          'type' => 'next',
          'name' => ts('Delete'),
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]);
      return;
    }

    $this->applyFilter('__ALL__', 'trim');

    // Assign custom data subtype for initial ajax load of custom data.
    $this->assign('entityID', $this->getCampaignID());
    $this->assign('customDataSubType', $this->getSubmittedValue('campaign_type_id') ?: $this->getCampaignValue('campaign_type_id'));

    $attributes = CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Campaign');

    $this->add('text', 'title', ts('Title'), $attributes['title'], TRUE);
    $this->add('textarea', 'description', ts('Description'), $attributes['description']);
    $this->add('datepicker', 'start_date', ts('Start Date'), [], TRUE);
    $this->add('datepicker', 'end_date', ts('End Date'));
    $this->addSelect('campaign_type_id', ['placeholder' => ts('- select type -'), 'onChange' => "CRM.buildCustomData( 'Campaign', this.value );"], TRUE);
    $this->addSelect('status_id', ['placeholder' => ts('- select status -')]);

    // add External Identifier Element
    $this->add('text', 'external_identifier', ts('External ID'),
      CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Campaign', 'external_identifier'), FALSE
    );

    // add Campaign Parent Id
    $campaigns = CRM_Campaign_BAO_Campaign::getCampaigns($this->getCampaignValue('parent_id'), $this->getCampaignID());
    if (!empty($campaigns)) {
      $this->addElement('select', 'parent_id', ts('Parent ID'),
        ['' => ts('- select Parent -')] + $campaigns,
        ['class' => 'crm-select2']
      );
    }
    $groups = CRM_Core_PseudoConstant::nestedGroup();
    //get the campaign groups.
    $this->add('select', 'includeGroups',
      ts('Include Group(s)'),
      $groups,
      FALSE,
      [
        'multiple' => TRUE,
        'class' => 'crm-select2 huge',
        'placeholder' => ts('- none -'),
      ]
    );

    $this->add('wysiwyg', 'goal_general', ts('Campaign Goals'), ['rows' => 2, 'cols' => 40]);
    $this->add('text', 'goal_revenue', ts('Revenue Goal'), ['size' => 8, 'maxlength' => 12]);
    $this->addRule('goal_revenue', ts('Please enter a valid money value (e.g. %1).',
      [1 => CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency(99.99)]
    ), 'money');

    // is this Campaign active
    $this->addElement('checkbox', 'is_active', ts('Is Active?'));

    $buttons = [
      [
        'type' => 'upload',
        'name' => ts('Save'),
        'isDefault' => TRUE,
      ],
    ];
    $buttons[] = [
      'type' => 'cancel',
      'name' => ts('Cancel'),
    ];

    $this->addButtons($buttons);

    $this->addFormRule(['CRM_Campaign_Form_Campaign', 'formRule']);
  }

  /**
   * Get the selected Campaign ID.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function getCampaignID(): ?int {
    if (!isset($this->_campaignId)) {
      $this->_campaignId = CRM_Utils_Request::retrieve('id', 'Positive') ?: NULL;
    }
    return $this->_campaignId;
  }

  /**
   * add the rules (mainly global rules) for form.
   * All local rules are added near the element
   *
   * @param $fields
   *
   * @return bool|array
   */
  public static function formRule($fields) {
    $errors = [];

    // Validate start/end date inputs
    $validateDates = \CRM_Utils_Date::validateStartEndDatepickerInputs('start_date', $fields['start_date'], 'end_date', $fields['end_date']);
    if ($validateDates !== TRUE) {
      $errors[$validateDates['key']] = $validateDates['message'];
    }

    // Validate that external_identifier is unique
    if (isset($fields['external_identifier'])) {
      $campaign = \Civi\Api4\Campaign::get(FALSE)
        ->addWhere('external_identifier', '=', $fields['external_identifier']);

      // when updating do not include the current campaign
      if ($fields['id'] != '' && is_numeric($fields['id'])) {
        $campaign->addWhere('id', '<>', $fields['id']);
      }

      $result = $campaign->execute()->first();
      if (isset($result)) {
        $errors['external_identifier'] = ts('External ID already exists.');
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Form submission of new/edit campaign is processed.
   */
  public function postProcess(): void {
    // store the submitted values in an array

    $session = CRM_Core_Session::singleton();
    $params = $this->getSubmittedValues();
    // To properly save the DAO we need to ensure we don't have a blank id key passed through.
    if (empty($params['id'])) {
      unset($params['id']);
    }
    if (!empty($params['id'])) {
      if ($this->_action & CRM_Core_Action::DELETE) {
        CRM_Campaign_BAO_Campaign::deleteRecord(['id' => $params['id']]);
        CRM_Core_Session::setStatus(ts('Campaign has been deleted.'), ts('Record Deleted'), 'success');
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=campaign'));
        return;
      }
      $this->_campaignId = $params['id'];
    }
    else {
      $params['created_id'] = $session->get('userID');
      $params['created_date'] = date('YmdHis');
    }
    // format params
    $params['is_active'] ??= FALSE;
    $result = self::submit($params, $this);
    if (!$result['is_error']) {
      CRM_Core_Session::setStatus(ts('Campaign %1 has been saved.', [1 => $result['values'][$result['id']]['title']]), ts('Saved'), 'success');
      $session->pushUserContext(CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=campaign'));
      $this->ajaxResponse['id'] = $result['id'];
      $this->ajaxResponse['label'] = $result['values'][$result['id']]['title'];
    }
    $buttonName = $this->controller->getButtonName();
    if ($buttonName == $this->getButtonName('upload', 'new')) {
      CRM_Core_Session::setStatus(ts(' You can add another Campaign.'), '', 'info');
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/campaign/add', 'reset=1&action=add'));
    }
    else {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=campaign'));
    }
  }

  public static function submit($params, $form) {
    $groups = [];
    if (!empty($params['includeGroups']) && is_array($params['includeGroups'])) {
      foreach ($params['includeGroups'] as $id) {
        if ($id) {
          $groups['include'][] = $id;
        }
      }
    }
    $params['groups'] = $groups;

    // delete previous includes/excludes, if campaign already existed
    $groupTableName = CRM_Contact_BAO_Group::getTableName();
    $dao = new CRM_Campaign_DAO_CampaignGroup();
    $dao->campaign_id = $form->_campaignId;
    $dao->entity_table = $groupTableName;
    $dao->find();
    while ($dao->fetch()) {
      $dao->delete();
    }

    //process custom data.
    $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params,
      $form->_campaignId,
      'Campaign'
    );

    // dev/core#1067 Clean Money before passing onto BAO to do the create.
    $params['goal_revenue'] = CRM_Utils_Rule::cleanMoney($params['goal_revenue']);
    return civicrm_api3('Campaign', 'create', $params);
  }

}
