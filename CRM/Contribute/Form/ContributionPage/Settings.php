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
class CRM_Contribute_Form_ContributionPage_Settings extends CRM_Contribute_Form_ContributionPage {

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    parent::preProcess();
    $this->setSelectedChild('settings');
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();
    // @todo handle properly on parent.
    if (!$this->_id) {
      $defaults['start_date'] = date('Y-m-d H:i:s');
      unset($defaults['start_time']);
    }
    $soft_credit_types = CRM_Core_OptionGroup::values('soft_credit_type', TRUE, FALSE, FALSE, NULL, 'name');

    if ($this->_id) {
      $title = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage',
        $this->_id,
        'title'
      );
      $this->setTitle(ts('Title and Settings') . " ($title)");

      foreach (['on_behalf', 'soft_credit'] as $module) {
        $ufJoinDAO = new CRM_Core_DAO_UFJoin();
        $ufJoinDAO->module = $module;
        $ufJoinDAO->entity_id = $this->_id;
        $ufJoinDAO->entity_table = 'civicrm_contribution_page';
        if ($ufJoinDAO->find(TRUE)) {
          $jsonData = CRM_Contribute_BAO_ContributionPage::formatModuleData($ufJoinDAO->module_data, TRUE, $module);
          if ($module == 'soft_credit') {
            $defaults['honoree_profile'] = $ufJoinDAO->uf_group_id;
            $defaults = array_merge($defaults, $jsonData);
            $defaults['honor_block_is_active'] = $ufJoinDAO->is_active;
          }
          else {
            $defaults['onbehalf_profile_id'] = $ufJoinDAO->uf_group_id;
            $defaults = array_merge($defaults, $jsonData);
            $defaults['is_organization'] = $ufJoinDAO->is_active;
          }
        }
        else {
          if ($module == 'soft_credit') {
            $ufGroupDAO = new CRM_Core_DAO_UFGroup();
            $ufGroupDAO->name = 'honoree_individual';
            if ($ufGroupDAO->find(TRUE)) {
              $defaults['honoree_profile'] = $ufGroupDAO->id;
            }
            $defaults['soft_credit_types'] = [
              $soft_credit_types['in_honor_of'] ?? NULL,
              $soft_credit_types['in_memory_of'] ?? NULL,
            ];
          }
          else {
            $ufGroupDAO = new CRM_Core_DAO_UFGroup();
            $ufGroupDAO->name = 'on_behalf_organization';
            if ($ufGroupDAO->find(TRUE)) {
              $defaults['onbehalf_profile_id'] = $ufGroupDAO->id;
            }
            $defaults['for_organization'] = ts('I am contributing on behalf of an organization.');
            $defaults['is_for_organization'] = 1;
          }
        }
      }
    }
    else {
      $ufGroupDAO = new CRM_Core_DAO_UFGroup();
      $ufGroupDAO->name = 'honoree_individual';
      if ($ufGroupDAO->find(TRUE)) {
        $defaults['honoree_profile'] = $ufGroupDAO->id;
      }
      $defaults['soft_credit_types'] = [
        $soft_credit_types['in_honor_of'] ?? NULL,
        $soft_credit_types['in_memory_of'] ?? NULL,
      ];
    }

    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {

    $this->_first = TRUE;
    $attributes = CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionPage');

    // financial Type
    CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($financialTypes, CRM_Core_Action::ADD);
    $financialOptions = [
      'options' => $financialTypes,
    ];
    if (!CRM_Core_Permission::check('administer CiviCRM Financial Types')) {
      $financialOptions['context'] = 'search';
    }
    $this->addSelect('financial_type_id', $financialOptions, TRUE);

    // name
    $this->add('text', 'title', ts('Title'), $attributes['title'], TRUE);
    $this->addField('frontend_title', ['entity' => 'ContributionPage'], TRUE);

    //CRM-7362 --add campaigns.
    CRM_Campaign_BAO_Campaign::addCampaign($this, $this->_values['campaign_id'] ?? NULL);

    $this->add('wysiwyg', 'intro_text', ts('Introductory Message'), $attributes['intro_text']);

    $this->add('wysiwyg', 'footer_text', ts('Footer Message'), $attributes['footer_text']);

    //Register schema which will be used for OnBehalOf and HonorOf profile Selector
    CRM_UF_Page_ProfileEditor::registerSchemas(['OrganizationModel', 'HouseholdModel']);

    // is on behalf of an organization ?
    $this->addElement('checkbox', 'is_organization', ts('Allow individuals to contribute and / or signup for membership on behalf of an organization?'), NULL, ['onclick' => "showHideByValue('is_organization',true,'for_org_text','table-row','radio',false);showHideByValue('is_organization',true,'for_org_option','table-row','radio',false);"]);

    //CRM-15787 - If applicable, register 'membership_1'
    $member = CRM_Member_BAO_Membership::getMembershipBlock($this->_id);
    $coreTypes = ['Contact', 'Organization'];

    $entities[] = [
      'entity_name' => ['contact_1'],
      'entity_type' => 'OrganizationModel',
    ];

    if ($member && $member['is_active']) {
      $coreTypes[] = 'Membership';
      $entities[] = [
        'entity_name' => ['membership_1'],
        'entity_type' => 'MembershipModel',
      ];
    }

    $allowCoreTypes = array_merge($coreTypes, CRM_Contact_BAO_ContactType::subTypes('Organization'));
    $allowSubTypes = [];

    $this->addProfileSelector('onbehalf_profile_id', ts('Organization Profile'), $allowCoreTypes, $allowSubTypes, $entities);

    $this->addRadio('is_for_organization', '', [1 => ts('Optional'), 2 => ts('Required')]);
    $this->add('textarea', 'for_organization', ts('On behalf of Label'), ['rows' => 2, 'cols' => 50]);

    // collect goal amount
    $this->add('text', 'goal_amount', ts('Goal Amount'), ['size' => 8, 'maxlength' => 12]);
    $this->addRule('goal_amount', ts('Please enter a valid money value (e.g. %1).', [1 => CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency('99.99')]), 'money');

    // is confirmation page enabled?
    $this->addElement('checkbox', 'is_confirm_enabled', ts('Use a confirmation page?'));

    // is this page shareable through social media ?
    $this->addElement('checkbox', 'is_share', ts('Add footer region with Twitter, Facebook and LinkedIn share buttons and scripts?'));

    // is this page active ?
    $this->addElement('checkbox', 'is_active', ts('Is this Online Contribution Page Active?'));

    // should the honor be enabled
    $this->addElement('checkbox', 'honor_block_is_active', ts('Honoree Section Enabled'), NULL, ['onclick' => "showHonor()"]);

    $this->add('text', 'honor_block_title', ts('Honoree Section Title'), ['maxlength' => 255, 'size' => 45]);

    $this->add('textarea', 'honor_block_text', ts('Honoree Introductory Message'), ['rows' => 2, 'cols' => 50]);

    $this->addSelect('soft_credit_types', [
      'label' => ts('Honor Types'),
      'entity' => 'ContributionSoft',
      'field' => 'soft_credit_type_id',
      'multiple' => TRUE,
      'class' => 'huge',
    ]);

    $entities = [
      [
        'entity_name' => 'contact_1',
        'entity_type' => 'IndividualModel',
      ],
    ];

    $allowCoreTypes = array_merge([
      'Contact',
      'Individual',
      'Organization',
      'Household',
    ], CRM_Contact_BAO_ContactType::subTypes('Individual'));
    $allowSubTypes = [];

    $this->addProfileSelector('honoree_profile', ts('Honoree Profile'), $allowCoreTypes, $allowSubTypes, $entities);

    if (!empty($this->_submitValues['honor_block_is_active'])) {
      $this->addRule('soft_credit_types', ts('At least one value must be selected if Honor Section is active'), 'required');
      $this->addRule('honoree_profile', ts('Please select a profile used for honoree'), 'required');
    }

    // add optional start and end dates
    $this->add('datepicker', 'start_date', ts('Start Date'));
    $this->add('datepicker', 'end_date', ts('End Date'));

    $this->addFormRule(['CRM_Contribute_Form_ContributionPage_Settings', 'formRule'], $this);

    parent::buildQuickForm();
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $values
   *   Posted values of the form.
   *
   * @param $files
   * @param self $self
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($values, $files, $self) {
    $errors = [];
    $contributionPageId = $self->_id;

    // ensure on-behalf-of profile meets minimum requirements
    if (!empty($values['is_organization'])) {
      if (empty($values['onbehalf_profile_id'])) {
        $errors['onbehalf_profile_id'] = ts('Please select a profile to collect organization information on this contribution page.');
      }
      else {
        $requiredProfileFields = ['organization_name', 'email'];
        if (!CRM_Core_BAO_UFGroup::checkValidProfile($values['onbehalf_profile_id'], $requiredProfileFields)) {
          $errors['onbehalf_profile_id'] = ts('Profile does not contain the minimum required fields for an On Behalf Of Organization');
        }
      }
    }

    // Validate start/end date inputs
    $validateDates = \CRM_Utils_Date::validateStartEndDatepickerInputs('start_date', $values['start_date'], 'end_date', $values['end_date']);
    if ($validateDates !== TRUE) {
      $errors[$validateDates['key']] = $validateDates['message'];
    }

    if (!empty($self->_values['payment_processor']) && $financialType = CRM_Contribute_BAO_Contribution::validateFinancialType($values['financial_type_id'])) {
      $errors['financial_type_id'] = ts("Financial Account of account relationship of 'Expense Account is' is not configured for Financial Type : ") . $financialType;
    }

    //dont allow on behalf of save when
    //pre or post profile consists of membership fields
    if ($contributionPageId && !empty($values['is_organization'])) {
      $ufJoinParams = [
        'module' => 'CiviContribute',
        'entity_table' => 'civicrm_contribution_page',
        'entity_id' => $contributionPageId,
      ];

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
          $conProfileType = empty($conProfileType) ? "'Includes Profile (bottom of page)'" : "{$conProfileType} and 'Includes Profile (bottom of page)'";
        }
      }
      if (!empty($conProfileType)) {
        $errors['is_organization'] = ts("You should move the membership related fields configured in %1 to the 'On Behalf' profile for this Contribution Page", [1 => $conProfileType]);
      }
    }
    return $errors;
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);

    // we do this in case the user has hit the forward/back button
    if ($this->_id) {
      $params['id'] = $this->_id;
    }
    else {
      $config = CRM_Core_Config::singleton();
      $params['currency'] = $config->defaultCurrency;
    }

    $params['is_confirm_enabled'] ??= FALSE;
    $params['is_share'] ??= FALSE;
    $params['is_active'] ??= FALSE;
    $params['is_credit_card_only'] ??= FALSE;
    $params['honor_block_is_active'] ??= FALSE;
    $params['is_for_organization'] ??= FALSE;
    $params['goal_amount'] = CRM_Utils_Rule::cleanMoney($params['goal_amount']);

    if (!$params['honor_block_is_active']) {
      $params['honor_block_title'] = NULL;
      $params['honor_block_text'] = NULL;
    }

    $dao = CRM_Contribute_BAO_ContributionPage::writeRecord($params);

    $ufJoinParams = [
      'is_organization' => [
        'module' => 'on_behalf',
        'entity_table' => 'civicrm_contribution_page',
        'entity_id' => $dao->id,
      ],
      'honor_block_is_active' => [
        'module' => 'soft_credit',
        'entity_table' => 'civicrm_contribution_page',
        'entity_id' => $dao->id,
      ],
    ];

    foreach ($ufJoinParams as $index => $ufJoinParam) {
      if (!empty($params[$index])) {
        // Look for an existing entry
        $ufJoinDAO = new CRM_Core_DAO_UFJoin();
        $ufJoinDAO->module = $ufJoinParam['module'];
        $ufJoinDAO->entity_table = 'civicrm_contribution_page';
        $ufJoinDAO->entity_id = $ufJoinParam['entity_id'];
        $ufJoinDAO->find(TRUE);

        if (!empty($ufJoinDAO->id)) {
          $ufJoinParam['id'] = $ufJoinDAO->id;
        }

        $ufJoinParam['weight'] = 1;
        $ufJoinParam['is_active'] = 1;
        if ($index == 'honor_block_is_active') {
          $ufJoinParam['uf_group_id'] = $params['honoree_profile'];
          $ufJoinParam['module_data'] = CRM_Contribute_BAO_ContributionPage::formatModuleData($params, FALSE, 'soft_credit');
        }
        else {
          $ufJoinParam['uf_group_id'] = $params['onbehalf_profile_id'];
          $ufJoinParam['module_data'] = CRM_Contribute_BAO_ContributionPage::formatModuleData($params, FALSE, 'on_behalf');
        }
        CRM_Core_BAO_UFJoin::create($ufJoinParam);
      }
      else {
        if ($index == 'honor_block_is_active') {
          $params['honor_block_title'] = NULL;
          $params['honor_block_text'] = NULL;
        }
        else {
          $params['for_organization'] = NULL;
        }

        //On subsequent honor_block_is_active uncheck, disable(don't delete)
        //that particular honoree profile entry in UFjoin table, CRM-13981
        $ufID = CRM_Core_BAO_UFJoin::findJoinEntryId($ufJoinParam);
        if ($ufID) {
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
      CRM_Utils_System::redirect(CRM_Utils_System::url($url, $urlParams));
    }
    parent::endPostProcess();
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Title and Settings');
  }

}
