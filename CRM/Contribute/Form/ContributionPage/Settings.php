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
    $soft_credit_types = CRM_Core_OptionGroup::values('soft_credit_type', TRUE, FALSE, FALSE, NULL, 'name');

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

      $ufJoinDAO = new CRM_Core_DAO_UFJoin();
      $ufJoinDAO->module = 'soft_credit';
      $ufJoinDAO->entity_id = $this->_id;
      if ($ufJoinDAO->find(TRUE)) {
        $defaults['honoree_profile'] = $ufJoinDAO->uf_group_id;
        $jsonData = CRM_Contribute_BAO_ContributionPage::formatMultilingualHonorParams($ufJoinDAO->module_data, TRUE);
        $defaults = array_merge($defaults, $jsonData);
        $defaults['honor_block_is_active'] = $ufJoinDAO->is_active;
      }
      else {
        $ufGroupDAO = new CRM_Core_DAO_UFGroup();
        $ufGroupDAO->name = 'honoree_individual';
        if ($ufGroupDAO->find(TRUE)) {
          $defaults['honoree_profile'] = $ufGroupDAO->id;
        }
        $defaults['soft_credit_types'] = array(
          CRM_Utils_Array::value('in_honor_of', $soft_credit_types),
          CRM_Utils_Array::value('in_memory_of', $soft_credit_types)
        );
      }
    }
    else {
      CRM_Utils_System::setTitle(ts('Title and Settings'));

      $ufGroupDAO = new CRM_Core_DAO_UFGroup();
      $ufGroupDAO->name = 'honoree_individual';
      if ($ufGroupDAO->find(TRUE)) {
        $defaults['honoree_profile'] = $ufGroupDAO->id;
      }
      $defaults['soft_credit_types'] = array(
        CRM_Utils_Array::value('in_honor_of', $soft_credit_types),
        CRM_Utils_Array::value('in_memory_of', $soft_credit_types)
      );
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
    $this->addSelect('financial_type_id', array(), TRUE);

    // name
    $this->add('text', 'title', ts('Title'), $attributes['title'], TRUE);

    //CRM-7362 --add campaigns.
    CRM_Campaign_BAO_Campaign::addCampaign($this, CRM_Utils_Array::value('campaign_id', $this->_values));

    $this->addWysiwyg('intro_text', ts('Introductory Message'), $attributes['intro_text']);

    $this->addWysiwyg('footer_text', ts('Footer Message'), $attributes['footer_text']);

    //Register schema which will be used for OnBehalOf and HonorOf profile Selector
    CRM_UF_Page_ProfileEditor::registerSchemas(array('OrganizationModel', 'HouseholdModel'));

    // is on behalf of an organization ?
    $this->addElement('checkbox', 'is_organization', ts('Allow individuals to contribute and / or signup for membership on behalf of an organization?'), NULL, array('onclick' => "showHideByValue('is_organization',true,'for_org_text','table-row','radio',false);showHideByValue('is_organization',true,'for_org_option','table-row','radio',false);"));

    $allowCoreTypes = array_merge(array('Contact', 'Organization'), CRM_Contact_BAO_ContactType::subTypes('Organization'));
    $allowSubTypes = array();
    $entities = array(
      array(
        'entity_name' => 'contact_1',
        'entity_type' => 'OrganizationModel',
      ),
    );

    $this->addProfileSelector('onbehalf_profile_id', ts('Organization Profile'), $allowCoreTypes, $allowSubTypes, $entities);

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

    $this->add('text', 'honor_block_title', ts('Honoree Section Title'), array('maxlength' => 255, 'size' => 45));

    $this->add('textarea', 'honor_block_text', ts('Honoree Introductory Message'), array('rows' => 2, 'cols' => 50));

    $this->addSelect('soft_credit_types', array(
      'label' => ts('Honor Types'),
      'entity' => 'ContributionSoft',
      'field' => 'soft_credit_type_id',
      'multiple' => TRUE,
      'class' => 'huge'
    ));

    $entities = array(
      array(
        'entity_name' => 'contact_1',
        'entity_type' => 'IndividualModel',
      ),
    );

    $allowCoreTypes = array_merge(array('Contact', 'Individual', 'Organization', 'Household'), CRM_Contact_BAO_ContactType::subTypes('Individual'));
    $allowSubTypes = array();

    $this->addProfileSelector('honoree_profile', ts('Honoree Profile'), $allowCoreTypes, $allowSubTypes, $entities);

    if (!empty($this->_submitValues['honor_block_is_active'])) {
      $this->addRule('soft_credit_types', ts('At least one value must be selected if Honor Section is active'), 'required');
      $this->addRule('honoree_profile', ts('Please select a profile used for honoree'), 'required');
    }

    // add optional start and end dates
    $this->addDateTime('start_date', ts('Start Date'));
    $this->addDateTime('end_date', ts('End Date'));

    $this->addFormRule(array('CRM_Contribute_Form_ContributionPage_Settings', 'formRule'), $this);

    parent::buildQuickForm();
  }

  /**
   * global validation rules for the form
   *
   * @param array $values posted values of the form
   *
   * @param $files
   * @param $self
   *
   * @return array list of errors to be posted back to the form
   * @static
   * @access public
   */
  static function formRule($values, $files, $self) {
    $errors = array();
    $contributionPageId = $self->_id;
    //CRM-4286
    if (strstr($values['title'], '/')) {
      $errors['title'] = ts("Please do not use '/' in Title");
    }

    // ensure on-behalf-of profile meets minimum requirements
    if (!empty($values['is_organization'])) {
      if (empty($values['onbehalf_profile_id']) ) {
        $errors['onbehalf_profile_id'] = ts('Please select a profile to collect organization information on this contribution page.');
      }
      else {
        $requiredProfileFields = array('organization_name', 'email');
        if (!CRM_Core_BAO_UFGroup::checkValidProfile($values['onbehalf_profile_id'], $requiredProfileFields)) {
          $errors['onbehalf_profile_id'] = ts('Profile does not contain the minimum required fields for an On Behalf Of Organization');
        }
      }
    }

    //CRM-11494
    $start = CRM_Utils_Date::processDate($values['start_date']);
    $end = CRM_Utils_Date::processDate($values['end_date']);
    if (($end < $start) && ($end != 0)) {
      $errors['end_date'] = ts('End date should be after Start date.');
    }

    if (!empty($self->_values['payment_processor']) && $financialType = CRM_Contribute_BAO_Contribution::validateFinancialType($values['financial_type_id'])) {
      $errors['financial_type_id'] = ts("Financial Account of account relationship of 'Expense Account is' is not configured for Financial Type : ") . $financialType;
    }

    //dont allow on behalf of save when
    //pre or post profile consists of membership fields
    if ($contributionPageId && !empty($values['is_organization'])) {
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
    $params['is_for_organization'] = !empty($params['is_organization']) ? CRM_Utils_Array::value('is_for_organization', $params, FALSE) : 0;

    $params['start_date'] = CRM_Utils_Date::processDate($params['start_date'], $params['start_date_time'], TRUE);
    $params['end_date'] = CRM_Utils_Date::processDate($params['end_date'], $params['end_date_time'], TRUE);

    $params['goal_amount'] = CRM_Utils_Rule::cleanMoney($params['goal_amount']);

    if (!$params['honor_block_is_active']) {
      $params['honor_block_title'] = NULL;
      $params['honor_block_text'] = NULL;
    }
    else {
      $sctJSON = CRM_Contribute_BAO_ContributionPage::formatMultilingualHonorParams($params);
    }

    $dao = CRM_Contribute_BAO_ContributionPage::create($params);

    $ufJoinParams = array(
      'onbehalf_profile_id' =>
        array(
          'is_active' => 1,
          'module' => 'OnBehalf',
          'entity_table' => 'civicrm_contribution_page',
          'entity_id' => $dao->id,
        ),
      'honor_block_is_active' =>
        array(
          'module' => 'soft_credit',
          'entity_table' => 'civicrm_contribution_page',
          'entity_id' => $dao->id,
        )
    );


    foreach ($ufJoinParams as $index => $ufJoinParam) {
      if (!empty($params[$index])) {
          $ufJoinParam['weight'] = 1;
          if ($index == 'honor_block_is_active') {
            $ufJoinParam['is_active'] = 1;
            $ufJoinParam['module'] = 'soft_credit';
            $ufJoinParam['uf_group_id'] = $params['honoree_profile'];
            $ufJoinParam['module_data'] = $sctJSON;
          }
          else {
            // first delete all past entries
            CRM_Core_BAO_UFJoin::deleteAll($ufJoinParam);
            $ufJoinParam['uf_group_id'] = $params[$index];
          }
            CRM_Core_BAO_UFJoin::create($ufJoinParam);
        }
      elseif ($index == 'honor_block_is_active') {
        //On subsequent honor_block_is_active uncheck, disable(don't delete)
        //that particular honoree profile entry in UFjoin table, CRM-13981
        $ufId = CRM_Core_BAO_UFJoin::findJoinEntryId($ufJoinParam);
        if ($ufId) {
          $ufJoinParam['uf_group_id'] = CRM_Core_BAO_UFJoin::findUFGroupId($ufJoinParam);
          $ufJoinParam['is_active'] = 0;
          CRM_Core_BAO_UFJoin::create($ufJoinParam);
        }
      }
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

