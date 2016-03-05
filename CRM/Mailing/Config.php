<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 */
class CRM_Mailing_Config {

  const OUTBOUND_OPTION_SMTP = 0;
  const OUTBOUND_OPTION_SENDMAIL = 1;
  const OUTBOUND_OPTION_DISABLED = 2;
  const OUTBOUND_OPTION_MAIL = 3;
  const OUTBOUND_OPTION_MOCK = 4; // seems to be the same as 2, but also calls Mail's pre/post hooks? - see packages/Mail
  const OUTBOUND_OPTION_REDIRECT_TO_DB = 5;

  // special value for mail bulk inserts to avoid
  // potential duplication, assuming a smaller number reduces number of queries
  // by some factor, so some tradeoff. CRM-8678
  const BULK_MAIL_INSERT_COUNT = 10;

}
