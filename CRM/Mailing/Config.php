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
class CRM_Mailing_Config extends CRM_Core_Component_Config {

  const OUTBOUND_OPTION_SMTP = 0;
  const OUTBOUND_OPTION_SENDMAIL = 1;
  const OUTBOUND_OPTION_DISABLED = 2;
  const OUTBOUND_OPTION_MAIL = 3;
  const OUTBOUND_OPTION_MOCK = 4; // seems to be the same as 2, but also calls Mail's pre/post hooks? - see packages/Mail
  const OUTBOUND_OPTION_REDIRECT_TO_DB = 5;

  /**
   * What should be the verp separator we use
   *
   * @var char
   */
  public $verpSeparator = '.';

  /**
   * How long should we wait before checking for new outgoing mailings?
   *
   * @var int
   */
  public $mailerPeriod = 180;

  /**
   * TODO
   *
   * @var int
   */
  public $mailerSpoolLimit = 0;

  /**
   * How many emails should CiviMail deliver on a given run
   *
   * @var int
   */
  public $mailerBatchLimit = 0;

  /**
   * How large should each mail thread be
   *
   * @var int
   */
  public $mailerJobSize = 0;

  /**
   * How many parallel delivery cron jobs should we run
   *
   * @var int
   */
  public $mailerJobsMax = 0;

  /**
   * Should we sleep after sending an email?
   * Setting this to 0 means no sleep
   *
   * @var int
   */
  public $mailThrottleTime = 0;
}

