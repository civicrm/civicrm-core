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
class CRM_Contribute_Form_ContributionPage_Settings extends CRM_Contribute_Form_ContributionPage {

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
      $title = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage',
        $this->_id,
        'title'
      );
      CRM_Utils_System::setTitle(ts('Title and Settings (%1)',
          array(1 => $title)
        ));

      $ufJoinParams = array(
        'module' => 'OnBehalf',
        'entity_table' => 'civicrm_contribution_page',
        'entity_id' => $this->_id,
      );
      $onBehalfIDs = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);
      if ($onBehalfIDs) {
        // get the first one only
        $defaults['onbehalf_profile_id'] = $onBehalfIDs[0];
      }
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

    $this->_first = TRUE;
    $attributes = CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionPage');

    // financial Type
    $financialType = CRM_Financial_BAO_FinancialType::getIncomeFinancialType();
    $this->add('select', 'financial_type_id',
      ts('Financial Type'),
      $financialType,
      TRUE
    );

    // name
    $this->add('text', 'title', ts('Title'), $attributes['title'], TRUE);

    //CRM-7362 --add campaigns.
    CRM_Campaign_BAO_Campaign::addCampaign($this, CRM_Utils_Array::value('campaign_id', $this->_values));

    $this->addWysiwyg('intro_text', ts('Introductory Message'), $attributes['intro_text']);

    $this->addWysiwyg('footer_text', ts('Footer Message'), $attributes['footer_text']);

    // is on behalf of an organization ?
    $this->addElement('checkbox', 'is_organization', ts('Allow individuals to contribute and / or signup for membership on behalf of an organization?'), NULL, array('onclick' => "showHideByValue('is_organization',true,'for_org_text','table-row','radio',false);showHideByValue('is_organization',true,'for_org_option','table-row','radio',false);"));

    $required = array('Contact', 'Organization');
    $optional = array('Contribution', 'Membership');

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

    // collect goal amount
    $this->add('text', 'goal_amount', ts('Goal Amount'), array('size' => 8, 'maxlength' => 12));
    $this->addRule('goal_amount', ts('Please enter a valid money value (e.g. %1).', array(1 => CRM_Utils_Money::format('99.99', ' '))), 'money');

    // is confirmation page enabled?
    $this->addElement('checkbox', 'is_confirm_enabled', ts('Use a confirmation page?'));

    // is this page shareable through social media ?
    $this->addElement('checkbox', 'is_share', ts('Allow sharing through social media?'));

    // is this page active ?
    $this->addElement('checkbox', 'is_active', ts('Is this Online Contribution Page Active?'));

    // should the honor be enabled
    $this->addElement('checkbox', 'honor_block_is_active', ts('Honoree Section Enabled'), NULL, array('onclick' => "showHonor()"));

    $this->add('text', 'honor_block_title', ts('Honoree Section Title'), $attributes['honor_block_title']);

    $this->add('textarea', 'honor_block_text', ts('Honoree Introductory Message'), $attributes['honor_block_text']);

    // add optional start and end dates
    $this->addDateTime('start_date', ts('Start Date'));
    $this->addDateTime('end_date', ts('End Date'));

    $this->addFormRule(array('CRM_Contribute_Form_ContributionPage_Settings', 'formRule'), $this->_id);

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
  static function formRule($values, $files, $contributionPageId) {
    $errors = array();

    //CRM-4286
    if (strstr($values['title'], '/')) {
      $errors['title'] = ts("Please do not use '/' in Title");
    }

    if (CRM_Utils_Array::value('is_organization', $values) &&
      !CRM_Utils_Array::value('onbehalf_profile_id', $values)
    ) {
      $errors['onbehalf_profile_id'] = ts('Please select a profile to collect organization information on this contribution page.');
    }

    //CRM-11494
    $start = CRM_Utils_Date::processDate($values['start_date']);
    $end = CRM_Utils_Date::processDate($values['end_date']);
    if (($end < $start) && ($end != 0)) {
      $errors['end_date'] = ts('End date should be after Start date.');
    }

    //dont allow on behalf of save when
    //pre or post profile consists of membership fields
    if ($contributionPageId && CRM_Utils_Array::value('is_organization', $values)) {
      $ufJoinParams = array(
        'module' => 'CiviContribute',
        'entity_table' => 'civicrm_contribution_page',
        'entity_id' => $contributionPageId,
      );

      list($contributionProfiles['custom_pre_id'],
        $contributionProfiles['custom_post_id']
      ) = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);

      $conProfileType = NULL;
      if ($contributionProfiles['custom_pre_id']) {
        $preProfileType = CRM_Core_BAO_UFField::getProfileType($contributionProfiles['custom_pre_id']);
        if ($preProfileType == 'Membership') {
          $conProfileType = "'Includes Profile (top of page)'";
        }
      }

      if ($contributionProfiles['custom_post_id']) {
        $postProfileType = CRM_Core_BAO_UFField::getProfileType($contributionProfiles['custom_post_id']);
        if ($postProfileType == 'Membership') {
          $conProfileType  = empty($conProfileType) ? "'Includes Profile (bottom of page)'" : "{$conProfileType} and 'Includes Profile (bottom of page)'";
        }
      }
      if (!empty($conProfileType)) {
        $errors['is_organization'] = ts("You should move the membership related fields configured in %1 to the 'On Behalf' profile for this Contribution Page", array(1 => $conProfileType));
      }
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

    $params['is_confirm_enabled'] = CRM_Utils_Array::value('is_confirm_enabled', $params, FALSE);
    $params['is_share'] = CRM_Utils_Array::value('is_share', $params, FALSE);
    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
    $params['is_credit_card_only'] = CRM_Utils_Array::value('is_credit_card_only', $params, FALSE);
    $params['honor_block_is_active'] = CRM_Utils_Array::value('honor_block_is_active', $params, FALSE);
    $params['is_for_organization'] = CRM_Utils_Array::value('is_organization', $params) ? CRM_Utils_Array::value('is_for_organization', $params, FALSE) : 0;

    $params['start_date'] = CRM_Utils_Date::processDate($params['start_date'], $params['start_date_time'], TRUE);
    $params['end_date'] = CRM_Utils_Date::processDate($params['end_date'], $params['end_date_time'], TRUE);

    $params['goal_amount'] = CRM_Utils_Rule::cleanMoney($params['goal_amount']);

    if (!$params['honor_block_is_active']) {
      $params['honor_block_title'] = NULL;
      $params['honor_block_text'] = NULL;
    }

    $dao = CRM_Contribute_BAO_ContributionPage::create($params);

    // make entry in UF join table for onbehalf of org profile
    $ufJoinParams = array(
      'is_active' => 1,
      'module' => 'OnBehalf',
      'entity_table' => 'civicrm_contribution_page',
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
      $url = 'civicrm/admin/contribute/amount';
      $urlParams = "action=update&reset=1&id={$dao->id}";
      // special case for 'Save and Done' consistency.
      if ($this->controller->getButtonName('submit') == '_qf_Amount_upload_done') {
        $url = 'civicrm/admin/contribute';
        $urlParams = 'reset=1';
        CRM_Core_Session::setStatus(ts("'%1' information has been saved.",
            array(1 => $this->getTitle())
          ), ts('Saved'), 'success');
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

