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
 * Form helper class for contact info section.
 */
class CRM_Contact_Form_Inline_ContactInfo extends CRM_Contact_Form_Inline {

  /**
   * Build the form object elements.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    // Build contact type specific fields
    $class = 'CRM_Contact_Form_Edit_' . $this->_contactType;
    $class::buildQuickForm($this, 2);
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    $this->saveContact();

    // Saving current employer affects relationship tab, and possibly related memberships and contributions
    $this->ajaxResponse['updateTabs'] = [
      '#tab_rel' => CRM_Contact_BAO_Contact::getCountComponent('rel', $this->getContactID()),
    ];
    if (CRM_Core_Permission::access('CiviContribute')) {
      $this->ajaxResponse['updateTabs']['#tab_contribute'] = CRM_Contact_BAO_Contact::getCountComponent('contribution', $this->getContactID());
    }
    if (CRM_Core_Permission::access('CiviMember')) {
      $this->ajaxResponse['updateTabs']['#tab_member'] = CRM_Contact_BAO_Contact::getCountComponent('membership', $this->getContactID());
    }

    $this->response();
  }

}
