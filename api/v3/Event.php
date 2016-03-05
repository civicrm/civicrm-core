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
 * This api exposes CiviCRM Event.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create a Event.
 *
 * @param array $params
 *   Input parameters.
 *
 * @return array
 *   API result Array.
 */
function civicrm_api3_event_create($params) {
  // Required fields for creating an event
  if (empty($params['id']) && empty($params['is_template'])) {
    civicrm_api3_verify_mandatory($params, NULL, array(
      'start_date',
      'title',
      array('event_type_id', 'template_id'),
    ));
  }
  // Required fields for creating an event template
  elseif (empty($params['id']) && !empty($params['is_template'])) {
    civicrm_api3_verify_mandatory($params, NULL, array(
      'template_title',
    ));
  }

  // Clone event from template
  if (!empty($params['template_id']) && empty($params['id'])) {
    $copy = CRM_Event_BAO_Event::copy($params['template_id']);
    $params['id'] = $copy->id;
    unset($params['template_id']);
  }

  _civicrm_api3_event_create_legacy_support_42($params);
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'Event');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_event_create_spec(&$params) {
  $params['is_active']['api.default'] = 1;
  $params['financial_type_id']['api.aliases'] = array('contribution_type_id');
  $params['is_template']['api.default'] = 0;
}

/**
 * Support for schema changes made in 4.2.
 *
 * The main purpose of the API is to provide integrators a level of stability not provided by
 * the core code or schema - this means we have to provide support for api calls (where possible)
 * across schema changes.
 *
 * @param array $params
 */
function _civicrm_api3_event_create_legacy_support_42(&$params) {
  if (!empty($params['payment_processor_id'])) {
    $params['payment_processor'] = CRM_Core_DAO::VALUE_SEPARATOR . $params['payment_processor_id'] . CRM_Core_DAO::VALUE_SEPARATOR;
  }
}

/**
 * Get Event record.
 *
 * @param array $params
 *
 * @return array
 *   Array of all found event property values.
 */
function civicrm_api3_event_get($params) {

  //legacy support for $params['return.sort']
  if (!empty($params['return.sort'])) {
    $params['options']['sort'] = $params['return.sort'];
    unset($params['return.sort']);
  }

  //legacy support for $params['return.offset']
  if (!empty($params['return.offset'])) {
    $params['options']['offset'] = $params['return.offset'];
    unset($params['return.offset']);
  }

  //legacy support for $params['return.max_results']
  if (!empty($params['return.max_results'])) {
    $params['options']['limit'] = $params['return.max_results'];
    unset($params['return.max_results']);
  }

  $sql = CRM_Utils_SQL_Select::fragment();
  if (!empty($params['isCurrent'])) {
    $sql->where('(start_date >= CURDATE() || end_date >= CURDATE())');
  }

  $events = _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, FALSE, 'Event', $sql, TRUE);
  $options = _civicrm_api3_get_options_from_params($params);
  if ($options['is_count']) {
    return civicrm_api3_create_success($events, $params, 'Event', 'get');
  }
  foreach ($events as $id => $event) {
    if (!empty($params['return.is_full'])) {
      _civicrm_api3_event_getisfull($events, $id);
    }
    _civicrm_api3_event_get_legacy_support_42($events, $id);
    if (!empty($options['return'])) {
      $events[$id]['price_set_id'] = CRM_Price_BAO_PriceSet::getFor('civicrm_event', $id);
    }
  }

  return civicrm_api3_create_success($events, $params, 'Event', 'get');
}

/**
 * Adjust Metadata for Get action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_event_get_spec(&$params) {
  $params['financial_type_id']['api.aliases'] = array('contribution_type_id');
}

/**
 * Support for schema changes made in 4.2.
 *
 * The main purpose of the API is to provide integrators a level of stability not provided by
 * the core code or schema - this means we have to provide support for api calls (where possible)
 * across schema changes.
 *
 * @param array $event
 * @param int $event_id
 */
function _civicrm_api3_event_get_legacy_support_42(&$event, $event_id) {
  if (!empty($event[$event_id]['payment_processor'])) {
    $processors = explode(CRM_Core_DAO::VALUE_SEPARATOR, $event[$event_id]['payment_processor']);
    if (count($processors) == 3) {
      $event[$event_id]['payment_processor_id'] = $processors[1];
    }
  }
}

/**
 * Delete an existing Event.
 *
 * This API is used for deleting a event given its id.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_event_delete($params) {
  return CRM_Event_BAO_Event::del($params['id']) ? civicrm_api3_create_success() : civicrm_api3_create_error(ts('Error while deleting event'));
}

/**
 * Add 'is_full' & 'available_seats' to the return array.
 *
 * (this might be better in the BAO)
 * Default BAO function returns a string if full rather than a Bool - which is more appropriate to a form
 *
 * @param array $event
 *   Return array of the event.
 * @param int $event_id
 *   Id of the event to be updated.
 */
function _civicrm_api3_event_getisfull(&$event, $event_id) {
  $eventFullResult = CRM_Event_BAO_Participant::eventFull($event_id, 1);
  if (!empty($eventFullResult) && is_int($eventFullResult)) {
    $event[$event_id]['available_places'] = $eventFullResult;
  }
  else {
    $event[$event_id]['available_places'] = 0;
  }
  $event[$event_id]['is_full'] = $event[$event_id]['available_places'] == 0 ? 1 : 0;
}


/**
 * Get event list parameters.
 *
 * @see _civicrm_api3_generic_getlist_params
 *
 * @param array $request
 */
function _civicrm_api3_event_getlist_params(&$request) {
  $fieldsToReturn = array('start_date', 'event_type_id', 'title', 'summary');
  $request['params']['return'] = array_unique(array_merge($fieldsToReturn, $request['extra']));
  $request['params']['options']['sort'] = 'start_date DESC';
  $request['params'] += array(
    'is_template' => 0,
    'is_active' => 1,
  );
}

/**
 * Get event list output.
 *
 * @see _civicrm_api3_generic_getlist_output
 *
 * @param array $result
 * @param array $request
 *
 * @return array
 */
function _civicrm_api3_event_getlist_output($result, $request) {
  $output = array();
  if (!empty($result['values'])) {
    foreach ($result['values'] as $row) {
      $data = array(
        'id' => $row[$request['id_field']],
        'label' => $row[$request['label_field']],
        'description' => array(
          CRM_Core_Pseudoconstant::getLabel(
            'CRM_Event_BAO_Event',
            'event_type_id',
            $row['event_type_id']
          ),
        ),
      );
      if (!empty($row['start_date'])) {
        $data['description'][0] .= ': ' . CRM_Utils_Date::customFormat($row['start_date']);
      }
      if (!empty($row['summary'])) {
        $data['description'][] = $row['summary'];
      }
      // Add repeating info
      $repeat = CRM_Core_BAO_RecurringEntity::getPositionAndCount($row['id'], 'civicrm_event');
      $data['extra']['is_recur'] = FALSE;
      if ($repeat) {
        $data['suffix'] = ts('(%1 of %2)', array(1 => $repeat[0], 2 => $repeat[1]));
        $data['extra']['is_recur'] = TRUE;
      }
      $output[] = $data;
    }
  }
  return $output;
}
