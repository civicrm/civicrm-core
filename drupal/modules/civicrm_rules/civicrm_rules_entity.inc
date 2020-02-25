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
