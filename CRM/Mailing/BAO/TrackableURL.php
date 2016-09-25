<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */
class CRM_Mailing_BAO_TrackableURL extends CRM_Mailing_DAO_TrackableURL {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Given a url, mailing id and queue event id, find or construct a
   * trackable url and redirect url.
   *
   * @param string $url
   *   The target url to track.
   * @param int $mailing_id
   *   The id of the mailing.
   * @param int $queue_id
   *   The queue event id (contact clicking through).
   *
   * @return string
   *   The redirect/tracking url
   */
  public static function getTrackerURL($url, $mailing_id, $queue_id) {

    static $urlCache = array();

    if (array_key_exists($mailing_id . $url, $urlCache)) {
      return $urlCache[$mailing_id . $url] . "&qid=$queue_id";
    }

    // hack for basic CRM-1014 and CRM-1151 and CRM-3492 compliance:
    // let's not replace possible image URLs and CiviMail ones
    if (preg_match('/\.(png|jpg|jpeg|gif|css)[\'"]?$/i', $url)
      or substr_count($url, 'civicrm/extern/')
      or substr_count($url, 'civicrm/mailing/')
    ) {
      // let's not cache these, so they don't get &qid= appended to them
      return $url;
    }
    else {

      $hrefExists = FALSE;
      $config = CRM_Core_Config::singleton();

      $tracker = new CRM_Mailing_BAO_TrackableURL();
      if (preg_match('/^href/i', $url)) {
        $url = preg_replace('/^href[ ]*=[ ]*[\'"](.*?)[\'"]$/i', '$1', $url);
        $hrefExists = TRUE;
      }

      $tracker->url = $url;
      $tracker->mailing_id = $mailing_id;
      if (strlen($tracker->url) > 254) {
        return $url;
      }
      if (!$tracker->find(TRUE)) {
        $tracker->save();
      }
      $id = $tracker->id;
      $tracker->free();

      $redirect = $config->userFrameworkResourceURL . "extern/url.php?u=$id";
      $urlCache[$mailing_id . $url] = $redirect;
    }

    $returnUrl = "{$urlCache[$mailing_id . $url]}&qid={$queue_id}";

    if ($hrefExists) {
      $returnUrl = "href='{$returnUrl}'";
    }

    return $returnUrl;
  }

  /**
   * @param $url
   * @param $mailing_id
   *
   * @return int
   *   Url id of the given url and mail
   */
  public static function getTrackerURLId($url, $mailing_id) {
    $tracker = new CRM_Mailing_BAO_TrackableURL();
    $tracker->url = $url;
    $tracker->mailing_id = $mailing_id;
    if ($tracker->find(TRUE)) {
      return $tracker->id;
    }

    return NULL;
  }

}
