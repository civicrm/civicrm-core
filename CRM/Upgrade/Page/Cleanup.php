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
