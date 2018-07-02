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
 * Build the $data array for all the filed that are part of any CivicRM install
 * Includes the following tables
 * civicrm_contact
 * civicrm_address
 * civicrm_email
 * civicrm_website
 * civicrm_im
 * civicrm_phone
 * civicrm_group
 * civicrm_tag
 * civicrm_relationship
 * civicrm_activity
 * civicrm_uf_match
 * All Custom fields
 */
function _civicrm_core_data(&$data, $enabled) {
  //------------------------------------------------------
  // CiviCRM Contacts Views Table
  //------------------------------------------------------

  $data['civicrm_contact']['table']['group'] = t('CiviCRM Contacts');

  $data['civicrm_contact']['table']['base'] = array(
    // Governs the whole mozilla
    'field' => 'id',
    'title' => t('CiviCRM Contacts'),
    'help' => t("View displays CiviCRM Contacts, of people, organizations, etc."),
  );

  //TABLE JOINS FOR CIVICRM CONTACTS GO HERE!

  $data['civicrm_contact']['table']['join'] = array(
    // Directly links to participants.
    'civicrm_participant' => array(
      'left_field' => 'contact_id',
      'field' => 'id',
    ),
    // Directly links to contribution
    'civicrm_contribution' => array(
      'left_field' => 'contact_id',
      'field' => 'id',
    ),
    // Directly links to activity.
    'civicrm_activity_contact' => array(
      'left_field' => 'contact_id',
      'field' => 'id',
    ),
    // Directly links to membership.
    'civicrm_membership' => array(
      'left_field' => 'contact_id',
      'field' => 'id',
    ),
    // Link to mailing.
    'civicrm_mailing' => array(
      'left_table' => 'civicrm_mailing_event_queue',
      'left_field' => 'contact_id',
      'field' => 'id',
    ),
    // Link to grant.
    'civicrm_grant' => array(
      'left_field' => 'contact_id',
      'field' => 'id',
    ),
    // makes contact fields available to PCP views.
    'civicrm_pcp' => array(
      'left_field' => 'contact_id',
      'field' => 'id',
    ),
    // Links to case contacts
    'civicrm_case_contact' => array(
      'left_field' => 'contact_id',
      'field' => 'id',
    ),
  );

  // For other base tables, explain how we join -

  $data['civicrm_contact']['table']['join']['users'] = array(
    'left_table' => 'civicrm_uf_match',
    'left_field' => 'contact_id',
    'field' => 'id',
  );
  $data['civicrm_uf_match']['table']['join']['users'] = array(
    'left_field' => 'uid',
    'field' => 'uf_id',
  );

  //CiviCRM Contacts - FIELDS

  //Numeric Contact ID
  $data['civicrm_contact']['id'] = array(
    'title' => t('Contact ID'),
    'help' => t('The numeric ID of the Contact'),
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

  $data['civicrm_contact']['relationship_id_a'] = array(
    'real field' => 'id',
    'title' => t('CiviCRM Relationship (starting from contact A)'),
    'help' => t('Connects a contact (as contact A) to a relationship.'),
    'relationship' => array(
      'base' => 'civicrm_relationship',
      'base field' => 'contact_id_a',
      'handler' => 'civicrm_handler_relationship_relationship',
      'label' => t('CiviCRM Relationship (starting from contact A)'),
    ),
  );

  $data['civicrm_contact']['relationship_id_b'] = array(
    'real field' => 'id',
    'title' => t('CiviCRM Relationship (starting from Contact B)'),
    'help' => t('Connects a contact (as contact B) to a relationship.'),
    'relationship' => array(
      'base' => 'civicrm_relationship',
      'base field' => 'contact_id_b',
      'handler' => 'civicrm_handler_relationship_relationship',
      'label' => t('CiviCRM Relationship (starting from contact B)'),
    ),
  );

  // Drupal ID of the contact (from uf_match.uf_id)
  $data['civicrm_contact']['drupal_id'] = array(
    'real field' => 'id',
    'title' => t('Drupal ID'),
    'help' => t('Relates a CiviCRM Contact to the Drupal User Record'),
    'field' => array(
      'handler' => 'civicrm_handler_field_drupalid',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'relationship' => array(
      'base' => 'users',
      'field' => 'uid',
      'handler' => 'civicrm_handler_relationship_contact2users',
      'label' => t('Drupal User'),
    ),
  );

  //SORT Name for the Contact (Last Name followed by First)
  $data['civicrm_contact']['sort_name'] = array(
    'title' => t('Sort Name'),
    'help' => t('The Contact Name for sorting purposes, Last Name then First'),
    'field' => array(
      'handler' => 'civicrm_handler_field_contact_link',
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

  //Current Employer ID
  $data['civicrm_contact']['employer_id'] = array(
    'title' => t('Current Employer Id'),
    'help' => t('Current Employer Id'),
    'field' => array(
      'handler' => 'civicrm_handler_field_contact_link',
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

  //DISPLAY Name for the Contact (Full Name with Prefixes and Suffixes)
  $data['civicrm_contact']['display_name'] = array(
    'title' => t('Display Name'),
    'help' => t('Full Name of the Contact with prefixes and suffixes'),
    'field' => array(
      'handler' => 'civicrm_handler_field_contact_link',
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

  //LEGAL Name for the Contact
  $data['civicrm_contact']['legal_name'] = array(
    'title' => t('Legal Name'),
    'help' => t('Legal Name of the Contact'),
    'field' => array(
      'handler' => 'civicrm_handler_field_contact_link',
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

  //Gender
  $data['civicrm_contact']['gender'] = array(
    'title' => t('Gender'),
    'real field' => 'gender_id',
    'help' => t("Contact's Gender"),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'dao class' => 'CRM_Contact_BAO_Contact',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'allow empty' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'dao class' => 'CRM_Contact_BAO_Contact',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  $data['civicrm_contact']['preferred_communication_method'] = array(
    // The item it appears as on the UI,
    'title' => t('Preferred communication method'),
    // The help that appears on the UI,
    'real field' => 'preferred_communication_method',
    'help' => t('Prefix'),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'dao class' => 'CRM_Contact_BAO_Contact',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'dao class' => 'CRM_Contact_BAO_Contact',
    ),
  );
  // Prefix
  $data['civicrm_contact']['prefix_id'] = array(
    // The item it appears as on the UI,
    'title' => t('Prefix'),
    // The help that appears on the UI,
    'real field' => 'prefix_id',
    'help' => t('Prefix'),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'dao class' => 'CRM_Contact_BAO_Contact',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'dao class' => 'CRM_Contact_BAO_Contact',
    ),
  );
  // Suffix
  $data['civicrm_contact']['suffix_id'] = array(
    // The item it appears as on the UI,
    'title' => t('Suffix'),
    // The help that appears on the UI,
    'real field' => 'suffix_id',
    'help' => t('Suffix'),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'dao class' => 'CRM_Contact_BAO_Contact',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'dao class' => 'CRM_Contact_BAO_Contact',
    ),
  );

  //FIRST NAME
  $data['civicrm_contact']['first_name'] = array(
    'title' => t('First Name'),
    'help' => t('First Name'),
    'field' => array(
      'handler' => 'civicrm_handler_field_contact_link',
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

  //MIDDLE NAME
  $data['civicrm_contact']['middle_name'] = array(
    'title' => t('Middle Name'),
    'help' => t('Middle Name'),
    'field' => array(
      'handler' => 'civicrm_handler_field_contact_link',
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

  //LAST NAME
  $data['civicrm_contact']['last_name'] = array(
    'title' => t('Last Name'),
    'help' => t('Last Name'),
    'field' => array(
      'handler' => 'civicrm_handler_field_contact_link',
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

  //PREFERRED LANGUAGE
  $data['civicrm_contact']['preferred_language'] = array(
    'title' => t('Preferred Language'),
    'real field' => 'preferred_language',
    'help' => t('Which language is preferred for selection'),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'dao class' => 'CRM_Contact_BAO_Contact',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'allow empty' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'dao class' => 'CRM_Contact_BAO_Contact',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //BOOLEAN : IS Deceased
  $data['civicrm_contact']['is_deceased'] = array(
    'title' => t('Deceased'),
    'help' => t('Is the contact deceased?'),
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

  //THUMBSUP: Contact Permissions ie Do not email, do not this, do not that.

  //BOOLEAN : DO NOT EMAIL
  $data['civicrm_contact']['do_not_email'] = array(
    'title' => t('Do Not E-mail'),
    'help' => t('Does this contact not want to be emailed?'),
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

  //BOOLEAN : DO NOT PHONE
  $data['civicrm_contact']['do_not_phone'] = array(
    'title' => t('Do Not Phone'),
    'help' => t('Does this contact not want to be phoned?'),
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

  //BOOLEAN : DO NOT MAIL
  $data['civicrm_contact']['do_not_mail'] = array(
    'title' => t('Do Not Mail'),
    'help' => t('Does this contact not want to be mailed?'),
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

  //BOOLEAN : DO NOT SMS
  $data['civicrm_contact']['do_not_sms'] = array(
    'title' => t('Do Not Sms'),
    'help' => t('Does this contact not want to receive messages?'),
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

  //BOOLEAN : DO NOT TRADE
  $data['civicrm_contact']['do_not_trade'] = array(
    'title' => t('Do Not Trade'),
    'help' => t('Does this contact not want trade?'),
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

  //BOOLEAN : Opted out of Bulk Mailing
  $data['civicrm_contact']['is_opt_out'] = array(
    'title' => t('Opted Out of Bulk Mail'),
    'help' => t('Has this contact opted out of bulk mail?'),
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

  //BOOLEAN : Is Deleted
  $data['civicrm_contact']['is_deleted'] = array(
    'title' => t('Is Deleted'),
    'help' => t('Has this contact been deleted?'),
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

  // Organization Name
  $data['civicrm_contact']['current_employer'] = array(
    // The item it appears as on the UI,
    'title' => t('Organization Name'),
    'real field' => 'organization_name',
    // The help that appears on the UI,
    'help' => t("The display name of the contact's organizations."),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );

  // contact_type
  $data['civicrm_contact']['contact_type'] = array(
    // The item it appears as on the UI,
    'title' => t('Contact Type'),
    // The help that appears on the UI,
    'help' => t('Contact Type'),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Contact_BAO_ContactType',
      'pseudo method' => 'basicTypePairs',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'pseudo class' => 'CRM_Contact_BAO_ContactType',
      'pseudo method' => 'basicTypePairs',
    ),
  );

  //Contact Sub Type
  $data['civicrm_contact']['contact_sub_type'] = array(
    'title' => t('Contact Sub Type'),
    'help' => t('The Sub-type for the Contact'),
    'field' => array(
      'handler' => 'civicrm_handler_field',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Contact_BAO_ContactType',
      'pseudo method' => 'subTypePairs',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_custom_option',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //Birth Date
  $data['civicrm_contact']['birth_date'] = array(
    'title' => t('Birth Date'),
    'help' => t("The contact's birth date."),
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

  //Deceased Date
  $data['civicrm_contact']['deceased_date'] = array(
    'title' => t('Deceased Date'),
    'help' => t("The contact's deceased date."),
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

  // Last modified date
  $data['civicrm_contact']['modified_date'] = array(
    'title' => t('Modified date'),
    'help' => t("The date the contact's record was last modified."),
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
  // Image URL
  $data['civicrm_contact']['image_URL'] = array(
    'title' => t('Contact Image'),
    'help' => t('The image associated with the contact.'),
    'field' => array(
      'handler' => 'civicrm_handler_field_contact_image',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // add the other generic fields
  $objType = 'Individual';
  $fields = CRM_Contact_BAO_Contact::exportableFields($objType);

  $skipFields = array(
    'contact_is_deleted' => 1,
    'current_employer_id' => 1,
    'group' => 1,
    'note' => 1,
    'tag' => 1,
  );
  civicrm_views_add_fields($fields, $data['civicrm_contact'], 'civicrm_contact', $skipFields);

  // CHILLING OUT AT THE CIVICRM_ADDRESS TABLE

  $data['civicrm_address']['table']['group'] = t('CiviCRM Address');

  // Explain how this table joins to others.
  $data['civicrm_address']['table']['join'] = array(
    // Directly links to contact table.
    'civicrm_contact' => array(
      'left_field' => 'id',
      'field' => 'contact_id',
    ),
  );
  //CiviCRM Address
  $data['civicrm_address']['table']['join']['civicrm_address'] = array(
    'left_field' => 'id',
    'field' => 'id',
  );

  //CiviCRM Address Using Contacts as a pitstop before going on to Participants
  $data['civicrm_address']['table']['join']['civicrm_participant'] = array(
    'left_table' => 'civicrm_contact',
    'left_field' => 'id',
    'field' => 'contact_id',
  );

  //CiviCRM Address Using Contacts as a pitstop before going on to Contributions
  $data['civicrm_address']['table']['join']['civicrm_contribution'] = array(
    'left_table' => 'civicrm_contact',
    'left_field' => 'id',
    'field' => 'contact_id',
  );

  //CiviCRM Address Using Contacts as a pitstop before going on to Activities
  $data['civicrm_address']['table']['join']['civicrm_activity'] = array(
    'left_table' => 'civicrm_contact',
    'left_field' => 'id',
    'field' => 'contact_id',
  );

  //CiviCRM Address Using Contacts as a pitstop before going on to PCP (makes address fields available to PCP views)
  $data['civicrm_address']['table']['join']['civicrm_pcp'] = array(
    'left_table' => 'civicrm_contact',
    'left_field' => 'id',
    'field' => 'contact_id',
  );

  //CiviCRM Address Using Locations as a pitstop before going on to Events
  $data['civicrm_address']['table']['join']['civicrm_event'] = array(
    'left_table' => 'civicrm_loc_block',
    'left_field' => 'address_id',
    'field' => 'id',
  );
  //CiviCRM Address Using Contacts as a pitstop before going on to Memberships
  $data['civicrm_address']['table']['join']['civicrm_membership'] = array(
    'left_table' => 'civicrm_contact',
    'left_field' => 'id',
    'field' => 'contact_id',
  );

  $data['civicrm_address']['table']['join']['users'] = array(
    'left_table' => 'civicrm_uf_match',
    'left_field' => 'contact_id',
    'field' => 'contact_id',
  );

  // ADDRESS ID
  $data['civicrm_address']['id'] = array(
    'title' => t('Address'),
    'help' => t('Contact Address'),
    'relationship' => array(
      'base' => 'civicrm_contact',
      'field' => 'id',
      'relationship table' => 'civicrm_address',
      'relationship field' => 'contact_id',
      'handler' => 'civicrm_handler_relationship_address',
      'label' => t('CiviCRM Address'),
    ),
  );

  //BOOLEAN : IS PRIMARY ADDRESS
  $data['civicrm_address']['is_primary'] = array(
    'title' => t('Is Primary Address'),
    'help' => t("Is this address the contact's primary address?"),
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

  //BOOLEAN : IS BILLING ADDRESS
  $data['civicrm_address']['is_billing'] = array(
    'title' => t('Is Billing Address'),
    'help' => t("Is this address the contact's billing address?"),
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

  //ADDRESS'S LOCATION TYPE
  $data['civicrm_address']['location_type'] = array(
    'title' => t('Location Type'),
    'real field' => 'location_type_id',
    'help' => t('The Location of the Address for the Contact'),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'dao class' => 'CRM_Core_DAO_Address',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'dao class' => 'CRM_Core_DAO_Address',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //FULL STREET ADDRESS
  $data['civicrm_address']['street_address'] = array(
    'title' => t('Full Street Address'),
    'help' => t('Full Street Address without Country, State or Postcode'),
    'field' => array(
      'handler' => 'civicrm_handler_field_address',
      'click sortable' => TRUE,
      'dao class' => 'CRM_Core_DAO_Address',
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

  //FULL STREET ADDRESS SUPPLEMENTAL 1
  $data['civicrm_address']['supplemental_address_1'] = array(
    'title' => t('Supplemental Street Address'),
    'help' => t('Supplemental Street Address such as Apartment or Suite Number or Department'),
    'field' => array(
      'handler' => 'civicrm_handler_field_address',
      'click sortable' => TRUE,
      'dao class' => 'CRM_Core_DAO_Address',
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

  //FULL STREET ADDRESS SUPPLEMENTAL 2
  $data['civicrm_address']['supplemental_address_2'] = array(
    'title' => t('2nd Supplemental Street Address'),
    'help' => t('2nd Supplemental Street Address such as Apartment or Suite Number or Department'),
    'field' => array(
      'handler' => 'civicrm_handler_field_address',
      'click sortable' => TRUE,
      'dao class' => 'CRM_Core_DAO_Address',
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

  //CITY OR SUBURB THE ADDRESS IS AT
  $data['civicrm_address']['city'] = array(
    'title' => t('City / Suburb'),
    'help' => t('The City or Suburb the address is at'),
    'field' => array(
      'handler' => 'civicrm_handler_field_address',
      'click sortable' => TRUE,
      'dao class' => 'CRM_Core_DAO_Address',
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

  //STATE OR PROVINCE
  $data['civicrm_address']['state_province'] = array(
    'title' => t('State/Province'),
    'real field' => 'state_province_id',
    'help' => t('The State or Province of the Address'),
    'field' => array(
      'handler' => 'civicrm_handler_field_state',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_state_multi',
      'allow empty' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'stateProvince',
    ),
    'sort' => array(
      'join_table' => 'civicrm_state_province',
      'join_left_field' => 'state_province_id',
      'join_field' => 'id',
      'handler' => 'civicrm_handler_sort_address_pseudoconstant',
    ),
  );

  //ZIP / POSTAL CODE
  $data['civicrm_address']['postal_code'] = array(
    'title' => t('Postal Code'),
    'help' => t('ZIP / Postal Code of the Address'),
    'field' => array(
      'handler' => 'civicrm_handler_field_address',
      'click sortable' => TRUE,
      'dao class' => 'CRM_Core_DAO_Address',
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

  //Latitude or Geo Code 1
  $data['civicrm_address']['geo_code_1'] = array(
    'title' => t('Latitude'),
    'help' => t('Latitude of the Address for mapping'),
    'field' => array(
      'handler' => 'civicrm_handler_field_address',
      'click sortable' => TRUE,
      'dao class' => 'CRM_Core_DAO_Address',
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

  //Longitude or Geo Code 2
  $data['civicrm_address']['geo_code_2'] = array(
    'title' => t('Longitude'),
    'help' => t('Longitude of the Address for mapping'),
    'field' => array(
      'handler' => 'civicrm_handler_field_address',
      'click sortable' => TRUE,
      'dao class' => 'CRM_Core_DAO_Address',
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

  //COUNTRY
  $data['civicrm_address']['country'] = array(
    'title' => t('Country'),
    'real field' => 'country_id',
    'help' => t('The Country of the Address'),
    'field' => array(
      'handler' => 'civicrm_handler_field_country',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'country',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_country_multi',
      'allow empty' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'country',
    ),
    'sort' => array(
      'join_table' => 'civicrm_country',
      'join_left_field' => 'country_id',
      'join_field' => 'id',
      'handler' => 'civicrm_handler_sort_address_pseudoconstant',
    ),
  );

  //COUNTY
  $data['civicrm_address']['county'] = array(
    'title' => t('County'),
    'real field' => 'county_id',
    'help' => t('The County of the Address'),
    'field' => array(
      'handler' => 'civicrm_handler_field_county',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'county',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'allow empty' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'county',
    ),
    'sort' => array(
      'join_table' => 'civicrm_county',
      'join_left_field' => 'county_id',
      'join_field' => 'id',
      'handler' => 'civicrm_handler_sort_address_pseudoconstant',
    ),
  );

  //Address name
  $data['civicrm_address']['name'] = array(
    'title' => t('Address Name'),
    'help' => t('Be sure addres name is enabled in CiviCRM'),
    'field' => array(
      'handler' => 'civicrm_handler_field_address',
      'click sortable' => TRUE,
      'dao class' => 'CRM_Core_DAO_Address',
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

  $fields = CRM_Core_DAO_Address::fields();

  civicrm_views_add_fields($fields, $data['civicrm_address'], 'civicrm_address');

  // CIVICRM_NOTE table
  $data['civicrm_note']['table']['group'] = t('CiviCRM Note');
  $data['civicrm_note']['table']['join'] = array();

  foreach (array_keys(CRM_Core_BAO_Note::entityTables()) as $entityTable) {
    // Explain how this table joins to others.
    $data['civicrm_note']['table']['join'][$entityTable] = array(
      'left_field' => 'id',
      'field' => 'entity_id',
      'extra' => array(
        array(
          'field' => 'entity_table',
          'value' => $entityTable,
          'numeric' => FALSE,
        ),
      ),
    );
  }

  // NOTE TEXT
  $data['civicrm_note']['note'] = array(
    'title' => t('Note'),
    'help' => t('Note'),
    'field' => array(
      'handler' => 'civicrm_handler_field_markup',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // CHILLING OUT AT THE CIVICRM_EMAIL TABLE

  $data['civicrm_email']['table']['group'] = t('CiviCRM Email');

  // Explain how this table joins to others.
  $data['civicrm_email']['table']['join'] = array(
    // Directly links to contact table.
    'civicrm_contact' => array(
      'left_field' => 'id',
      'field' => 'contact_id',
    ),
  );

  //CiviCRM Email
  $data['civicrm_email']['table']['join']['civicrm_email'] = array(
    'left_field' => 'id',
    'field' => 'id',
  );

  //CiviCRM Email Using Contacts as a pitstop before going on to Participants
  $data['civicrm_email']['table']['join']['civicrm_participant'] = array(
    'left_table' => 'civicrm_contact',
    'left_field' => 'id',
    'field' => 'contact_id',
  );

  //CiviCRM Email Using Contacts as a pitstop before going on to Contributions
  $data['civicrm_email']['table']['join']['civicrm_contribution'] = array(
    'left_table' => 'civicrm_contact',
    'left_field' => 'id',
    'field' => 'contact_id',
  );

  //CiviCRM Email Using Contacts as a pitstop before going on to Activities
  $data['civicrm_email']['table']['join']['civicrm_activity'] = array(
    'left_table' => 'civicrm_contact',
    'left_field' => 'id',
    'field' => 'contact_id',
  );

  //CiviCRM Email Using Locations as a pitstop before going on to Events
  $data['civicrm_email']['table']['join']['civicrm_event'] = array(
    'left_table' => 'civicrm_loc_block',
    'left_field' => 'email_id',
    'field' => 'id',
  );
  //CiviCRM Email Using Contacts as a pitstop before going on to Memberhsips
  $data['civicrm_email']['table']['join']['civicrm_membership'] = array(
    'left_table' => 'civicrm_contact',
    'left_field' => 'id',
    'field' => 'contact_id',
  );

  //Display E-mails in User Views
  $data['civicrm_email']['table']['join']['users'] = array(
    'left_table' => 'civicrm_uf_match',
    'left_field' => 'contact_id',
    'field' => 'contact_id',
  );

  // EMAIL ADDRESS ID
  $data['civicrm_email']['id'] = array(
    'title' => t('Email Address'),
    'help' => t('Contact Email Address'),
    'relationship' => array(
      'base' => 'civicrm_contact',
      'field' => 'id',
      'relationship table' => 'civicrm_email',
      'relationship field' => 'contact_id',
      'handler' => 'civicrm_handler_relationship_mail',
      'label' => t('CiviCRM Email'),
    ),
  );

  //BOOLEAN : IS PRIMARY EMAIL
  $data['civicrm_email']['is_primary'] = array(
    'title' => t('Is Primary Email'),
    'help' => t("Is this address the contact's primary email?"),
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
      'handler' => 'civicrm_handler_sort_mail',
    ),
  );

  //BOOLEAN : IS BILLING EMAIL
  $data['civicrm_email']['is_billing'] = array(
    'title' => t('Is Billing Email'),
    'help' => t("Is this address the contact's billing email?"),
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

  //EMAIL'S LOCATION TYPE
  $data['civicrm_email']['location_type'] = array(
    'title' => t('Location Type'),
    'real field' => 'location_type_id',
    'help' => t('The Location of the Address for the Contact'),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'dao class' => 'CRM_Core_DAO_Email',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'allow empty' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'dao class' => 'CRM_Core_DAO_Email',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //FULL EMAIL ADDRESS
  $data['civicrm_email']['email'] = array(
    'title' => t('Email Address'),
    'help' => t('Email Address'),
    'field' => array(
      'handler' => 'civicrm_handler_field_email',
      'click sortable' => TRUE,
      'dao class' => 'CRM_Core_DAO_Email',
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

  // CHILLING OUT AT THE CIVICRM_PHONE TABLE

  $data['civicrm_phone']['table']['group'] = t('CiviCRM Phone Details');

  // Explain how this table joins to others.
  $data['civicrm_phone']['table']['join'] = array(
    // Directly links to contact table.
    'civicrm_contact' => array(
      'left_field' => 'id',
      'field' => 'contact_id',
    ),
  );

  //CiviCRM Phone
  $data['civicrm_phone']['table']['join']['civicrm_phone'] = array(
    'left_field' => 'id',
    'field' => 'id',
  );

  //CiviCRM Phone Using Contacts as a pitstop before going on to Participants
  $data['civicrm_phone']['table']['join']['civicrm_participant'] = array(
    'left_table' => 'civicrm_contact',
    'left_field' => 'id',
    'field' => 'contact_id',
  );

  //CiviCRM Phone Using Contacts as a pitstop before going on to Contributions
  $data['civicrm_phone']['table']['join']['civicrm_contribution'] = array(
    'left_table' => 'civicrm_contact',
    'left_field' => 'id',
    'field' => 'contact_id',
  );

  //CiviCRM Phone Using Contacts as a pitstop before going on to Activities
  $data['civicrm_phone']['table']['join']['civicrm_activity'] = array(
    'left_table' => 'civicrm_contact',
    'left_field' => 'id',
    'field' => 'contact_id',
  );

  //CiviCRM Phone Using Locations as a pitstop before going on to Events
  $data['civicrm_phone']['table']['join']['civicrm_event'] = array(
    'left_table' => 'civicrm_loc_block',
    'left_field' => 'phone_id',
    'field' => 'id',
  );
  //CiviCRM Phone Using Contacts as a pitstop before going on to Memberhsips
  $data['civicrm_phone']['table']['join']['civicrm_membership'] = array(
    'left_table' => 'civicrm_contact',
    'left_field' => 'id',
    'field' => 'contact_id',
  );

  //Display Phone Details in User Views
  $data['civicrm_phone']['table']['join']['users'] = array(
    'left_table' => 'civicrm_uf_match',
    'left_field' => 'contact_id',
    'field' => 'contact_id',
  );

  //BOOLEAN : IS PRIMARY EMAIL
  $data['civicrm_phone']['is_primary'] = array(
    'title' => t('Is Primary Phone'),
    'help' => t("Is this the contact's primary phone?"),
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

  //BOOLEAN : IS BILLING EMAIL
  $data['civicrm_phone']['is_billing'] = array(
    'title' => t('Is Billing Phone'),
    'help' => t("Is this the contact's billing phone?"),
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

  //PHONE'S LOCATION TYPE
  $data['civicrm_phone']['location_type'] = array(
    'title' => t('Location Type'),
    'real field' => 'location_type_id',
    'help' => t('The Location of the Phone for the Contact'),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'dao class' => 'CRM_Core_DAO_Phone',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'allow empty' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'dao class' => 'CRM_Core_DAO_Phone',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //FULL PHONE NUMBER
  $data['civicrm_phone']['phone'] = array(
    'title' => t('Phone'),
    'help' => t('Full Phone Number'),
    'field' => array(
      'handler' => 'civicrm_handler_field_phone',
      'click sortable' => TRUE,
      'dao class' => 'CRM_Core_DAO_Phone',
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

  // PHONE ID
  $data['civicrm_phone']['id'] = array(
    'title' => t('Phone'),
    'help' => t('Contact Phone Number'),
    'relationship' => array(
      'base' => 'civicrm_contact',
      'field' => 'id',
      'relationship table' => 'civicrm_phone',
      'relationship field' => 'contact_id',
      'handler' => 'civicrm_handler_relationship_phone',
      'label' => t('CiviCRM Phone'),
    ),
  );

  $data['civicrm_phone']['phone_ext'] = array(
    'title' => t('Phone Number Extension'),
    'help' => t('Extension for Phone Number'),
    'field' => array(
      'handler' => 'civicrm_handler_field_phone',
      'click sortable' => TRUE,
      'dao class' => 'CRM_Core_DAO_Phone',
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

  //CONTACT ID contact this number belongs to
  $data['civicrm_phone']['contact_id'] = array(
    'title' => t('Contact ID'),
    'help' => t('Contact phone number belongs to.'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'relationship' => array(
      'base' => 'civicrm_contact',
      'base field' => 'id',
      'handler' => 'views_handler_relationship',
      'label' => t('Related Phone'),
    ),
  );

  //TODO: Mobile Provider

  //PHONE TYPE  , Phone, Mobile, Fax or Pager
  $data['civicrm_phone']['phone_type'] = array(
    'title' => t('Phone Type'),
    'real field' => 'phone_type_id',
    'help' => t('The Type, either Phone, Mobile, Fax or Pager'),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'dao class' => 'CRM_Core_DAO_Phone',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'allow empty' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'dao class' => 'CRM_Core_DAO_Phone',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // CHILLING OUT AT THE CIVICRM_IM TABLE

  $data['civicrm_im']['table']['group'] = t('CiviCRM Instant Messaging');

  // Explain how this table joins to others.
  $data['civicrm_im']['table']['join'] = array(
    // Directly links to contact table.
    'civicrm_contact' => array(
      'left_field' => 'id',
      'field' => 'contact_id',
    ),
  );

  //CiviCRM Im
  $data['civicrm_im']['table']['join']['civicrm_im'] = array(
    'left_field' => 'id',
    'field' => 'id',
  );

  //CiviCRM Im Using Contacts as a pitstop before going on to Participants
  $data['civicrm_im']['table']['join']['civicrm_participant'] = array(
    'left_table' => 'civicrm_contact',
    'left_field' => 'id',
    'field' => 'contact_id',
  );

  //CiviCRM Im Using Contacts as a pitstop before going on to Contributions
  $data['civicrm_im']['table']['join']['civicrm_contribution'] = array(
    'left_table' => 'civicrm_contact',
    'left_field' => 'id',
    'field' => 'contact_id',
  );

  //CiviCRM Im Using Contacts as a pitstop before going on to Activities
  $data['civicrm_im']['table']['join']['civicrm_activity'] = array(
    'left_table' => 'civicrm_contact',
    'left_field' => 'id',
    'field' => 'contact_id',
  );

  //CiviCRM Im Using Locations as a pitstop before going on to Events
  $data['civicrm_im']['table']['join']['civicrm_event'] = array(
    'left_table' => 'civicrm_loc_block',
    'left_field' => 'im_id',
    'field' => 'id',
  );
  //CiviCRM Im Using Contacts as a pitstop before going on to Memberhsips
  $data['civicrm_im']['table']['join']['civicrm_membership'] = array(
    'left_table' => 'civicrm_contact',
    'left_field' => 'id',
    'field' => 'contact_id',
  );

  //Display Im Details in User Views
  $data['civicrm_im']['table']['join']['users'] = array(
    'left_table' => 'civicrm_uf_match',
    'left_field' => 'contact_id',
    'field' => 'contact_id',
  );

  //BOOLEAN : IS PRIMARY IM
  $data['civicrm_im']['is_primary'] = array(
    'title' => t('Is Primary Instant Messaging'),
    'help' => t("Is this the contact's primary Instant Messaging?"),
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

  //BOOLEAN : IS BILLING IM
  $data['civicrm_im']['is_billing'] = array(
    'title' => t('Is Billing Instant Messaging'),
    'help' => t("Is this the contact's billing Instant Messaging?"),
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

  //IM'S LOCATION TYPE
  $data['civicrm_im']['location_type'] = array(
    'title' => t('Location Type'),
    'real field' => 'location_type_id',
    'help' => t('The Location of the Instant Messaging for the Contact'),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'dao class' => 'CRM_Core_DAO_IM',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'allow empty' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'dao class' => 'CRM_Core_DAO_IM',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //NAME
  $data['civicrm_im']['name'] = array(
    'title' => t('Name'),
    'help' => t('IM screen name'),
    'field' => array(
      'handler' => 'civicrm_handler_field_im',
      'click sortable' => TRUE,
    ),
  );

  // IM ID
  $data['civicrm_im']['id'] = array(
    'title' => t('Instant Messaging'),
    'help' => t('Contact Instant Messaging'),
    'relationship' => array(
      'base' => 'civicrm_contact',
      'field' => 'id',
      'relationship table' => 'civicrm_im',
      'relationship field' => 'contact_id',
      'handler' => 'civicrm_handler_relationship_im',
      'label' => t('CiviCRM Instant Messaging'),
    ),
  );

  //CONTACT ID contact this IM belongs to
  $data['civicrm_im']['contact_id'] = array(
    'title' => t('Contact ID'),
    'help' => t('Contact Instant Messaging belongs to.'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'relationship' => array(
      'base' => 'civicrm_contact',
      'base field' => 'id',
      'handler' => 'views_handler_relationship',
      'label' => t('Related Instant Messaging'),
    ),
  );

  //IM Provider
  $data['civicrm_im']['provider'] = array(
    'title' => t('IM Provider'),
    'real field' => 'provider_id',
    'help' => t('Which IM Provider does this screen name belong to'),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'dao class' => 'CRM_Core_DAO_IM',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'allow empty' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'dao class' => 'CRM_Core_DAO_IM',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // CHILLING OUT AT THE CIVICRM_WEBSITE TABLE

  $data['civicrm_website']['table']['group'] = t('CiviCRM Website');

  // Explain how this table joins to others.
  $data['civicrm_website']['table']['join'] = array(
    // Directly links to contact table.
    'civicrm_contact' => array(
      'left_field' => 'id',
      'field' => 'contact_id',
    ),
  );

  //CiviCRM Website
  $data['civicrm_website']['table']['join']['civicrm_website'] = array(
    'left_field' => 'id',
    'field' => 'id',
  );

  //CiviCRM Website Using Contacts as a pitstop before going on to Participants
  $data['civicrm_website']['table']['join']['civicrm_participant'] = array(
    'left_table' => 'civicrm_contact',
    'left_field' => 'id',
    'field' => 'contact_id',
  );

  //CiviCRM Website Using Contacts as a pitstop before going on to Contributions
  $data['civicrm_website']['table']['join']['civicrm_contribution'] = array(
    'left_table' => 'civicrm_contact',
    'left_field' => 'id',
    'field' => 'contact_id',
  );

  //CiviCRM Website Using Contacts as a pitstop before going on to Activities
  $data['civicrm_website']['table']['join']['civicrm_activity'] = array(
    'left_table' => 'civicrm_contact',
    'left_field' => 'id',
    'field' => 'contact_id',
  );

  //CiviCRM Website Using Contacts as a pitstop before going on to Memberhsips
  $data['civicrm_website']['table']['join']['civicrm_membership'] = array(
    'left_table' => 'civicrm_contact',
    'left_field' => 'id',
    'field' => 'contact_id',
  );

  //Display Websites in User Views
  $data['civicrm_website']['table']['join']['users'] = array(
    'left_table' => 'civicrm_uf_match',
    'left_field' => 'contact_id',
    'field' => 'contact_id',
  );

  //WEBSITE TYPE
  $data['civicrm_website']['website_type'] = array(
    'title' => t('Website Type'),
    'real field' => 'website_type_id',
    'help' => t('The type of website for the contact'),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'dao class' => 'CRM_Core_DAO_Website',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'allow empty' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'dao class' => 'CRM_Core_DAO_Website',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //FULL WEB ADDRESS
  $data['civicrm_website']['url'] = array(
    'title' => t('Website URL'),
    'help' => t('Website URL'),
    'field' => array(
      'handler' => 'civicrm_handler_field_website',
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
    'relationship' => array(
      'base' => 'civicrm_contact',
      'base field' => 'id',
      'handler' => 'civicrm_handler_relationship_website',
      'label' => t('Related Website'),
    ),
  );
  // CIVICRM Activities are here with all the activeness they can muster, base tabling it up.
  //----------------------------------------------------------------------------------------

  $data['civicrm_activity']['table']['group'] = t('CiviCRM Activities');

  $data['civicrm_activity']['table']['base'] = array(
    // Governs the whole mozilla
    'field' => 'id',
    'title' => t('CiviCRM Activities'),
    'help' => t("View displays CiviCRM Activities"),
  );

  //TABLE JOINS FOR CIVICRM ACTIVITIES GO HERE!

  //CiviCRM Activities - FIELDS

  //Numeric Activity ID
  $data['civicrm_activity']['id'] = array(
    'title' => t('Activity ID'),
    'help' => t('The numeric ID of the Activity Instance'),
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

  $data['civicrm_activity']['source_record_id'] = array(
    'title' => t('Record Source'),
    'real field' => 'source_record_id',
    'help' => t('Source record activity'),
  );

  /*
   * This block is commented out for two reasons:
   *  1) The help text is misleading to users; source_record_id is NOT
   *     "the contact who made this activity."
   *  2) It doesn't work; the code attempts to join on the wrong table.
   *
   * TODO: Figure out how to tell Views to join to a table whose identity
   *       isn't known yet. Because the details of the "parent" activity
   *       reside in different tables depending on the value of
   *       activity_type_id, Views needs to be told which table to
   *       join to on the fly.
   *
   *  $data['civicrm_activity']['source_record_id'] = array(
   *    'title' => t('Source Record ID'),
   *    // 'help' => t('The contact who made this activity'),
   *    'help' => t('The parent transaction (e.g. Contribution, Event Registration) if it is not an Activity.'),
   *    'relationship' => array(
   *      'base' => 'civicrm_contact',
   *      'field' => 'id',
   *      'handler' => 'views_handler_relationship',
   *      'label' => t('CiviCRM Source Record Contact'),
   *    ),
   *  );
   */

  //Activity Type
  $data['civicrm_activity']['activity_type'] = array(
    'title' => t('Activity Type'),
    'real field' => 'activity_type_id',
    'help' => t('The Type of Activity'),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'activityType',
      'pseudo args' => array(TRUE, TRUE), // Include cases (if CiviCase is enabled)
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'allow empty' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'activityType',
      'pseudo args' => array(TRUE, TRUE),
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //Activity Status
  $data['civicrm_activity']['status'] = array(
    'title' => t('Activity Status'),
    'real field' => 'status_id',
    'help' => t('The Status of this Activity'),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'activityStatus',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'allow empty' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'activityStatus',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //Activity Priority
  $data['civicrm_activity']['priority'] = array(
    'title' => t('Priority'),
    'real field' => 'priority_id',
    'help' => t('The Priority of this Activity'),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'dao class' => 'CRM_Activity_DAO_Activity',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'allow empty' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'dao class' => 'CRM_Activity_DAO_Activity',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  //Parent ID
  $data['civicrm_activity']['parent_id'] = array(
    'title' => t('Parent Activity ID'),
    'help' => t('The numeric ID of the Parent Activity.'),
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

  // Original ID
  $data['civicrm_activity']['original_id'] = array(
    'title' => t('Original Activity ID'),
    'help' => t('The numeric ID of the Original Activity in a versioned chain.'),
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

  //Activity Subject
  $data['civicrm_activity']['subject'] = array(
    'title' => t('Subject'),
    'help' => t('A short description of the activity..'),
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

  //Scheduled Activity Date
  $data['civicrm_activity']['activity_date_time'] = array(
    'title' => t('Scheduled Activity Date'),
    'help' => t('The Date and Time the activity is scheduled to happen.'),
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
  civicrm_views_add_date_arguments($data['civicrm_activity'], array(
    'title' => 'Scheduled Activity Date',
    'name' => 'activity_date_time',
  ));

  //Created Activity Date
  $data['civicrm_activity']['created_date'] = array(
    'title' => t('Created Date'),
    'help' => t('The Date and Time the activity was created.'),
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
  civicrm_views_add_date_arguments($data['civicrm_activity'], array(
    'title' => 'Created Date',
    'name' => 'created_date',
  ));

  //Modified Activity Date
  $data['civicrm_activity']['modified_date'] = array(
    'title' => t('Modified Date'),
    'help' => t('The Date and Time the activity was modified.'),
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
  civicrm_views_add_date_arguments($data['civicrm_activity'], array(
    'title' => 'Modified Date',
    'name' => 'modified_date',
  ));

  //Activity Duration
  $data['civicrm_activity']['duration'] = array(
    'title' => t('Duration (min)'),
    'help' => t("The Activity's Duration in Minutes"),
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

  //Activity Location
  $data['civicrm_activity']['location'] = array(
    'title' => t('Location'),
    'help' => t("Activity's Location in a text string."),
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

  //Activity Details / Notes
  $data['civicrm_activity']['details'] = array(
    'title' => t('Details'),
    'help' => t('Details and Notes regarding the activity.'),
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

  //BOOLEAN : IS A TEST ACTIVITY
  $data['civicrm_activity']['is_test'] = array(
    'title' => t('Is Test'),
    'help' => t('Is the activity a test entry?'),
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
  //Activity Medium (ie Phone, e-mail etc...)
  $data['civicrm_activity']['medium'] = array(
    'title' => t('Medium'),
    'real field' => 'medium_id',
    'help' => t('The Medium of this Activity (ie phone, e-mail, etc...'),
    'field' => array(
      'handler' => 'civicrm_handler_field_encounter_medium',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_encounter_medium',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  //BOOLEAN : IS AUTO ACTIVITY
  $data['civicrm_activity']['is_auto'] = array(
    'title' => t('Is Automaticaly created'),
    'help' => t('Is the activity created by the system?'),
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
  // Result
  // TODO: Add in Filter handler that can be narrowed down to spefics record_source_id and Activity Type
  $data['civicrm_activity']['result'] = array(
    'title' => t('Result'),
    'help' => t('Survey or Petition Result'),
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
  // Link to Campaign Table
  if (isset($enabled['CiviCampaign'])) {
    $data['civicrm_activity']['campaign_id'] = array(
      'title' => t('Campaign'),
      'help' => t('Activity -> Campaign'),
      'relationship' => array(
        'base' => 'civicrm_campaign',
        'base field' => 'id',
        'handler' => 'views_handler_relationship',
        'label' => t('Activity -> Campaign'),
      ),
    );
  }
  $data['civicrm_activity']['engagement_level'] = array(
    'title' => t('Engagement level'),
    'help' => t('The Engagement Level of this Activity for this contact'),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Campaign_PseudoConstant',
      'pseudo method' => 'engagementLevel',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'allow empty' => TRUE,
      'pseudo class' => 'CRM_Campaign_PseudoConstant',
      'pseudo method' => 'engagementLevel',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // CRM-11488
  $data['civicrm_activity']['is_current_revision'] = array(
    'title' => t('Current Revision'),
    'help' => t('Use to dedupe Civicase revisions'),
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
  $data['civicrm_activity']['is_deleted'] = array(
    'title' => t('Is Deleted'),
    'help' => t('If the current Activity is deleted. Also used to dedupe Civicase revisions'),
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

  // let's add activity contact table
  $data['civicrm_activity_contact']['table']['group'] = t('CiviCRM Activity Contacts');

  $data['civicrm_activity_contact']['table']['base'] = array(
    'field' => 'id',
    'title' => t('CiviCRM Activity Contacts'),
    'help' => t("View displays CiviCRM Activity, with connection to contacts."),
  );

  // Explain how this table joins to others.
  $data['civicrm_activity_contact']['table']['join'] = array(
    // Directly links to activity table
    'civicrm_activity' => array(
      'left_field' => 'id',
      'field' => 'activity_id',
    ),
  );

  $data['civicrm_activity_contact']['contact_id'] = array(
    'title' => t('Activity Contact ID'),
    'real field' => 'contact_id',
    'help' => t('The contact related to the activity.'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => FALSE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'relationship' => array(
      'base' => 'civicrm_contact',
      'base field' => 'id',
      'handler' => 'civicrm_handler_field_activity',
      'label' => t('CiviCRM Activity Contact ID'),
      'relationship table' => 'civicrm_activity',
      'relationship field' => 'activity_id',
      'other_field' => 'contact_id',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
  );

  $data['civicrm_activity_contact']['record_type'] = array(
    'title' => t('Record Type'),
    'real field' => 'record_type_id',
    'help' => t('Record type'),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'dao class' => 'CRM_Activity_DAO_ActivityContact',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'dao class' => 'CRM_Activity_DAO_ActivityContact',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  //Activity Text Link
  $data['civicrm_activity']['text_link'] = array(
    'field' => array(
      'title' => t('Text Link'),
      'help' => t('Creates a Text Link for various activity actions.'),
      'handler' => 'civicrm_handler_field_link_activity',
    ),
  );

  //TEXT LINKS THAT AREN'T PART OF THE TABLE
  //Contact Text Link
  $data['civicrm_contact']['text_link'] = array(
    'field' => array(
      'title' => t('Text Link'),
      'help' => t('Creates a Text Link for various contact actions.'),
      'handler' => 'civicrm_handler_field_link_contact',
    ),
  );

  //----------------------------------------------------------------
  // CIVICRM Relationships are here with all the connections, base tabling it up.
  //----------------------------------------------------------------

  $data['civicrm_relationship']['table']['group'] = t('CiviCRM Relationships');

  $data['civicrm_relationship']['table']['base'] = array(
    // Governs the whole mozilla
    'field' => 'id',
    'title' => t('CiviCRM Relationships'),
    'help' => t("View displays CiviCRM Relationships, with connection to contacts."),
  );

  //TABLE JOINS FOR CIVICRM RELATIONSHIPS GO HERE!

  // NOTE: This join is deprecated, as it hardcodes contact_a
  // It is left here to not break existing views.
  $data['civicrm_relationship']['table']['join'] = array(
    // Directly links to contact table - link A
    'civicrm_contact' => array(
      'left_field' => 'id',
      'field' => 'contact_id_a',
    ),
  );

  //Display A->B relationships in User Views
  $data['civicrm_relationship']['table']['join']['users'] = array(
    'left_table' => 'civicrm_uf_match',
    'left_field' => 'contact_id',
    'field' => 'contact_id_a',
  );

  //CiviCRM Relationships - FIELDS

  //Numeric Relationship ID
  $data['civicrm_relationship']['id'] = array(
    'title' => t('Relationship ID'),
    'help' => t('The numeric ID of the Relationship'),
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

  //Relationship Type
  $data['civicrm_relationship']['relationship_type'] = array(
    'title' => t('Relationship Type A-to-B'),
    'real field' => 'relationship_type_id',
    'help' => t('The Type of Relationship, i.e. Employee of, Household Head for...'),
    'field' => array(
      'handler' => 'civicrm_handler_field_relationship_type',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_relationship_type',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['civicrm_relationship']['contact_id_a_'] = array(
    'title' => t('Contact ID A'),
    'real field' => 'contact_id_a',
    'help' => t('The contact A'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => FALSE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'relationship' => array(
      'base' => 'civicrm_contact',
      'base field' => 'id',
      'handler' => 'views_handler_relationship',
      'label' => t('CiviCRM Contact A'),
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
  );

  $data['civicrm_relationship']['contact_id_b_'] = array(
    'title' => t('Contact ID B'),
    'real field' => 'contact_id_b',
    'help' => t('The contact B'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => FALSE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'relationship' => array(
      'base' => 'civicrm_contact',
      'base field' => 'id',
      'handler' => 'views_handler_relationship',
      'label' => t('CiviCRM Contact B'),
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
  );

  $data['civicrm_relationship']['contact_id_a'] = array(
    'title' => t('Contact ID A [Deprecated]'),
    'real field' => 'contact_id_a',
    'help' => t('The contact A [Deprecated]'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => FALSE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'relationship' => array(
      'base' => 'civicrm_contact',
      'field' => 'id',
      'relationship table' => 'civicrm_relationship',
      'relationship field' => 'contact_id_a',
      'other_field' => 'contact_id_b',
      'handler' => 'civicrm_handler_relationship',
      'label' => t('CiviCRM Contact A'),
    ),
  );

  $data['civicrm_relationship']['contact_id_b'] = array(
    'title' => t('Contact ID B [Deprecated]'),
    'real field' => 'contact_id_b',
    'help' => t('The contact B [Deprecated]'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => FALSE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'relationship' => array(
      'base' => 'civicrm_contact',
      'field' => 'id',
      'relationship table' => 'civicrm_relationship',
      'relationship field' => 'contact_id_b',
      'other_field' => 'contact_id_a',
      'handler' => 'civicrm_handler_relationship',
      'label' => t('CiviCRM Contact B'),
    ),
  );

  $data['civicrm_relationship']['description'] = array(
    'title' => t('Description'),
    'help' => t('Description of the relationship'),
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

  $data['civicrm_relationship']['is_permission_a_b'] = array(
    'title' => t('Contact A has Permission Over Contact B?'),
    'help' => t('Contact A has Permission Over Contact B?'),
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

  $data['civicrm_relationship']['is_permission_b_a'] = array(
    'title' => t('Contact B has Permission Over Contact A?'),
    'help' => t('Contact B has Permission Over Contact A?'),
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

  //Relationship start date
  $data['civicrm_relationship']['start_date'] = array(
    'title' => t('Start Date'),
    'help' => t("The Relationship's Start Date"),
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

  civicrm_views_add_date_arguments($data['civicrm_relationship'], array(
    'title' => 'Start Date',
    'name' => 'start_date',
  ));

  //Relationship end date
  $data['civicrm_relationship']['end_date'] = array(
    'title' => t('End Date'),
    'help' => t("The Relationship's End Date"),
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

  civicrm_views_add_date_arguments($data['civicrm_relationship'], array(
    'title' => 'End Date',
    'name' => 'end_date',
  ));
  //BOOLEAN : IS RELATIONSHIP ACTIVE
  $data['civicrm_relationship']['is_active'] = array(
    'title' => t('Is Relationship Active'),
    'help' => t('Is the Relationship active?'),
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

  //Relationship Text Link
  $data['civicrm_relationship']['text_link'] = array(
    'field' => array(
      'title' => t('Text Link'),
      'help' => t('Creates a Text Link for various relationship actions.'),
      'handler' => 'civicrm_handler_field_link_relationship',
    ),
  );

  // Case ID for CiviCase
  if (isset($enabled['CiviCase'])) {
    $data['civicrm_relationship']['case_id'] = array(
      'title' => t('Case ID'),
      'real field' => 'case_id',
      'help' => t('The case ID of this relationship'),
      'field' => array(
        'handler' => 'views_handler_field',
        'click sortable' => FALSE,
      ),
      'argument' => array(
        'handler' => 'views_handler_argument',
      ),
      'filter' => array(
        'handler' => 'views_handler_filter_numeric',
        'allow empty' => TRUE,
      ),
      'relationship' => array(
        'base' => 'civicrm_case',
        'field' => 'case_id',
        'handler' => 'views_handler_relationship',
        'label' => t('CiviCRM Case'),
      ),
    );
  }

  // Add support for CivicRM groups table

  $data['civicrm_group']['table']['group'] = t('CiviCRM Groups');

  $data['civicrm_group']['table']['base'] = array(
    'field' => 'id',
    'title' => t('CiviCRM Groups'),
    'help' => t("View displays CiviCRM Groups."),
  );

  $data['civicrm_group']['table']['join']['civicrm_contact'] = array(
    'left_table' => 'civicrm_group_contact',
    'left_field' => 'group_id',
    'field' => 'id',
  );

  $data['civicrm_group_contact']['table']['join']['civicrm_contact'] = array(
    // Directly links to group table
    'left_field' => 'id',
    'field' => 'contact_id',
  );

  $data['civicrm_group_contact']['table']['group'] = t('CiviCRM Groups');
  $data['civicrm_group_status']['table']['group'] = t('Contact Group Status');

  $data['civicrm_group_contact']['status'] = array(
    'title' => t('Contact Status'),
    'help' => t('The group status of the contact'),
    'field' => array(
      'handler' => 'civicrm_handler_filter_group_status',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_group_status',
      'allow empty' => TRUE,
    ),
  );

  // CiviCRM Groups - FIELDS

  // Numeric Group ID
  $data['civicrm_group_contact']['status'] = array(
    'title' => t('Contact Status'),
    'help' => t('The group status of the contact'),
    'field' => array(
      'handler' => 'civicrm_handler_filter_group_status',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_group_status',
      'allow empty' => TRUE,
    ),
  );

  //CiviCRM Groups - FIELDS

  //Numeric Group ID
  $data['civicrm_group']['id'] = array(
    'title' => t('Group ID'),
    'help' => t('The numeric ID of the Group'),
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
  //Is Group Active?
  $data['civicrm_group']['is_active'] = array(
    'title' => t('Is Active'),
    'help' => t('Is the Group Active'),
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

  $data['civicrm_group']['title'] = array(
    'title' => t('Title'),
    'help' => t('Group Title'),
    'real field' => 'id',
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'allGroup',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'allGroup',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  $data['civicrm_group']['description'] = array(
    'title' => t('Description'),
    'help' => t('Description of the Group'),
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

  // Add support for CiviCRM tags

  $data['civicrm_tag']['table']['group'] = t('CiviCRM Tags');

  $data['civicrm_tag']['table']['join']['civicrm_contact'] = array(
    'left_table' => 'civicrm_entity_tag',
    'left_field' => 'tag_id',
    'field' => 'id',
  );
  $data['civicrm_tag']['table']['join']['civicrm_activity'] = array(
    'left_table' => 'civicrm_entity_tag',
    'left_field' => 'tag_id',
    'field' => 'id',
  );
  $data['civicrm_tag']['table']['join']['civicrm_case'] = array(
    'left_table' => 'civicrm_entity_tag',
    'left_field' => 'tag_id',
    'field' => 'id',
  );

  $data['civicrm_entity_tag']['table']['join']['civicrm_contact'] = array(
    // Directly links to tag table
    'left_field' => 'id',
    'field' => 'entity_id',
    'extra' => array(
      array(
        'field' => 'entity_table',
        'value' => 'civicrm_contact',
        'numeric' => FALSE,
      ),
    ),
  );
  $data['civicrm_entity_tag']['table']['join']['civicrm_activity'] = array(
    // Directly links to tag table
    'left_field' => 'id',
    'field' => 'entity_id',
    'extra' => array(
      array(
        'field' => 'entity_table',
        'value' => 'civicrm_activity',
        'numeric' => FALSE,
      ),
    ),
  );
  $data['civicrm_entity_tag']['table']['join']['civicrm_case'] = array(
    // Directly links to tag table
    'left_field' => 'id',
    'field' => 'entity_id',
    'extra' => array(
      array(
        'field' => 'entity_table',
        'value' => 'civicrm_case',
        'numeric' => FALSE,
      ),
    ),
  );
  //CiviCRM Groups - FIELDS

  //Numeric Group ID
  $data['civicrm_tag']['id'] = array(
    'title' => t('Tag ID'),
    'help' => t('The numeric ID of the Tag'),
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

  $data['civicrm_tag']['name'] = array(
    'title' => t('Name'),
    'help' => t('Name of Tag'),
    'real field' => 'id',
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'pseudo args' => array('CRM_Core_BAO_EntityTag', 'tag_id'),
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'pseudo args' => array('CRM_Core_BAO_EntityTag', 'tag_id'),
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  $data['civicrm_tag']['description'] = array(
    'title' => t('Description'),
    'help' => t('Description of the Tag'),
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
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'allow empty' => TRUE,
    ),
  );

  $data['civicrm_tag']['parent_id'] = array(
    'title' => t('Tag Parent ID'),
    'help' => t('The numeric ID of the parent tag'),
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

  $data['civicrm_tag']['parent_name'] = array(
    'title' => t('Parent Name'),
    'help' => t('Name of Parent Tag'),
    'real field' => 'parent_id',
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'pseudo args' => array('CRM_Core_BAO_EntityTag', 'tag_id'),
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'get',
      'pseudo args' => array('CRM_Core_BAO_EntityTag', 'tag_id'),
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // UF Match table - expose drupal ID

  $data['civicrm_uf_match']['table']['group'] = t("CiviCRM Drupal match table");
  $data['civicrm_uf_match']['table']['join']['civicrm_contact'] = array(
    'left_field' => 'id',
    'field' => 'contact_id',
  );
  $data['civicrm_uf_match']['uf_id'] = array(
    'title' => t('Drupal User ID'),
    'help' => t('The user id of the User matched to a CiviCRM Contact'),
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
  $data['civicrm_uf_match']['domain_id'] = array(
    'title' => t('Domain ID'),
    'help' => t('Relates a CiviCRM contact to its domain'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_domain',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort_numeric',
    ),
  );

  // Pull in all the Custom fields
  $query = "select id, extends, extends_entity_column_value, style from civicrm_custom_group where is_active = 1";

  $dao = CRM_Core_DAO::executeQuery($query);

  while ($dao->fetch()) {
    // Call getTree using $dao->id as groupID, $dao->extends as entityType, with possible subtypes in $dao->extends_entity_column_value
    $data = civicrm_views_custom_data_cache($data, $dao->extends, $dao->id, $dao->extends_entity_column_value);
  }
}
