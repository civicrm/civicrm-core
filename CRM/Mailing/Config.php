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
class CRM_Mailing_Config {

  const OUTBOUND_OPTION_SMTP = 0;
  const OUTBOUND_OPTION_SENDMAIL = 1;
  const OUTBOUND_OPTION_DISABLED = 2;
  const OUTBOUND_OPTION_MAIL = 3;
  // seems to be the same as 2, but also calls Mail's pre/post hooks? - see packages/Mail
  const OUTBOUND_OPTION_MOCK = 4;
  const OUTBOUND_OPTION_REDIRECT_TO_DB = 5;

  // special value for mail bulk inserts to avoid
  // potential duplication, assuming a smaller number reduces number of queries
  // by some factor, so some tradeoff. CRM-8678
  // dev/core#1768 Remove this after Dec 2020.
  // Replaced with civimail_sync_interval.
  const BULK_MAIL_INSERT_COUNT = 10;

}
