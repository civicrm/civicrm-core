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
 * Dummy page for details for OpenID.
 */
class CRM_Contact_Page_Inline_OpenID extends CRM_Core_Page {
  use CRM_Custom_Page_CustomDataTrait;
  use CRM_Contact_Form_Edit_OpenIDBlockTrait;
  use CRM_Contact_Form_ContactFormTrait;

  /**
   * Run the page.
   *
   * This method is called after the page is created.
   *
   * @throws \CRM_Core_Exception
   */
  public function run(): void {
    $openids = (array) $this->getExistingOpenIDs();
    if (!empty($openids)) {
      foreach ($openids as &$value) {
        $value['location_type'] = $value['location_type_id:label'];
        $value['custom'] = $this->getCustomDataFieldsForEntityDisplay('Openid', $value['id']);
      }
    }

    $this->assign('contactId', $this->getContactID());
    $this->assign('openid', $openids);

    // check logged in user permission
    CRM_Contact_Page_View::checkUserPermission($this, $this->getContactID());

    // finally call parent
    parent::run();
  }

}
