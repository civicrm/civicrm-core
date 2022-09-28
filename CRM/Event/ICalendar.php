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
 * Class to generate various "icalendar" type event feeds
 */
class CRM_Event_ICalendar {

  /**
   * Heart of the iCalendar data assignment process. The runner gets all the meta
   * data for the event and calls the  method to output the iCalendar
   * to the user. If gData param is passed on the URL, outputs gData XML format.
   * Else outputs iCalendar format per IETF RFC2445. Page param true means send
   * to browser as inline content. Else, we send .ics file as attachment.
   */
  public static function run() {
    $id = CRM_Utils_Request::retrieveValue('id', 'Positive', NULL, FALSE, 'GET');
    $type = CRM_Utils_Request::retrieveValue('type', 'Positive', 0);
    $start = CRM_Utils_Request::retrieveValue('start', 'Positive', 0);
    $end = CRM_Utils_Request::retrieveValue('end', 'Positive', 0);

    // We used to handle the event list as a html page at civicrm/event/ical - redirect to the new URL if that was what we requested.
    if (CRM_Utils_Request::retrieveValue('html', 'Positive', 0)) {
      $urlParams = [
        'reset' => 1,
      ];
      $id ? $urlParams['id'] = $id : NULL;
      $type ? $urlParams['type'] = $type : NULL;
      $start ? $urlParams['start'] = $start : NULL;
      $end ? $urlParams['end'] = $end : NULL;
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/event/list', $urlParams, FALSE, NULL, FALSE, TRUE));
    }

    $iCalPage = CRM_Utils_Request::retrieveValue('list', 'Positive', 0);
    $gData = CRM_Utils_Request::retrieveValue('gData', 'Positive', 0);
    $rss = CRM_Utils_Request::retrieveValue('rss', 'Positive', 0);

    $template = CRM_Core_Smarty::singleton();
    $config = CRM_Core_Config::singleton();

    $info = CRM_Event_BAO_Event::getCompleteInfo($start, $type, $id, $end);
    $template->assign('events', $info);
    $template->assign('timezone', @date_default_timezone_get());

    // Send data to the correct template for formatting (iCal vs. gData)
    if ($rss) {
      // rss 2.0 requires lower case dash delimited locale
      $template->assign('rssLang', str_replace('_', '-', strtolower($config->lcMessages)));
      $calendar = $template->fetch('CRM/Core/Calendar/Rss.tpl');
    }
    elseif ($gData) {
      $calendar = $template->fetch('CRM/Core/Calendar/GData.tpl');
    }
    else {
      $calendar = $template->fetch('CRM/Core/Calendar/ICal.tpl');
      $calendar = preg_replace('/(?<!\r)\n/', "\r\n", $calendar);
    }

    // Push output for feed or download
    if ($iCalPage == 1) {
      if ($gData || $rss) {
        CRM_Utils_ICalendar::send($calendar, 'text/xml', 'utf-8');
      }
      else {
        CRM_Utils_ICalendar::send($calendar, 'text/calendar', 'utf-8');
      }
    }
    else {
      CRM_Utils_ICalendar::send($calendar, 'text/calendar', 'utf-8', 'civicrm_ical.ics', 'attachment');
    }
    CRM_Utils_System::civiExit();
  }

}
