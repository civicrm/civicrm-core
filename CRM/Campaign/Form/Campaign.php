<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This class generates form components for processing a campaign
 *
 */
class CRM_Campaign_Form_Campaign extends CRM_Core_Form {

  /**
   * action
   *
   * @var int
   */
  protected $_action;

  /**
   * context
   *
   * @var string
   */
  protected $_context;

  /**
   * object values.
   *
   * @var array
   */
  protected $_values;

  /**
   * the id of the campaign we are proceessing
   *
   * @var int
   * @protected
   */
  protected $_campaignId;

  public function preProcess() {
    if (!CRM_Campaign_BAO_Campaign::accessCampaign()) {
      CRM_Utils_System::permissionDenied();
    }

    //check for custom data type.
    $this->_cdType = CRM_Utils_Array::value('type', $_GET);
    $this->assign('cdType', FALSE);
    if ($this->_cdType) {
      $this->assign('cdType', TRUE);
      return CRM_Custom_Form_CustomData::preProcess($this);
    }

    $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this);

    $this->assign('context', $this->_context);

    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this);
    $this->_campaignId = CRM_Utils_Request::retrieve('id', 'Positive', $this);

    $title = NULL;
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $title = ts('Edit Campaign');
    }
    if ($this->_action & CRM_Core_Action::DELETE) {
      $title = ts('Delete Campaign');
    }
    if ($title) {
      CRM_Utils_System::setTitle($title);
    }

    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=campaign'));
    $this->assign('action', $this->_action);

    //load the values;
    $this->_values = $this->get('values');
    if (!is_array($this->_values)) {
      $this->_values = array();

      // if we are editing
      if (isset($this->_campaignId) && $this->_campaignId) {
        $params = array('id' => $this->_campaignId);
        CRM_Campaign_BAO_Campaign::retrieve($params, $this->_values);
      }

      //lets use current object session.
      $this->set('values', $this->_values);
    }

    // when custom data is included in form.
    if (!empty($_POST['hidden_custom'])) {
      $this->set('type', 'Campaign');
      $this->set('subType', CRM_Utils_Array::value('campaign_type_id', $_POST));
      $this->set('entityId', $this->_campaignId);

      CRM_Custom_Form_CustomData::preProcess($this);
      CRM_Custom_Form_CustomData::buildQuickForm($this);
      CRM_Custom_Form_CustomData::setDefaultValues($this);
    }
  }

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return array
   */
  function setDefaultValues() {
    $defaults = $this->_values;

    //load only custom data defaults.
    if ($this->_cdType) {
      return CRM_Custom_Form_CustomData::setDefaultValues($this);
    }

    if (isset($defaults['start_date'])) {
      list($defaults['start_date'],
        $defaults['start_date_time']
      ) = CRM_Utils_Date::setDateDefaults($defaults['start_date'],
        'activityDateTime'
      );
    }
    else {
      list($defaults['start_date'],
        $defaults['start_date_time']
      ) = CRM_Utils_Date::setDateDefaults();
    }

    if (isset($defaults['end_date'])) {
      list($defaults['end_date'],
        $defaults['end_date_time']
      ) = CRM_Utils_Date::setDateDefaults($defaults['end_date'],
        'activityDateTime'
      );
    }

    if (!isset($defaults['is_active'])) {
      $defaults['is_active'] = 1;
    }

    if (!$this->_campaignId) {
      return $defaults;
    }

    $dao = new CRM_Campaign_DAO_CampaignGroup();

    $campaignGroups = array();
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
    if ($this->_action & CRM_Core_Action::DELETE) {

      $this->addButtons(array(
          array(
            'type' => 'next',
            'name' => ts('Delete'),
            'isDefault' => TRUE,
          ),
          array(
            'type' => 'cancel',
            'name' => ts('Cancel'),
          ),
        )
      );
      return;
    }

    $this->applyFilter('__ALL__', 'trim');

    if ($this->_cdType) {
      return CRM_Custom_Form_CustomData::buildQuickForm($this);
    }

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
    $this->addDateTime('start_date', ts('Start Date'), TRUE, array('formatType' => 'activityDateTime'));

    // add campaign end date
    $this->addDateTime('end_date', ts('End Date'), FALSE, array('formatType' => 'activityDateTime'));

    // add campaign type
    $this->addSelect('campaign_type_id', array('onChange' => "CRM.buildCustomData( 'Campaign', this.value );"), TRUE);

    // add campaign status
    $this->addSelect('status_id');

    // add External Identifier Element
    $this->add('text', 'external_identifier', ts('External ID'),
      CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Campaign', 'external_identifier'), FALSE
    );

    // add Campaign Parent Id
    $campaigns = CRM_Campaign_BAO_Campaign::getCampaigns(CRM_Utils_Array::value('parent_id', $this->_values), $this->_campaignId);
    if (!empty($campaigns)) {
      $this->addElement('select', 'parent_id', ts('Parent ID'),
        array('' => ts('- select Parent -')) + $campaigns,
        array('class' => 'crm-select2')
      );
    }
    $groups = CRM_Core_PseudoConstant::nestedGroup();
    //get the campaign groups.
    $this->add('select', 'includeGroups',
      ts('Include Group(s)'),
      $groups,
      FALSE,
      array(
        'multiple' => TRUE,
        'class' => 'crm-select2 huge',
        'placeholder' => ts('- none -'),
      )
    );

    $this->addWysiwyg('goal_general', ts('Campaign Goals'), array('rows' => 2, 'cols' => 40));
    $this->add('text', 'goal_revenue', ts('Revenue Goal'), array('size' => 8, 'maxlength' => 12));
    $this->addRule('goal_revenue', ts('Please enter a valid money value (e.g. %1).',
        array(1 => CRM_Utils_Money::format('99.99', ' '))
      ), 'money');

    // is this Campaign active
    $this->addElement('checkbox', 'is_active', ts('Is Active?'));

    $this->addButtons(array(
        array(
          'type' => 'upload',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'upload',
          'name' => ts('Save and New'),
          'subName' => 'new',
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
  }

  /**
   * This function is used to add the rules (mainly global rules) for form.
   * All local rules are added near the element
   *
   * @param $fields
   * @param $files
   * @param $errors
   *
   * @return bool|array
   * @access public
   * @see valid_date
   */
  static function formRule($fields, $files, $errors) {
    $errors = array();

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Form submission of new/edit campaign is processed.
   *
   * @access public
   *
   * @return void
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);
    $session = CRM_Core_Session::singleton();

    $groups = array();
    if (isset($this->_campaignId)) {
      if ($this->_action & CRM_Core_Action::DELETE) {
        CRM_Campaign_BAO_Campaign::del($this->_campaignId);
        CRM_Core_Session::setStatus(ts('Campaign has been deleted.'), ts('Record Deleted'), 'success');
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=campaign'));
        return;
      }
      $params['id'] = $this->_campaignId;
    }
    else {
      $params['created_id'] = $session->get('userID');
      $params['created_date'] = date('YmdHis');
    }
    // format params
    $params['start_date'] = CRM_Utils_Date::processDate($params['start_date'], $params['start_date_time']);
    $params['end_date'] = CRM_Utils_Date::processDate($params['end_date'], $params['end_date_time'], TRUE);
    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
    $params['last_modified_id'] = $session->get('userID');
    $params['last_modified_date'] = date('YmdHis');

    if (is_array($params['includeGroups'])) {
      foreach ($params['includeGroups'] as $key => $id) {
        if ($id) {
          $groups['include'][] = $id;
        }
      }
    }
    $params['groups'] = $groups;

    // delete previous includes/excludes, if campaign already existed
    $groupTableName    = CRM_Contact_BAO_Group::getTableName();
    $dao               = new CRM_Campaign_DAO_CampaignGroup();
    $dao->campaign_id  = $this->_campaignId;
    $dao->entity_table = $groupTableName;
    $dao->find();
    while ($dao->fetch()) {
      $dao->delete();
    }

    //process custom data.
    $customFields = CRM_Core_BAO_CustomField::getFields('Campaign', FALSE, FALSE,
      CRM_Utils_Array::value('campaign_type_id', $params)
    );
    $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params,
      $customFields,
      $this->_campaignId,
      'Campaign'
    );

    $result = CRM_Campaign_BAO_Campaign::create($params);

    if ($result) {
      CRM_Core_Session::setStatus(ts('Campaign %1 has been saved.', array(1 => $result->title)), ts('Saved'), 'success');
      $session->pushUserContext(CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=campaign'));
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
}



