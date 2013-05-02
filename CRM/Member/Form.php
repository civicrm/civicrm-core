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
 * Base class for offline membership / membership type / membership renewal and membership status forms
 *
 */
class CRM_Member_Form extends CRM_Core_Form {

  /**
   * The id of the object being edited / created
   *
   * @var int
   */
  protected $_id;

  /**
   * The name of the BAO object for this form
   *
   * @var string
   */
  protected $_BAOName; 

  function preProcess() {
    $this->_id = $this->get('id');
    $this->_BAOName = $this->get('BAOName');
  }

  /**
   * This function sets the default values for the form. MobileProvider that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    $defaults = array();

    if (isset($this->_id)) {
      $params = array('id' => $this->_id);
      require_once (str_replace('_', DIRECTORY_SEPARATOR, $this->_BAOName) . ".php");
      eval($this->_BAOName . '::retrieve( $params, $defaults );');
    }

    if (isset($defaults['minimum_fee'])) {
      $defaults['minimum_fee'] = CRM_Utils_Money::format($defaults['minimum_fee'], NULL, '%a');
    }

    if (isset($defaults['status'])) {
      $this->assign('membershipStatus', $defaults['status']);
    }

    if ($this->_action & CRM_Core_Action::ADD) {
      $defaults['is_active'] = 1;
    }

    if (isset($defaults['member_of_contact_id']) &&
      $defaults['member_of_contact_id']
    ) {
      $defaults['member_org'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
        $defaults['member_of_contact_id'], 'display_name'
      );
    }
    return $defaults;
  }

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    if ($this->_action & CRM_Core_Action::RENEW) {
      $this->addButtons(array(
          array(
            'type' => 'upload',
            'name' => ts('Renew'),
            'isDefault' => TRUE
          ),
          array(
            'type' => 'cancel',
            'name' => ts('Cancel')
          )
        )
      );
    }
    elseif ($this->_action & CRM_Core_Action::DELETE) {
      $this->addButtons(array(
          array(
            'type' => 'next',
            'name' => ts('Delete'),
            'isDefault' => TRUE
          ),
          array(
            'type' => 'cancel',
            'name' => ts('Cancel')
          )
        )
      );
    }
    else {
      $this->addButtons(array(
          array(
            'type' => 'upload',
            'name' => ts('Save'),
            'isDefault' => TRUE
          ),
          array(
            'type' => 'upload',
            'name' => ts('Save and New'),
            'subName' => 'new'
          ),
          array(
            'type' => 'cancel',
            'name' => ts('Cancel')
          )
        )
      );
    }
  }

  /*
   * Function to extract values from the contact create boxes on the form and assign appropriatley  to
   *
   *  - $this->_contributorEmail,
   *  - $this->_memberEmail &
   *  - $this->_contributonName
   *  - $this->_memberName
   *  - $this->_contactID (effectively memberContactId but changing might have spin-off effects)
   *  - $this->_contributorContactId - id of the contributor
   *  - $this->_receiptContactId
   *
   * If the member & contributor are the same then the values will be the same. But if different people paid
   * then they weill differ
   *
   * @param $formValues array values from form. The important values we are looking for are
   *  - contact_select_id[1]
   *  - contribution_contact_select_id[1]
   */
  function storeContactFields($formValues){
    // in a 'standalone form' (contact id not in the url) the contact will be in the form values
    if (CRM_Utils_Array::value('contact_select_id', $formValues)) {
      $this->_contactID = $formValues['contact_select_id'][1];
    }

    list($this->_memberDisplayName,
         $this->_memberEmail
    ) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contactID);

    //CRM-10375 Where the payer differs to the member the payer should get the email.
    // here we store details in order to do that
    if (CRM_Utils_Array::value('contribution_contact_select_id', $formValues) && CRM_Utils_Array::value('1', $formValues['contribution_contact_select_id'])) {
      $this->_receiptContactId = $this->_contributorContactID = $formValues['contribution_contact_select_id'][1];
       list( $this->_contributorDisplayName,
         $this->_contributorEmail ) = CRM_Contact_BAO_Contact_Location::getEmailDetails( $this->_contributorContactID );
    }
    else {
      $this->_receiptContactId = $this->_contributorContactID = $this->_contactID;
      $this->_contributorDisplayName = $this->_memberDisplayName;
      $this->_contributorEmail = $this->_memberEmail;
    }
  }
}

