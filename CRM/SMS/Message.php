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
class CRM_SMS_Message {

  /**
   * What address is this SMS message coming from.
   *
   * @var string
   */
  public $from = '';


  /**
   * What address is this SMS message going to.
   *
   * @var string
   */
  public $to = '';

  /**
   * Contact ID that is matched to the From address.
   *
   * @var int
   */
  public $fromContactID = NULL;

  /**
   * Contact ID that is matched to the To address.
   *
   * @var int
   */
  public $toContactID = NULL;

  /**
   * Body content of the message.
   *
   * @var string
   */
  public $body = '';

  /**
   * Trackable ID in the system to match to.
   *
   * @var int
   */
  public $trackID = NULL;

}
