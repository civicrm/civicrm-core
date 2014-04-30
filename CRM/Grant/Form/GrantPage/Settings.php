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
class CRM_Grant_Form_GrantPage_Settings extends CRM_Grant_Form_GrantPage {

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    parent::preProcess();
  }

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return void
   */
  function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    if ($this->_id) {
      $title = CRM_Core_DAO::getFieldValue('CRM_Grant_DAO_GrantApplicationPage',
        $this->_id,
        'title'
      );
      if ($this->_action & CRM_Core_Action::UPDATE) {
        $titleView = 'Title and Settings (%1)';
      }

      $ufJoinParams = array(
        'module' => 'OnBehalf',
        'entity_table' => 'civicrm_grant_app_page',
        'entity_id' => $this->_id,
      );
      $onBehalfIDs = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);
      if ($onBehalfIDs) {
        // get the first one only
        $defaults['onbehalf_profile_id'] = $onBehalfIDs[0];
      }
      if ($this->_action & CRM_Core_Action::DELETE) {
        $titleView = 'Delete Grant Application Page \'%1\'?';
      }
      CRM_Utils_System::setTitle(ts($titleView, array(1 => $title)));
    }
    else {
      CRM_Utils_System::setTitle(ts('Title and Settings'));
    }
    return $defaults;
  }

  /**
   * Function to actually build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    // Set up the delete form
    if ($this->_action & CRM_Core_Action::DELETE) {
      $this->_title = CRM_Core_DAO::getFieldValue('CRM_Grant_DAO_GrantApplicationPage', $this->_id, 'title');

      $buttons = array();
      $buttons[] = array(
        'type' => 'next',
        'name' => ts('Delete Grant Application Page'),
        'isDefault' => TRUE,
      );

      $buttons[] = array(
        'type' => 'cancel',
        'name' => ts('Cancel'),
      );

      $this->addButtons($buttons);
      return;
    }
    $this->_first = TRUE;
    $attributes = CRM_Core_DAO::getAttribute('CRM_Grant_DAO_GrantApplicationPage');

    // name
    $this->add('text', 'title', ts('Title'), $attributes['title'], TRUE);

    $grant = CRM_Core_OptionGroup::values('grant_type');

    $this->add('select', 'grant_type_id',
      ts('Grant Type'),
      $grant,
      TRUE
    );
 
    $this->addWysiwyg('intro_text', ts('Introductory Message'), $attributes['intro_text']);

    $this->addWysiwyg('footer_text', ts('Footer Message'), $attributes['footer_text']);
    
    // collect default amount
    $this->add('text', 'default_amount', ts('Default Amount'), array('size' => 8, 'maxlength' => 12));
    $this->addRule('default_amount', ts('Please enter a valid money value (e.g. %1).', array(1 => CRM_Utils_Money::format('99.99', ' '))), 'money');

    // is this page active ?
    $this->addElement('checkbox', 'is_active', ts('Is this Grant Aplication Page Active?'));
    
    $this->addElement('checkbox', 'is_organization', ts('Allow individuals to apply for grants on behalf of an organization?'), NULL, array('onclick' => "showHideByValue('is_organization',true,'for_org_text','table-row','radio',false);showHideByValue('is_organization',true,'for_org_option','table-row','radio',false);"));

    $required = array('Contact', 'Organization');
    $optional = array('Grant');

    $profiles = CRM_Core_BAO_UFGroup::getValidProfiles($required, $optional);
    //Check profiles for Organization subtypes
    $contactSubType = CRM_Contact_BAO_ContactType::subTypes('Organization');
    foreach ($contactSubType as $type) {
      $required = array('Contact', $type);
      $subTypeProfiles = CRM_Core_BAO_UFGroup::getValidProfiles($required, $optional);
      foreach ($subTypeProfiles as $profileId => $profileName) {
        $profiles[$profileId] = $profileName;
      }
    }
    
    $requiredProfileFields = array('organization_name', 'email');

    if (!empty($profiles)) {
      foreach ($profiles as $id => $dontCare) {
        $validProfile = CRM_Core_BAO_UFGroup::checkValidProfile($id, $requiredProfileFields);
        if (!$validProfile) {
          unset($profiles[$id]);
        }
      }
    }

    if (empty($profiles)) {
      $invalidProfiles = TRUE;
      $this->assign('invalidProfiles', $invalidProfiles);
    }

    $this->add('select', 'onbehalf_profile_id', ts('Organization Profile'),
      array(
        '' => ts('- select -')) + $profiles
    );
    $options   = array();
    $options[] = $this->createElement('radio', NULL, NULL, ts('Optional'), 1);
    $options[] = $this->createElement('radio', NULL, NULL, ts('Required'), 2);
    $this->addGroup($options, 'is_for_organization', ts(''));
    $this->add('textarea', 'for_organization', ts('On behalf of Label'), $attributes['for_organization']);
    // add optional start and end dates
    $this->addDateTime('start_date', ts('Start Date'));
    $this->addDateTime('end_date', ts('End Date'));

    $this->addFormRule(array('CRM_Grant_Form_GrantPage_Settings', 'formRule'));

    parent::buildQuickForm();
  }

  /**
   * global validation rules for the form
   *
   * @param array $values posted values of the form
   *
   * @return array list of errors to be posted back to the form
   * @static
   * @access public
   */
  static
  function formRule($values) {
    $errors = array();

    //CRM-4286
    if (strstr($values['title'], '/')) {
      $errors['title'] = ts("Please do not use '/' in Title");
    }

    if (CRM_Utils_Array::value('is_organization', $values) &&
      !CRM_Utils_Array::value('onbehalf_profile_id', $values)
    ) {
      $errors['onbehalf_profile_id'] = ts('Please select a profile to collect organization information on this grant application page.');
    }
    return $errors;
  }

  /**
   * Process the form
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Grant_BAO_GrantApplicationPage::deleteGrantApplicationPage($this->_id, $this->_title);
      $url = 'civicrm/grant';
      $urlParams = 'reset=1';
      CRM_Utils_System::redirect(CRM_Utils_System::url($url, $urlParams));
      return;
    }
    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);
    
    // we do this in case the user has hit the forward/back button
    if ($this->_id) {
      $params['id'] = $this->_id;
    }
    else {
      $session = CRM_Core_Session::singleton();
      $params['created_id'] = $session->get('userID');
      $params['created_date'] = date('YmdHis');
      $config = CRM_Core_Config::singleton();
      $params['currency'] = $config->defaultCurrency;
    }

    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
    $params['default_amount'] = CRM_Utils_Rule::cleanMoney($params['default_amount']);

    $params['is_for_organization'] = CRM_Utils_Array::value('is_organization', $params) ? CRM_Utils_Array::value('is_for_organization', $params, FALSE) : 0;
    $params['start_date'] = CRM_Utils_Date::processDate($params['start_date'], $params['start_date_time'], TRUE);
    $params['end_date'] = CRM_Utils_Date::processDate($params['end_date'], $params['end_date_time'], TRUE);

    $dao = CRM_Grant_BAO_GrantApplicationPage::create($params);

    // make entry in UF join table for onbehalf of org profile
    $ufJoinParams = array(
      'is_active' => 1,
      'module' => 'OnBehalf',
      'entity_table' => 'civicrm_grant_app_page',
      'entity_id' => $dao->id,
    );

    // first delete all past entries
    CRM_Core_BAO_UFJoin::deleteAll($ufJoinParams);

    if (CRM_Utils_Array::value('onbehalf_profile_id', $params)) {
      $ufJoinParams['weight'] = 1;
      $ufJoinParams['uf_group_id'] = $params['onbehalf_profile_id'];
      CRM_Core_BAO_UFJoin::create($ufJoinParams);
    }
 
    $this->set('id', $dao->id);
    if ($this->_action & CRM_Core_Action::ADD) {
      $url = 'civicrm/admin/grant/thankyou';
      $urlParams = "action=update&reset=1&id={$dao->id}";
      // special case for 'Save and Done' consistency.
      if ($this->controller->getButtonName('submit') == '_qf_Amount_upload_done') {
        $url = 'civicrm/admin/grant';
        $urlParams = 'reset=1';
        CRM_Core_Session::setStatus(ts("'%1' information has been saved.",
          array(1 => $this->getTitle())
        ));
      }

       CRM_Utils_System::redirect(CRM_Utils_System::url($url, $urlParams));
    }
    parent::endPostProcess();
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   * @access public
   */
  public function getTitle() {
    return ts('Title and Settings');
  }
}

