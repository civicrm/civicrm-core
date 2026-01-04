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
 * Dummy page for details of website.
 */
class CRM_Contact_Page_Inline_Website extends CRM_Core_Page {

  /**
   * Run the page.
   *
   * This method is called after the page is created.
   *
   * @throws \CRM_Core_Exception
   */
  public function run() {
    // get the emails for this contact
    $contactId = CRM_Utils_Request::retrieveValue('cid', 'Positive');

    $websiteTypes = CRM_Core_DAO_Website::buildOptions('website_type_id');

    $params = ['contact_id' => $contactId];
    $websites = CRM_Core_BAO_Website::getValues($params);
    if (!empty($websites)) {
      foreach ($websites as $key => & $value) {
        $value['website_type'] = $websiteTypes[$value['website_type_id']];
      }
    }

    $this->assign('contactId', $contactId);
    $this->assign('website', $websites);

    // check logged in user permission
    CRM_Contact_Page_View::checkUserPermission($this, $contactId);

    // finally call parent
    parent::run();
  }

}
