<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * Form to process actions on Premiums.
 */
class CRM_Contribute_Form_ContributionPage_Premium extends CRM_Contribute_Form_ContributionPage {

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    $defaults = array();
    if (isset($this->_id)) {
      $dao = new CRM_Contribute_DAO_Premium();
      $dao->entity_table = 'civicrm_contribution_page';
      $dao->entity_id = $this->_id;
      $dao->find(TRUE);
      CRM_Core_DAO::storeValues($dao, $defaults);
    }
    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $attributes = CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Premium');
    $this->addElement('checkbox', 'premiums_active', ts('Premiums Section Enabled?'), NULL);

    $this->addElement('text', 'premiums_intro_title', ts('Title'), $attributes['premiums_intro_title']);

    $this->add('textarea', 'premiums_intro_text', ts('Introductory Message'), 'rows=5, cols=50');

    $this->add('text', 'premiums_contact_email', ts('Contact Email') . ' ', $attributes['premiums_contact_email']);

    $this->addRule('premiums_contact_email', ts('Please enter a valid email address.') . ' ', 'email');

    $this->add('text', 'premiums_contact_phone', ts('Contact Phone'), $attributes['premiums_contact_phone']);

    $this->addRule('premiums_contact_phone', ts('Please enter a valid phone number.'), 'phone');

    $this->addElement('checkbox', 'premiums_display_min_contribution', ts('Display Minimum Contribution Amount?'));

    // CRM-10999 Control label and position for No Thank-you radio button
    $this->add('text', 'premiums_nothankyou_label', ts('No Thank-you Label'), $attributes['premiums_nothankyou_label']);
    $positions = array(1 => ts('Before Premiums'), 2 => ts('After Premiums'));
    $this->add('select', 'premiums_nothankyou_position', ts('No Thank-you Option'), $positions);
    $showForm = TRUE;

    if ($this->_single) {
      if ($this->_id) {
        $daoPremium = new CRM_Contribute_DAO_Premium();
        $daoPremium->entity_id = $this->_id;
        $daoPremium->entity_table = 'civicrm_contribution_page';
        $daoPremium->premiums_active = 1;
        if ($daoPremium->find(TRUE)) {
          $showForm = FALSE;
        }
      }
    }
    $this->assign('showForm', $showForm);

    parent::buildQuickForm();
    $this->addFormRule(array('CRM_Contribute_Form_ContributionPage_Premium', 'formRule'), $this);

    $premiumPage = new CRM_Contribute_Page_Premium();
    $premiumPage->browse();
  }

  /**
   * Validation.
   *
   * @param array $params
   *   (ref.) an assoc array of name/value pairs.
   *
   * @return bool|array
   *   mixed true or array of errors
   */
  public static function formRule($params) {
    $errors = array();
    if (!empty($params['premiums_active'])) {
      if (empty($params['premiums_nothankyou_label'])) {
        $errors['premiums_nothankyou_label'] = ts('No Thank-you Label is a required field.');
      }
    }
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);

    // we do this in case the user has hit the forward/back button

    $dao = new CRM_Contribute_DAO_Premium();
    $dao->entity_table = 'civicrm_contribution_page';
    $dao->entity_id = $this->_id;
    $dao->find(TRUE);
    $premiumID = $dao->id;
    if ($premiumID) {
      $params['id'] = $premiumID;
    }

    $params['premiums_active'] = CRM_Utils_Array::value('premiums_active', $params, FALSE);
    $params['premiums_display_min_contribution'] = CRM_Utils_Array::value('premiums_display_min_contribution', $params, FALSE);
    $params['entity_table'] = 'civicrm_contribution_page';
    $params['entity_id'] = $this->_id;

    $dao = new CRM_Contribute_DAO_Premium();
    $dao->copyValues($params);
    $dao->save();
    parent::endPostProcess();
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Premiums');
  }

}
