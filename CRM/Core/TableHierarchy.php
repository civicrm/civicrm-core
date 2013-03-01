<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * Class representing the table relationships
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Core_TableHierarchy {

  /**
   * This array defines weights for table, which are used to sort array of table in from clause
   * @var array
   * @static
   */
  static $info = array(
    'civicrm_contact' => '01',
    'civicrm_address' => '09',
    'civicrm_county' => '10',
    'civicrm_state_province' => '11',
    'civicrm_country' => '12',
    'civicrm_email' => '13',
    'civicrm_phone' => '14',
    'civicrm_im' => '15',
    'civicrm_openid' => '17',
    'civicrm_location_type' => '18',
    'civicrm_group_contact' => '19',
    'civicrm_group' => '20',
    'civicrm_subscription_history' => '21',
    'civicrm_entity_tag' => '22',
    'civicrm_note' => '23',
    'civicrm_contribution' => '24',
                         'civicrm_financial_type'    => '25',
    'civicrm_participant' => '26',
    'civicrm_event' => '27',
    'civicrm_worldregion' => '28',
    'civicrm_case_contact' => '29',
    'civicrm_case' => '30',
    'case_relationship' => '31',
    'case_relation_type' => '32',
    'civicrm_activity' => '33',
    'civicrm_mailing_summary' => '34',
    'civicrm_mailing_event_queue' => '35',
    'civicrm_mailing_job' => '36',
    'civicrm_mailing' => '37',
    'civicrm_mailing_event_bounce' => '38',
    'civicrm_mailing_event_opened' => '39',
    'civicrm_mailing_event_reply' => '40',
    'civicrm_mailing_event_trackable_url_open' => '41',
  );

  static function &info() {
    //get the campaign related tables.
    CRM_Campaign_BAO_Query::info(self::$info);

    return self::$info;
  }
}

