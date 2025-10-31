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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Form to process actions on the group aspect of Custom Data.
 */
class CRM_Contribute_Form_ContributionPage_Custom extends CRM_Contribute_Form_ContributionPage {

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    parent::preProcess();
    $this->setSelectedChild('custom');
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {

    // Register 'contact_1' model
    $entities = [];
    $entities[] = ['entity_name' => 'contact_1', 'entity_type' => 'IndividualModel'];
    $allowCoreTypes = array_merge(['Contact', 'Individual'], CRM_Contact_BAO_ContactType::subTypes('Individual'));

    // Register 'contribution_1'
    $financialTypeId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage', $this->_id, 'financial_type_id');
    $allowCoreTypes[] = 'Contribution';
    $entities[] = [
      'entity_name' => 'contribution_1',
      'entity_type' => 'ContributionModel',
      'entity_sub_type' => '*',
    ];

    // If applicable, register 'membership_1'
    $member = CRM_Member_BAO_Membership::getMembershipBlock($this->_id);
    if ($member && $member['is_active']) {
      //CRM-15427
      $entities[] = [
        'entity_name' => 'membership_1',
        'entity_type' => 'MembershipModel',
        'entity_sub_type' => '*',
      ];
      $allowCoreTypes[] = 'Membership';
    }

    $this->addProfileSelector('custom_pre_id', ts('Top Profile Fields'), $allowCoreTypes);
    $this->addProfileSelector('custom_post_id', ts('Bottom Profile Fields'), $allowCoreTypes);
    $this->addFormRule(['CRM_Contribute_Form_ContributionPage_Custom', 'formRule'], $this);

    parent::buildQuickForm();
  }

  /**
   * Set default values for the form.
   *
   * Note that in edit/view mode the default values are retrieved from the database.
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    $defaults['custom_pre_id'] = $this->_values['custom_pre_id'];
    $defaults['custom_post_id'] = $this->_values['custom_post_id'];

    return $defaults;
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);

    if ($this->_action & CRM_Core_Action::UPDATE) {
      $params['id'] = $this->_id;
    }

    $transaction = new CRM_Core_Transaction();

    // also update uf join table
    $ufJoinParams = [
      'is_active' => 1,
      'module' => 'CiviContribute',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => $this->_id,
    ];

    // first delete all past entries
    CRM_Core_BAO_UFJoin::deleteAll($ufJoinParams);

    if (!empty($params['custom_pre_id'])) {
      $ufJoinParams['weight'] = 1;
      $ufJoinParams['uf_group_id'] = $params['custom_pre_id'];
      CRM_Core_BAO_UFJoin::create($ufJoinParams);
    }

    unset($ufJoinParams['id']);

    if (!empty($params['custom_post_id'])) {
      $ufJoinParams['weight'] = 2;
      $ufJoinParams['uf_group_id'] = $params['custom_post_id'];
      CRM_Core_BAO_UFJoin::create($ufJoinParams);
    }

    $transaction->commit();
    parent::endPostProcess();
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Include Profiles');
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   *
   * @param $files
   * @param object $form
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $files, $form) {
    $errors = [];
    $preProfileType = $postProfileType = NULL;
    // for membership profile make sure Membership section is enabled
    // get membership section for this contribution page
    $dao = new CRM_Member_DAO_MembershipBlock();
    $dao->entity_table = 'civicrm_contribution_page';
    $dao->entity_id = $form->_id;

    $membershipEnable = FALSE;

    if ($dao->find(TRUE) && $dao->is_active) {
      $membershipEnable = TRUE;
    }

    if ($fields['custom_pre_id']) {
      $preProfileType = CRM_Core_BAO_UFField::getProfileType($fields['custom_pre_id']);
    }

    if ($fields['custom_post_id']) {
      $postProfileType = CRM_Core_BAO_UFField::getProfileType($fields['custom_post_id']);
    }

    $errorMsg = ts('You must enable the Membership Block for this Contribution Page if you want to include a Profile with Membership fields.');

    if (($preProfileType == 'Membership') && !$membershipEnable) {
      $errors['custom_pre_id'] = $errorMsg;
    }

    if (($postProfileType == 'Membership') && !$membershipEnable) {
      $errors['custom_post_id'] = $errorMsg;
    }

    $behalf = (!empty($form->_values['onbehalf_profile_id'])) ? $form->_values['onbehalf_profile_id'] : NULL;
    if ($fields['custom_pre_id']) {
      $errorMsg = ts('You should move the membership related fields in the "On Behalf" profile for this Contribution Page');
      if ($preProfileType == 'Membership' && $behalf) {
        $errors['custom_pre_id'] = isset($errors['custom_pre_id']) ? $errors['custom_pre_id'] . $errorMsg : $errorMsg;
      }
    }

    if ($fields['custom_post_id']) {
      $errorMsg = ts('You should move the membership related fields in the "On Behalf" profile for this Contribution Page');
      if ($postProfileType == 'Membership' && $behalf) {
        $errors['custom_post_id'] = isset($errors['custom_post_id']) ? $errors['custom_post_id'] . $errorMsg : $errorMsg;
      }
    }
    return empty($errors) ? TRUE : $errors;
  }

}
