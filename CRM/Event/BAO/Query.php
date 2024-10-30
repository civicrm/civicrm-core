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
class CRM_Event_BAO_Query extends CRM_Core_BAO_Query {

  /**
   * Function get the import/export fields for contribution.
   *
   * @param bool $checkPermission
   *
   * @return array
   *   Associative array of contribution fields
   */
  public static function &getFields($checkPermission = TRUE) {
    $fields = [];
    $fields = array_merge($fields, CRM_Event_DAO_Event::import());
    $fields = array_merge($fields, self::getParticipantFields());
    $fields = array_merge($fields, CRM_Core_DAO_Discount::export());
    $fields['event'] = self::getPseudoEventDateFieldMetadata();
    return $fields;
  }

  /**
   * @return array
   */
  public static function getParticipantFields() {
    $checkPermission = TRUE;
    $fields = [];

    $tmpFields = CRM_Event_DAO_Participant::import();

    $note = [
      'participant_note' => [
        'title' => ts('Participant Note'),
        'name' => 'participant_note',
        'headerPattern' => '/(participant.)?note$/i',
        'data_type' => CRM_Utils_Type::T_TEXT,
      ],
    ];

    // Split status and status id into 2 fields
    // Fixme: it would be better to leave as 1 field and intelligently handle both during import
    // note import undoes this - it is still here in case the search usage uses it.
    $participantStatus = [
      'participant_status' => [
        'title' => ts('Participant Status'),
        'name' => 'participant_status',
        'data_type' => CRM_Utils_Type::T_STRING,
      ],
    ];
    $tmpFields['participant_status_id']['title'] = ts('Participant Status Id');

    // Split role and role id into 2 fields
    // Fixme: it would be better to leave as 1 field and intelligently handle both during import
    // note import undoes this - it is still here in case the search usage uses it.
    $participantRole = [
      'participant_role' => [
        'title' => ts('Participant Role'),
        'name' => 'participant_role',
        'data_type' => CRM_Utils_Type::T_STRING,
      ],
    ];
    $tmpFields['participant_role_id']['title'] = ts('Participant Role Id');

    $eventType = [
      'event_type' => [
        'title' => ts('Event Type'),
        'name' => 'event_type',
        'data_type' => CRM_Utils_Type::T_STRING,
      ],
    ];

    $tmpContactField = [];
    $tmpFields['participant_contact_id']['title'] = $tmpFields['participant_contact_id']['title'] . ' (match to contact)';

    $fields = array_merge($fields, $tmpContactField);
    $fields = array_merge($fields, $tmpFields);
    $fields = array_merge($fields, $note, $participantStatus, $participantRole, $eventType);
    $fields = array_merge($fields, CRM_Core_BAO_CustomField::getFieldsForImport('Participant', FALSE, FALSE, FALSE, $checkPermission));

    return $fields;
  }

  /**
   * Build select for CiviEvent.
   *
   * @param $query
   */
  public static function select(&$query) {
    if (($query->_mode & CRM_Contact_BAO_Query::MODE_EVENT) ||
      CRM_Contact_BAO_Query::componentPresent($query->_returnProperties, 'participant_')
    ) {
      $query->_select['participant_id'] = 'civicrm_participant.id as participant_id';
      $query->_element['participant_id'] = 1;
      $query->_tables['civicrm_participant'] = $query->_whereTables['civicrm_participant'] = 1;

      //add fee level
      if (!empty($query->_returnProperties['participant_fee_level'])) {
        $query->_select['participant_fee_level'] = 'civicrm_participant.fee_level as participant_fee_level';
        $query->_element['participant_fee_level'] = 1;
      }

      //add participant contact ID
      if (!empty($query->_returnProperties['participant_contact_id'])) {
        $query->_select['participant_contact_id'] = 'civicrm_participant.contact_id as participant_contact_id';
        $query->_element['participant_contact_id'] = 1;
      }

      //add fee amount
      if (!empty($query->_returnProperties['participant_fee_amount'])) {
        $query->_select['participant_fee_amount'] = 'civicrm_participant.fee_amount as participant_fee_amount';
        $query->_element['participant_fee_amount'] = 1;
      }

      //add fee currency
      if (!empty($query->_returnProperties['participant_fee_currency'])) {
        $query->_select['participant_fee_currency'] = "civicrm_participant.fee_currency as participant_fee_currency";
        $query->_element['participant_fee_currency'] = 1;
      }

      //add event title also if event id is select
      if (!empty($query->_returnProperties['event_id']) || !empty($query->_returnProperties['event_title'])) {
        $query->_select['event_id'] = "civicrm_event.id as event_id";
        $query->_select['event_title'] = "civicrm_event.title as event_title";
        $query->_element['event_id'] = 1;
        $query->_element['event_title'] = 1;
        $query->_tables['civicrm_event'] = 1;
        $query->_whereTables['civicrm_event'] = 1;
      }

      //add start date / end date
      if (!empty($query->_returnProperties['event_start_date'])) {
        $query->_select['event_start_date'] = "civicrm_event.start_date as event_start_date";
        $query->_element['event_start_date'] = 1;
      }

      if (!empty($query->_returnProperties['event_end_date'])) {
        $query->_select['event_end_date'] = "civicrm_event.end_date as event_end_date";
        $query->_element['event_end_date'] = 1;
      }

      //event type
      if (!empty($query->_returnProperties['event_type'])) {
        $query->_select['event_type'] = "event_type.label as event_type";
        $query->_element['event_type'] = 1;
        $query->_tables['event_type'] = 1;
        $query->_whereTables['event_type'] = 1;
      }

      if (!empty($query->_returnProperties['event_type_id'])) {
        $query->_select['event_type_id'] = "civicrm_event.event_type_id as event_type_id";
        $query->_element['event_type_id'] = 1;
        $query->_tables['civicrm_event'] = 1;
        $query->_whereTables['civicrm_event'] = 1;
      }

      //add status_id
      if (!empty($query->_returnProperties['participant_status_id'])) {
        $query->_select['participant_status_id'] = "civicrm_participant.status_id as participant_status_id";
        $query->_element['participant_status_id'] = 1;
        $query->_tables['civicrm_participant'] = 1;
        $query->_whereTables['civicrm_participant'] = 1;
      }

      // get particupant_status label
      if (!empty($query->_returnProperties['participant_status'])) {
        $query->_select['participant_status'] = "participant_status.label as participant_status";
        $query->_element['participant_status'] = 1;
        $query->_tables['participant_status'] = 1;
        $query->_whereTables['civicrm_participant'] = 1;
      }

      //add participant_role_id
      if (!empty($query->_returnProperties['participant_role_id'])) {
        $query->_select['participant_role_id'] = "civicrm_participant.role_id as participant_role_id";
        $query->_element['participant_role_id'] = 1;
        $query->_tables['civicrm_participant'] = 1;
        $query->_whereTables['civicrm_participant'] = 1;
        $query->_pseudoConstantsSelect['participant_role_id'] = [
          'pseudoField' => 'participant_role_id',
          'idCol' => 'participant_role_id',
        ];
      }

      //add participant_role
      if (!empty($query->_returnProperties['participant_role'])) {
        $query->_select['participant_role'] = "civicrm_participant.role_id as participant_role";
        $query->_element['participant_role'] = 1;
        $query->_tables['participant_role'] = 1;
        $query->_whereTables['civicrm_participant'] = 1;
        $query->_pseudoConstantsSelect['participant_role'] = [
          'pseudoField' => 'participant_role',
          'idCol' => 'participant_role',
        ];
      }

      //add register date
      if (!empty($query->_returnProperties['participant_register_date'])) {
        $query->_select['participant_register_date'] = "civicrm_participant.register_date as participant_register_date";
        $query->_element['participant_register_date'] = 1;
      }

      //add source
      if (!empty($query->_returnProperties['participant_source'])) {
        $query->_select['participant_source'] = "civicrm_participant.source as participant_source";
        $query->_element['participant_source'] = 1;
        $query->_tables['civicrm_participant'] = $query->_whereTables['civicrm_participant'] = 1;
      }

      //participant note
      if (!empty($query->_returnProperties['participant_note'])) {
        $query->_select['participant_note'] = "participant_note.note as participant_note";
        $query->_element['participant_note'] = 1;
        $query->_tables['participant_note'] = 1;
        $query->_whereTables['participant_note'] = 1;
      }

      if (!empty($query->_returnProperties['participant_is_pay_later'])) {
        $query->_select['participant_is_pay_later'] = "civicrm_participant.is_pay_later as participant_is_pay_later";
        $query->_element['participant_is_pay_later'] = 1;
      }

      if (!empty($query->_returnProperties['participant_is_test'])) {
        $query->_select['participant_is_test'] = "civicrm_participant.is_test as participant_is_test";
        $query->_element['participant_is_test'] = 1;
      }

      if (!empty($query->_returnProperties['participant_registered_by_id'])) {
        $query->_select['participant_registered_by_id'] = "civicrm_participant.registered_by_id as participant_registered_by_id";
        $query->_element['participant_registered_by_id'] = 1;
      }

      // get discount name
      if (!empty($query->_returnProperties['participant_discount_name'])) {
        $query->_select['participant_discount_name'] = "discount_name.title as participant_discount_name";
        $query->_element['participant_discount_name'] = 1;
        $query->_tables['civicrm_discount'] = 1;
        $query->_tables['participant_discount_name'] = 1;
        $query->_whereTables['civicrm_discount'] = 1;
        $query->_whereTables['participant_discount_name'] = 1;
      }

      //carry campaign id to selectors.
      if (!empty($query->_returnProperties['participant_campaign_id'])) {
        $query->_select['participant_campaign_id'] = 'civicrm_participant.campaign_id as participant_campaign_id';
        $query->_element['participant_campaign_id'] = 1;
      }
    }
  }

  /**
   * Get event related where clauses.
   *
   * @param \CRM_Contact_BAO_Query $query
   */
  public static function where(&$query) {
    foreach (array_keys($query->_params) as $id) {
      if (empty($query->_params[$id][0])) {
        continue;
      }
      if (substr($query->_params[$id][0], 0, 6) == 'event_' ||
        substr($query->_params[$id][0], 0, 12) == 'participant_'
      ) {
        if ($query->_mode == CRM_Contact_BAO_Query::MODE_CONTACTS) {
          $query->_useDistinct = TRUE;
        }
        self::whereClauseSingle($query->_params[$id], $query);
      }
    }
  }

  /**
   * @param $values
   * @param \CRM_Contact_BAO_Query $query
   *
   * @throws \CRM_Core_Exception
   */
  public static function whereClauseSingle(&$values, &$query) {
    $checkPermission = empty($query->_skipPermission);
    [$name, $op, $value, $grouping, $wildcard] = $values;
    $fields = array_merge(CRM_Event_BAO_Event::fields(), CRM_Event_BAO_Participant::exportableFields());
    $fieldSpec = $fields[$values[0]] ?? [];

    switch ($name) {
      case 'event_low':
      case 'event_high':
        $query->dateQueryBuilder($values,
          'civicrm_event', 'event', 'start_date', ts('Event Active On'), TRUE, 'YmdHis', 'end_date'
        );
        return;

      case 'event_start_date_low':
      case 'event_start_date_high':
        $query->dateQueryBuilder($values,
          'civicrm_event', 'event_start_date', 'start_date', 'Start Date'
        );
        return;

      case 'event_end_date_low':
      case 'event_end_date_high':
        $query->dateQueryBuilder($values,
          'civicrm_event', 'event_end_date', 'end_date', 'End Date'
        );
        return;

      case 'event_include_repeating_events':
        /**
         * Include Repeating Events
         */
        //Get parent of this event
        $exEventId = '';
        if ($query->_where[$grouping]) {
          foreach ($query->_where[$grouping] as $key => $val) {
            if (str_contains($val, 'civicrm_event.id =')) {
              $exEventId = $val;
              $extractEventId = explode(" ", $val);
              $value = $extractEventId[2];
              $where = $query->_where[$grouping][$key];
            }
            elseif (str_contains($val, 'civicrm_event.id IN')) {
              //extract the first event id if multiple events are selected
              preg_match('/civicrm_event.id IN \(\"(\d+)/', $val, $matches);
              $value = $matches[1];
              $where = $query->_where[$grouping][$key];
            }
          }
          if ($exEventId) {
            $extractEventId = explode(" ", $exEventId);
            $value = $extractEventId[2];
          }
          elseif (!empty($matches[1])) {
            $value = $matches[1];
          }
          $where = $query->_where[$grouping][$key];
        }
        $thisEventHasParent = CRM_Core_BAO_RecurringEntity::getParentFor($value, 'civicrm_event');
        if ($thisEventHasParent) {
          $getAllConnections = CRM_Core_BAO_RecurringEntity::getEntitiesForParent($thisEventHasParent, 'civicrm_event');
          $allEventIds = [];
          foreach ($getAllConnections as $key => $val) {
            $allEventIds[] = $val['id'];
          }
          if (!empty($allEventIds)) {
            $op = "IN";
            $value = "(" . implode(",", $allEventIds) . ")";
          }
        }
        $query->_where[$grouping][] = "{$where} OR civicrm_event.id $op {$value}";
        $query->_qill[$grouping][] = ts('Include Repeating Events');
        $query->_tables['civicrm_event'] = $query->_whereTables['civicrm_event'] = 1;
        return;

      case 'participant_is_test':
        $key = array_search('civicrm_participant.is_test = 0', $query->_where[$grouping]);
        if (!empty($key)) {
          unset($query->_where[$grouping][$key]);
        }
      case 'participant_test':
        // We dont want to include all tests for sql OR CRM-7827
        if (!$value || $query->getOperator() != 'OR') {
          $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_participant.is_test",
            $op,
            $value,
            "Boolean"
          );

          $isTest = $value ? ts('a Test') : ts('not a Test');
          $query->_qill[$grouping][] = ts("Participant is %1", [1 => $isTest]);
          $query->_tables['civicrm_participant'] = $query->_whereTables['civicrm_participant'] = 1;
        }
        return;

      case 'participant_fee_id':
        $labels = [];
        foreach ($value as $val) {
          $labels[] = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceFieldValue', $val, 'label');
        }
        $query->_where[$grouping][] = "civicrm_line_item.price_field_value_id IN (" . implode(', ', $value) . ")";
        $query->_where[$grouping][] = "civicrm_line_item.qty > 0";
        $query->_qill[$grouping][] = ts("Fee level") . " IN " . implode(', ', $labels);
        $query->_tables['civicrm_participant'] = $query->_tables['civicrm_line_item'] = $query->_whereTables['civicrm_line_item'] = 1;
        return;

      case 'participant_fee_amount_high':
      case 'participant_fee_amount_low':
        $query->numberRangeBuilder($values,
          'civicrm_participant', 'participant_fee_amount', 'fee_amount', 'Fee Amount'
        );
        return;

      case 'participant_status_id':
        $query->handleWhereFromMetadata($fieldSpec, $name, $value, $op);
        return;

      case 'participant_status':
      case 'participant_source':
      case 'participant_id':
      case 'participant_contact_id':
      case 'participant_is_pay_later':
      case 'participant_fee_amount':
      case 'participant_fee_level':
      case 'participant_campaign_id':
      case 'participant_registered_by_id':

        $qillName = $name;
        if (in_array($name, [
          'participant_source',
          'participant_id',
          'participant_contact_id',
          'participant_fee_amount',
          'participant_fee_level',
          'participant_is_pay_later',
          'participant_campaign_id',
          'participant_registered_by_id',
        ])) {
          $name = str_replace('participant_', '', $name);
          if ($name == 'is_pay_later') {
            $qillName = $name;
          }
        }
        elseif ($name == 'participant_status') {
          $name = 'status_id';
        }

        $dataType = !empty($fields[$qillName]['type']) ? CRM_Utils_Type::typeToString($fields[$qillName]['type']) : 'String';
        $tableName = empty($tableName) ? 'civicrm_participant' : $tableName;
        if (is_array($value) && in_array(key($value), CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
          $op = key($value);
          $value = $value[$op];
        }
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("$tableName.$name", $op, $value, $dataType);

        [$op, $value] = CRM_Contact_BAO_Query::buildQillForFieldValue('CRM_Event_DAO_Participant', $name, $value, $op);
        $query->_qill[$grouping][] = ts('%1 %2 %3', [1 => $fields[$qillName]['title'], 2 => $op, 3 => $value]);
        $query->_tables['civicrm_participant'] = $query->_whereTables['civicrm_participant'] = 1;
        return;

      case 'participant_role':
      case 'participant_role_id':
        $qillName = $name;
        $name = 'role_id';

        $dataType = !empty($fields[$qillName]['type']) ? CRM_Utils_Type::typeToString($fields[$qillName]['type']) : 'String';
        $tableName = empty($tableName) ? 'civicrm_participant' : $tableName;
        if (is_array($value) && in_array(key($value), CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
          $op = key($value);
          $value = $value[$op];
        }
        if (!str_contains($op, 'NULL') && !str_contains($op, 'EMPTY') && !str_contains($op, 'LIKE')) {
          $regexOp = (str_contains($op, '!') || str_contains($op, 'NOT')) ? 'NOT REGEXP' : 'REGEXP';
          $regexp = "([[:cntrl:]]|^)" . implode('([[:cntrl:]]|$)|([[:cntrl:]]|^)', (array) $value) . "([[:cntrl:]]|$)";
          $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_participant.$name", $regexOp, $regexp, 'String');
        }
        else {
          $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("$tableName.$name", $op, $value, $dataType);
        }

        [$op, $value] = CRM_Contact_BAO_Query::buildQillForFieldValue('CRM_Event_DAO_Participant', $name, $value, $op);
        $query->_qill[$grouping][] = ts('%1 %2 %3', [1 => $fields[$qillName]['title'], 2 => $op, 3 => $value]);
        $query->_tables['civicrm_participant'] = $query->_whereTables['civicrm_participant'] = 1;
        return;

      case 'participant_register_date':
      case 'participant_register_date_high':
      case 'participant_register_date_low':
        $query->dateQueryBuilder($values,
          'civicrm_participant', 'participant_register_date', 'register_date', 'Register Date'
        );
        return;

      case 'event_id':
      case 'participant_event_id':
        $name = str_replace('participant_', '', $name);
      case 'event_is_public':
      case 'event_type_id':
      case 'event_title':
        $qillName = $name;
        if (in_array($name, ['event_id', 'event_title', 'event_is_public'])) {
          $name = str_replace('event_', '', $name);
        }
        $dataType = !empty($fields[$qillName]['type']) ? CRM_Utils_Type::typeToString($fields[$qillName]['type']) : 'String';

        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_event.$name", $op, $value, $dataType);
        $query->_tables['civicrm_event'] = $query->_whereTables['civicrm_event'] = 1;
        if (!array_key_exists($qillName, $fields)) {
          break;
        }
        [$op, $value] = CRM_Contact_BAO_Query::buildQillForFieldValue('CRM_Event_DAO_Event', $name, $value, $op, ['check_permission' => $checkPermission]);
        $query->_qill[$grouping][] = ts('%1 %2 %3', [1 => $fields[$qillName]['title'], 2 => $op, 3 => $value]);
        return;

      case 'participant_note':
        $query->_tables['civicrm_participant'] = $query->_whereTables['civicrm_participant'] = 1;
        $query->_tables['participant_note'] = $query->_whereTables['participant_note'] = 1;
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause('participant_note.note', $op, $value, 'String');
        $query->_qill[$grouping][] = ts('%1 %2 %3', [1 => $fields[$name]['title'], 2 => $op, 3 => $value]);
        break;
    }
  }

  /**
   * @param string $name
   * @param $mode
   * @param $side
   *
   * @return null|string
   */
  public static function from($name, $mode, $side) {
    $from = NULL;
    switch ($name) {
      case 'civicrm_participant':
        $from = ' LEFT JOIN civicrm_participant ON civicrm_participant.contact_id = contact_a.id ';
        break;

      case 'civicrm_event':
        //CRM-17121
        $from = ' LEFT JOIN civicrm_event ON civicrm_participant.event_id = civicrm_event.id ';
        break;

      case 'event_type':
        $from = " $side JOIN civicrm_option_group option_group_event_type ON (option_group_event_type.name = 'event_type')";
        $from .= " $side JOIN civicrm_option_value event_type ON (civicrm_event.event_type_id = event_type.value AND option_group_event_type.id = event_type.option_group_id ) ";
        break;

      case 'participant_note':
        $from .= " $side JOIN civicrm_note participant_note ON ( participant_note.entity_table = 'civicrm_participant' AND
                                                        civicrm_participant.id = participant_note.entity_id )";
        break;

      case 'participant_status':
        $from .= " $side JOIN civicrm_participant_status_type participant_status ON (civicrm_participant.status_id = participant_status.id) ";
        break;

      case 'participant_role':
        $from = " $side JOIN civicrm_option_group option_group_participant_role ON (option_group_participant_role.name = 'participant_role')";
        $from .= " $side JOIN civicrm_option_value participant_role ON ((civicrm_participant.role_id = participant_role.value OR SUBSTRING_INDEX(role_id,'', 1) = participant_role.value)
                               AND option_group_participant_role.id = participant_role.option_group_id ) ";
        break;

      case 'participant_discount_name':
        $from = " $side JOIN civicrm_discount discount ON ( civicrm_participant.discount_id = discount.id )";
        $from .= " $side JOIN civicrm_option_group discount_name ON ( discount_name.id = discount.price_set_id ) ";
        break;

      case 'civicrm_line_item':
        $from .= " $side JOIN civicrm_line_item ON civicrm_line_item.entity_id = civicrm_participant.id AND civicrm_line_item.entity_table = 'civicrm_participant'";
        break;
    }
    return $from;
  }

  /**
   * @param $mode
   * @param bool $includeCustomFields
   *
   * @return array|null
   */
  public static function defaultReturnProperties(
    $mode,
    $includeCustomFields = TRUE
  ) {
    $properties = NULL;
    if ($mode & CRM_Contact_BAO_Query::MODE_EVENT) {
      $properties = [
        'contact_type' => 1,
        'contact_sub_type' => 1,
        'sort_name' => 1,
        'display_name' => 1,
        'event_id' => 1,
        'event_title' => 1,
        'event_start_date' => 1,
        'event_end_date' => 1,
        'event_type' => 1,
        'participant_id' => 1,
        'participant_status' => 1,
        'participant_status_id' => 1,
        'participant_role' => 1,
        'participant_role_id' => 1,
        'participant_note' => 1,
        'participant_register_date' => 1,
        'participant_source' => 1,
        'participant_fee_level' => 1,
        'participant_is_test' => 1,
        'participant_is_pay_later' => 1,
        'participant_fee_amount' => 1,
        'participant_discount_name' => 1,
        'participant_fee_currency' => 1,
        'participant_registered_by_id' => 1,
        'participant_campaign_id' => 1,
      ];

      if ($includeCustomFields) {
        // also get all the custom participant properties
        $fields = CRM_Core_BAO_CustomField::getFieldsForImport('Participant');
        if (!empty($fields)) {
          foreach ($fields as $name => $dontCare) {
            $properties[$name] = 1;
          }
        }
      }
    }

    return $properties;
  }

  /**
   * Get the metadata for fields to be included on the grant search form.
   *
   * @throws \CRM_Core_Exception
   */
  public static function getSearchFieldMetadata() {
    $fields = [
      'participant_status_id',
      'participant_register_date',
      // Super-weird but we have to make it work.....
      'event',
    ];
    $metadata = civicrm_api3('Participant', 'getfields', [])['values'];
    $metadata['event'] = self::getPseudoEventDateFieldMetadata();
    return array_intersect_key($metadata, array_flip($fields));
  }

  /**
   * Build the event search form.
   *
   * @param \CRM_Event_Form_Search $form
   *
   * @throws \CRM_Core_Exception
   */
  public static function buildSearchForm(&$form) {
    $form->addSearchFieldMetadata(['Participant' => self::getSearchFieldMetadata()]);
    $form->addFormFieldsFromMetadata();
    $dataURLEventFee = CRM_Utils_System::url('civicrm/ajax/eventFee',
      "reset=1",
      FALSE, NULL, FALSE
    );

    $form->assign('dataURLEventFee', $dataURLEventFee);

    $form->addEntityRef('event_id', ts('Event Name'), [
      'entity' => 'Event',
      'placeholder' => ts('- any -'),
      'multiple' => 1,
      'select' => ['minimumInputLength' => 0],
    ]);
    $form->addEntityRef('event_type_id', ts('Event Type'), [
      'entity' => 'OptionValue',
      'placeholder' => ts('- any -'),
      'select' => ['minimumInputLength' => 0],
      'api' => [
        'params' => ['option_group_id' => 'event_type'],
      ],
    ]);
    $obj = new CRM_Report_Form_Event_ParticipantListing();
    $form->add('select', 'participant_fee_id',
       ts('Fee Level'),
       $obj->getPriceLevels(),
       FALSE, ['class' => 'crm-select2', 'multiple' => 'multiple', 'placeholder' => ts('- any -')]
    );

    $form->addElement('checkbox', "event_include_repeating_events", NULL, ts('Include participants from all events in the %1 series', [1 => '<em>%1</em>']));

    $form->addSelect('participant_role_id',
      [
        'entity' => 'participant',
        'label' => ts('Participant Role'),
        'multiple' => 'multiple',
        'option_url' => NULL,
        'placeholder' => ts('- any -'),
      ]
    );

    $form->addYesNo('participant_test', ts('Participant is a Test?'), TRUE);
    $form->addYesNo('participant_is_pay_later', ts('Participant is Pay Later?'), TRUE);
    $form->addElement('text', 'participant_fee_amount_low', ts('From'), ['size' => 8, 'maxlength' => 8]);
    $form->addElement('text', 'participant_fee_amount_high', ts('To'), ['size' => 8, 'maxlength' => 8]);

    $form->addRule('participant_fee_amount_low', ts('Please enter a valid money value.'), 'money');
    $form->addRule('participant_fee_amount_high', ts('Please enter a valid money value.'), 'money');
    $form->add('number', 'participant_id', ts('Participant ID'), ['class' => 'four', 'min' => 1]);

    self::addCustomFormFields($form, ['Participant', 'Event']);

    CRM_Campaign_BAO_Campaign::addCampaignInComponentSearch($form, 'participant_campaign_id');

    $form->assign('validCiviEvent', TRUE);
    $form->setDefaults(['participant_test' => 0]);
  }

  /**
   * @param $tables
   */
  public static function tableNames(&$tables) {
    //add participant table
    if (!empty($tables['civicrm_event'])) {
      $tables = array_merge(['civicrm_participant' => 1], $tables);
    }
  }

  /**
   * Get metadata from pseudo search field 'event'.
   *
   * @return array
   */
  protected static function getPseudoEventDateFieldMetadata(): array {
    return [
      'name' => 'event',
      'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
      'title' => ts('Event Active On'),
      'table_name' => 'civicrm_event',
      'where' => 'civicrm_event.start_date',
      'where_end' => 'civicrm_event.end_date',
      'html' => ['type' => 'SelectDate', 'formatType' => 'activityDateTime'],
    ];
  }

}
