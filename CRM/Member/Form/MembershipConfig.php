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
 * Base class for offline membership / membership type / membership renewal and membership status forms
 *
 */
class CRM_Member_Form_MembershipConfig extends CRM_Core_Form {

  /**
   * The id of the object being edited / created
   *
   * @var int
   */
  public $_id;

  /**
   * The name of the BAO object for this form.
   *
   * @var string
   */
  protected $_BAOName;

  /**
   * Set default values for the form. MobileProvider that in edit/view mode
   * the default values are retrieved from the database
   *
   * @return array
   *   defaults
   */
  public function setDefaultValues() {
    $defaults = [];

    if (isset($this->_id)) {
      $params = ['id' => $this->_id];
      $baoName = $this->_BAOName;
      $baoName::retrieve($params, $defaults);
    }

    if (isset($defaults['minimum_fee'])) {
      $defaults['minimum_fee'] = CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency($defaults['minimum_fee']);
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
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    if ($this->_action & CRM_Core_Action::RENEW) {
      $this->addButtons([
        [
          'type' => 'upload',
          'name' => ts('Renew'),
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]);
    }
    elseif ($this->_action & CRM_Core_Action::DELETE) {
      $this->addButtons([
        [
          'type' => 'next',
          'name' => ts('Delete'),
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]);
    }
    else {
      $this->addButtons([
        [
          'type' => 'upload',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ],
        [
          'type' => 'upload',
          'name' => ts('Save and New'),
          'subName' => 'new',
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]);
    }
  }

}
