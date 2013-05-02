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
 * This class generates form components for Option Value
 *
 */
class CRM_Admin_Form_OptionValue extends CRM_Admin_Form {
  static $_gid = NULL;

  /**
   * The option group name
   *
   * @var string
   * @static
   */
  static $_gName = NULL;

  /**
   * Function to for pre-processing
   *
   * @return None
   * @access public
   */
  public function preProcess() {
    parent::preProcess();
    $this->_gid = CRM_Utils_Request::retrieve('gid', 'Positive',
      $this, FALSE, 0
    );
    //get optionGroup name in case of email/postal greeting or addressee, CRM-4575
    if (!empty($this->_gid)) {
      $this->_gName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', $this->_gid, 'name');
    }
    $session = CRM_Core_Session::singleton();
    $url = CRM_Utils_System::url('civicrm/admin/optionValue', 'reset=1&action=browse&gid=' . $this->_gid);
    $session->replaceUserContext($url);

    $this->assign('id', $this->_id);

    if ($this->_id && in_array($this->_gName, CRM_Core_OptionGroup::$_domainIDGroups)) {
      $domainID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $this->_id, 'domain_id', 'id');
      if (CRM_Core_Config::domainID() != $domainID) {
        CRM_Core_Error::fatal(ts('You do not have permission to access this page'));
      }
    }
  }

  /**
   * This function sets the default values for the form.
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    $defaults = array();
    $defaults = parent::setDefaultValues();
    if (!CRM_Utils_Array::value('weight', $defaults)) {
      $query = "SELECT max( `weight` ) as weight FROM `civicrm_option_value` where option_group_id=" . $this->_gid;
      $dao = new CRM_Core_DAO();
      $dao->query($query);
      $dao->fetch();
      $defaults['weight'] = ($dao->weight + 1);
    }
    
    // CRM-11516
    if ($this->_gName == 'payment_instrument' && $this->_id) {
      $defaults['financial_account_id'] = CRM_Financial_BAO_FinancialTypeAccount::getFinancialAccount($this->_id, 'civicrm_option_value', 'financial_account_id');
    }
    //setDefault of contact types for email greeting, postal greeting, addressee, CRM-4575
    if (in_array($this->_gName, array(
      'email_greeting', 'postal_greeting', 'addressee'))) {
      $defaults['contactOptions'] = CRM_Utils_Array::value('filter', $defaults);
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
    //CRM-4575
    $isReserved = FALSE;
    if ($this->_id) {
      $isReserved = (bool) CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $this->_id, 'is_reserved');
    }
    parent::buildQuickForm();
    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $this->applyFilter('__ALL__', 'trim');
    $this->add('text', 'label', ts('Title'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'label'), TRUE);
    $this->add('text', 'value', ts('Value'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'value'), TRUE);
    $this->add('text', 'name', ts('Name'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'name'));
    if ($this->_gName == 'custom_search') {
      $this->add('text',
        'description',
        ts('Description'),
        CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'description')
      );
    }
    else {
      $this->addWysiwyg('description',
        ts('Description'),
        CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'description')
      );
    }

    if ($this->_gName == 'case_status') {
      $grouping = $this->add('select', 'grouping', ts('Option Grouping Name'), array('Opened' => ts('Opened'),
          'Closed' => ts('Closed'),
        ));
      if ($isReserved) {
        $grouping->freeze();
      }
    }
    else {
      $this->add('text', 'grouping', ts('Grouping'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'grouping'));
    }

    $this->add('text', 'weight', ts('Weight'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'weight'), TRUE);
    $this->add('checkbox', 'is_active', ts('Enabled?'));
    $this->add('checkbox', 'is_default', ts('Default Option?'));
    $this->add('checkbox', 'is_optgroup', ts('Is OptGroup?'));

    // CRM-11516
    if ($this->_gName == 'payment_instrument') {
      $accountType = CRM_Core_PseudoConstant::accountOptionValues('financial_account_type', NULL, " AND v.name = 'Asset' ");
      $financialAccount = CRM_Contribute_PseudoConstant::financialAccount(NULL, key($accountType));
      
      $this->add('select', 'financial_account_id', ts('Financial Account'), 
        array('' => ts('- select -')) + $financialAccount
      );
    }
    // CRM-9953
    // we dont display this in the template, but the form sets the default values which are then saved
    // this allow us to retain the previous values
    $this->add('text', 'filter', ts('Filter'));

    if ($this->_action & CRM_Core_Action::UPDATE && $isReserved) {
      $this->freeze(array('name', 'description', 'is_active'));
    }
    //get contact type for which user want to create a new greeting/addressee type, CRM-4575
    if (in_array($this->_gName, array(
      'email_greeting', 'postal_greeting', 'addressee')) && !$isReserved) {
      $values = array(1 => ts('Individual'),
        2 => ts('Household'),
        3 => ts('Organization'),
        4 => ts('Multiple Contact Merge'),
      );

      $this->add('select', 'contactOptions', ts('Contact Type'), array('' => '-select-') + $values, TRUE);
    }

    $this->addFormRule(array('CRM_Admin_Form_OptionValue', 'formRule'), $this);
    $cancelURL = CRM_Utils_System::url('civicrm/admin/optionValue', "gid={$this->_gid}&reset=1");
    $cancelURL = str_replace('&amp;', '&', $cancelURL);
    $this->addButtons(
      array(
        array(
          'type' => 'next',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
          'js' => array('onclick' => "location.href='{$cancelURL}'; return false;"),
        ),
      )
    );
  }

  /**
   * global form rule
   *
   * @param array $fields  the input form values
   * @param array $files   the uploaded files if any
   * @param array $self    this object.
   *
   * @return true if no errors, else an array of errors
   * @access public
   * @static
   */
  static function formRule($fields, $files, $self) {
    $errors = array();

    //don't allow duplicate value within group.
    $optionValues = array();
    CRM_Core_OptionValue::getValues(array('id' => $self->_gid), $optionValues);
    foreach ($optionValues as $values) {
      if ($values['id'] != $self->_id) {
        if ($fields['value'] == $values['value']) {
          $errors['value'] = ts('Value already exist in database.');
          break;
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
    CRM_Utils_System::flushCache();

    $params = $this->exportValues();
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Core_BAO_OptionValue::del($this->_id);
      CRM_Core_Session::setStatus(ts('Selected option value has been deleted.'), ts('Record Deleted'), 'success');
    }
    else {

      $params = $ids = array();
      // store the submitted values in an array
      $params = $this->exportValues();
      $params['option_group_id'] = $this->_gid;

      if ($this->_action & CRM_Core_Action::UPDATE) {
        $ids['optionValue'] = $this->_id;
      }

      //set defaultGreeting option in params to save default value as per contactOption-defaultValue mapping
      if (CRM_Utils_Array::value('contactOptions', $params)) {
        $params['filter'] = CRM_Utils_Array::value('contactOptions', $params);
        $params['defaultGreeting'] = 1;
      }

      $optionValue = CRM_Core_BAO_OptionValue::add($params, $ids);
      // CRM-11516
      if (CRM_Utils_Array::value('financial_account_id', $params)) {
        $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Asset Account is' "));
        $params = array(
          'entity_table' => 'civicrm_option_value',
          'entity_id' => $optionValue->id,
          'account_relationship' => $relationTypeId,
          'financial_account_id' => $params['financial_account_id']
        );
        CRM_Financial_BAO_FinancialTypeAccount::add($params);
      }
      CRM_Core_Session::setStatus(ts('The Option Value \'%1\' has been saved.', array(1 => $optionValue->label)), ts('Saved'), 'success');
    }
  }
}
