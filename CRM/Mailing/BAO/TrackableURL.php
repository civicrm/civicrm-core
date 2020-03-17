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

    static $urlCache = [];

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

      $tracker = new CRM_Mailing_BAO_TrackableURL();
      if (preg_match('/^href/i', $url)) {
        $url = preg_replace('/^href[ ]*=[ ]*[\'"](.*?)[\'"]$/i', '$1', $url);
        $hrefExists = TRUE;
      }

      $tracker->url = $url;
      $tracker->mailing_id = $mailing_id;

      if (!$tracker->find(TRUE)) {
        $tracker->save();
      }
      $id = $tracker->id;

      $redirect = CRM_Utils_System::externUrl('extern/url', "u=$id");
      $urlCache[$mailing_id . $url] = $redirect;
    }

    $returnUrl = CRM_Utils_System::externUrl('extern/url', "u=$id&qid=$queue_id");

    if ($hrefExists) {
      $returnUrl = "href='{$returnUrl}' rel='nofollow'";
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
