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
 * $Id$
 *
 */
function civicrm_rules_get_entity() {

  return array(
    'contact' => array(
      'label' => t('Contact'),
      'class' => 'rules_data_type_contact',
      'savable' => FALSE,
      'identifiable' => TRUE,
    ),
    'mailing' => array(
      'label' => t('Mailing'),
      'class' => 'rules_data_type_mailing',
      'savable' => FALSE,
      'identifiable' => TRUE,
    ),
    'event' => array(
      'label' => t('Event'),
      'class' => 'rules_data_type_event',
      'savable' => FALSE,
      'identifiable' => TRUE,
    ),
    'participant' => array(
      'label' => t('Participant'),
      'class' => 'rules_data_type_participant',
      'savable' => FALSE,
      'identifiable' => TRUE,
    ),
  );
}

