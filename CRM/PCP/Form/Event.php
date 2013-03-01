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
 * This class generates form components for PCP
 *
 */
class CRM_PCP_Form_Event extends CRM_Event_Form_ManageEvent {

  /**
   * the type of pcp component.
   *
   * @var int
   * @protected
   */
  public $_component = 'event';


  public function preProcess() {
    parent::preProcess();
  }

  /**
   * This function sets the default values for the form.
   *
   * @access public
   *
   * @return None
   */
  public function setDefaultValues() {
    $defaults = array();

    $defaults = array();
    if (isset($this->_id)) {
    $title = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $this->_id, 'title');
    CRM_Utils_System::setTitle(ts('Personal Campaign Page Settings (%1)', array(1 => $title)));

      $params = array('entity_id' => $this->_id, 'entity_table' => 'civicrm_event');
      CRM_Core_DAO::commonRetrieve('CRM_PCP_DAO_PCPBlock', $params, $defaults);
      $defaults['pcp_active'] = CRM_Utils_Array::value('is_active', $defaults);
      // Assign contribution page ID to pageId for referencing in PCP.hlp - since $id is overwritten there. dgg
      $this->assign('pageId', $this->_id);
    }

    if (!CRM_Utils_Array::value('id', $defaults)) {
      $defaults['target_entity_type'] = 'event';
      $defaults['is_approval_needed'] = 1;
      $defaults['is_tellfriend_enabled'] = 1;
      $defaults['tellfriend_limit'] = 5;
      $defaults['link_text'] = ts('Promote this event with a personal campaign page');

      if ($this->_id && 
          $ccReceipt = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage', $this->_id, 'cc_receipt')) {
        $defaults['notify_email'] = $ccReceipt;
      }
    }
    return $defaults;
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    CRM_PCP_BAO_PCP::buildPCPForm($this);

    $this->addElement('checkbox', 'pcp_active', ts('Enable Personal Campaign Pages? (for this event)'), NULL, array('onclick' => "return showHideByValue('pcp_active',true,'pcpFields','table-row','radio',false);"));

    $this->add('select', 'target_entity_type', ts('Campaign Type'),
      array('' => ts('- select -'), 'event' => ts('Event'), 'contribute' => ts('Contribution')),
      NULL, array('onchange' => "return showHideByValue('target_entity_type','contribute','pcpDetailFields','block','select',false);")
    );

    $this->add('select', 'target_entity_id',
      ts('Online Contribution Page'),
      array(
        '' => ts('- select -')) +
      CRM_Contribute_PseudoConstant::contributionPage()
    );

    parent::buildQuickForm();

    // If at least one PCP has been created, don't allow changing the target
    $pcpBlock = new CRM_PCP_DAO_PCPBlock();
    $pcpBlock->entity_table = 'civicrm_event';
    $pcpBlock->entity_id = $this->_id;
    $pcpBlock->find(TRUE);

    if (!empty($pcpBlock->id) && CRM_PCP_BAO_PCP::getPcpBlockInUse($pcpBlock->id)) {
      foreach (array(
        'target_entity_type', 'target_entity_id') as $element_name) {
        $element = $this->getElement($element_name);
        $element->freeze();
      }
    }
    $this->addFormRule(array('CRM_PCP_Form_Event', 'formRule'), $this);
  }

  /**
   * Function for validation
   *
   * @param array $params (ref.) an assoc array of name/value pairs
   *
   * @return mixed true or array of errors
   * @access public
   * @static
   */
  public static function formRule($params, $files, $self) {
    $errors = array();
    if (CRM_Utils_Array::value('is_active', $params)) {

      if (CRM_Utils_Array::value('is_tellfriend_enabled', $params) &&
        (CRM_Utils_Array::value('tellfriend_limit', $params) <= 0)
      ) {
        $errors['tellfriend_limit'] = ts('if Tell Friend is enable, Maximum recipients limit should be greater than zero.');
      }
      if (!CRM_Utils_Array::value('supporter_profile_id', $params)) {
        $errors['supporter_profile_id'] = ts('Supporter profile is a required field.');
      }
      else {
        if (CRM_PCP_BAO_PCP::checkEmailProfile($params['supporter_profile_id'])) {
          $errors['supporter_profile_id'] = ts('Profile is not configured with Email address.');
        }
      }

      if ($emails = CRM_Utils_Array::value('notify_email', $params)) {
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
   * Function to process the form
   *
   * @access public
   *
   * @return None
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

    $dao               = new CRM_PCP_DAO_PCPBlock();
    $dao->entity_table = $params['entity_table'];
    $dao->entity_id    = $this->_id;
    $dao->find(TRUE);
    $params['id'] = $dao->id;
    $params['is_active'] = CRM_Utils_Array::value('pcp_active', $params, FALSE);
    $params['is_approval_needed'] = CRM_Utils_Array::value('is_approval_needed', $params, FALSE);
    $params['is_tellfriend_enabled'] = CRM_Utils_Array::value('is_tellfriend_enabled', $params, FALSE);

    $dao = CRM_PCP_BAO_PCP::add($params);

    parent::endPostProcess();
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   * @access public
   */
  public function getTitle() {
    return ts('Enable Personal Campaign Pages');
  }
}

