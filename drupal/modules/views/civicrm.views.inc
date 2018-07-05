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

/*
   * Copyright (C) 2009 James Rakich
   * Licensed to CiviCRM under the Academic Free License version 3.0.
   *
   */


/*
   *  civicrm.views.inc  Called from civicrm.module, gives the views cache all information it needs
   *                     to access CiviCRM's database for use in Views, as well as referencing the
   *                     custom handlers for displaying, sorting, filtering and accepting arguments
   *                     for this data.
   *
   // Defines tables, joins and relationships
   *  function civicrm_views_data() {
   *     CiviCRM Contacts Base Table
   *     CiviCRM Activities Base Table
   *     CiviCRM Relationships Base Table
   *     Custom Data Cache Query and Calls
   *
   *  function civicrm_views_href( $text, $path, $query )
   *     Generates a link for CiviCRM Paths - unchanged from previous code (anonymous donation)
   *
   *  function civicrm_views_custom_data_cache($data, $entity_type, $groupID, $subType, $style)
   *     Collects the data from Custom Data Groups and assigns them to base tables.
   *
   *  function civicrm_views_get_field ($type)
   *  function civicrm_views_get_argument ($type)
   *  function civicrm_views_get_filter ($type)
   *  function civicrm_views_get_sort ($type)
   *     Assign handlers to custom fields based on the data type (from the database records)
   *
   *  function civicrm_date_api_tables()
   *  function civicrm_date_api_fields()
   *     Tells Views which fields to expose as Date API fields
   */

/**
 * Implements hook_views_data().
 *
 * Run hook_views_data for active CiviCRM components
 */
function civicrm_views_data_alter(&$data) {
  if (!civicrm_initialize() || CRM_Utils_System::isInUpgradeMode()) {
    return;
  }

  require_once 'CRM/Core/Config.php';
  require_once 'CRM/Core/BAO/CustomGroup.php';
  require_once 'CRM/Core/DAO.php';
  require_once 'CRM/Core/Error.php';
  require_once 'CRM/Contact/BAO/Contact.php';
  require_once 'CRM/Event/BAO/Query.php';
  require_once 'CRM/Case/BAO/Case.php';
  require_once 'components/civicrm.core.inc';

  // Get list of enabled CiviCRM components
  $enabled = CRM_Core_Component::getEnabledComponents();

  // $data = array();
  // Load Core CiviCRM data
  _civicrm_core_data($data, $enabled);

  // Load enabled optional components
  if (isset($enabled['CiviCampaign'])) {
    include_once 'components/civicrm.campaign.inc';
    _civicrm_campaign_data($data, $enabled);
  }
  // Though not explicitly effectively CiviPledge depends on CiviContribute
  // so they share an include file
  if (isset($enabled['CiviContribute']) ||
    isset($enabled['CiviPledge'])
  ) {
    include_once 'components/civicrm.contribute.inc';
    _civicrm_contribute_data($data, $enabled);
    include_once 'components/civicrm.lineitem.inc';
    _civicrm_lineitem_data($data, $enabled);
  }
  if (isset($enabled['CiviEvent'])) {
    include_once 'components/civicrm.event.inc';
    _civicrm_event_data($data, $enabled);
  }
  if (isset($enabled['CiviGrant'])) {
    include_once 'components/civicrm.grant.inc';
    _civicrm_grant_data($data, $enabled);
  }
  if (isset($enabled['CiviMail'])) {
    include_once 'components/civicrm.mail.inc';
    _civicrm_mail_data($data, $enabled);
  }
  if (isset($enabled['CiviMember'])) {
    include_once 'components/civicrm.member.inc';
    _civicrm_member_data($data, $enabled);
  }
  if (isset($enabled['CiviCase'])) {
    include_once 'components/civicrm.case.inc';
    _civicrm_case_data($data, $enabled);
  }
  if (isset($enabled['CiviContribute']) ||
    isset($enabled['CiviEvent']) ||
    isset($enabled['CiviMember'])
  ) {
    include_once 'components/civicrm.price_set.inc';
    _civicrm_price_set_data($data, $enabled);
  }

  return $data;
}

/**
 * Return link to CiviCRM path
 *
 * @param $text
 *
 * @param $path
 *
 * @param $query
 *
 * @return String path to CiviCRM
 */
function civicrm_views_href($text, $path, $query) {
  $url = civicrm_views_url($path, $query);
  return "<a href=\"$url\">$text</a>";
}

/**
 * Return url to CiviCRM path
 *
 * @param $path     string   The path being linked to, such as "civicrm/add"
 * @param $query    string   A query string to append to the link.
 * @param $absolute boolean  Whether to force the output to be an absolute link (beginning with http:).
 *                           Useful for links that will be displayed outside the site, such as in an
 *                           RSS feed.
 *
 * @return string            an HTML string containing a link to the given path.
 */
function civicrm_views_url($path, $query, $absolute = FALSE) {
  // Force alphabetical order of query params, for consistent support
  // of Drupal aliases. This is required because $query is a string that may
  // be coming to us in any order; but query parameter order matters when
  // passing that query string as part of $path in url($path). Admittedly it's
  // not common to passt the query string as part of $path in url($path) (you
  // would normally pass it as $options['query'] in url($path, $options)), but
  // doing so is required for Drupal alias support.
  if (!empty($query)) {
    if (is_array($query)) {
      $query_data = $query;
    }
    else {
      parse_str($query, $query_data);
    }
    ksort($query_data);
    $query = http_build_query($query_data);
    $path .= "?{$query}";
  }
  $options = array(
    'absolute' => $absolute,
  );
  $url = url($path, $options);
  return $url;
}

/**
 * Creates new View fields from CiviCRM fields
 *
 * @param $fields
 *   Array of fields in a table obtained from a DAO fields method for $tableName
 *
 * @param $data
 *   Array returned to hook_views_data
 *
 * @param $tableName
 *   String tabled nam of field whose DAO is returned in $fields
 *
 * @param $skipFields
 *   (optional) Array of fields not to add form the $fields table
 */
function civicrm_views_add_fields(&$fields, &$data, $tableName, &$skipFields = NULL) {
  foreach ($fields as $name => $value) {
    // Only add fields not in $data or $skipFields and has a ['title']
    if (isset($value['custom_field_id']) ||
      CRM_Utils_Array::value($name, $skipFields) ||
      CRM_Utils_Array::value($name, $data) ||
      !isset($value['title']) ||
      (isset($value['where']) &&
        substr($value['where'], 0, strlen($tableName) + 1) != "{$tableName}."
      )
    ) {
      continue;
    }

    // Ensure the field isn't alredy defined in $data using $data[$xyz]['real field'] where $xyz is a field name passed in $data
    foreach ($data as $field => $current) {
      if (isset($current['real field']) and $current['real field'] == $name) {
        continue 2;
      }
    }

    $type = CRM_Utils_Array::value('type', $value, 'String');
    $data[$value['name']] = array(
      'title' => $value['title'],
      'help' => $value['title'],
      'field' => civicrm_views_get_field($type),
      'sort' => civicrm_views_get_sort($type),
      'filter' => civicrm_views_get_filter($type),
      'argument' => civicrm_views_get_argument($type),
    );

    // For date fields add in 6 arguments
    // not sure how its numeric here, but leaving it as is for now
    if ($type == 4) {
      civicrm_views_add_date_arguments($data, $value);
    }
  }
}

/**
 * Function adds 6 date arguments to a date field
 *
 * @param $data
 *   Array passed back to hook_views_data()
 *
 * @param $value
 *   Array contains meta data about field from DAO fields function
 *
 */
function civicrm_views_add_date_arguments(&$data, $value) {

  $data[$value['name']]['argument'] = array(
    'handler' => 'date_views_argument_handler',
    'empty field name' => t('Undated'),
    'is date' => TRUE,
  );
  $data[$value['name'] . '_full'] = array(
    'title' => $value['title'],
    'help' => t('In the form of CCYYMMDD.'),
    'argument' => array(
      'field' => $value['name'],
      'handler' => 'views_handler_argument_civicrm_fulldate',
    ),
  );
  $data[$value['name'] . '_year_month'] = array(
    'title' => t('%title year + month', array('%title' => $value['title'])),
    'help' => t('In the form of YYYYMM.'),
    'argument' => array(
      'field' => $value['name'],
      'handler' => 'views_handler_argument_civicrm_year_month',
    ),
  );
  $data[$value['name'] . '_year'] = array(
    'title' => t('%title year', array('%title' => $value['title'])),
    'help' => t('In the form of YYYY.'),
    'argument' => array(
      'field' => $value['name'],
      'handler' => 'views_handler_argument_civicrm_year',
    ),
  );
  $data[$value['name'] . '_month'] = array(
    'title' => t('%title month', array('%title' => $value['title'])),
    'help' => t('In the form of MM (01 - 12).'),
    'argument' => array(
      'field' => $value['name'],
      'handler' => 'views_handler_argument_civicrm_month',
    ),
  );
  $data[$value['name'] . '_day'] = array(
    'title' => t('%title day', array('%title' => $value['title'])),
    'help' => t('In the form of DD (01 - 31).'),
    'argument' => array(
      'field' => $value['name'],
      'handler' => 'views_handler_argument_civicrm_day',
    ),
  );
  $data[$value['name'] . '_week'] = array(
    'title' => t('%title week', array('%title' => $value['title'])),
    'help' => t('In the form of WW (01 - 53).'),
    'argument' => array(
      'field' => $value['name'],
      'handler' => 'views_handler_argument_civicrm_week',
    ),
  );
}

/**
 * Add Custom Fields to $data array
 *
 * @param $data
 *   Array of fields passed to hook_views_data()
 *
 * @param $entity_type
 *   String CivicRM entity Type ie "Contact"
 *
 * @param $group_id
 *   Integer Id of the Custom Field Group
 *
 * @param $sub_type
 *   (optional) Integer Id of the Sub-Type, ie Contact Sub Type
 *
 * @return mixed
 *   $Data array with the new custom field appended or void
 */
function civicrm_views_custom_data_cache(&$data, $entity_type, $group_id, $sub_type) {
  if (CRM_Core_Config::isUpgradeMode()) {
    return;
  }
  // From http://forum.civicrm.org/index.php/topic,17658.msg73901.html#msg73901, CRM-7860.
  // Checking permissions results in bug CRM-20292.
  $tree = CRM_Core_BAO_CustomGroup::getTree($entity_type, NULL, NULL, $group_id, $sub_type, NULL, TRUE, NULL, FALSE, FALSE);

  $join_table = civicrm_views_get_join_table($entity_type);
  foreach ($tree as $groupkey => $current_group) {
    // Ignore 'info' key as it is not a real field.
    if ($groupkey == 'info') {
      continue;
    }

    // Provide custom table data, including group title and implicit table join.
    $data[$current_group['table_name']]['table'] = array(
      'group' => t('CiviCRM Custom: !title', array('!title' => $current_group['title'])),
      'join' => array(
        $join_table => array(
          'left_field' => 'id',
          'field' => 'entity_id',
        ),
      ),
    );

    // Provide implicit joins in the other direction.
    // Skip 'contribution', activity' and 'event' as these are not directly related to users.
    if ($join_table != 'civicrm_event' && $join_table != 'civicrm_activity' && $join_table != 'civicrm_contribution') {
      // Expose custom data to users view.
      $data[$current_group['table_name']]['table']['join']['users'] = array(
        'left_table' => $join_table,
        'left_field' => 'id',
        'field' => 'entity_id',
      );

      // Expose custom data to contact view.
      if ($join_table != 'civicrm_contact') {
        $data[$current_group['table_name']]['table']['join']['civicrm_contact'] = array(
          'left_table' => $join_table,
          'left_field' => 'id',
          'field' => 'entity_id',
        );
      }
    }

    foreach ($current_group['fields'] as $key => $current_field) {
      //Create the Views Field
      $option_group_id = isset($current_field['option_group_id']) ? $current_field['option_group_id'] : NULL;
      $data[$current_group['table_name']][$current_field['column_name']] = array(
        'title' => $current_field['label'],
        'help' => empty($current_field['help_post']) ? t('Custom Data Field') : $current_field['help_post'],
        'field' => civicrm_views_get_field($current_field['data_type'], $current_field['html_type']),
        'argument' => civicrm_views_get_argument($current_field['data_type']),
        'filter' => civicrm_views_get_filter($current_field['data_type'], $current_field['html_type'], $option_group_id),
        'sort' => civicrm_views_get_sort($current_field['data_type']),
        'relationship' => civicrm_views_get_relationship($current_field['data_type']),
      );

      //For date fields add in 6 arguments
      if ($current_field['data_type'] == 'Date') {
        //@TODO  Still need to get the field under it's respecitve group, I may e able to set the civicrm_views_add_date_arguments() function with a group variable and default it to null
        $value = array();
        $value['title'] = $current_field['label'];
        $value['name'] = $current_field['column_name'];
        civicrm_views_add_date_arguments($data[$current_group['table_name']], $value);
      }
    }
  }
  return $data;
}

/**
 * Return the implicit join table for a custom group table based on its entity type.
 */
function civicrm_views_get_join_table($entity_type) {
  switch ($entity_type) {
    case "Contact":
    case "Individual":
    case "Household":
    case "Organization":
      return 'civicrm_contact';

    case "Group":
      return 'civicrm_group';

    case "Address":
      return 'civicrm_address';

    case "Event":
      return 'civicrm_event';

    case "Participant":
      return 'civicrm_participant';

    case "Contribution":
      return 'civicrm_contribution';

    case "Activity":
      return 'civicrm_activity';

    case "Relationship":
      return 'civicrm_relationship';

    case "Membership":
      return 'civicrm_membership';

    case "Grant":
      return 'civicrm_grant';

    case "Campaign":
    case "Survey":
      return 'civicrm_campaign';

    case "Case":
      return 'civicrm_case';

    default:
      return NULL;
  }
}

/**
 * Acquire the proper field handler by checking against the field's data_type as defined by CRM_Utils_Type.
 *
 * @param $data_type
 *   A String containing the field data type
 * @param $html_type
 *   A String containing the field html type
 *
 * @return array
 *   An array containing the handler name and any extra settings
 */
function civicrm_views_get_field($data_type, $html_type = NULL) {
  // Relying on html types as opposed to data types seems like a code smell.
  // Would love to be able to remove this logic.
  $customHTMLTypes = array(
    'Select', 'Multi-Select', 'Radio', 'CheckBox',
    'Select State/Province', 'Select Country', 'Multi-Select Country',
    'Multi-Select State/Province', 'Autocomplete-Select',
  );
  if (in_array($html_type, $customHTMLTypes)) {
    return array(
      'handler' => 'civicrm_handler_field_custom',
      'click sortable' => TRUE,
    );
  }
  if ($html_type == 'File') {
    return array(
      'handler' => 'civicrm_handler_field_custom_file',
      'click sortable' => TRUE,
    );
  }

  switch ($data_type) {
    case "String":
    case "Memo":
      return array('handler' => 'civicrm_handler_field_markup', 'click sortable' => TRUE);

    case "Float":
      return array('handler' => 'views_handler_field_numeric', 'click sortable' => TRUE, 'float' => TRUE);

    case "Int":
      return array('handler' => 'civicrm_handler_field_custom', 'click sortable' => TRUE);

    case "Date":
      return array('handler' => 'civicrm_handler_field_datetime', 'click sortable' => TRUE);

    case "Boolean":
      return array('handler' => 'views_handler_field_boolean', 'click sortable' => TRUE);

    case "StateProvince":
      return array('handler' => 'civicrm_handler_field_state', 'click sortable' => TRUE);

    case "Country":
      return array('handler' => 'civicrm_handler_field_country', 'click sortable' => TRUE);

    case "County":
      return array('handler' => 'civicrm_handler_field_pseudo_constant', 'click sortable' => TRUE, 'pseudo class' => 'CRM_Core_PseudoConstant', 'pseudo method' => 'county');

    default:
      return array('views_handler_field', 'click sortable' => TRUE);
  }
}

/**
 * Acquire the proper argument handler by checking against the field's data_type as defined by CRM_Utils_Type.
 *
 * @param $type
 *   A String containing the field type
 *
 * @return array
 *   An array containing the handler name and any extra settings
 */
function civicrm_views_get_argument($type) {
  switch ($type) {
    case "String":
    case "Memo":
      return array('handler' => 'views_handler_argument');

    case "Float":
    case "Int":
      return array('handler' => 'views_handler_argument_numeric');

    case "Date":
      return array('handler' => 'views_handler_argument_date');

    case "Boolean":
      return array('handler' => 'views_handler_argument');

    case "StateProvince":
      return array('handler' => 'views_handler_argument');

    case "Country":
      return array('handler' => 'views_handler_argument');

    case "County":
      return array('handler' => 'views_handler_argument');

    default:
      return array('handler' => 'views_handler_argument');
  }
}

/**
 * Acquire the proper filter handler by checking against the field's data_type as defined by CRM_Utils_Type.
 *
 * @param $data_type
 *   A String containing the field data type
 * @param $html_type
 *   A string containing the field html type
 *
 * @return array
 *   An array containing the handler name and any extra settings
 */
function civicrm_views_get_filter($data_type, $html_type = NULL, $option_group_id = NULL) {
  // Relying on html types as opposed to data types seems like a code smell.
  // Would love to be able to remove this logic.
  $customMultiValueHTMLTypes = array(
    'Multi-Select', 'CheckBox', 'Multi-Select Country',
    'Multi-Select State/Province',
  );
  if ($html_type == 'Multi-Select Country') {
    return array(
      'handler' => 'civicrm_handler_filter_country_multi',
    );
  }
  elseif ($html_type == 'Multi-Select State/Province') {
    return array(
      'handler' => 'civicrm_handler_filter_state_multi',
    );
  }
  elseif (!empty($option_group_id) && in_array($html_type, $customMultiValueHTMLTypes)) {
    return array(
      'handler' => 'civicrm_handler_filter_custom_option',
    );
  }
  elseif (!empty($option_group_id)) {
    return array(
      'handler' => 'civicrm_handler_filter_custom_single_option',
    );
  }

  switch ($data_type) {
    case "String":
    case "Memo":
      return array('handler' => 'views_handler_filter_string', 'allow empty' => TRUE);

    case "Float":
    case "Int":
      return array('handler' => 'views_handler_filter_numeric', 'allow empty' => TRUE);

    case "Date":
      return array('handler' => 'civicrm_handler_filter_datetime');

    case "Boolean":
      return array('handler' => 'views_handler_filter_boolean_operator');

    case "StateProvince":
      return array('handler' => 'civicrm_handler_filter_pseudo_constant', 'pseudo class' => 'CRM_Core_PseudoConstant', 'pseudo method' => 'stateProvince', 'allow empty' => TRUE);

    case "Country":
      return array('handler' => 'civicrm_handler_filter_pseudo_constant', 'pseudo class' => 'CRM_Core_PseudoConstant', 'pseudo method' => 'country', 'allow empty' => TRUE);

    case "County":
      return array('handler' => 'civicrm_handler_filter_pseudo_constant', 'pseudo class' => 'CRM_Core_PseudoConstant', 'pseudo method' => 'county', 'allow empty' => TRUE);

    case "ContactReference":
      return array('handler' => 'civicrm_handler_filter_contact_ref');

    default:
      return array('handler' => 'views_handler_filter_string', 'allow empty' => TRUE);
  }
}

/**
 * Acquire the proper sort handler by checking against the field's data_type as defined by CRM_Utils_Type.
 *
 * @param $type
 *   A String containing the field type
 *
 * @return array
 *   An array containing the handler name and any extra settings
 */
function civicrm_views_get_sort($type) {
  switch ($type) {
    case "String":
    case "Memo":
    case "Float":
    case "Int":
      return array('handler' => 'views_handler_sort');

    case "Date":
      return array('handler' => 'views_handler_sort_date');

    case "Boolean":
      return array('handler' => 'views_handler_sort');

    case "StateProvince":
      return array('handler' => 'views_handler_sort');

    case "Country":
      return array('handler' => 'views_handler_sort');

    case "County":
      return array('handler' => 'views_handler_sort');

    default:
      return array('handler' => 'views_handler_sort');
  }
}

/**
 * Acquire the proper relationship handler by checking against the field's data_type as defined by CRM_Utils_Type.
 *
 * @param $type
 *   A String containing the field type
 *
 * @return array
 *   An array containing the handler name and any extra settings
 */
function civicrm_views_get_relationship($type) {
  switch ($type) {
    case "ContactReference":
      return array('handler' => 'views_handler_relationship', 'base' => 'civicrm_contact', 'base field' => 'id');

    default:
      return array();
  }
}

/**
 * Implements hook_date_views_fields().
 */
function civicrm_date_views_fields($field) {
  $values = array(
    // The type of date: DATE_UNIX, DATE_ISO, DATE_DATETIME.
    'sql_type' => DATE_DATETIME,
    // Timezone handling options: 'none', 'site', 'date', 'utc'.
    'tz_handling' => 'none',
    // Needed only for dates that use 'date' tz_handling.
    'timezone_field' => '',
    // Needed only for dates that use 'date' tz_handling.
    'offset_field' => '',
    // Array of "table.field" values for related fields that should be
    // loaded automatically in the Views SQL.
    'related_fields' => array(),
    // Granularity of this date field's db data.
    'granularity' => array('year', 'month', 'day', 'hour', 'minute', 'second'),
  );

  switch ($field) {
    case 'civicrm_event.start_date':
    case 'civicrm_event.end_date':
    case 'civicrm_event.registration_start_date':
    case 'civicrm_event.registration_end_date':
    case 'civicrm_mailing_job.scheduled_date':
    case 'civicrm_mailing_job.start_date':
    case 'civicrm_mailing_job.end_date':
    case 'civicrm_activity.activity_date_time':
    case 'civicrm_campaign.start_date':
    case 'civicrm_campaign.end_date':
    case 'civicrm_case.start_date':
    case 'civicrm_case.end_date':
      return $values;
  }
}

/**
 * Implements hook_date_api_tables().
 */
function civicrm_date_views_tables() {
  return array(
    'civicrm_mailing_job',
    'civicrm_event',
    'civicrm_case',
    'civicrm_activity',
    'civicrm_campaign',
  );
}

/**
 * Implements hook_views_plugins().
 */
function civicrm_views_plugins() {
  $data = array();

  // This just tells us that the themes are elsewhere
  $data['module'] = 'civicrm';

  // Default argument to pull CiviCRM IDs from the URL
  $data['argument default']['civicrm_id'] = array(
    'title' => t('CiviCRM ID from URL'),
    'handler' => 'civicrm_plugin_argument_default_civicrm_id',
  );

  // Calendar module integration
  if (module_exists('calendar')) {
    $civicrm_module_path = drupal_get_path('module', 'civicrm');
    $data['row'] = array(
      'civicrm_event_calendar' => array(
        'title' => t('Calendar Items'),
        'help' => t('Displays each selected event as a Calendar item.'),
        'handler' => 'calendar_plugin_row_civicrm',
        'path' => "$civicrm_module_path/modules/views/plugins",
        'base' => array('civicrm_event'),
        'uses options' => TRUE,
        'uses fields' => TRUE,
        'type' => 'normal',
        'dao class' => 'CRM_Event_DAO_Event',
        'title field' => 'title',
      ),
      'civicrm_activity_calendar' => array(
        'title' => t('Calendar Items'),
        'help' => t('Displays each selected activity as a Calendar item.'),
        'handler' => 'calendar_plugin_row_civicrm',
        'path' => "$civicrm_module_path/modules/views/plugins",
        'base' => array('civicrm_activity'),
        'uses options' => TRUE,
        'uses fields' => TRUE,
        'type' => 'normal',
        'dao class' => 'CRM_Activity_DAO_Activity',
        'title field' => 'subject',
      ),
      'civicrm_case_calendar' => array(
        'title' => t('Case Items'),
        'help' => t('Displays each selected case as a Calendar item.'),
        'handler' => 'calendar_plugin_row_civicrm',
        'path' => "$civicrm_module_path/modules/views/plugins",
        'base' => array('civicrm_case'),
        'uses options' => TRUE,
        'uses fields' => TRUE,
        'type' => 'normal',
        'dao class' => 'CRM_Case_DAO_Case',
        'title field' => 'subject',
      ),
      'civicrm_mail_calendar' => array(
        'title' => t('Calendar Items'),
        'help' => t('Displays each selected mailing as a Calendar item.'),
        'handler' => 'calendar_plugin_row_civicrm',
        'path' => "$civicrm_module_path/modules/views/plugins",
        'base' => array('civicrm_mail'),
        'uses options' => TRUE,
        'uses fields' => TRUE,
        'type' => 'normal',
        'dao class' => 'CRM_Mailing_DAO_MailingJob',
        // @TODO come up with a better title field
        'title field' => 'mailing_id',
      ),
      'civicrm_campaign_calendar' => array(
        'title' => t('Calendar Items'),
        'help' => t('Displays each selected campaign as a Calendar item.'),
        'handler' => 'calendar_plugin_row_civicrm',
        'path' => "$civicrm_module_path/modules/views/plugins",
        'base' => array('civicrm_campaign'),
        'uses options' => TRUE,
        'uses fields' => TRUE,
        'type' => 'normal',
        'dao class' => 'CRM_Campaign_DAO_Campaign',
        'title field' => 'title',
      ),
    );
  }

  return $data;
}
