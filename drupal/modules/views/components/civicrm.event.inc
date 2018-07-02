<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
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
 * Build the $data array for CiviEvent related tables
 * Includes the following tables
 * civicrm_event
 * civicrm_participant
 */
function _civicrm_event_data(&$data, $enabled) {
  //----------------------------------------------------------------
  // CIVICRM Events Be next bro! It's A BASE TABLE
  //----------------------------------------------------------------

  $data['civicrm_event']['table']['group'] = t('CiviCRM Events');

  $data['civicrm_event']['table']['base'] = array(
    // Governs the whole mozilla
    'field' => 'id',
    'title' => t('CiviCRM Events'),
    'help' => t("View displays CiviCRM Events"),
  );

  //TABLE JOINS FOR CIVICRM EVENTS GO HERE!

  $data['civicrm_event']['table']['join'] = array(
    // Directly links to contact table.
    'civicrm_participant' => array(
      'left_field' => 'event_id',
      'field' => 'id',
    ),
  );

  // Numeric Event ID
  $data['civicrm_event']['id'] = array(
    'title' => t('Event ID'),
    'help' => t('The numeric ID of the Event'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'relationship' => array(
      'base' => 'civicrm_participant',
      'base field' => 'event_id',
      'handler' => 'views_handler_relationship',
      'label' => t('Event -> Participant'),
    ),
  );

  // Event Max-Partipicants
  $data['civicrm_event']['max_participants'] = array(
    'title' => t('Event Max-Participants'),
    'help' => t('The maximal number of participants of the Event'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //Event Type
  $data['civicrm_event']['event_type'] = array(
    'title' => t('Event Type'),
    'real field' => 'event_type_id',
    'help' => t('The Event Type'),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Event_PseudoConstant',
      'pseudo method' => 'eventType',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'pseudo class' => 'CRM_Event_PseudoConstant',
      'pseudo method' => 'eventType',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //BOOLEAN : IS EVENT PUBLIC
  $data['civicrm_event']['is_public'] = array(
    'title' => t('Is Public'),
    'help' => t('Has this event been made public?'),
    'field' => array(
      'handler' => 'views_handler_field_boolean',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //BOOLEAN : IS EVENT ACTIVE
  $data['civicrm_event']['is_active'] = array(
    'title' => t('Is Active'),
    'help' => t('Has this event been made active?'),
    'field' => array(
      'handler' => 'views_handler_field_boolean',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  //BOOLEAN : Include EVENT Templates ?
  $data['civicrm_event']['is_template'] = array(
    'title' => t('Include Template'),
    'help' => t('Include Event templates?'),
    'field' => array(
      'handler' => 'views_handler_field_boolean',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  //EVENT TITLE
  $data['civicrm_event']['title'] = array(
    'title' => t('Title'),
    'help' => t("The Event's Title"),
    'field' => array(
      'handler' => 'civicrm_handler_field_event_link',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // EVENT SUMMARY
  $data['civicrm_event']['summary'] = array(
    'title' => t('Summary'),
    'help' => t("The Event's Summary"),
    'field' => array(
      'handler' => 'civicrm_handler_field_markup',
      'click sortable' => TRUE,
    ),
  );

  // EVENT DESCRIPTION
  $data['civicrm_event']['description'] = array(
    'title' => t('Description'),
    'help' => t("The Event's Full Description"),
    'field' => array(
      'handler' => 'civicrm_handler_field_markup',
      'click sortable' => TRUE,
    ),
  );

  //EVENT Start DATE
  $data['civicrm_event']['start_date'] = array(
    'title' => t('Start Date'),
    'help' => t("The Event's Start Date"),
    'field' => array(
      'handler' => 'civicrm_handler_field_datetime',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_datetime',
      'is date' => TRUE,
    ),
    'sort' => array(
      'handler' => 'civicrm_handler_sort_date',
    ),
    'argument' => array(
      'handler' => 'date_views_argument_handler',
      'empty field name' => t('Undated'),
      'is date' => TRUE,
    ),
  );

  civicrm_views_add_date_arguments($data['civicrm_event'], array(
    'title' => 'Start Date',
      'name' => 'start_date',
    ));
  //EVENT END DATE
  $data['civicrm_event']['end_date'] = array(
    'title' => t('End Date'),
    'help' => t("The Event's End Date"),
    'field' => array(
      'handler' => 'civicrm_handler_field_datetime',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_datetime',
      'is date' => TRUE,
    ),
    'sort' => array(
      'handler' => 'civicrm_handler_sort_date',
    ),
  );

  civicrm_views_add_date_arguments($data['civicrm_event'], array(
    'title' => 'End Date',
      'name' => 'end_date',
    ));
  //BOOLEAN : IS ONLINE REGISTRATION ENABLED
  $data['civicrm_event']['is_online_registration'] = array(
    'title' => t('Is Online Registration On'),
    'help' => t('Is Online Registration enabled for the event?'),
    'field' => array(
      'handler' => 'views_handler_field_boolean',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //EVENT ONLINE REGISTRATION START DATE
  $data['civicrm_event']['registration_start_date'] = array(
    'title' => t('Registration Start Date'),
    'help' => t("The Event's Online Registration Start Date"),
    'field' => array(
      'handler' => 'civicrm_handler_field_datetime',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_datetime',
      'is date' => TRUE,
    ),
    'sort' => array(
      'handler' => 'civicrm_handler_sort_date',
    ),
  );

  civicrm_views_add_date_arguments($data['civicrm_event'], array(
    'title' => 'Registration Start Date',
      'name' => 'registration_start_date',
    ));
  //EVENT ONLINE REGISTRATION END DATE
  $data['civicrm_event']['registration_end_date'] = array(
    'title' => t('Registration End Date'),
    'help' => t("The Event's Online Registration End Date"),
    'field' => array(
      'handler' => 'civicrm_handler_field_datetime',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_datetime',
      'is date' => TRUE,
    ),
    'sort' => array(
      'handler' => 'civicrm_handler_sort_date',
    ),
  );

  civicrm_views_add_date_arguments($data['civicrm_event'], array(
    'title' => 'Registration End Date',
      'name' => 'registration_end_date',
    ));

  //EVENT LINK TEXT
  $data['civicrm_event']['registration_link_text'] = array(
    'title' => t('Registration Link Text'),
    'help' => t('Text for the Registration Link'),
    'field' => array(
      'handler' => 'civicrm_handler_field_event_link',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  // Event created_id
  $data['civicrm_event']['created_id'] = array(
    'title' => t('Created ID'),
    'help' => t("Contact ID of the event's creator"),
    'field' => array(
      'handler' => 'civicrm_handler_field_event_link',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'relationship' => array(
      'base' => 'civicrm_contact',
      'field' => 'created_id',
      'handler' => 'views_handler_relationship',
      'label' => t('CiviCRM Contact of the event creator'),
    ),
  );
  // Link to Campaign Table
  if (isset($enabled['CiviCampaign'])) {
    $data['civicrm_event']['campaign_id'] = array(
      'title' => t('Campaign'),
      'help' => t('Event -> Campaign'),
      'relationship' => array(
        'base' => 'civicrm_campaign',
        'base field' => 'id',
        'handler' => 'views_handler_relationship',
        'label' => t('Event -> Campaign'),
      ),
    );
  }
  // CHILLING OUT AT THE CIVICRM_LOC_BLOCK TABLE

  $data['civicrm_loc_block']['table']['group'] = t('CiviCRM Locations');

  // Explain how this table joins to others.
  $data['civicrm_loc_block']['table']['join'] = array(
    // Directly links to event table
    'civicrm_event' => array(
      'left_field' => 'loc_block_id',
      'field' => 'id',
    ),
  );

  //----------------------------------------------------------------
  // CIVICRM Participants are here, base tabling it up.
  //----------------------------------------------------------------

  $data['civicrm_participant']['table']['group'] = t('CiviCRM Participants');

  $data['civicrm_participant']['table']['base'] = array(
    // Governs the whole mozilla
    'field' => 'id',
    'title' => t('CiviCRM Participants'),
    'help' => t("View displays CiviCRM Participants, often in conjunction with Event Filters"),
  );

  //TABLE JOINS FOR CIVICRM PARTICIPANTS GO HERE!
  // display user fields in Participant view
  $data['civicrm_participant']['table']['join']['users'] = array(
    'left_table' => 'civicrm_uf_match',
    'left_field' => 'contact_id',
    'field' => 'contact_id',
  );
  $data['users']['table']['join']['civicrm_participant'] = array(
    'left_table' => 'civicrm_uf_match',
    'left_field' => 'uf_id',
    'field' => 'uid',
  );
  //Display Participants in User Views
  $data['civicrm_uf_match']['table']['join']['civicrm_participant'] = array(
    'left_field' => 'contact_id',
    'field' => 'contact_id',
  );

  //CiviCRM Participants - FIELDS

  //Numeric Participant ID
  $data['civicrm_participant']['id'] = array(
    'title' => t('Participant ID'),
    'help' => t('The numeric ID of the Participant'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  //Numeric Event ID
  $data['civicrm_participant']['event_id'] = array(
    'title' => t('Event ID'),
    'help' => t('The numeric ID of the Event'),
    'relationship' => array(
      'base' => 'civicrm_event',
      'field' => 'event_id',
      'left_field' => 'id',
      'handler' => 'views_handler_relationship',
      'label' => t('CiviCRM Event, with custom fields'),
    ),
  );

  //Numeric Contact ID
  $data['civicrm_participant']['contact_id'] = array(
    'title' => t("Participant's Contact ID"),
    'help' => t('The numeric ID of the Contact of the Participant'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'relationship' => array(
      'base' => 'civicrm_contact',
      'field' => 'contact_id',
      'handler' => 'views_handler_relationship',
      'label' => t('CiviCRM Contact, with custom fields'),
    ),
  );

  //Participant Status
  $data['civicrm_participant']['status'] = array(
    'title' => t('Participant Status'),
    'real field' => 'status_id',
    'help' => t("The Status of the Participant's Booking and Attendance"),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Event_PseudoConstant',
      'pseudo method' => 'participantStatus',
      'pseudo args' => array(NULL, NULL, 'label'),
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'allow empty' => TRUE,
      'pseudo class' => 'CRM_Event_PseudoConstant',
      'pseudo method' => 'participantStatus',
      'pseudo args' => array(NULL, NULL, 'label'),
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //Participant Role
  $data['civicrm_participant']['role'] = array(
    'title' => t('Participant Role'),
    'real field' => 'role_id',
    'help' => t('The Role the Participant is registered for.'),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Event_PseudoConstant',
      'pseudo method' => 'participantRole',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'allow empty' => TRUE,
      'pseudo class' => 'CRM_Event_PseudoConstant',
      'pseudo method' => 'participantRole',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //Source
  $data['civicrm_participant']['source'] = array(
    'title' => t('Source'),
    'help' => t('Where did this registration come from?'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //Participant Registration Date
  $data['civicrm_participant']['register_date'] = array(
    'title' => t('Register Date'),
    'help' => t('The Date the participant Registered for the Event'),
    'field' => array(
      'handler' => 'civicrm_handler_field_datetime',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_datetime',
      'is date' => TRUE,
    ),
    'sort' => array(
      'handler' => 'civicrm_handler_sort_date',
    ),
  );
  civicrm_views_add_date_arguments($data['civicrm_participant'], array(
    'title' => 'Register Date',
      'name' => 'register_date',
    ));

  //BOOLEAN : IS SET TO PAY LATER
  $data['civicrm_participant']['is_pay_later'] = array(
    'title' => t('Is Pay Later'),
    'help' => t('Opted to Pay Later after Registration, and is still pending payment.'),
    'field' => array(
      'handler' => 'views_handler_field_boolean',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // Registered by ID - Participant ID of the person who made the registration
  $data['civicrm_participant']['registered_by_id'] = array(
    'title' => t('Registered by ID'),
    'help' => t('The numeric ID of the Participant who registered this Participant'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'relationship' => array(
      'base' => 'civicrm_participant',
      'field' => 'id',
      'handler' => 'views_handler_relationship',
      'label' => t('CiviCRM Source Record Contact'),
    ),
  );

  //Fee Level
  $data['civicrm_participant']['fee_level'] = array(
    'title' => t('Fee Level'),
    'help' => t('The Fee Level the Participant is registered for.'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // Fee Amount , the amount the Participant has paid or will have to pay.
  $data['civicrm_participant']['fee_amount'] = array(
    'title' => t('Fee Amount'),
    'help' => t('The amount the Participant has paid or will have to pay.'),
    'field' => array(
      'handler' => 'civicrm_handler_field_money',
      'click sortable' => TRUE,
      'currency field' => 'fee_currency',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  // Link to Campaign Table
  if (isset($enabled['CiviCampaign'])) {
    $data['civicrm_participant']['campaign_id'] = array(
      'title' => t('Campaign'),
      'help' => t('Participant -> Campaign'),
      'relationship' => array(
        'base' => 'civicrm_campaign',
        'base field' => 'id',
        'handler' => 'views_handler_relationship',
        'label' => t('Participant -> Campaign'),
      ),
    );
  }

  // Add the Contribution relevant to the Participant.
  $data['civicrm_participant_payment']['table']['group'] = t('Participant Payment (Contributions)');

  $data['civicrm_participant_payment']['table']['base'] = array(
    'field' => 'id',
    'title' => t('Participant Payment (Contributions)'),
    'help' => t("View participant's Contributions (for paid events)."),
  );

  $data['civicrm_participant_payment']['table']['join'] = array(
    'civicrm_participant' => array(
      'left_field' => 'id',
      'field' => 'participant_id',
    ),
  );

  $data['civicrm_participant_payment']['contribution_id'] = array(
    'title' => t('Participant Contribution Id'),
    'help' => t("The numeric ID of the Participant's Contribution (for event fees)."),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'relationship' => array(
      'base' => 'civicrm_contribution',
      'left_field' => 'id',
      'field' => 'contribution_id',
      'handler' => 'views_handler_relationship',
      'label' => t('CiviCRM Participant Payment, with custom fields'),
    ),
  );

  //Event Text Link
  $data['civicrm_event']['text_link'] = array(
    'field' => array(
      'title' => t('Text Link'),
      'help' => t('Creates a Text Link for various event actions.'),
      'handler' => 'civicrm_handler_field_link_event',
    ),
  );

  //Participant Text Link
  $data['civicrm_participant']['text_link'] = array(
    'field' => array(
      'title' => t('Text Link'),
      'help' => t('Creates a Text Link for various participant actions.'),
      'handler' => 'civicrm_handler_field_link_participant',
    ),
  );

  //Add CiviVolunteer if it is installed
  $sql = "SHOW TABLES LIKE 'civicrm_volunteer_project'";
  $result = CRM_Core_DAO::executeQuery($sql);
  if ($result->fetch()) {
    $data['civicrm_volunteer_project']['table']['group'] = t('CiviCRM CiviVolunteer');
    $data['civicrm_volunteer_project']['table']['join'] = array(
      'civicrm_event' => array(
        'left_field' => 'id',
        'field' => 'entity_id',
      ),
    );
    //Add check to see if volunteering is turned on
    $data['civicrm_volunteer_project']['is_active'] = array(
      'title' => t('Is Volunteering On'),
      'help' => t('Is Volunteering enabled for the event?'),
      'field' => array(
        'handler' => 'views_handler_field_boolean',
        'click sortable' => FALSE,
      ),
      'filter' => array(
        'handler' => 'views_handler_filter_boolean_operator',
      ),
      'sort' => array(
        'handler' => 'views_handler_sort',
      ),
    );
  }
}
