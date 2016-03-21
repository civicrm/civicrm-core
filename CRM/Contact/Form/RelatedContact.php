<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * This class generates form components generic to all the contact types.
 *
 * It delegates the work to lower level subclasses and integrates the changes
 * back in. It also uses a lot of functionality with the CRM API's, so any change
 * made here could potentially affect the API etc. Be careful, be aware, use unit tests.
 */
class CRM_Contact_Form_RelatedContact extends CRM_Core_Form {

  /**
   * The contact type of the form.
   *
   * @var string
   */
  protected $_contactType;

  /**
   * The contact id, used when editing the form
   *
   * @var int
   */
  public $_contactId;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    // reset action from the session
    $this->_action = CRM_Utils_Request::retrieve('action', 'String',
      $this, FALSE, 'update'
    );
    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);

    $rcid = CRM_Utils_Request::retrieve('rcid', 'Positive', $this);
    $rcid = $rcid ? "&id={$rcid}" : '';
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/user', "reset=1{$rcid}"));

    if ($this->_contactId) {
      $contact = new CRM_Contact_DAO_Contact();
      $contact->id = $this->_contactId;
      if (!$contact->find(TRUE)) {
        CRM_Core_Error::statusBounce(ts('contact does not exist: %1', array(1 => $this->_contactId)));
      }
      $this->_contactType = $contact->contact_type;

      // check for permissions
      if (!CRM_Contact_BAO_Contact_Permission::allow($this->_contactId, CRM_Core_Permission::EDIT)) {
        CRM_Core_Error::statusBounce(ts('You do not have the necessary permission to edit this contact.'));
      }

      list($displayName, $contactImage) = CRM_Contact_BAO_Contact::getDisplayAndImage($this->_contactId);
      CRM_Utils_System::setTitle($displayName, $contactImage . ' ' . $displayName);
    }
    else {
      CRM_Core_Error::statusBounce(ts('Could not get a contact_id and/or contact_type'));
    }
  }

  /**
   * Set default values for the form.
   *
   * Note that in edit/view mode the default values are retrieved from the
   * database
   */
  public function setDefaultValues() {
    return $this->_defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $params = array();
    $params['id'] = $params['contact_id'] = $this->_contactId;
    $contact = CRM_Contact_BAO_Contact::retrieve($params, $this->_defaults);

    $countryID = '';
    $stateID = '';
    if (!empty($this->_defaults['address'][1])) {
      $countryID = CRM_Utils_Array::value('country_id',
        $this->_defaults['address'][1]
      );
      $stateID = CRM_Utils_Array::value('state_province_id',
        $this->_defaults['address'][1]
      );
    }
    CRM_Contact_BAO_Contact_Utils::buildOnBehalfForm($this,
      $this->_contactType,
      $countryID,
      $stateID,
      ts('Contact Information')
    );

    $this->addButtons(array(
      array(
        'type' => 'next',
        'name' => ts('Save'),
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ),
    ));
  }

  /**
   * Form submission of new/edit contact is processed.
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);

    $locType = CRM_Core_BAO_LocationType::getDefault();
    foreach (array(
               'phone',
               'email',
               'address',
             ) as $locFld) {
      if (!empty($this->_defaults[$locFld]) && $this->_defaults[$locFld][1]['location_type_id']) {
        $params[$locFld][1]['is_primary'] = $this->_defaults[$locFld][1]['is_primary'];
        $params[$locFld][1]['location_type_id'] = $this->_defaults[$locFld][1]['location_type_id'];
      }
      else {
        $params[$locFld][1]['is_primary'] = 1;
        $params[$locFld][1]['location_type_id'] = $locType->id;
      }
    }

    $params['contact_type'] = $this->_contactType;
    //CRM-14904
    if (isset($this->_defaults['contact_sub_type'])) {
      $params['contact_sub_type'] = $this->_defaults['contact_sub_type'];
    }
    $params['contact_id'] = $this->_contactId;

    $contact = CRM_Contact_BAO_Contact::create($params, TRUE);

    // set status message.
    if ($this->_contactId) {
      $message = ts('%1 has been updated.', array(1 => $contact->display_name));
    }
    else {
      $message = ts('%1 has been created.', array(1 => $contact->display_name));
    }
    CRM_Core_Session::setStatus($message, ts('Contact Saved'), 'success');
  }

}
