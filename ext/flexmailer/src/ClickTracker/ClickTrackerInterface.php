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
namespace Civi\FlexMailer\ClickTracker;

interface ClickTrackerInterface {

  /**
   * @param string $msg
   * @param int $mailing_id
   * @param int|string $queue_id
   * @return mixed
   */
  public function filterContent($msg, $mailing_id, $queue_id);

  //  /**
  //   * @param string $url
  //   * @param int $mailing_id
  //   * @param int|string $queue_id
  //   * @return mixed
  //   */
  //  public function filterUrl($url, $mailing_id, $queue_id);

}
