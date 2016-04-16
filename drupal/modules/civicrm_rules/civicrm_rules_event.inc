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
 * $Id$
 *
 */
function civicrm_rules_get_event() {
  $defaults = array(
    'access callback' => 'civicrm_rules_rules_integration_access',
    'module' => 'civicrm',
  );

  $events = array(
    'contact_create' =>
    array(
      'label' => t('Contact has been created'),
      'group' => 'CiviCRM Contact',
      'variables' => civicrm_rules_rules_events_variables(t('created contact')),
    ),
    'contact_edit' =>
    array(
      'label' => t('Contact has been updated'),
      'group' => 'CiviCRM Contact',
      'variables' => civicrm_rules_rules_events_variables(t('update contact')),
    ),
    'contact_view' =>
    array(
      'label' => t('Contact has been viewed'),
      'group' => 'CiviCRM Contact',
      'variables' => civicrm_rules_rules_events_variables(t('viewed contact')),
    ),
    'contact_delete' =>
    array(
      'label' => t('Contact has been deleted'),
      'group' => 'CiviCRM Contact',
      'variables' => civicrm_rules_rules_events_variables(t('deleted contact')),
    ),
    'mailing_create' =>
    array(
      'label' => t('Mailing has been created'),
      'group' => 'CiviCRM Mailing',
      'variables' => civicrm_rules_rules_events_variables(t('created mailing'), 'mailing'),
    ),
    'mailing_edit' =>
    array(
      'label' => t('Mailing has been updated'),
      'group' => 'CiviCRM Mailing',
      'variables' => civicrm_rules_rules_events_variables(t('updated mailing'), 'mailing'),
    ),
    'mailing_uploaded' =>
    array(
      'label' => t('Mailing content has been uploaded'),
      'group' => 'CiviCRM Mailing',
      'variables' => civicrm_rules_rules_events_variables(t('mailing content uploaded'), 'mailing'),
    ),
    'mailing_scheduled' =>
    array(
      'label' => t('Mailing has been scheduled'),
      'group' => 'CiviCRM Mailing',
      'variables' => civicrm_rules_rules_events_variables(t('scheduled mailing'), 'mailing'),
    ),
    'mailing_approved' =>
    array(
      'label' => t('Mailing has been approved/rejected'),
      'group' => 'CiviCRM Mailing',
      'variables' => civicrm_rules_rules_events_variables(t('approved mailing'), 'mailing'),
    ),
    'mailing_inform' =>
    array(
      'label' => t('Inform scheduler about the mailing'),
      'group' => 'CiviCRM Mailing',
      'variables' => civicrm_rules_rules_events_variables(t('inform mailing'), 'mailing'),
    ),
    'mailing_queued' =>
    array(
      'label' => t('Mailing has been queued'),
      'group' => 'CiviCRM Mailing',
      'variables' => civicrm_rules_rules_events_variables(t('queued mailing'), 'mailing'),
    ),
    'mailing_complete' => array(
      'label' => t('Mailing has been completed'),
      'group' => 'CiviCRM Mailing',
      'variables' => civicrm_rules_rules_events_variables(t('completed mailing'), 'mailing'),
    ),
  );

  $validObjects = variable_get('civicrm_rules_post_entities', array());

  if (is_array($validObjects)) {
    foreach ($validObjects as $entity => $enabled) {
      $entity = strtolower($entity);
      if (!$enabled == 0) {
        //todo consider building the entity name into the argument rather than calling the same argument for each
        $events['civicrm_' . $entity . '_create'] = $defaults + array(
          'label' => t("%entity has been created", array('%entity' => $entity)),
          'group' => 'CiviCRM ' . $entity,
          'variables' => civicrm_rules_rules_events_variables(t('Created %entity', array('%entity' => $entity)), $entity),
        );
        $events['civicrm_' . $entity . '_edit'] = $defaults + array(
          'group' => 'CiviCRM ' . $entity,
          'label' => t("%entity has been updated", array('%entity' => $entity)),
          'variables' => civicrm_rules_rules_events_variables(t('Updated %entity', array('%entity' => $entity)), $entity),
        );
        $events['civicrm_' . $entity . '_view'] = $defaults + array(
          'group' => 'CiviCRM ' . $entity,
          'label' => t("%entity has been viewed", array('%entity' => $entity)),
          'variables' => civicrm_rules_rules_events_variables(t('Viewed %entity', array('%entity' => $entity)), $entity),
        );
        $events['civicrm_' . $entity . '_delete'] = $defaults + array(
          'group' => 'CiviCRM ' . $entity,
          'label' => t("%entity has been deleted", array('%entity' => $entity)),
          'variables' => civicrm_rules_rules_events_variables(t('Deleted %entity', array('%entity' => $entity)), $entity),
        );
      }
    }
  }

  foreach ($events as $key => $event) {
    $events[$key] = $defaults + $event;
  }

  return $events;
}

function civicrm_rules_rules_events_variables($label, $type = 'contact') {

  $default = array(
    $type => array('type' => $type,
      'label' => $label,
    ));

  if ($type == 'event') {
    return $default + array(
      'cms_user' => array(
        'type' => 'user',
        'label' => t('User that created the event'),
        'handler' => 'civicrm_rules_events_argument_civicrm_event',
      ),
    );
  }

  if ($type == 'participant') {

    return $default + array(
      'event_node' => array(
        'type' => 'node',
        'label' => t('Node related to the event'),
        'handler' => 'civicrm_rules_events_argument_civicrm_eventnode',
      ),
      'cms_user' => array(
        'type' => 'user',
        'label' => t('User that registered for the event'),
        'handler' => 'civicrm_rules_events_argument_civicrm_contactID_load_user',
      ),
    );
  }

  return $default;
}

