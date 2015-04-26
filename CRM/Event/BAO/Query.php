<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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
class CRM_Event_BAO_Query {

  /**
   * @return array
   */
  public static function &getFields() {
    $fields = array();
    $fields = array_merge($fields, CRM_Event_DAO_Event::import());
    $fields = array_merge($fields, self::getParticipantFields());
    $fields = array_merge($fields, CRM_Core_DAO_Discount::export());

    return $fields;
  }

  /**
   * @return array
   */
  public static function &getParticipantFields() {
    $fields = CRM_Event_BAO_Participant::importableFields('Individual', TRUE, TRUE);
    return $fields;
  }

  /**
   * Build select for CiviEvent.
   *
   * @param $query
   *
   * @return void
   */
  public static function select(&$query) {
    if (($query->_mode & CRM_Contact_BAO_Query::MODE_EVENT) ||
      CRM_Contact_BAO_Query::componentPresent($query->_returnProperties, 'participant_')
    ) {
      $query->_select['participant_id'] = "civicrm_participant.id as participant_id";
      $query->_element['participant_id'] = 1;
      $query->_tables['civicrm_participant'] = $query->_whereTables['civicrm_participant'] = 1;

      //add fee level
      if (!empty($query->_returnProperties['participant_fee_level'])) {
        $query->_select['participant_fee_level'] = "civicrm_participant.fee_level as participant_fee_level";
        $query->_element['participant_fee_level'] = 1;
      }

      //add participant contact ID
      if (!empty($query->_returnProperties['participant_contact_id'])) {
        $query->_select['participant_contact_id'] = "civicrm_participant.contact_id as participant_contact_id";
        $query->_element['participant_contact_id'] = 1;
      }

      //add fee amount
      if (!empty($query->_returnProperties['participant_fee_amount'])) {
        $query->_select['participant_fee_amount'] = "civicrm_participant.fee_amount as participant_fee_amount";
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
        $query->_select['event_type_id'] = "event_type.id as event_type_id";
        $query->_element['event_type_id'] = 1;
        $query->_tables['event_type'] = 1;
        $query->_whereTables['event_type'] = 1;
      }

      //add status and status_id
      if (!empty($query->_returnProperties['participant_status']) || !empty($query->_returnProperties['participant_status_id'])) {
        $query->_select['participant_status'] = "participant_status.label as participant_status";
        $query->_select['participant_status_id'] = "participant_status.id as participant_status_id";
        $query->_element['participant_status_id'] = 1;
        $query->_element['participant_status'] = 1;
        $query->_tables['civicrm_participant'] = 1;
        $query->_tables['participant_status'] = 1;
        $query->_whereTables['civicrm_participant'] = 1;
        $query->_whereTables['participant_status'] = 1;
        $query->_pseudoConstantsSelect['participant_status'] = array(
          'pseudoField' => 'participant_status',
          'idCol' => 'participant_status_id',
        );
      }

      //add participant_role and participant_role_id
      if (!empty($query->_returnProperties['participant_role']) || !empty($query->_returnProperties['participant_role_id'])) {
        $query->_select['participant_role'] = "participant_role.label as participant_role";
        $query->_select['participant_role_id'] = "civicrm_participant.role_id as participant_role_id";
        $query->_element['participant_role'] = 1;
        $query->_element['participant_role_id'] = 1;
        $query->_tables['civicrm_participant'] = 1;
        $query->_tables['participant_role'] = 1;
        $query->_whereTables['civicrm_participant'] = 1;
        $query->_whereTables['participant_role'] = 1;
        $query->_pseudoConstantsSelect['participant_role'] = array(
          'pseudoField' => 'participant_role',
          'idCol' => 'participant_role_id',
        );
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
        $query->_select['participant_note'] = "civicrm_note.note as participant_note";
        $query->_element['participant_note'] = 1;
        $query->_tables['participant_note'] = 1;
        $query->_whereTables['civicrm_note'] = 1;
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
   * @param $query
   */
  public static function where(&$query) {
    $grouping = NULL;
    foreach (array_keys($query->_params) as $id) {
      if (empty($query->_params[$id][0])) {
        continue;
      }
      if (substr($query->_params[$id][0], 0, 6) == 'event_' ||
        substr($query->_params[$id][0], 0, 12) == 'participant_'
      ) {
        if ($query->_mode == CRM_Contact_BAO_QUERY::MODE_CONTACTS) {
          $query->_useDistinct = TRUE;
        }
        $grouping = $query->_params[$id][3];
        self::whereClauseSingle($query->_params[$id], $query);
      }
    }
  }

  /**
   * @param $values
   * @param $query
   */
  public static function whereClauseSingle(&$values, &$query) {
    list($name, $op, $value, $grouping, $wildcard) = $values;
    $fields = array_merge(CRM_Event_BAO_Event::fields(), CRM_Event_BAO_Participant::exportableFields());

    switch ($name) {
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
            if (strstr($val, 'civicrm_event.id =')) {
              $exEventId = $val;
              $extractEventId = explode(" ", $val);
              $value = $extractEventId[2];
              unset($query->_where[$grouping][$key]);
            }
          }
          $extractEventId = explode(" ", $exEventId);
          $value = $extractEventId[2];
          unset($query->_where[$grouping][$key]);
        }
        $thisEventHasParent = CRM_Core_BAO_RecurringEntity::getParentFor($value, 'civicrm_event');
        if ($thisEventHasParent) {
          $getAllConnections = CRM_Core_BAO_RecurringEntity::getEntitiesForParent($thisEventHasParent, 'civicrm_event');
          $allEventIds = array();
          foreach ($getAllConnections as $key => $val) {
            $allEventIds[] = $val['id'];
          }
          if (!empty($allEventIds)) {
            $op = "IN";
            $value = "(" . implode(",", $allEventIds) . ")";
          }
        }
        $query->_where[$grouping][] = "civicrm_event.id $op {$value}";
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
          if ($value) {
            $query->_qill[$grouping][] = ts("Participant is a Test");
          }
          $query->_tables['civicrm_participant'] = $query->_whereTables['civicrm_participant'] = 1;
        }
        return;

      case 'participant_fee_id':
        $feeLabel = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceFieldValue', $value, 'label');
        $feeLabel = CRM_Core_DAO::escapeString(trim($feeLabel));
        if ($value) {
          $query->_where[$grouping][] = "civicrm_participant.fee_level LIKE '%$feeLabel%'";
          $query->_qill[$grouping][] = ts("Fee level") . " contains $feeLabel";
        }
        $query->_tables['civicrm_participant'] = $query->_whereTables['civicrm_participant'] = 1;
        return;

      case 'participant_fee_amount_high':
      case 'participant_fee_amount_low':
        $query->numberRangeBuilder($values,
          'civicrm_participant', 'participant_fee_amount', 'fee_amount', 'Fee Amount'
        );
        return;

      case 'participant_status_id':
      case 'participant_role_id':
        if ($value && is_array($value) && strpos($op, 'IN') === FALSE) {
          $op = 'IN';
        }
      case 'participant_status':
      case 'participant_role':
      case 'participant_source':
      case 'participant_id':
      case 'participant_contact_id':
      case 'participant_is_pay_later':
      case 'participant_fee_amount':
      case 'participant_fee_level':
        $qillName = $name;
        if (in_array($name, array(
              'participant_status_id',
              'participant_role_id',
              'participant_source',
              'participant_id',
              'participant_contact_id',
              'participant_fee_amount',
              'participant_fee_level',
              'participant_is_pay_later',
            ))) {
          $name = str_replace('participant_', '', $name);
          if ($name == 'is_pay_later') {
            $qillName = $name;
          }
          if ($name == 'role_id') {
            $qillName = 'participant_role';
            $query->_where[$grouping][] = " civicrm_participant.$name REGEXP '[[:<:]]" . implode('[[:>:]]|[[:<:]]', (array) $value) . "[[:>:]]' ";
          }
        }

        $dataType = !empty($fields[$qillName]['type']) ? CRM_Utils_Type::typeToString($fields[$qillName]['type']) : 'String';

        if (in_array($name, array('participant_status', 'participant_role'))) {
          $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("$name.label", $op, $value, $dataType);
        }
        elseif ($name != 'role_id') {
          $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_participant.$name", $op, $value, $dataType);
        }
        list($op, $value) = CRM_Contact_BAO_Query::buildQillForFieldValue('CRM_Event_DAO_Participant', $name, $value, $op);
        $query->_qill[$grouping][] = ts('%1 %2 %3', array(1 => $fields[$qillName]['title'], 2 => $op, 3 => $value));
        $query->_tables['civicrm_participant'] = $query->_whereTables['civicrm_participant'] = 1;
        return;

      case 'participant_register_date':
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
        if (in_array($name, array(
              'event_id',
              'event_title',
              'event_is_public',
            )
          )) {
          $name = str_replace('event_', '', $name);
        }
        $dataType = !empty($fields[$qillName]['type']) ? CRM_Utils_Type::typeToString($fields[$qillName]['type']) : 'String';

        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_event.$name", $op, $value, $dataType);
        $query->_tables['civicrm_event'] = $query->_whereTables['civicrm_event'] = 1;
        if (!array_key_exists($qillName, $fields)) {
          break;
        }
        list($op, $value) = CRM_Contact_BAO_Query::buildQillForFieldValue('CRM_Event_DAO_Event', $name, $value, $op);
        $query->_qill[$grouping][] = ts('%1 %2 %3', array(1 => $fields[$qillName]['title'], 2 => $op, 3 => $value));
        return;

      case 'participant_campaign_id':
        $campParams = array(
          'op' => $op,
          'campaign' => $value,
          'grouping' => $grouping,
          'tableName' => 'civicrm_participant',
        );
        CRM_Campaign_BAO_Query::componentSearchClause($campParams, $query);
        return;
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
        $from = " LEFT JOIN civicrm_participant ON civicrm_participant.contact_id = contact_a.id ";
        break;

      case 'civicrm_event':
        $from = " INNER JOIN civicrm_event ON civicrm_participant.event_id = civicrm_event.id ";
        break;

      case 'event_type':
        $from = " $side JOIN civicrm_option_group option_group_event_type ON (option_group_event_type.name = 'event_type')";
        $from .= " $side JOIN civicrm_option_value event_type ON (civicrm_event.event_type_id = event_type.value AND option_group_event_type.id = event_type.option_group_id ) ";
        break;

      case 'participant_note':
        $from .= " $side JOIN civicrm_note ON ( civicrm_note.entity_table = 'civicrm_participant' AND
                                                        civicrm_participant.id = civicrm_note.entity_id )";
        break;

      case 'participant_status':
        $from .= " $side JOIN civicrm_participant_status_type participant_status ON (civicrm_participant.status_id = participant_status.id) ";
        break;

      case 'participant_role':
        $from = " $side JOIN civicrm_option_group option_group_participant_role ON (option_group_participant_role.name = 'participant_role')";
        $from .= " $side JOIN civicrm_option_value participant_role ON (civicrm_participant.role_id = participant_role.value
                               AND option_group_participant_role.id = participant_role.option_group_id ) ";
        break;

      case 'participant_discount_name':
        $from = " $side JOIN civicrm_discount discount ON ( civicrm_participant.discount_id = discount.id )";
        $from .= " $side JOIN civicrm_option_group discount_name ON ( discount_name.id = discount.price_set_id ) ";
        break;
    }
    return $from;
  }

  /**
   * Getter for the qill object.
   *
   * @return string
   */
  public function qill() {
    return (isset($this->_qill)) ? $this->_qill : "";
  }

  /**
   * @param $mode
   * @param bool $includeCustomFields
   *
   * @return array|null
   */
  public  static function defaultReturnProperties(
    $mode,
    $includeCustomFields = TRUE
  ) {
    $properties = NULL;
    if ($mode & CRM_Contact_BAO_Query::MODE_EVENT) {
      $properties = array(
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
      );

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
   * @param CRM_Core_Form $form
   */
  public static function buildSearchForm(&$form) {
    $dataURLEventFee = CRM_Utils_System::url('civicrm/ajax/eventFee',
      "reset=1",
      FALSE, NULL, FALSE
    );

    $form->assign('dataURLEventFee', $dataURLEventFee);

    $form->addEntityRef('event_id', ts('Event Name'), array(
        'entity' => 'event',
        'placeholder' => ts('- any -'),
        'select' => array('minimumInputLength' => 0),
      )
    );
    $form->addEntityRef('event_type_id', ts('Event Type'), array(
        'entity' => 'option_value',
        'placeholder' => ts('- any -'),
        'select' => array('minimumInputLength' => 0),
        'api' => array(
          'params' => array('option_group_id' => 'event_type'),
        ),
      )
    );
    $form->add('text', 'participant_fee_id', ts('Fee Level'), array('class' => 'big crm-ajax-select'));

    CRM_Core_Form_Date::buildDateRange($form, 'event', 1, '_start_date_low', '_end_date_high', ts('From'), FALSE);

    $form->addElement('checkbox', "event_include_repeating_events", NULL, ts('Include participants from all events in the %1 series', array(1 => '<em>%1</em>')));

    $form->addSelect('participant_status_id',
      array('entity' => 'participant', 'label' => ts('Participant Status'), 'multiple' => 'multiple', 'option_url' => NULL, 'placeholder' => ts('- any -'))
    );

    $form->addSelect('participant_role_id',
      array('entity' => 'participant', 'label' => ts('Participant Role'), 'multiple' => 'multiple', 'option_url' => NULL, 'placeholder' => ts('- any -'))
    );

    $form->addYesNo('participant_test', ts('Participant is a Test?'), TRUE);
    $form->addYesNo('participant_is_pay_later', ts('Participant is Pay Later?'), TRUE);
    $form->addElement('text', 'participant_fee_amount_low', ts('From'), array('size' => 8, 'maxlength' => 8));
    $form->addElement('text', 'participant_fee_amount_high', ts('To'), array('size' => 8, 'maxlength' => 8));

    $form->addRule('participant_fee_amount_low', ts('Please enter a valid money value.'), 'money');
    $form->addRule('participant_fee_amount_high', ts('Please enter a valid money value.'), 'money');
    // add all the custom  searchable fields
    $extends = array('Participant', 'Event');
    $groupDetails = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, TRUE, $extends);
    if ($groupDetails) {
      $form->assign('participantGroupTree', $groupDetails);
      foreach ($groupDetails as $group) {
        foreach ($group['fields'] as $field) {
          $fieldId = $field['id'];
          $elementName = 'custom_' . $fieldId;
          CRM_Core_BAO_CustomField::addQuickFormElement($form,
            $elementName,
            $fieldId,
            FALSE, FALSE, TRUE
          );
        }
      }
    }

    CRM_Campaign_BAO_Campaign::addCampaignInComponentSearch($form, 'participant_campaign_id');

    $form->assign('validCiviEvent', TRUE);
    $form->setDefaults(array('participant_test' => 0));
  }

  /**
   * @param $row
   * @param int $id
   */
  public static function searchAction(&$row, $id) {
  }

  /**
   * @param $tables
   */
  public static function tableNames(&$tables) {
    //add participant table
    if (!empty($tables['civicrm_event'])) {
      $tables = array_merge(array('civicrm_participant' => 1), $tables);
    }
  }

}
