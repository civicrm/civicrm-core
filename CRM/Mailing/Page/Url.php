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
 * Redirects a user to the full url from a mailing url.
 */
class CRM_Mailing_Page_Url extends CRM_Core_Page {

  /**
   * Redirect the user to the specified url.
   *
   * @throws \CRM_Core_Exception
   */
  public function run() {
    $queue_id = CRM_Utils_Request::retrieveValue('qid', 'Integer');
    $url_id = CRM_Utils_Request::retrieveValue('u', 'Integer', NULL, TRUE);
    $url = CRM_Mailing_Event_BAO_TrackableURLOpen::track($queue_id, $url_id);

    // CRM-7103
    // Looking for additional query variables and append them when redirecting.
    $query_param = $_GET;
    unset($query_param['qid'], $query_param['u']);
    $query_string = http_build_query($query_param);

    if (strlen($query_string) > 0) {
      // Parse the url to preserve the fragment.
      $pieces = parse_url($url);

      if (isset($pieces['fragment'])) {
        $url = str_replace('#' . $pieces['fragment'], '', $url);
      }

      // Handle additional query string params.
      if ($query_string) {
        if (stristr($url, '?')) {
          $url .= '&' . $query_string;
        }
        else {
          $url .= '?' . $query_string;
        }
      }

      // slap the fragment onto the end per URL spec
      if (isset($pieces['fragment'])) {
        $url .= '#' . $pieces['fragment'];
      }
    }
    CRM_Utils_System::redirect($url);
  }

}
