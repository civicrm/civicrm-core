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
 * $Id$
 *
 */

/**
 * Main page for Cases dashlet
 *
 */
class CRM_Dashlet_Page_MyCases extends CRM_Core_Page {

  /**
   * List activities as dashlet.
   *
   * @return void
   */
  public function run() {
    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this, FALSE, 'dashlet');
    $this->assign('context', $context);

    //check for civicase access.
    if (!CRM_Case_BAO_Case::accessCiviCase()) {
      CRM_Core_Error::fatal(ts('You are not authorized to access this page.'));
    }

    $controller = new CRM_Core_Controller_Simple('CRM_Case_Form_Search',
      ts('Case'), CRM_Core_Action::BROWSE,
      NULL,
      FALSE, FALSE, TRUE
    );
    $controller->setEmbedded(TRUE);
    $controller->process();
    $controller->run();

    if (CRM_Case_BAO_Case::getCases(FALSE, ['type' => 'any'], $context, TRUE)) {
      $this->assign('casePresent', TRUE);
    }
    return parent::run();
  }

}
