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
   * Explicitly declare the form context.
   */
  public function getDefaultContext() {
    return 'create';
  }

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
        CRM_Core_Error::statusBounce(ts('contact does not exist: %1', [1 => $this->_contactId]));
      }
      $this->_contactType = $contact->contact_type;

      // check for permissions
      if (!CRM_Contact_BAO_Contact_Permission::allow($this->_contactId, CRM_Core_Permission::EDIT)) {
        CRM_Core_Error::statusBounce(ts('You do not have the necessary permission to edit this contact.'));
      }

      list($displayName, $contactImage) = CRM_Contact_BAO_Contact::getDisplayAndImage($this->_contactId);
      $this->setTitle($displayName, $contactImage . ' ' . $displayName);
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
    $params = [];
    $params['id'] = $params['contact_id'] = $this->_contactId;
    CRM_Contact_BAO_Contact::retrieve($params, $this->_defaults);

    $this->buildOnBehalfForm();

    $this->assign('contact_type', $this->_contactType);
    $this->assign('fieldSetTitle', ts('Contact Information'));
    $this->assign('contactEditMode', TRUE);

    $this->addButtons([
      [
        'type' => 'next',
        'name' => ts('Save'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);
  }

  /**
   * Build form for related contacts / on behalf of organization.
   *
   * @throws \CRM_Core_Exception
   */
  private function buildOnBehalfForm() {
    $form = $this;

    $attributes = CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact');
    if ($form->_contactId) {
      $form->assign('orgId', $form->_contactId);
    }

    switch ($this->_contactType) {
      case 'Organization':
        $form->add('text', 'organization_name', ts('Organization Name'), $attributes['organization_name'], TRUE);
        break;

      case 'Household':
        $form->add('text', 'household_name', ts('Household Name'), $attributes['household_name']);
        break;

      default:
        // individual
        $form->addElement('select', 'prefix_id', ts('Prefix'),
          ['' => ts('- prefix -')] + CRM_Contact_DAO_Contact::buildOptions('prefix_id')
        );
        $form->addElement('text', 'first_name', ts('First Name'),
          $attributes['first_name']
        );
        $form->addElement('text', 'middle_name', ts('Middle Name'),
          $attributes['middle_name']
        );
        $form->addElement('text', 'last_name', ts('Last Name'),
          $attributes['last_name']
        );
        $form->addElement('select', 'suffix_id', ts('Suffix'),
          ['' => ts('- suffix -')] + CRM_Contact_DAO_Contact::buildOptions('suffix_id')
        );
    }

    $addressSequence = CRM_Utils_Address::sequence(\Civi::settings()->get('address_format'));
    $form->assign('addressSequence', array_fill_keys($addressSequence, 1));

    //Primary Phone
    $form->addElement('text',
      'phone[1][phone]',
      ts('Primary Phone'),
      CRM_Core_DAO::getAttribute('CRM_Core_DAO_Phone',
        'phone'
      )
    );
    //Primary Email
    $form->addElement('text',
      'email[1][email]',
      ts('Primary Email'),
      CRM_Core_DAO::getAttribute('CRM_Core_DAO_Email',
        'email'
      )
    );
    //build the address block
    CRM_Contact_Form_Edit_Address::buildQuickForm($form);
  }

  /**
   * Form submission of new/edit contact is processed.
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);

    $locType = CRM_Core_BAO_LocationType::getDefault();
    foreach (['phone', 'email', 'address'] as $locFld) {
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
      $message = ts('%1 has been updated.', [1 => $contact->display_name]);
    }
    else {
      $message = ts('%1 has been created.', [1 => $contact->display_name]);
    }
    CRM_Core_Session::setStatus($message, ts('Contact Saved'), 'success');
  }

}
