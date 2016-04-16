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
 * Build the $data array for Price Set related tables
 * Includes the following tables
 * civicrm_price_set_entity
 * civicrm_price_set
 * civicrm_price_field
 * civicrm_price_field_value
 */
function _civicrm_price_set_data(&$data, $enabled) {
  
  //----------------------------------------------------------------
  // CIVICRM Price Set Entity - BASE TABLE
  //----------------------------------------------------------------
  
  $data['civicrm_price_set_entity']['table']['group'] = t('CiviCRM Price Set Entities');

  $data['civicrm_price_set_entity']['table']['base'] = array(
    'field' => 'id',
    'title' => t('CiviCRM Price Set Entities'),
    'help' => t("View displays CiviCRM Price Set to Entity mapping info"),
  );
  
  //----------------------------------------------------------------
  // CIVICRM Price Set Entity - TABLE JOINS
  //----------------------------------------------------------------
    
  $data['civicrm_price_set_entity']['table']['join'] = array(
    // Directly links to event table.
    'civicrm_event' => array(
      'left_field' => 'id',
      'field' => 'entity_id',
    ),
  );
  
  //----------------------------------------------------------------
  // CIVICRM Price Set Entity - FIELDS
  //----------------------------------------------------------------
      
  // Numeric: Price Set ID
  $data['civicrm_price_set_entity']['price_set_id'] = array(
    'title' => t('Price Set ID'),
    'help' => t('The numeric ID of the Price Set'),
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

  // Entity Table
  $data['civicrm_price_set_entity']['entity_table'] = array(
    'title' => t('Entity Table'),
    'help' => t('The entity table for the Price Set'),
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
  
  //----------------------------------------------------------------
  // CIVICRM Price Set Entity - RELATIONSHIPS
  //----------------------------------------------------------------
    
  // Price Set
  $data['civicrm_price_set_entity']['price_set'] = array(
    'title' => t('Price Set'),
    'help' => t('Price Set for this Entity'),
    'real field' => 'price_set_id',
    'relationship' => array(
      'base' => 'civicrm_price_set',
      'base field' => 'id',
      'handler' => 'views_handler_relationship',
      'label' => t('Price Set'),
    ),
  );
  
  //----------------------------------------------------------------
  // CIVICRM Price Set - BASE TABLE
  //----------------------------------------------------------------

  $data['civicrm_price_set']['table']['group'] = t('CiviCRM Price Sets');

  $data['civicrm_price_set']['table']['base'] = array(
    'field' => 'id',
    'title' => t('CiviCRM Price Sets'),
    'help' => t("View displays CiviCRM Price Sets"),
  );
  
  //----------------------------------------------------------------
  // CIVICRM Price Set - FIELDS
  //----------------------------------------------------------------
    
  // Numeric: Price Set ID
  $data['civicrm_price_set']['id'] = array(
    'title' => t('Price Set ID'),
    'help' => t('The numeric ID of the Price Set'),
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

  // Numeric: Domain ID
  $data['civicrm_price_set']['domain_id'] = array(
    'title' => t('Domain ID'),
    'help' => t('The Domain ID of the Price Set'),
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

  // Name
  $data['civicrm_price_set']['name'] = array(
    'title' => t('Name'),
    'help' => t('The Price Set\'s machine name'),
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
  
  // Title
  $data['civicrm_price_set']['title'] = array(
    'title' => t('Title'),
    'help' => t('The Price Set\'s title'),
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
  
  // Boolean: Is Active
  $data['civicrm_price_set']['is_active'] = array(
    'title' => t('Is Active'),
    'help' => t('Has this Price Set been made active?'),
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
 
  // Extends
  $data['civicrm_price_set']['extends'] = array(
    'title' => t('Extends'),
    'help' => t('The CiviCRM component extended by this Price Set'),
    'field' => array(
      'handler' => 'civicrm_handler_field_component',
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
  
  // Financial Type
  $data['civicrm_price_set']['financial_type'] = array(
    'title' => t('Financial Type'),
    'help' => t('The financial type of the Price Set'),
    'real field' => 'financial_type_id',
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'pseudo class' => 'CRM_Contribute_PseudoConstant',
      'pseudo method' => 'financialType',
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
  
  // Boolean: Is Quick Config
  $data['civicrm_price_set']['is_quick_config'] = array(
    'title' => t('Is Quick Config'),
    'help' => t('Is this a Quick Config Price Set?'),
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
  
  // Boolean: Is Reserved
  $data['civicrm_price_set']['is_reserved'] = array(
    'title' => t('Is Reserved'),
    'help' => t('Is this Price Set reserved?'),
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
  // CIVICRM Price Field - BASE TABLE
  //----------------------------------------------------------------
  
  $data['civicrm_price_field']['table']['group'] = t('CiviCRM Price Fields');
  
  $data['civicrm_price_field']['table']['base'] = array(
    'field' => 'id',
    'title' => t('CiviCRM Price Fields'),
    'help' => t("View displays CiviCRM Price Fields"),
  );
  
  //----------------------------------------------------------------
  // CIVICRM Price Field - FIELDS
  //----------------------------------------------------------------
  
  // Numeric: Price Field ID
  $data['civicrm_price_field']['id'] = array(
    'title' => t('Price Field ID'),
    'help' => t('The numeric ID of the Price Field'),
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

  // Numeric: Price Set ID
  $data['civicrm_price_field']['price_set_id'] = array(
    'title' => t('Price Set ID'),
    'help' => t('The numeric ID of the Price Set to which this Price Field belongs'),
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

  // Name
  $data['civicrm_price_field']['name'] = array(
    'title' => t('Name'),
    'help' => t('The Price Field\'s machine name'),
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
  
  // Label
  $data['civicrm_price_field']['label'] = array(
    'title' => t('Label'),
    'help' => t('The Price Field\'s label'),
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

  // HTML Type
  $data['civicrm_price_field']['html_type'] = array(
    'title' => t('HTML Type'),
    'help' => t('The Price Field\'s HTML type (Text, Select, Radio, or Checkbox)'),
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
  
  // Boolean: Is Enter Quantity
  $data['civicrm_price_field']['is_enter_qty'] = array(
    'title' => t('Is Enter Quantity'),
    'help' => t('Can a quantity be entered for this Price Field?'),
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
 
  // Numeric: Weight
  $data['civicrm_price_field']['weight'] = array(
    'title' => t('Weight'),
    'help' => t('The weight of the Price Field'),
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
  
  // Boolean: Is Display Amounts
  $data['civicrm_price_field']['is_display_amounts'] = array(
    'title' => t('Is Display Amounts'),
    'help' => t('Should amounts be displayed for this Price Field?'),
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
 
  // Boolean: Is Active
  $data['civicrm_price_field']['is_active'] = array(
    'title' => t('Is Active'),
    'help' => t('Has this Price Field been made active?'),
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
 
  // Boolean: Is Required
  $data['civicrm_price_field']['is_required'] = array(
    'title' => t('Is Required'),
    'help' => t('Is this Price Field required?'),
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
  
  // Date: Active On
  $data['civicrm_price_field']['active_on'] = array(
    'title' => t('Active On'),
    'help' => t('The date on which this Price Field becomes active'),
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
  
  // Date: Expire On
  $data['civicrm_price_field']['expire_on'] = array(
    'title' => t('Expire On'),
    'help' => t('The date on which this Price Field expires'),
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
  
  // Visibility
  $data['civicrm_price_field']['visibility'] = array(
    'title' => t('Visibility'),
    'help' => t('The visibility of the Price Field (Public or Admin)'),
    'real field' => 'visibility_id',
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'pseudo class' => 'CRM_Core_PseudoConstant',
      'pseudo method' => 'visibility',
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
  
  //----------------------------------------------------------------
  // CIVICRM Price Field - RELATIONSHIPS
  //----------------------------------------------------------------
  
  // Price Field Value
  $data['civicrm_price_field']['price_field_value'] = array(
    'title' => t('Price Field Value'),
    'help' => t('Value(s) for this Price Field'),
    'real field' => 'id',
    'relationship' => array(
      'base' => 'civicrm_price_field_value',
      'base field' => 'price_field_id',
      'handler' => 'views_handler_relationship',
      'label' => t('Price Field Value'),
    ),
  );

  // Price set
  $data['civicrm_price_field']['price_set'] = array(
    'title' => t('Price Set'),
    'help' => t('Price Set related to this Price Field'),
    'real field' => 'price_set_id',
    'relationship' => array(
      'base' => 'civicrm_price_set',
      'base field' => 'id',
      'handler' => 'views_handler_relationship',
      'label' => t('Price Set'),
    ),
  );
  
  //----------------------------------------------------------------
  // CIVICRM Price Field Value - BASE TABLE
  //----------------------------------------------------------------
  
  $data['civicrm_price_field_value']['table']['group'] = t('CiviCRM Price Field Values');
  
  $data['civicrm_price_field_value']['table']['base'] = array(
    'field' => 'id',
    'title' => t('CiviCRM Price Field Values'),
    'help' => t("View displays CiviCRM Price Field Values"),
  );
  
  //----------------------------------------------------------------
  // CIVICRM Price Field Value - FIELDS
  //----------------------------------------------------------------
  
  // Numeric: Price Field Value ID
  $data['civicrm_price_field_value']['id'] = array(
    'title' => t('Price Field Value ID'),
    'help' => t('The numeric ID of the Price Field Value'),
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

  // Numeric: Price Field ID
  $data['civicrm_price_field_value']['price_field_id'] = array(
    'title' => t('Price Field ID'),
    'help' => t('The numeric ID of the Price Field that contains this Price Field Value'),
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

  // Name
  $data['civicrm_price_field_value']['name'] = array(
    'title' => t('Name'),
    'help' => t('The Price Field Value\'s machine name'),
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
  
  // Label
  $data['civicrm_price_field_value']['label'] = array(
    'title' => t('Label'),
    'help' => t('The Price Field\'s label'),
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

  // Description
  $data['civicrm_price_field_value']['description'] = array(
    'title' => t('Description'),
    'help' => t('The Price Field Value\'s Description'),
    'field' => array(
      'handler' => 'civicrm_handler_field_markup',
      'click sortable' => TRUE,
    ),
  );
  
  // Numeric: Amount
  $data['civicrm_price_field_value']['amount'] = array(
    'title' => t('Amount'),
    'help' => t('The Price Field Value\'s amount'),
    'field' => array(
      'handler' => 'civicrm_handler_field_money',
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
  
  // Numeric: Count
  $data['civicrm_price_field_value']['count'] = array(
    'title' => t('Count'),
    'help' => t('The count of the Price Field Value (i.e. total number of times the value has been selected e.g. by event participants)'),
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
  
  // Numeric: Max Value
  $data['civicrm_price_field_value']['max_value'] = array(
    'title' => t('Max Value'),
    // TODO: Need to document this field, but I don't understand what it is...
    // Is it the maximum value of the Price Field Value? the maximum value of Count?
    //'help' => t('The maximum value of the Price Field Value'),
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
  
  // Numeric: Weight
  $data['civicrm_price_field_value']['weight'] = array(
    'title' => t('Weight'),
    'help' => t('The weight of the Price Field Value'),
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
  
  // Numeric: Membership Type ID
  $data['civicrm_price_field_value']['membership_type_id'] = array(
    'title' => t('Membership Type ID'),
    'help' => t('The numerical ID for the Membership Type associated with the Price Field Value'),
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
  
  // Membership Num Terms
  $data['civicrm_price_field_value']['membership_num_terms'] = array(
    'title' => t('Membership Number of Terms'),
    'help' => t('The number of terms (months, years, etc.) for the Membership Type associated with this Price Field Value'),
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
  
  // Boolean: Is Default
  $data['civicrm_price_field_value']['is_default'] = array(
    'title' => t('Is Default'),
    'help' => t('Is this Price Field Value the default value?'),
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
  
  // Boolean: Is Active
  $data['civicrm_price_field_value']['is_active'] = array(
    'title' => t('Is Active'),
    'help' => t('Has this Price Field Value been made active?'),
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
  
  // Financial Type
  $data['civicrm_price_field_value']['financial_type'] = array(
    'title' => t('Financial Type'),
    'help' => t('The financial type of the Price Field Value'),
    'real field' => 'financial_type_id',
    'field' => array(
      'handler' => 'civicrm_handler_field_financial_type',
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
   
  // Numeric: Deductible Amount
  $data['civicrm_price_field_value']['deductible_amount'] = array(
    'title' => t('Deductible Amount'),
    'help' => t('The deductible amount of this Price Field Value'),
    'field' => array(
      'handler' => 'civicrm_handler_field_money',
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
  
}