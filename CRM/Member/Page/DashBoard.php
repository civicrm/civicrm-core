<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
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
   * @access public
   *
   */
  function preProcess() {

    //CRM-13901 don't show dashboard to contacts with limited view writes & it does not relect
    //what they have access to
    //@todo implement acls on dashboard querys (preferably via api to enhance that at the same time)
    if(!CRM_Core_Permission::check(array('view all contacts', 'edit all contacts'))) {
      $this->showMembershipSummary = FALSE;
      $this->assign('membershipSummary', FALSE);
      return;
    }
    $this->assign('membershipSummary', TRUE);
    CRM_Utils_System::setTitle(ts('CiviMember'));
    $membershipSummary = array();
    $preMonth = date("Y-m-d", mktime(0, 0, 0, date("m") - 1, 01, date("Y")));
    $preMonthEnd = date("Y-m-t", mktime(0, 0, 0, date("m") - 1, 01, date("Y")));

    $preMonthYear = mktime(0, 0, 0, substr($preMonth, 4, 2), 1, substr($preMonth, 0, 4));

    $today = getdate();
    $date = CRM_Utils_Date::getToday();
    $isCurrentMonth = 0;

    // You can force the dashboard to display based upon a certain date
    $ym = CRM_Utils_Array::value('date', $_GET);

    if ($ym) {
      if (preg_match('/^\d{6}$/', $ym) == 0 ||
        !checkdate(substr($ym, 4, 2), 1, substr($ym, 0, 4)) ||
        substr($ym, 0, 1) == 0
      ) {
        CRM_Core_Error::fatal(ts('Invalid date query "%1" in URL (valid syntax is yyyymm).', array(1 => $ym)));
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

      $membershipSummary[$key]['premonth']['new'] = array('count' => CRM_Member_BAO_Membership::getMembershipJoins($key, $preMonth, $preMonthEnd),
        'name' => $value,
      );

      $membershipSummary[$key]['premonth']['renew'] = array('count' => CRM_Member_BAO_Membership::getMembershipRenewals($key, $preMonth, $preMonthEnd),
        'name' => $value,
      );

      $membershipSummary[$key]['premonth']['total'] = array('count' => CRM_Member_BAO_Membership::getMembershipStarts($key, $preMonth, $preMonthEnd),
        'name' => $value,
      );


      $membershipSummary[$key]['month']['new'] = array('count' => CRM_Member_BAO_Membership::getMembershipJoins($key, $monthStart, $ymd),
        'name' => $value,
      );

      $membershipSummary[$key]['month']['renew'] = array('count' => CRM_Member_BAO_Membership::getMembershipRenewals($key, $monthStart, $ymd),
        'name' => $value,
      );

      $membershipSummary[$key]['month']['total'] = array('count' => CRM_Member_BAO_Membership::getMembershipStarts($key, $monthStart, $ymd),
        'name' => $value,
      );


      $membershipSummary[$key]['year']['new'] = array('count' => CRM_Member_BAO_Membership::getMembershipJoins($key, $yearStart, $ymd),
        'name' => $value,
      );

      $membershipSummary[$key]['year']['renew'] = array('count' => CRM_Member_BAO_Membership::getMembershipRenewals($key, $yearStart, $ymd),
        'name' => $value,
      );

      $membershipSummary[$key]['year']['total'] = array('count' => CRM_Member_BAO_Membership::getMembershipStarts($key, $yearStart, $ymd),
        'name' => $value,
      );


      $membershipSummary[$key]['current']['total'] = array('count' => CRM_Member_BAO_Membership::getMembershipCount($key, $current),
        'name' => $value,
      );

      $membershipSummary[$key]['total']['total'] = array('count' => CRM_Member_BAO_Membership::getMembershipCount($key, $ymd));

      //LCD also get summary stats for membership owners
      $membershipSummary[$key]['premonth_owner']['premonth_owner'] = array('count' => CRM_Member_BAO_Membership::getMembershipStarts($key, $preMonth, $preMonthEnd, 0, 1),
        'name' => $value,
      );

      $membershipSummary[$key]['month_owner']['month_owner'] = array('count' => CRM_Member_BAO_Membership::getMembershipStarts($key, $monthStart, $ymd, 0, 1),
        'name' => $value,
      );

      $membershipSummary[$key]['year_owner']['year_owner'] = array('count' => CRM_Member_BAO_Membership::getMembershipStarts($key, $yearStart, $ymd, 0, 1),
        'name' => $value,
      );

      $membershipSummary[$key]['current_owner']['current_owner'] = array('count' => CRM_Member_BAO_Membership::getMembershipCount($key, $current, 0, 1),
        'name' => $value,
      );

      $membershipSummary[$key]['total_owner']['total_owner'] = array('count' => CRM_Member_BAO_Membership::getMembershipCount($key, $ymd, 0, 1));
      //LCD end
    }

    // LCD debug
    //CRM_Core_Error::debug($membershipSummary);
    $status = CRM_Member_BAO_MembershipStatus::getMembershipStatusCurrent();
    $status = implode(',', $status);

    /* Disabled for lack of appropriate search

       The Membership search isn't able to properly filter by join or renewal events.
       Until that works properly, the subtotals shouldn't get links.

    foreach ($membershipSummary as $typeID => $details) {
      foreach ($details as $key => $value) {
        switch ($key) {
          case 'premonth':
            $membershipSummary[$typeID][$key]['new']['url'] = CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&status=$status&type=$typeID&join=$preMonth&joinEnd=$preMonthEnd&start=$preMonth&end=$preMonthEnd");
            $membershipSummary[$typeID][$key]['renew']['url'] = CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&status=$status&type=$typeID&joinEnd=$prePreMonthEnd&start=$preMonth&end=$preMonthEnd");
            $membershipSummary[$typeID][$key]['total']['url'] = CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&status=$status&type=$typeID&start=$preMonth&end=$preMonthEnd");
            break;

          case 'month':
            $membershipSummary[$typeID][$key]['new']['url'] = CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&status=$status&type=$typeID&join=$monthStart&joinEnd=$ymd&start=$monthStart&end=$ymd");
            $membershipSummary[$typeID][$key]['renew']['url'] = CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&status=$status&type=$typeID&joinEnd=$preMonthStart&start=$monthStart&end=$ymd");
            $membershipSummary[$typeID][$key]['total']['url'] = CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&status=$status&type=$typeID&start=$monthStart&end=$ymd");
            break;

          case 'year':
            $membershipSummary[$typeID][$key]['new']['url'] = CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&status=$status&type=$typeID&join=$yearStart&joinEnd=$ymd&start=$yearStart&end=$ymd");
            $membershipSummary[$typeID][$key]['renew']['url'] = CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&status=$status&type=$typeID&joinEnd=$preYearStart&start=$yearStart&end=$ymd");
            $membershipSummary[$typeID][$key]['total']['url'] = CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&status=$status&type=$typeID&start=$yearStart&end=$ymd");
            break;

          case 'current':
            $membershipSummary[$typeID][$key]['total']['url'] = CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&status=$status&type=$typeID");
            break;

          case 'total':
            if (!$isCurrentMonth) {
              $membershipSummary[$typeID][$key]['total']['url'] = CRM_Utils_System::url('civicrm/member/search',
                "reset=1&force=1&start=&end=$ymd&status=$status&type=$typeID"
              );
            }
            else {
              $membershipSummary[$typeID][$key]['total']['url'] = CRM_Utils_System::url('civicrm/member/search',
                "reset=1&force=1&status=$status"
              );
            }
            break;

          //LCD add owner urls

          case 'premonth_owner':
            $membershipSummary[$typeID][$key]['premonth_owner']['url'] = CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&status=$status&type=$typeID&start=$preMonth&end=$preMonthEnd&owner=1");
            break;

          case 'month_owner':
            $membershipSummary[$typeID][$key]['month_owner']['url'] = CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&status=$status&type=$typeID&start=$monthStart&end=$ymd&owner=1");
            break;

          case 'year_owner':
            $membershipSummary[$typeID][$key]['year_owner']['url'] = CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&status=$status&type=$typeID&start=$yearStart&end=$ymd&owner=1");
            break;

          case 'current_owner':
            $membershipSummary[$typeID][$key]['current_owner']['url'] = CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&status=$status&type=$typeID&owner=1");
            break;

          case 'total_owner':
            if (!$isCurrentMonth) {
              $membershipSummary[$typeID][$key]['total_owner']['url'] = CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&start=&end=$ymd&status=$status&type=$typeID&owner=1");
            }
            else {
              $membershipSummary[$typeID][$key]['total_owner']['url'] = CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&status=$status&owner=1");
            }
            break;
          //LCD end
        }
      }
    }
    */

    // Temporary replacement for current totals column

    foreach ($membershipSummary as $typeID => $details) {
      if (!$isCurrentMonth) {
        $membershipSummary[$typeID]['total']['total']['url'] = CRM_Utils_System::url('civicrm/member/search',
          "reset=1&force=1&start=&end=$ymd&status=$status&type=$typeID"
        );
        $membershipSummary[$typeID]['total_owner']['total_owner']['url'] = CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&start=&end=$ymd&status=$status&type=$typeID&owner=1");
      }
      else {
        $membershipSummary[$typeID]['total']['total']['url'] = CRM_Utils_System::url('civicrm/member/search',
          "reset=1&force=1&status=$status"
        );
        $membershipSummary[$typeID]['total_owner']['total_owner']['url'] = CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&status=$status&owner=1");
      }
      $membershipSummary[$typeID]['current']['total']['url'] = CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&status=$status&type=$typeID");
      $membershipSummary[$typeID]['current_owner']['current_owner']['url'] = CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&status=$status&type=$typeID&owner=1");
    }

    $totalCount = array();

    $newCountPreMonth = $newCountMonth = $newCountYear = 0;
    $renewCountPreMonth = $renewCountMonth = $renewCountYear = 0;

    $totalCountPreMonth = $totalCountMonth = $totalCountYear = $totalCountCurrent = $totalCountTotal = 0;
    $totalCountPreMonth_owner = $totalCountMonth_owner = $totalCountYear_owner = $totalCountCurrent_owner = $totalCountTotal_owner = 0;
    foreach ($membershipSummary as $key => $value) {
      $newCountPreMonth   = $newCountPreMonth + $value['premonth']['new']['count'];
      $renewCountPreMonth = $renewCountPreMonth + $value['premonth']['renew']['count'];
      $totalCountPreMonth = $totalCountPreMonth + $value['premonth']['total']['count'];
      $newCountMonth      = $newCountMonth + $value['month']['new']['count'];
      $renewCountMonth    = $renewCountMonth + $value['month']['renew']['count'];
      $totalCountMonth    = $totalCountMonth + $value['month']['total']['count'];
      $newCountYear       = $newCountYear + $value['year']['new']['count'];
      $renewCountYear     = $renewCountYear + $value['year']['renew']['count'];
      $totalCountYear     = $totalCountYear + $value['year']['total']['count'];
      $totalCountCurrent  = $totalCountCurrent + $value['current']['total']['count'];
      $totalCountTotal    = $totalCountTotal + $value['total']['total']['count'];

      //LCD add owner values
      $totalCountPreMonth_owner = $totalCountPreMonth_owner + $value['premonth_owner']['premonth_owner']['count'];
      $totalCountMonth_owner = $totalCountMonth_owner + $value['month_owner']['month_owner']['count'];
      $totalCountYear_owner = $totalCountYear_owner + $value['year_owner']['year_owner']['count'];
      $totalCountCurrent_owner = $totalCountCurrent_owner + $value['current_owner']['current_owner']['count'];
      $totalCountTotal_owner = $totalCountTotal_owner + $value['total_owner']['total_owner']['count'];
    }

    $totalCount['premonth']['new'] = array(
      'count' => $newCountPreMonth,
      //'url' => CRM_Utils_System::url('civicrm/activity/search',
      //  "reset=1&force=1&signupType=1&dateLow=$preMonth&dateHigh=$preMonthEnd"
      //),
    );

    $totalCount['premonth']['renew'] = array(
      'count' => $renewCountPreMonth,
      //'url' => CRM_Utils_System::url('civicrm/activity/search',
      //  "reset=1&force=1&signupType=2&dateLow=$preMonth&dateHigh=$preMonthEnd"
      //),
    );

    $totalCount['premonth']['total'] = array(
      'count' => $totalCountPreMonth,
      //'url' => CRM_Utils_System::url('civicrm/activity/search',
      //  "reset=1&force=1&signupType=3&dateLow=$preMonth&dateHigh=$preMonthEnd"
      //),
    );

    $totalCount['month']['new'] = array(
      'count' => $newCountMonth,
      //'url' => CRM_Utils_System::url('civicrm/activity/search',
      //  "reset=1&force=1&signupType=1&dateLow=$monthStart&dateHigh=$ymd"
      //),
    );

    $totalCount['month']['renew'] = array(
      'count' => $renewCountMonth,
      //'url' => CRM_Utils_System::url('civicrm/activity/search',
      //  "reset=1&force=1&signupType=2&dateLow=$monthStart&dateHigh=$ymd"
      //),
    );

    $totalCount['month']['total'] = array(
      'count' => $totalCountMonth,
      //'url' => CRM_Utils_System::url('civicrm/activity/search',
      //  "reset=1&force=1&signupType=3&dateLow=$monthStart&dateHigh=$ymd"
      //),
    );

    $totalCount['year']['new'] = array(
      'count' => $newCountYear,
      //'url' => CRM_Utils_System::url('civicrm/activity/search',
      //  "reset=1&force=1&signupType=1&dateLow=$yearStart&dateHigh=$ymd"
      //),
    );

    $totalCount['year']['renew'] = array(
      'count' => $renewCountYear,
      //'url' => CRM_Utils_System::url('civicrm/activity/search',
      //  "reset=1&force=1&signupType=2&dateLow=$yearStart&dateHigh=$ymd"
      //),
    );

    $totalCount['year']['total'] = array(
      'count' => $totalCountYear,
      //'url' => CRM_Utils_System::url('civicrm/activity/search',
      //  "reset=1&force=1&signupType=3&dateLow=$yearStart&dateHigh=$ymd"
      //),
    );

    $totalCount['current']['total'] = array(
      'count' => $totalCountCurrent,
      'url' => CRM_Utils_System::url('civicrm/member/search',
        "reset=1&force=1&status=$status"
      ),
    );

    $totalCount['total']['total'] = array(
      'count' => $totalCountTotal,
      'url' => CRM_Utils_System::url('civicrm/member/search',
        "reset=1&force=1&status=$status"
      ),
    );

    if (!$isCurrentMonth) {
      $totalCount['total']['total'] = array(
        'count' => $totalCountTotal,
        'url' => CRM_Utils_System::url('civicrm/member/search',
          "reset=1&force=1&status=$status&start=&end=$ymd"
        ),
      );
    }

    // Activity search also unable to handle owner vs. inherited

    //LCD add owner values
    $totalCount['premonth_owner']['premonth_owner'] = array(
      'count' => $totalCountPreMonth_owner,
    //  'url' => CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&status=$status&start=$preMonth&end=$preMonthEnd&owner=1"),
    );

    $totalCount['month_owner']['month_owner'] = array(
      'count' => $totalCountMonth_owner,
    //  'url' => CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&status=$status&start=$monthStart&end=$ymd&owner=1"),
    );

    $totalCount['year_owner']['year_owner'] = array(
      'count' => $totalCountYear_owner,
    //  'url' => CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&status=$status&start=$yearStart&end=$ymd&owner=1"),
    );

    $totalCount['current_owner']['current_owner'] = array(
      'count' => $totalCountCurrent_owner,
    //  'url' => CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&status=$status&owner=1"),
    );

    $totalCount['total_owner']['total_owner'] = array(
      'count' => $totalCountTotal_owner,
    //  'url' => CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&status=$status&owner=1"),
    );

    if (!$isCurrentMonth) {
      $totalCount['total_owner']['total_owner'] = array(
        'count' => $totalCountTotal_owner,
      //  'url' => CRM_Utils_System::url('civicrm/member/search', "reset=1&force=1&status=$status&start=&end=$ymd&owner=1"),
      );
    }
    //LCD end

    $this->assign('membershipSummary', $membershipSummary);
    $this->assign('totalCount', $totalCount);
    $this->assign('month', date('F', $monthStartTs));
    $this->assign('year', date('Y', $monthStartTs));
    $this->assign('premonth', date('F', strtotime($preMonth)));
    $this->assign('currentMonth', date('F'));
    $this->assign('currentYear', date('Y'));
    $this->assign('isCurrent', $isCurrentMonth);
    $this->assign('preMonth', $isPreviousMonth);
  }

  /**
   * This function is the main function that is called when the page loads,
   * it decides the which action has to be taken for the page.
   *
   * return null
   * @access public
   */
  function run() {
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

