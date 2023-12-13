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
   * Object values.
   *
   * @var array
   */
  protected $_values;

  /**
   * The id of the campaign we are proceessing
   *
   * @var int
   */
  protected $_campaignId;

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'Campaign';
  }

  public function preProcess() {
    if (!CRM_Campaign_BAO_Campaign::accessCampaign()) {
      CRM_Utils_System::permissionDenied();
    }

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

    //load the values;
    $this->_values = $this->get('values');
    if (!is_array($this->_values)) {
      $this->_values = [];

      // if we are editing
      if (isset($this->_campaignId) && $this->_campaignId) {
        $params = ['id' => $this->_campaignId];
        CRM_Campaign_BAO_Campaign::retrieve($params, $this->_values);
      }

      //lets use current object session.
      $this->set('values', $this->_values);
    }

    // when custom data is included in form.
    if (!empty($_POST['hidden_custom'])) {
      $campaignTypeId = empty($_POST['campaign_type_id']) ? NULL : $_POST['campaign_type_id'];
      $this->set('type', 'Campaign');
      $this->set('subType', $campaignTypeId);
      $this->set('entityId', $this->_campaignId);

      CRM_Custom_Form_CustomData::preProcess($this, NULL, $campaignTypeId, 1, 'Campaign', $this->_campaignId);
      CRM_Custom_Form_CustomData::buildQuickForm($this);
      CRM_Custom_Form_CustomData::setDefaultValues($this);
    }
  }

  /**
   * Set default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = $this->_values;

    if (empty($defaults['start_date'])) {
      $defaults['start_date'] = date('Y-m-d H:i:s');
    }

    if (!isset($defaults['is_active'])) {
      $defaults['is_active'] = 1;
    }

    if (!$this->_campaignId) {
      return $defaults;
    }

    $dao = new CRM_Campaign_DAO_CampaignGroup();

    $campaignGroups = [];
    $dao->campaign_id = $this->_campaignId;
    $dao->find();

    while ($dao->fetch()) {
      $campaignGroups[$dao->entity_table][$dao->group_type][] = $dao->entity_id;
    }

    if (!empty($campaignGroups)) {
      $defaults['includeGroups'] = $campaignGroups['civicrm_group']['Include'];
    }
    return $defaults;
  }

  public function buildQuickForm() {
    $this->add('hidden', 'id', $this->_campaignId);
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

    //lets assign custom data type and subtype.
    $this->assign('customDataType', 'Campaign');
    $this->assign('entityID', $this->_campaignId);
    $this->assign('customDataSubType', CRM_Utils_Array::value('campaign_type_id', $this->_values));

    $attributes = CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Campaign');

    // add comaign title.
    $this->add('text', 'title', ts('Title'), $attributes['title'], TRUE);

    // add description
    $this->add('textarea', 'description', ts('Description'), $attributes['description']);

    // add campaign start date
    $this->add('datepicker', 'start_date', ts('Start Date'), [], TRUE);

    // add campaign end date
    $this->add('datepicker', 'end_date', ts('End Date'));

    // add campaign type
    $this->addSelect('campaign_type_id', ['placeholder' => ts('- select type -'), 'onChange' => "CRM.buildCustomData( 'Campaign', this.value );"], TRUE);

    // add campaign status
    $this->addSelect('status_id', ['placeholder' => ts('- select status -')]);

    // add External Identifier Element
    $this->add('text', 'external_identifier', ts('External ID'),
      CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Campaign', 'external_identifier'), FALSE
    );

    // add Campaign Parent Id
    $campaigns = CRM_Campaign_BAO_Campaign::getCampaigns(CRM_Utils_Array::value('parent_id', $this->_values), $this->_campaignId);
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

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Form submission of new/edit campaign is processed.
   */
  public function postProcess() {
    // store the submitted values in an array

    $session = CRM_Core_Session::singleton();
    $params = $this->controller->exportValues($this->_name);
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
    $params['last_modified_id'] = $session->get('userID');
    $params['last_modified_date'] = date('YmdHis');
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
      foreach ($params['includeGroups'] as $key => $id) {
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
    $result = civicrm_api3('Campaign', 'create', $params);
    return $result;
  }

}
