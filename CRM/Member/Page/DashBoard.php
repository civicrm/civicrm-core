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
 * Page for displaying list of Payment-Instrument
 */
class CRM_Member_Page_DashBoard extends CRM_Core_Page {

  /**
   * Heart of the viewing process. The runner gets all the meta data for
   * the contact and calls the appropriate type of page to view.
   *
   * @return void
   */
  public function preProcess() {

    //CRM-13901 don't show dashboard to contacts with limited view writes & it does not relect
    //what they have access to
    //@todo implement acls on dashboard querys (preferably via api to enhance that at the same time)
    if (!CRM_Core_Permission::check('view all contacts') && !CRM_Core_Permission::check('edit all contacts')) {
      $this->showMembershipSummary = FALSE;
      $this->assign('membershipSummary', FALSE);
      return;
    }
    $this->assign('membershipSummary', TRUE);
    CRM_Utils_System::setTitle(ts('CiviMember'));
    $membershipSummary = [];
    $preMonth = date("Y-m-d", mktime(0, 0, 0, date("m") - 1, 01, date("Y")));
    $preMonthEnd = date("Y-m-t", mktime(0, 0, 0, date("m") - 1, 01, date("Y")));

    $preMonthYear = mktime(0, 0, 0, substr($preMonth, 4, 2), 1, substr($preMonth, 0, 4));

    $today = getdate();
    $date = CRM_Utils_Date::getToday();
    $isCurrentMonth = 0;

    // You can force the dashboard to display based upon a certain date
    $ym = $_GET['date'] ?? NULL;

    if ($ym) {
      if (preg_match('/^\d{6}$/', $ym) == 0 ||
        !checkdate(substr($ym, 4, 2), 1, substr($ym, 0, 4)) ||
        substr($ym, 0, 1) == 0
      ) {
        CRM_Core_Error::statusBounce(ts('Invalid date query "%1" in URL (valid syntax is yyyymm).', array(1 => $ym)));
      }

      $isPreviousMonth = 0;
      $isCurrentMonth = substr($ym, 0, 4) == $today['year'] && substr($ym, 4, 2) == $today['mon'];
      $ymd = date('Y-m-d', mktime(0, 0, -1, substr($ym, 4, 2) + 1, 1, substr($ym, 0, 4)));
      $monthStartTs = mktime(0, 0, 0, substr($ym, 4, 2), 1, substr($ym, 0, 4));
      $current = CRM_Utils_Date::customFormat($date, '%Y-%m-%d');
      $ym = substr($ym, 0, 4) . '-' . substr($ym, 4, 2);
    }
    else {
      $ym = sprintf("%04d-%02d", $today['year'], $today['mon']);
      $ymd = sprintf("%04d-%02d-%02d", $today['year'], $today['mon'], $today['mday']);
      $monthStartTs = mktime(0, 0, 0, $today['mon'], 1, $today['year']);
      $current = CRM_Utils_Date::customFormat($date, '%Y-%m-%d');
      $isCurrentMonth = 1;
      $isPreviousMonth = 1;
    }
    $monthStart = $ym . '-01';
    $yearStart = substr($ym, 0, 4) . '-01-01';

    $membershipTypes = CRM_Member_BAO_MembershipType::getMembershipTypes(FALSE);
    // added
    //$membership = new CRM_Member_BAO_Membership;

    foreach ($membershipTypes as $key => $value) {

      $membershipSummary[$key]['premonth']['new'] = array(
        'count' => CRM_Member_BAO_Membership::getMembershipJoins($key, $preMonth, $preMonthEnd),
        'name' => $value,
      );

      $membershipSummary[$key]['premonth']['renew'] = array(
        'count' => CRM_Member_BAO_Membership::getMembershipRenewals($key, $preMonth, $preMonthEnd),
        'name' => $value,
      );

      $membershipSummary[$key]['premonth']['total'] = array(
        'count' => CRM_Member_BAO_Membership::getMembershipStarts($key, $preMonth, $preMonthEnd),
        'name' => $value,
      );

      $membershipSummary[$key]['month']['new'] = array(
        'count' => CRM_Member_BAO_Membership::getMembershipJoins($key, $monthStart, $ymd),
        'name' => $value,
      );

      $membershipSummary[$key]['month']['renew'] = array(
        'count' => CRM_Member_BAO_Membership::getMembershipRenewals($key, $monthStart, $ymd),
        'name' => $value,
      );

      $membershipSummary[$key]['month']['total'] = array(
        'count' => CRM_Member_BAO_Membership::getMembershipStarts($key, $monthStart, $ymd),
        'name' => $value,
      );

      $membershipSummary[$key]['year']['new'] = array(
        'count' => CRM_Member_BAO_Membership::getMembershipJoins($key, $yearStart, $ymd),
        'name' => $value,
      );

      $membershipSummary[$key]['year']['renew'] = array(
        'count' => CRM_Member_BAO_Membership::getMembershipRenewals($key, $yearStart, $ymd),
        'name' => $value,
      );

      $membershipSummary[$key]['year']['total'] = array(
        'count' => CRM_Member_BAO_Membership::getMembershipStarts($key, $yearStart, $ymd),
        'name' => $value,
      );

      $membershipSummary[$key]['current']['total'] = array(
        'count' => CRM_Member_BAO_Membership::getMembershipCount($key, $current),
        'name' => $value,
      );

      $membershipSummary[$key]['total']['total'] = array('count' => CRM_Member_BAO_Membership::getMembershipCount($key, $ymd));

      //LCD also get summary stats for membership owners
      $membershipSummary[$key]['premonth_owner']['premonth_owner'] = array(
        'count' => CRM_Member_BAO_Membership::getMembershipStarts($key, $preMonth, $preMonthEnd, 0, 1),
        'name' => $value,
      );

      $membershipSummary[$key]['month_owner']['month_owner'] = array(
        'count' => CRM_Member_BAO_Membership::getMembershipStarts($key, $monthStart, $ymd, 0, 1),
        'name' => $value,
      );

      $membershipSummary[$key]['year_owner']['year_owner'] = array(
        'count' => CRM_Member_BAO_Membership::getMembershipStarts($key, $yearStart, $ymd, 0, 1),
        'name' => $value,
      );

      $membershipSummary[$key]['current_owner']['current_owner'] = array(
        'count' => CRM_Member_BAO_Membership::getMembershipCount($key, $current, 0, 1),
        'name' => $value,
      );

      $membershipSummary[$key]['total_owner']['total_owner'] = array('count' => CRM_Member_BAO_Membership::getMembershipCount($key, $ymd, 0, 1));
      //LCD end
    }

    $status = CRM_Member_BAO_MembershipStatus::getMembershipStatusCurrent();
    $status = implode(',', $status);

    foreach ($membershipSummary as $typeID => $details) {
      if (!$isCurrentMonth) {
        $membershipSummary[$typeID]['total']['total']['url'] = CRM_Utils_System::url('civicrm/member/search',
          "reset=1&force=1&start=&end=$ymd&membership_status_id=$status&membership_type_id=$typeID"
        );
        $membershipSummary[$typeID]['total_owner']['total_owner']['url'] = CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&start=&end=$ymd&membership_status_id=$status&membership_type_id=$typeID&owner=1");
      }
      else {
        $membershipSummary[$typeID]['total']['total']['url'] = CRM_Utils_System::url('civicrm/member/search',
          "reset=1&force=1&membership_status_id=$status"
        );
        $membershipSummary[$typeID]['total_owner']['total_owner']['url'] = CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&membership_status_id=$status&owner=1");
      }
      $membershipSummary[$typeID]['current']['total']['url'] = CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&membership_status_id=$status&membership_type_id=$typeID");
      $membershipSummary[$typeID]['current_owner']['current_owner']['url'] = CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&membership_status_id=$status&membership_type_id=$typeID&owner=1");
    }

    $totalCount = [];

    $newCountPreMonth = $newCountMonth = $newCountYear = 0;
    $renewCountPreMonth = $renewCountMonth = $renewCountYear = 0;

    $totalCountPreMonth = $totalCountMonth = $totalCountYear = $totalCountCurrent = $totalCountTotal = 0;
    $totalCountPreMonth_owner = $totalCountMonth_owner = $totalCountYear_owner = $totalCountCurrent_owner = $totalCountTotal_owner = 0;
    foreach ($membershipSummary as $key => $value) {
      $newCountPreMonth = $newCountPreMonth + $value['premonth']['new']['count'];
      $renewCountPreMonth = $renewCountPreMonth + $value['premonth']['renew']['count'];
      $totalCountPreMonth = $totalCountPreMonth + $value['premonth']['total']['count'];
      $newCountMonth = $newCountMonth + $value['month']['new']['count'];
      $renewCountMonth = $renewCountMonth + $value['month']['renew']['count'];
      $totalCountMonth = $totalCountMonth + $value['month']['total']['count'];
      $newCountYear = $newCountYear + $value['year']['new']['count'];
      $renewCountYear = $renewCountYear + $value['year']['renew']['count'];
      $totalCountYear = $totalCountYear + $value['year']['total']['count'];
      $totalCountCurrent = $totalCountCurrent + $value['current']['total']['count'];
      $totalCountTotal = $totalCountTotal + $value['total']['total']['count'];

      //LCD add owner values
      $totalCountPreMonth_owner = $totalCountPreMonth_owner + $value['premonth_owner']['premonth_owner']['count'];
      $totalCountMonth_owner = $totalCountMonth_owner + $value['month_owner']['month_owner']['count'];
      $totalCountYear_owner = $totalCountYear_owner + $value['year_owner']['year_owner']['count'];
      $totalCountCurrent_owner = $totalCountCurrent_owner + $value['current_owner']['current_owner']['count'];
      $totalCountTotal_owner = $totalCountTotal_owner + $value['total_owner']['total_owner']['count'];
    }

    $totalCount['premonth']['new'] = array(
      'count' => $newCountPreMonth,
    );

    $totalCount['premonth']['renew'] = array(
      'count' => $renewCountPreMonth,
    );

    $totalCount['premonth']['total'] = array(
      'count' => $totalCountPreMonth,
    );

    $totalCount['month']['new'] = array(
      'count' => $newCountMonth,
    );

    $totalCount['month']['renew'] = array(
      'count' => $renewCountMonth,
    );

    $totalCount['month']['total'] = array(
      'count' => $totalCountMonth,
    );

    $totalCount['year']['new'] = array(
      'count' => $newCountYear,
    );

    $totalCount['year']['renew'] = array(
      'count' => $renewCountYear,
    );

    $totalCount['year']['total'] = array(
      'count' => $totalCountYear,
    );

    $totalCount['current']['total'] = array(
      'count' => $totalCountCurrent,
      'url' => CRM_Utils_System::url('civicrm/member/search',
        "reset=1&force=1&membership_status_id=$status"
      ),
    );

    $totalCount['total']['total'] = array(
      'count' => $totalCountTotal,
      'url' => CRM_Utils_System::url('civicrm/member/search',
        "reset=1&force=1&membership_status_id=$status"
      ),
    );

    if (!$isCurrentMonth) {
      $totalCount['total']['total'] = array(
        'count' => $totalCountTotal,
        'url' => CRM_Utils_System::url('civicrm/member/search',
          "reset=1&force=1&membership_status_id=$status&start=&end=$ymd"
        ),
      );
    }

    // Activity search also unable to handle owner vs. inherited

    //LCD add owner values
    $totalCount['premonth_owner']['premonth_owner'] = array(
      'count' => $totalCountPreMonth_owner,
      //  'url' => CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&membership_status_id=$status&start=$preMonth&end=$preMonthEnd&owner=1"),
    );

    $totalCount['month_owner']['month_owner'] = array(
      'count' => $totalCountMonth_owner,
      //  'url' => CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&membership_status_id=$status&start=$monthStart&end=$ymd&owner=1"),
    );

    $totalCount['year_owner']['year_owner'] = array(
      'count' => $totalCountYear_owner,
      //  'url' => CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&membership_status_id=$status&start=$yearStart&end=$ymd&owner=1"),
    );

    $totalCount['current_owner']['current_owner'] = array(
      'count' => $totalCountCurrent_owner,
      //  'url' => CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&membership_status_id=$status&owner=1"),
    );

    $totalCount['total_owner']['total_owner'] = array(
      'count' => $totalCountTotal_owner,
      //  'url' => CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&membership_status_id=$status&owner=1"),
    );

    if (!$isCurrentMonth) {
      $totalCount['total_owner']['total_owner'] = array(
        'count' => $totalCountTotal_owner,
        //  'url' => CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&membership_status_id=$status&start=&end=$ymd&owner=1"),
      );
    }
    //LCD end

    $this->assign('membershipSummary', $membershipSummary);
    $this->assign('totalCount', $totalCount);
    $this->assign('month', CRM_Utils_Date::customFormatTs($monthStartTs, '%B'));
    $this->assign('year', date('Y', $monthStartTs));
    $this->assign('premonth', CRM_Utils_Date::customFormat($preMonth, '%B'));
    $this->assign('currentMonth', date('F'));
    $this->assign('currentYear', date('Y'));
    $this->assign('isCurrent', $isCurrentMonth);
    $this->assign('preMonth', $isPreviousMonth);
  }

  /**
   * the main function that is called when the page loads,
   * it decides the which action has to be taken for the page.
   *
   * @return null
   */
  public function run() {
    $this->preProcess();

    $controller = new CRM_Core_Controller_Simple('CRM_Member_Form_Search', ts('Member'), NULL);
    $controller->setEmbedded(TRUE);
    $controller->reset();
    $controller->set('limit', 20);
    $controller->set('force', 1);
    $controller->set('context', 'dashboard');
    $controller->process();
    $controller->run();

    return parent::run();
  }

}
