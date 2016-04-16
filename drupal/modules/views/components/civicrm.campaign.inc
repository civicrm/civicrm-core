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
 * Build the $data array for CiviCampaign related tables
 * Includes the following tables
 * civicrm_campaign
 */
require_once 'CRM/Campaign/PseudoConstant.php';
function _civicrm_campaign_data(&$data, $enabled) {

  /**
   *CiviCRM Campaign Base Table
   */

  $data['civicrm_campaign']['table']['group'] = t('CiviCRM Campaigns');

  $data['civicrm_campaign']['table']['base'] = array(
    // Governs the whole mozilla
    'field' => 'id',
    'title' => t('CiviCRM Campaigns'),
    'help' => t("View displays CiviCRM Campaigns Data"),
  );
  // Campaign ID
  $data['civicrm_campaign']['id'] = array(
    'title' => t('Campaign ID'),
    'help' => t('The numeric ID of the campaign'),
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
  // Campaign's Name
  $data['civicrm_campaign']['name'] = array(
    'title' => t('Name'),
    'help' => t('Campaign\'s name'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // Campaign Title
  $data['civicrm_campaign']['title'] = array(
    'title' => t('Title'),
    'help' => t('Campaing\'s title'),
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
  //Campaign Description
  $data['civicrm_campaign']['description'] = array(
    'title' => t('Description'),
    'help' => t('Campaing\'s description'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
  );
  //Mailing finish date
  $data['civicrm_campaign']['start_date'] = array(
    'title' => t('Start Date'),
    'help' => t('The Campaign\'s Start Date'),
    'field' => array(
      'handler' => 'civicrm_handler_field_datetime',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_date',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_datetime',
      'is date' => TRUE,
    ),
    'sort' => array(
      'handler' => 'civicrm_handler_sort_date',
    ),
  );

  civicrm_views_add_date_arguments($data['civicrm_campaign'], array(
    'title' => 'Campaign Start Date',
      'name' => 'start_date',
    ));

  //Mailing finish date
  $data['civicrm_campaign']['end_date'] = array(
    'title' => t('End Date'),
    'help' => t('The Campaign\'s End Date'),
    'field' => array(
      'handler' => 'civicrm_handler_field_datetime',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_date',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_datetime',
      'is date' => TRUE,
    ),
    'sort' => array(
      'handler' => 'civicrm_handler_sort_date',
    ),
  );

  civicrm_views_add_date_arguments($data['civicrm_campaign'], array(
    'title' => 'Campaign End Date',
      'name' => 'end_date',
    ));

  //Campaign Type
  $data['civicrm_campaign']['campaign_type'] = array(
    'title' => t('Campaign Type'),
    'real field' => 'campaign_type_id',
    'help' => t('The Type of Campaign, ie Direct Mail, Constituent Engagement etc'),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Campaign_PseudoConstant',
      'pseudo method' => 'campaignType',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'allow empty' => TRUE,
      'pseudo class' => 'CRM_Campaign_PseudoConstant',
      'pseudo method' => 'campaignType',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  //Campaign Type
  $data['civicrm_campaign']['campaign_status'] = array(
    'title' => t('Campaign Status'),
    'real field' => 'status_id',
    'help' => t('The Status of the Campaign, ie Completed, Cancelled etc'),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Campaign_PseudoConstant',
      'pseudo method' => 'campaignStatus',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'allow empty' => TRUE,
      'pseudo class' => 'CRM_Campaign_PseudoConstant',
      'pseudo method' => 'campaignStatus',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  // Parent Campaign ID
  $data['civicrm_campaign']['parent_id'] = array(
    'title' => t('Parent Campaign ID'),
    'help' => t('The ID of the campaign\'s parent campaign'),
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
  //BOOLEAN : IS Active
  $data['civicrm_campaign']['is_active'] = array(
    'title' => t('Is Active'),
    'help' => t('Is the campaign active ?'),
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
  // Campaign's General Goal
  $data['civicrm_campaign']['goal_general'] = array(
    'title' => t('General Goal'),
    'help' => t('Campaing\'s general goal'),
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
  // Campaign ID
  $data['civicrm_campaign']['goal_revenue'] = array(
    'title' => t('Campaign Monetary Goal'),
    'help' => t('The Monetary goal of the campaign'),
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
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // Add Support of Survey Fields.
  $data['civicrm_survey']['table']['group'] = t('CiviCRM Surveys');


  $data['civicrm_survey']['table']['base'] = array(
    // Governs the whole mozilla
    'field' => 'id',
    'title' => t('CiviCRM Surveys'),
    'help' => t("View displays CiviCRM Survey Data"),
  );
  // Link to activites table for Results
  $data['civicrm_activity']['table']['join']['civicrm_survey'] = array(
    // Directly links to tag table
    'left_field' => 'id',
    'field' => 'source_record_id',
    'extra' => array(
      array(
        'field' => 'activity_type_id',
        'value' => CRM_Campaign_PseudoConstant::activityType('value'),
        'numeric' => FALSE,
      ),
    ),
  );
  // Survey ID
  $data['civicrm_survey']['id'] = array(
    'title' => t('Survey ID'),
    'help' => t('The numeric ID of the survey'),
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
  // Campaign ID
  $data['civicrm_survey']['campaign_id'] = array(
    'title' => t('Campaign'),
    'help' => t('Event -> Campaign'),
    'relationship' => array(
      'base' => 'civicrm_campaign',
      'field' => 'id',
      'handler' => 'views_handler_relationship',
      'label' => t('Survey -> Campaign'),
    ),
  );



  //Activity Type
  $data['civicrm_survey']['activity_type'] = array(
    'title' => t('Activity Type'),
    'real field' => 'activity_type_id',
    'help' => t('The Type of activity, ie Survey, Canvass, Phone Bank etc'),
    'field' => array(
      'handler' => 'civicrm_handler_field_pseudo_constant',
      'click sortable' => TRUE,
      'pseudo class' => 'CRM_Campaign_PseudoConstant',
      'pseudo method' => 'activityType',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
      'handler' => 'civicrm_handler_filter_pseudo_constant',
      'allow empty' => TRUE,
      'pseudo class' => 'CRM_Campaign_PseudoConstant',
      'pseudo method' => 'activityType',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  //BOOLEAN : IS Active
  $data['civicrm_survey']['is_active'] = array(
    'title' => t('Is Active'),
    'help' => t('Is the survey active?'),
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
  //BOOLEAN : IS Default
  $data['civicrm_survey']['is_default'] = array(
    'title' => t('Is Default'),
    'help' => t('Is the survey the default?'),
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
  require_once 'CRM/Campaign/DAO/Survey.php';
  $fields = CRM_Campaign_DAO_Survey::fields();
  //$skipFields = array( );
  civicrm_views_add_fields($fields, $data['civicrm_survey'], 'civicrm_survey');
}

