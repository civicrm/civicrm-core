<?php

/**
 * +--------------------------------------------------------------------+
 * | CiviCRM version 5                                                  |
 * +--------------------------------------------------------------------+
 * | Copyright CiviCRM LLC (c) 2004-2019                                |
 * +--------------------------------------------------------------------+
 * | This file is a part of CiviCRM.                                    |
 * |                                                                    |
 * | CiviCRM is free software; you can copy, modify, and distribute it  |
 * | under the terms of the GNU Affero General Public License           |
 * | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 * |                                                                    |
 * | CiviCRM is distributed in the hope that it will be useful, but     |
 * | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 * | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 * | See the GNU Affero General Public License for more details.        |
 * |                                                                    |
 * | You should have received a copy of the GNU Affero General Public   |
 * | License and the CiviCRM Licensing Exception along                  |
 * | with this program; if not, contact CiviCRM LLC                     |
 * | at info[AT]civicrm[DOT]org. If you have questions about the        |
 * | GNU Affero General Public License or the licensing of CiviCRM,     |
 * | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 * +--------------------------------------------------------------------+
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
