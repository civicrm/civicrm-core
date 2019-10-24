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
 * Class CRM_Upgrade_Page_Cleanup
 */
class CRM_Upgrade_Page_Cleanup extends CRM_Core_Page {

  public function cleanup425() {
    $rows = CRM_Upgrade_Incremental_php_FourTwo::deleteInvalidPairs();
    $template = CRM_Core_Smarty::singleton();

    $columnHeaders = [
      "Contact ID",
      "ContributionID",
      "Contribution Status",
      "MembershipID",
      "Membership Type",
      "Start Date",
      "End Date",
      "Membership Status",
      "Action",
    ];
    $template->assign('columnHeaders', $columnHeaders);
    $template->assign('rows', $rows);

    $preMessage = !empty($rows) ? ts('The following records have been processed. Membership records with action = Un-linked have been disconnected from the listed contribution record:') : ts('Could not find any records to process.');
    $template->assign('preMessage', $preMessage);

    $postMessage = ts('You can <a href="%1">click here</a> to try running the 4.2 upgrade script again. <a href="%2" target="_blank">(Review upgrade documentation)</a>',
      [
        1 => CRM_Utils_System::url('civicrm/upgrade', 'reset=1'),
        2 => 'http://wiki.civicrm.org/confluence/display/CRMDOC/Installation+and+Upgrades',
      ]);
    $template->assign('postMessage', $postMessage);

    $content = $template->fetch('CRM/common/upgradeCleanup.tpl');
    echo CRM_Utils_System::theme($content, FALSE, TRUE);
  }

}
