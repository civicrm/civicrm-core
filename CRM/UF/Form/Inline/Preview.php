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
 * This class generates form components
 * for previewing Civicrm Profile Group
 */
class CRM_UF_Form_Inline_Preview extends CRM_UF_Form_AbstractPreview {

  /**
   * Pre processing work done here.
   *
   * gets session variables for group or field id
   */
  public function preProcess() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      // CRM_Core_Controller validates qfKey for POST requests, but not necessarily
      // for GET requests. Allowing GET would therefore be CSRF vulnerability.
      CRM_Core_Error::statusBounce(ts('Preview only supports HTTP POST'));
    }
    // Inline forms don't get menu-level permission checks
    $checkPermission = [
      [
        'administer CiviCRM data',
        'manage event profiles',
      ],
    ];
    if (!CRM_Core_Permission::check($checkPermission)) {
      CRM_Core_Error::statusBounce(ts('Permission Denied'));
    }
    $content = json_decode($_REQUEST['ufData'], TRUE);
    foreach (['ufGroup', 'ufFieldCollection'] as $key) {
      if (!is_array($content[$key])) {
        CRM_Core_Error::statusBounce("Missing JSON parameter, $key");
      }
    }

    $fields = CRM_Core_BAO_UFGroup::formatUFFields($content['ufGroup'], $content['ufFieldCollection']);
    $this->setProfile($fields);
  }

}
