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
 * This class generates form components for Tell A Friend
 *
 */
class CRM_PCP_Form_Contribute extends CRM_Contribute_Form_ContributionPage {

  /**
   * The type of pcp component.
   *
   * @var int
   */
  public $_component = 'contribute';

  public function preProcess() {
    parent::preProcess();
    $this->setSelectedChild('pcp');
  }

  /**
   * Set default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   *
   * @return void
   */
  public function setDefaultValues() {
    $defaults = [];

    if (isset($this->_id)) {
      $params = ['entity_id' => $this->_id, 'entity_table' => 'civicrm_contribution_page'];
      CRM_Core_DAO::commonRetrieve('CRM_PCP_DAO_PCPBlock', $params, $defaults);
      $defaults['pcp_active'] = $defaults['is_active'] ?? NULL;
      // Assign contribution page ID to pageId for referencing in PCP.hlp - since $id is overwritten there. dgg
      $this->assign('pageId', $this->_id);
    }

    if (empty($defaults['id'])) {
      $defaults['target_entity_type'] = 'contribute';
      $defaults['is_approval_needed'] = 1;
      $defaults['is_tellfriend_enabled'] = 1;
      $defaults['tellfriend_limit'] = 5;
      $defaults['link_text'] = ts('Create your own fundraising page');
      $defaults['owner_notify_id'] = CRM_Core_OptionGroup::getDefaultValue('pcp_owner_notify');

      if ($ccReceipt = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage', $this->_id, 'cc_receipt')) {
        $defaults['notify_email'] = $ccReceipt;
      }
    }
    return $defaults;
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    CRM_PCP_BAO_PCP::buildPCPForm($this);
    $this->addElement('checkbox', 'pcp_active', ts('Enable Personal Campaign Pages? (for this contribution page)'), NULL, ['onclick' => "return showHideByValue('pcp_active',true,'pcpFields','table-row','radio',false);"]);
    parent::buildQuickForm();
    $this->addFormRule(['CRM_PCP_Form_Contribute', 'formRule'], $this);
  }

  /**
   * Validation.
   *
   * @param array $params
   *   (ref.) an assoc array of name/value pairs.
   *
   * @param $files
   * @param self $self
   *
   * @return bool|array
   *   mixed true or array of errors
   */
  public static function formRule($params, $files, $self) {
    $errors = [];
    if (!empty($params['pcp_active'])) {

      if (!empty($params['is_tellfriend_enabled']) &&
        (CRM_Utils_Array::value('tellfriend_limit', $params) <= 0)
      ) {
        $errors['tellfriend_limit'] = ts('if Tell Friend is enabled, Maximum recipients limit should be greater than zero.');
      }
      if (empty($params['supporter_profile_id'])) {
        $errors['supporter_profile_id'] = ts('Supporter profile is a required field.');
      }
      else {
        if (CRM_PCP_BAO_PCP::checkEmailProfile($params['supporter_profile_id'])) {
          $errors['supporter_profile_id'] = ts('Profile is not configured with Email address.');
        }
      }

      $emails = $params['notify_email'] ?? NULL;
      if ($emails) {
        $emailArray = explode(',', $emails);
        foreach ($emailArray as $email) {
          if ($email && !CRM_Utils_Rule::email(trim($email))) {
            $errors['notify_email'] = ts('A valid Notify Email address must be specified');
          }
        }
      }
    }
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form submission.
   *
   *
   * @return void
   */
  public function postProcess() {
    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);

    // Source
    $params['entity_table'] = 'civicrm_contribution_page';
    $params['entity_id'] = $this->_id;

    // Target
    $params['target_entity_type'] = CRM_Utils_Array::value('target_entity_type', $params, 'contribute');
    $params['target_entity_id'] = $this->_id;

    $dao = new CRM_PCP_DAO_PCPBlock();
    $dao->entity_table = $params['entity_table'];
    $dao->entity_id = $this->_id;
    $dao->find(TRUE);
    $params['id'] = $dao->id;
    $params['is_active'] = $params['pcp_active'] ?? FALSE;
    $params['is_approval_needed'] ??= FALSE;
    $params['is_tellfriend_enabled'] ??= FALSE;

    CRM_PCP_BAO_PCPBlock::writeRecord($params);

    parent::endPostProcess();
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Enable Personal Campaign Pages');
  }

}
