<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
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
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
      // CRM_Core_Controller validates qfKey for POST requests, but not necessarily
      // for GET requests. Allowing GET would therefore be CSRF vulnerability.
      CRM_Core_Error::fatal(ts('Preview only supports HTTP POST'));
    }
    // Inline forms don't get menu-level permission checks
    $checkPermission = [
      [
        'administer CiviCRM',
        'manage event profiles',
      ],
    ];
    if (!CRM_Core_Permission::check($checkPermission)) {
      CRM_Core_Error::fatal(ts('Permission Denied'));
    }
    $content = json_decode($_REQUEST['ufData'], TRUE);
    foreach (['ufGroup', 'ufFieldCollection'] as $key) {
      if (!is_array($content[$key])) {
        CRM_Core_Error::fatal("Missing JSON parameter, $key");
      }
    }
    //echo '<pre>'.htmlentities(var_export($content, TRUE)) .'</pre>';
    //CRM_Utils_System::civiExit();
    $fields = CRM_Core_BAO_UFGroup::formatUFFields($content['ufGroup'], $content['ufFieldCollection']);
    //$fields = CRM_Core_BAO_UFGroup::getFields(1);
    $this->setProfile($fields);
    //echo '<pre>'.htmlentities(var_export($fields, TRUE)) .'</pre>';CRM_Utils_System::civiExit();
  }

}
