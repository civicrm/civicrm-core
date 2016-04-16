<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
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
 * Build the $data array for CiviCase related tables
 * Includes the following tables
 * civicrm_case
 */

function _civicrm_case_data(&$data, $enabled) {

  /**
   *CiviCRM Case Base Table
   */

  $data['civicrm_case']['table']['group'] = t('CiviCRM Cases');

  $data['civicrm_case']['table']['base'] = array(
    // Governs the whole mozilla
    'field' => 'id',
    'title' => t('CiviCRM Cases'),
    'help' => t("View displays CiviCRM Cases Data"),
  );

  //TABLE JOINS FOR CIVICRM CASE GO HERE!

  $data['civicrm_case']['table']['join'] = array(
    // Directly links to contact table.
    'civicrm_case_contact' => array(
      'left_field' => 'case_id',
      'field' => 'id',
    ),
    'civicrm_case_activity' => array(
      'left_field' => 'case_id',
      'field' => 'id',
    ),
    'civicrm_relationship' => array(
      'left_field' => 'case_id',
      'field' => 'id',
    ),
  );

  // Case ID
  $data['civicrm_case']['id'] = array(
    'title' => t('Case ID'),
    'help' => t('The numeric ID of the case'),
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
  );

  // Case Type
  $data['civicrm_case']['case_type'] = array(
    'title' => t('Case Type'),
    'real field' => 'case_type_id',
    'help' => t('The Case Type'),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Case_PseudoConstant',
      'pseudo method' => 'caseType',
      'pseudo args' => array('Title', FALSE),
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'pseudo class' => 'CRM_Case_PseudoConstant',
      'pseudo method' => 'caseType',
      'pseudo args' => array('Title', FALSE),
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
      'pseudo args' => 'caseType',
    ),
  );

  //DISPLAY Name for the Contact (Full Name with Prefixes and Suffixes)
  $data['civicrm_case']['subject'] = array(
    'title' => t('Subject'),
    'help' => t('A short description of the case.'),
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

  //CASE Start DATE
  $data['civicrm_case']['start_date'] = array(
    'title' => t('Start Date'),
    'help' => t('The Case\'s Start Date'),
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

  civicrm_views_add_date_arguments($data['civicrm_case'], array(
    'title' => 'Start Date',
      'name' => 'start_date',
    ));

  //CASE END DATE
  $data['civicrm_case']['end_date'] = array(
    'title' => t('End Date'),
    'help' => t('The Case\'s End Date'),
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

  civicrm_views_add_date_arguments($data['civicrm_case'], array(
    'title' => 'End Date',
      'name' => 'end_date',
    ));

  //Case Details / Notes
  $data['civicrm_case']['details'] = array(
    'title' => t('Details'),
    'help' => t('Details and Notes regarding the case.'),
    'field' => array(
      'handler' => 'civicrm_handler_field_markup',
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

  //Case Status
  $data['civicrm_case']['status'] = array(
    'title' => t('Case Status'),
    'real field' => 'status_id',
    'help' => t('The Status of this case'),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Case_PseudoConstant',
      'pseudo method' => 'caseStatus',
      'pseudo args' => array('Label', FALSE),
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'allow empty' => TRUE,
      'pseudo class' => 'CRM_Case_PseudoConstant',
      'pseudo method' => 'caseStatus',
      'pseudo args' => array('Label', FALSE),
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //Case is deleted
  $data['civicrm_case']['is_deleted'] = array(
    'title' => t('Is Deleted'),
    'help' => t('If the current Case is deleted.'),
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


  //----------------------------------------------------------------
  // CIVICRM Case Activities are here, base tabling it up.
  //----------------------------------------------------------------

  $data['civicrm_case_activity']['table']['group'] = t('CiviCRM Case Activity');

  $data['civicrm_case_activity']['table']['base'] = array(
    // Governs the whole mozilla
    'field' => 'id',
    'title' => t('CiviCRM Case Activity'),
    'help' => t("View displays CiviCRM Case Activities"),
  );

  // Explain how this table joins to others.
  $data['civicrm_case_activity']['table']['join'] = array(
    // Directly links to activity table.
    'civicrm_activity' => array(
      'left_field' => 'id',
      'field' => 'activity_id',
    ),
    'civicrm_case' => array(
      'left_field' => 'id',
      'field' => 'case_id',
    ),
  );

  //CiviCRM Case Activity - FIELDS

  //Numeric Case Activity ID
  $data['civicrm_case_activity']['id'] = array(
    'title' => t('Case Activity ID'),
    'help' => t('The numeric ID of the Case Activity'),
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

  //Numeric Case ID
  $data['civicrm_case_activity']['case_id'] = array(
    'title' => t('Case ID'),
    'help' => t('The numeric ID of the Case'),
    'relationship' => array(
      'base' => 'civicrm_case',
                                                                             'field' => 'case_id',
                                                                             'left_field' => 'id',
      'handler' => 'views_handler_relationship',
      'label' => t('CiviCRM Case, with custom fields'),
    ),
  );

  //Numeric Activity ID
  $data['civicrm_case_activity']['activity_id'] = array(
    'title' => t('Case Activity\'s Activity ID'),
    'help' => t('The numeric ID of the Activity of the Case'),
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
      'base' => 'civicrm_activity',
      'field' => 'activity_id',
      'handler' => 'views_handler_relationship',
      'label' => t('CiviCRM Activity, with custom fields'),
    ),
  );

  //----------------------------------------------------------------
  // CIVICRM Case Contacts are here, base tabling it up.
  //----------------------------------------------------------------

  $data['civicrm_case_contact']['table']['group'] = t('CiviCRM Case Contact');

  $data['civicrm_case_contact']['table']['base'] = array(
    // Governs the whole mozilla
    'field' => 'id',
    'title' => t('CiviCRM Case Contact'),
    'help' => t("View displays CiviCRM Case Contact"),
  );

  // Explain how this table joins to others.
  $data['civicrm_case_contact']['table']['join'] = array(
    // Directly links to case table.
    'civicrm_case' => array(
      'left_field' => 'id',
      'field' => 'case_id',
    ),
  );

  // display user fields in Case Contacts view
  $data['civicrm_case_contact']['table']['join']['users'] = array(
    'left_table' => 'civicrm_uf_match',
    'left_field' => 'contact_id',
    'field' => 'contact_id',
  );
  $data['users']['table']['join']['civicrm_case_contact'] = array(
    'left_table' => 'civicrm_uf_match',
    'left_field' => 'uf_id',
    'field' => 'uid',
  );
  //Display Case Contacts in User Views
  $data['civicrm_uf_match']['table']['join']['civicrm_case_contact'] = array(
    'left_field' => 'contact_id',
    'field' => 'contact_id',
  );

  //CiviCRM Case Contacts - FIELDS

  //Numeric Case Contact ID
  $data['civicrm_case_contact']['id'] = array(
    'title' => t('Case Contact ID'),
    'help' => t('The numeric ID of the Case Contact'),
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

  //Numeric Case ID
  $data['civicrm_case_contact']['case_id'] = array(
    'title' => t('Case ID'),
    'help' => t('The numeric ID of the Case'),
    'relationship' => array(
      'base' => 'civicrm_case',
                                                                             'field' => 'case_id',
                                                                             'left_field' => 'id',
      'handler' => 'views_handler_relationship',
      'label' => t('CiviCRM Case, with custom fields'),
    ),
  );

  //Numeric Contact ID
  $data['civicrm_case_contact']['contact_id'] = array(
    'title' => t('Case Contact\'s Contact ID'),
    'help' => t('The numeric ID of the Contact of the Case'),
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
  //----------------------------------------------------------------
  // CIVICRM Case Type Names are here, base tabling it up.
  //----------------------------------------------------------------

  $data['civicrm_case_type']['table']['group'] = t('CiviCRM Case Type Names');

  $data['civicrm_case_type']['table']['base'] = array(
    // Governs the whole mozilla
    'field' => 'id',
    'title' => t('CiviCRM Case Type Names'),
    'help' => t("View displays CiviCRM Case Type Names"),
  );

  // Explain how this table joins to others.
  $data['civicrm_case_type']['table']['join'] = array(
    // Directly links to case table.
    'civicrm_case' => array(
      'left_field' => 'case_type_id',
      'field' => 'id',
    ),
  );

  //CiviCRM Case Type - FIELDS

  //Case Type Title
  $data['civicrm_case_type']['title'] = array(
    'title' => t('Case Type Name'),
    'real field' => 'title',
    'help' => t('The Name of the Case Type'),
    'field' => array(
    'handler' => 'views_handler_field',
    'click sortable' => TRUE,
    ),
    'argument' => array(
     'handler' => 'views_handler_argument',
    ),
    'sort' => array(
    'handler' => 'views_handler_sort',
    ),
  );
}
