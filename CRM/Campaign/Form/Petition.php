<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class generates form components for adding a petition
 *
 */

class CRM_Campaign_Form_Petition extends CRM_Core_Form {

  /**
   * @var int
   * @protected
   */
  protected $_surveyId;

  public function preProcess() {
    if (!CRM_Campaign_BAO_Campaign::accessCampaign()) {
      CRM_Utils_System::permissionDenied();
    }

    $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this);

    $this->assign('context', $this->_context);

    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this);

    if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::DELETE)) {
      $this->_surveyId = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);

      if ($this->_action & CRM_Core_Action::UPDATE) {
        CRM_Utils_System::setTitle(ts('Edit Survey'));
      }
      else {
        CRM_Utils_System::setTitle(ts('Delete Survey'));
      }
    }

    $this->_cdType = CRM_Utils_Array::value('type', $_GET);
    $this->assign('cdType', FALSE);
    if ($this->_cdType) {
      $this->assign('cdType', TRUE);
      return CRM_Custom_Form_CustomData::preProcess($this);
    }

    // when custom data is included in this page
    if (CRM_Utils_Array::value('hidden_custom', $_POST)) {
      CRM_Custom_Form_CustomData::preProcess($this);
      CRM_Custom_Form_CustomData::buildQuickForm($this);
    }

    $session = CRM_Core_Session::singleton();
    $url = CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=survey');
    $session->pushUserContext($url);

    $this->_values = $this->get('values');
    
    if (!is_array($this->_values)) {
      $this->_values = array();
      if ($this->_surveyId) {
        $params = array('id' => $this->_surveyId);
        CRM_Campaign_BAO_Survey::retrieve($params, $this->_values);
      }
      $this->set('values', $this->_values);
    }

    $this->assign('action', $this->_action);
    $this->assign('surveyId', $this->_surveyId);
    // for custom data
    $this->assign('entityID', $this->_surveyId);

    if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::DELETE)) {
      $this->_surveyId = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);

      if ($this->_action & CRM_Core_Action::UPDATE) {
        CRM_Utils_System::setTitle(ts('Edit Petition'));
      }
      else {
        CRM_Utils_System::setTitle(ts('Delete Petition'));
      }
    }

    $session = CRM_Core_Session::singleton();
    $url = CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=petition');
    $session->pushUserContext($url);

    CRM_Utils_System::appendBreadCrumb(array(array('title' => ts('Petition Dashboard'), 'url' => $url)));
  }

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @param null
   *
   * @return array    array of default values
   * @access public
   */
  function setDefaultValues() {
    $defaults = $this->_values;

    $ufJoinParams = array(
      'entity_table' => 'civicrm_survey',
      'entity_id' => $this->_surveyId,
      'weight' => 2,
    );

    if ($ufGroupId = CRM_Core_BAO_UFJoin::findUFGroupId($ufJoinParams)) {
      $defaults['contact_profile_id'] = $ufGroupId;
    }
    
    if (!isset($defaults['is_active'])) {
      $defaults['is_active'] = 1;
    }

    $defaultSurveys = CRM_Campaign_BAO_Survey::getSurveys(TRUE, TRUE);
    if (!isset($defaults['is_default']) && empty($defaultSurveys)) {
      $defaults['is_default'] = 1;
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


    $this->add('text', 'title', ts('Petition Title'), CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Survey', 'title'), TRUE);

    $attributes = CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Survey');

    $petitionTypeID = CRM_Core_OptionGroup::getValue('activity_type', 'petition', 'name');
    $this->addElement('hidden', 'activity_type_id', $petitionTypeID);

    // script / instructions / description of petition purpose
    $this->addWysiwyg('instructions', ts('Introduction'), $attributes['instructions']);

    // Campaign id
    $campaigns = CRM_Campaign_BAO_Campaign::getCampaigns(CRM_Utils_Array::value('campaign_id', $this->_values));
    $this->add('select', 'campaign_id', ts('Campaign'), array('' => ts('- select -')) + $campaigns);

    $customContactProfiles = CRM_Core_BAO_UFGroup::getProfiles(array('Individual'));
    // custom group id
    $this->add('select', 'contact_profile_id', ts('Contact Profile'),
      array(
        '' => ts('- select -')) + $customContactProfiles, TRUE
    );

    $customProfiles = CRM_Core_BAO_UFGroup::getProfiles(array('Activity'));
    // custom group id
    $this->add('select', 'profile_id', ts('Activity Profile'),
      array(
        '' => ts('- select -')) + $customProfiles
    );

    // thank you title and text (html allowed in text)
    $this->add('text', 'thankyou_title', ts('Thank-you Page Title'), CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Survey', 'thankyou_title'));
    $this->addWysiwyg('thankyou_text', ts('Thank-you Message'), CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Survey', 'thankyou_text'));
    
    // bypass email confirmation?
    $this->add('checkbox', 'bypass_confirm', ts('Bypass email confirmation'));

    // is active ?
    $this->add('checkbox', 'is_active', ts('Is Active?'));

    // is default ?
    $this->add('checkbox', 'is_default', ts('Is Default?'));

    // add buttons
    $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'next',
          'name' => ts('Save and New'),
          'subName' => 'new',
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );

    // add a form rule to check default value
    $this->addFormRule(array('CRM_Campaign_Form_Survey_Results', 'formRule'), $this);
  }


  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);

    $session = CRM_Core_Session::singleton();

    $params['last_modified_id'] = $session->get('userID');
    $params['last_modified_date'] = date('YmdHis');

    if ($this->_surveyId) {

      if ($this->_action & CRM_Core_Action::DELETE) {
        CRM_Campaign_BAO_Survey::del($this->_surveyId);
        CRM_Core_Session::setStatus(ts(' Petition has been deleted.'), ts('Record Deleted'), 'success');
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=petition'));
        return;
      }

      $params['id'] = $this->_surveyId;
    }
    else {
      $params['created_id'] = $session->get('userID');
      $params['created_date'] = date('YmdHis');
    }

    $params['bypass_confirm'] = CRM_Utils_Array::value('bypass_confirm', $params, 0);
    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, 0);
    $params['is_default'] = CRM_Utils_Array::value('is_default', $params, 0);

    $surveyId = CRM_Campaign_BAO_Survey::create($params);


    // also update the ProfileModule tables
    $ufJoinParams = array(
      'is_active' => 1,
      'module' => 'CiviCampaign',
      'entity_table' => 'civicrm_survey',
      'entity_id' => $surveyId->id,
    );

    // first delete all past entries
    if ($this->_surveyId) {
      CRM_Core_BAO_UFJoin::deleteAll($ufJoinParams);
    }
    if (CRM_Utils_Array::value('profile_id', $params)) {
      $ufJoinParams['weight'] = 1;
      $ufJoinParams['uf_group_id'] = $params['profile_id'];
      CRM_Core_BAO_UFJoin::create($ufJoinParams);
    }

    if (CRM_Utils_Array::value('contact_profile_id', $params)) {
      $ufJoinParams['weight'] = 2;
      $ufJoinParams['uf_group_id'] = $params['contact_profile_id'];
      CRM_Core_BAO_UFJoin::create($ufJoinParams);
    }

    if (!is_a($surveyId, 'CRM_Core_Error')) {
      CRM_Core_Session::setStatus(ts('Petition has been saved.'), ts('Saved'), 'success');
    }

    $buttonName = $this->controller->getButtonName();
    if ($buttonName == $this->getButtonName('next', 'new')) {
      CRM_Core_Session::setStatus(ts(' You can add another Petition.'), '', 'info');
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/petition/add', 'reset=1&action=add'));
    }
    else {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=petition'));
    }
  }
}




