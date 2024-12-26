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
 * This class generates form components for PCP
 *
 */
class CRM_PCP_Form_Event extends CRM_Event_Form_ManageEvent {

  /**
   * The type of pcp component.
   *
   * @var int
   */
  public $_component = 'event';

  public function preProcess() {
    parent::preProcess();
    $this->setSelectedChild('pcp');
  }

  /**
   * Set default values for the form.
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = [];
    if (isset($this->_id)) {
      $title = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $this->_id, 'title');
      $this->setTitle(ts('Personal Campaign Page Settings (%1)', [1 => $title]));

      $params = ['entity_id' => $this->_id, 'entity_table' => 'civicrm_event'];
      CRM_Core_DAO::commonRetrieve('CRM_PCP_DAO_PCPBlock', $params, $defaults);
      $defaults['pcp_active'] = $defaults['is_active'] ?? NULL;
      // Assign contribution page ID to pageId for referencing in PCP.hlp - since $id is overwritten there. dgg
      $this->assign('pageId', $this->_id);
    }

    if (empty($defaults['id'])) {
      $defaults['target_entity_type'] = 'event';
      $defaults['is_approval_needed'] = 1;
      $defaults['is_tellfriend_enabled'] = 1;
      $defaults['tellfriend_limit'] = 5;
      $defaults['link_text'] = ts('Promote this event with a personal campaign page');
      $defaults['owner_notify_id'] = CRM_Core_OptionGroup::getDefaultValue('pcp_owner_notify');

      if ($this->_id &&
        $ccReceipt = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage', $this->_id, 'cc_receipt')
      ) {
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

    $this->addElement('checkbox', 'pcp_active', ts('Enable Personal Campaign Pages? (for this event)'), NULL, ['onclick' => "return showHideByValue('pcp_active',true,'pcpFields','table-row','radio',false);"]);

    $this->add('select', 'target_entity_type', ts('Campaign Type'),
      ['' => ts('- select -'), 'event' => ts('Event'), 'contribute' => ts('Contribution')],
      NULL, ['onchange' => "return showHideByValue('target_entity_type','contribute','pcpDetailFields','block','select',false);"]
    );

    $this->add('select', 'target_entity_id',
      ts('Online Contribution Page'),
      [
        '' => ts('- select -'),
      ] +
      CRM_Contribute_PseudoConstant::contributionPage()
    );

    parent::buildQuickForm();

    // If at least one PCP has been created, don't allow changing the target
    $pcpBlock = new CRM_PCP_DAO_PCPBlock();
    $pcpBlock->entity_table = 'civicrm_event';
    $pcpBlock->entity_id = $this->_id;
    $pcpBlock->find(TRUE);

    if (!empty($pcpBlock->id) && CRM_PCP_BAO_PCP::getPcpBlockInUse($pcpBlock->id)) {
      foreach (['target_entity_type', 'target_entity_id'] as $element_name) {
        $element = $this->getElement($element_name);
        $element->freeze();
      }
    }
    $this->addFormRule(['CRM_PCP_Form_Event', 'formRule'], $this);
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

      if (!empty($params['is_tellfriend_enabled']) && ($params['is_tellfriend_enabled'] <= 0)) {
        $errors['tellfriend_limit'] = ts('If Tell a Friend is enabled, maximum recipients limit should be greater than zero.');
      }

      if (empty($params['target_entity_type'])) {
        $errors['target_entity_type'] = ts('Campaign Type is a required field.');
      }
      elseif (($params['target_entity_type'] === 'contribute') && (empty($params['target_entity_id']))) {
        $errors['target_entity_id'] = ts('Online Contribution Page is a required field.');
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
   * @return void
   */
  public function postProcess() {
    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);

    // Source
    $params['entity_table'] = 'civicrm_event';
    $params['entity_id'] = $this->_id;

    // Target
    $params['target_entity_type'] = CRM_Utils_Array::value('target_entity_type', $params, 'event');
    if ($params['target_entity_type'] == 'event') {
      $params['target_entity_id'] = $this->_id;
    }
    else {
      $params['target_entity_id'] = CRM_Utils_Array::value('target_entity_id', $params, $this->_id);
    }

    $dao = new CRM_PCP_DAO_PCPBlock();
    $dao->entity_table = $params['entity_table'];
    $dao->entity_id = $this->_id;
    $dao->find(TRUE);
    $params['id'] = $dao->id;
    $params['is_active'] = $params['pcp_active'] ?? FALSE;
    $params['is_approval_needed'] ??= FALSE;
    $params['is_tellfriend_enabled'] ??= FALSE;

    CRM_PCP_BAO_PCPBlock::writeRecord($params);

    // Update tab "disabled" css class
    $this->ajaxResponse['tabValid'] = !empty($params['is_active']);

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
