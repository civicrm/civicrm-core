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
 * CiviCRM default views
 * @return array|null
 */
function civicrm_views_default_views() {
  $views = NULL;

  if (module_exists('date_views') && module_exists('calendar')) {
    $view = new view();
    $view->name = 'civicrm_events';
    $view->description = 'Calendar of CiviCRM Events';
    $view->tag = 'civicrm';
    $view->base_table = 'civicrm_event';
    $view->human_name = 'CiviCRM Events';
    $view->core = 7;
    $view->api_version = '3.0';
    $view->disabled = TRUE;

    /* Display: Master */
    $handler = $view->new_display('default', 'Master', 'default');
    $handler->display->display_options['title'] = 'CiviCRM Event Calendar';
    $handler->display->display_options['access']['type'] = 'perm';
    $handler->display->display_options['access']['perm'] = 'view event info';
    $handler->display->display_options['cache']['type'] = 'none';
    $handler->display->display_options['query']['type'] = 'views_query';
    $handler->display->display_options['query']['options']['query_comment'] = FALSE;
    $handler->display->display_options['exposed_form']['type'] = 'basic';
    $handler->display->display_options['pager']['type'] = 'some';
    $handler->display->display_options['pager']['options']['items_per_page'] = '5';
    $handler->display->display_options['pager']['options']['offset'] = '0';
    $handler->display->display_options['style_plugin'] = 'calendar_style';
    $handler->display->display_options['style_options']['name_size'] = '3';
    $handler->display->display_options['style_options']['mini'] = '0';
    $handler->display->display_options['style_options']['with_weekno'] = '0';
    $handler->display->display_options['style_options']['multiday_theme'] = '1';
    $handler->display->display_options['style_options']['theme_style'] = '1';
    $handler->display->display_options['style_options']['max_items'] = '0';
    $handler->display->display_options['row_plugin'] = 'civicrm_event_calendar';
    $handler->display->display_options['row_options']['colors']['calendar_colors_type'] = array(
      'article' => '#ffffff',
      'page' => '#ffffff',
    );

    /* Field: CiviCRM Events: Title */
    $handler->display->display_options['fields']['title']['id'] = 'title';
    $handler->display->display_options['fields']['title']['table'] = 'civicrm_event';
    $handler->display->display_options['fields']['title']['field'] = 'title';
    $handler->display->display_options['fields']['title']['label'] = '';
    $handler->display->display_options['fields']['title']['alter']['alter_text'] = 0;
    $handler->display->display_options['fields']['title']['alter']['make_link'] = 0;
    $handler->display->display_options['fields']['title']['alter']['absolute'] = 0;
    $handler->display->display_options['fields']['title']['alter']['external'] = 0;
    $handler->display->display_options['fields']['title']['alter']['replace_spaces'] = 0;
    $handler->display->display_options['fields']['title']['alter']['trim_whitespace'] = 0;
    $handler->display->display_options['fields']['title']['alter']['nl2br'] = 0;
    $handler->display->display_options['fields']['title']['alter']['word_boundary'] = 1;
    $handler->display->display_options['fields']['title']['alter']['ellipsis'] = 1;
    $handler->display->display_options['fields']['title']['alter']['strip_tags'] = 0;
    $handler->display->display_options['fields']['title']['alter']['trim'] = 0;
    $handler->display->display_options['fields']['title']['alter']['html'] = 0;
    $handler->display->display_options['fields']['title']['element_label_colon'] = FALSE;
    $handler->display->display_options['fields']['title']['element_default_classes'] = 1;
    $handler->display->display_options['fields']['title']['hide_empty'] = 0;
    $handler->display->display_options['fields']['title']['empty_zero'] = 0;
    $handler->display->display_options['fields']['title']['hide_alter_empty'] = 0;

    /* Field: CiviCRM Events: Start Date */
    $handler->display->display_options['fields']['start_date']['id'] = 'start_date';
    $handler->display->display_options['fields']['start_date']['table'] = 'civicrm_event';
    $handler->display->display_options['fields']['start_date']['field'] = 'start_date';
    $handler->display->display_options['fields']['start_date']['label'] = '';
    $handler->display->display_options['fields']['start_date']['alter']['alter_text'] = 0;
    $handler->display->display_options['fields']['start_date']['alter']['make_link'] = 0;
    $handler->display->display_options['fields']['start_date']['alter']['absolute'] = 0;
    $handler->display->display_options['fields']['start_date']['alter']['external'] = 0;
    $handler->display->display_options['fields']['start_date']['alter']['replace_spaces'] = 0;
    $handler->display->display_options['fields']['start_date']['alter']['trim_whitespace'] = 0;
    $handler->display->display_options['fields']['start_date']['alter']['nl2br'] = 0;
    $handler->display->display_options['fields']['start_date']['alter']['word_boundary'] = 1;
    $handler->display->display_options['fields']['start_date']['alter']['ellipsis'] = 1;
    $handler->display->display_options['fields']['start_date']['alter']['strip_tags'] = 0;
    $handler->display->display_options['fields']['start_date']['alter']['trim'] = 0;
    $handler->display->display_options['fields']['start_date']['alter']['html'] = 0;
    $handler->display->display_options['fields']['start_date']['element_label_colon'] = FALSE;
    $handler->display->display_options['fields']['start_date']['element_default_classes'] = 1;
    $handler->display->display_options['fields']['start_date']['hide_empty'] = 0;
    $handler->display->display_options['fields']['start_date']['empty_zero'] = 0;
    $handler->display->display_options['fields']['start_date']['hide_alter_empty'] = 0;
    $handler->display->display_options['fields']['start_date']['date_format'] = 'long';

    /* Sort criterion: CiviCRM Events: Start Date */
    $handler->display->display_options['sorts']['start_date']['id'] = 'start_date';
    $handler->display->display_options['sorts']['start_date']['table'] = 'civicrm_event';
    $handler->display->display_options['sorts']['start_date']['field'] = 'start_date';

    /* Contextual filter: CiviCRM Events: Start Date */
    $handler->display->display_options['arguments']['start_date']['id'] = 'start_date';
    $handler->display->display_options['arguments']['start_date']['table'] = 'civicrm_event';
    $handler->display->display_options['arguments']['start_date']['field'] = 'start_date';
    $handler->display->display_options['arguments']['start_date']['default_action'] = 'default';
    $handler->display->display_options['arguments']['start_date']['default_argument_skip_url'] = 0;
    $handler->display->display_options['arguments']['start_date']['summary']['format'] = 'default_summary';
    $handler->display->display_options['arguments']['start_date']['granularity'] = 'year';
    $handler->display->display_options['arguments']['start_date']['add_delta'] = 'yes';
    $handler->display->display_options['arguments']['start_date']['date_fields'] = array(
      'civicrm_event.start_date' => 'civicrm_event.start_date',
    );

    /* Filter criterion: CiviCRM Events: Is Active */
    $handler->display->display_options['filters']['is_active']['id'] = 'is_active';
    $handler->display->display_options['filters']['is_active']['table'] = 'civicrm_event';
    $handler->display->display_options['filters']['is_active']['field'] = 'is_active';
    $handler->display->display_options['filters']['is_active']['value'] = '1';
    $handler->display->display_options['filters']['is_active']['group'] = 0;

    /* Filter criterion: CiviCRM Events: Is Public */
    $handler->display->display_options['filters']['is_public']['id'] = 'is_public';
    $handler->display->display_options['filters']['is_public']['table'] = 'civicrm_event';
    $handler->display->display_options['filters']['is_public']['field'] = 'is_public';
    $handler->display->display_options['filters']['is_public']['value'] = '1';
    $handler->display->display_options['filters']['is_public']['group'] = 0;

    /* Display: Month */
    $handler = $view->new_display('page', 'Month', 'page');
    $handler->display->display_options['display_description'] = 'Monthly calendar of events';
    $handler->display->display_options['defaults']['pager'] = FALSE;
    $handler->display->display_options['pager']['type'] = 'date_views_pager';
    $handler->display->display_options['pager']['options']['date_id'] = 'month';
    $handler->display->display_options['defaults']['style_plugin'] = FALSE;
    $handler->display->display_options['style_plugin'] = 'calendar_style';
    $handler->display->display_options['style_options']['name_size'] = '3';
    $handler->display->display_options['style_options']['mini'] = '0';
    $handler->display->display_options['style_options']['with_weekno'] = '0';
    $handler->display->display_options['style_options']['multiday_theme'] = '1';
    $handler->display->display_options['style_options']['theme_style'] = '1';
    $handler->display->display_options['style_options']['max_items'] = '0';
    $handler->display->display_options['defaults']['style_options'] = FALSE;
    $handler->display->display_options['defaults']['row_plugin'] = FALSE;
    $handler->display->display_options['row_plugin'] = 'civicrm_event_calendar';
    $handler->display->display_options['row_options']['colors']['calendar_colors_type'] = array(
      'article' => '#ffffff',
      'page' => '#ffffff',
    );
    $handler->display->display_options['defaults']['row_options'] = FALSE;
    $handler->display->display_options['defaults']['arguments'] = FALSE;

    /* Contextual filter: CiviCRM Events: Start Date */
    $handler->display->display_options['arguments']['start_date']['id'] = 'start_date';
    $handler->display->display_options['arguments']['start_date']['table'] = 'civicrm_event';
    $handler->display->display_options['arguments']['start_date']['field'] = 'start_date';
    $handler->display->display_options['arguments']['start_date']['default_action'] = 'default';
    $handler->display->display_options['arguments']['start_date']['default_argument_skip_url'] = 0;
    $handler->display->display_options['arguments']['start_date']['summary']['format'] = 'default_summary';
    $handler->display->display_options['arguments']['start_date']['add_delta'] = 'yes';
    $handler->display->display_options['arguments']['start_date']['date_fields'] = array(
      'civicrm_event.start_date' => 'civicrm_event.start_date',
    );
    $handler->display->display_options['path'] = 'events/month';

    /* Display: Week */
    $handler = $view->new_display('page', 'Week', 'page_1');
    $handler->display->display_options['display_description'] = 'Weekly calendar of events';
    $handler->display->display_options['defaults']['pager'] = FALSE;
    $handler->display->display_options['pager']['type'] = 'date_views_pager';
    $handler->display->display_options['pager']['options']['date_id'] = 'month';
    $handler->display->display_options['defaults']['style_plugin'] = FALSE;
    $handler->display->display_options['style_plugin'] = 'calendar_style';
    $handler->display->display_options['style_options']['calendar_type'] = 'week';
    $handler->display->display_options['style_options']['name_size'] = '3';
    $handler->display->display_options['style_options']['mini'] = '0';
    $handler->display->display_options['style_options']['with_weekno'] = '0';
    $handler->display->display_options['style_options']['multiday_theme'] = '1';
    $handler->display->display_options['style_options']['theme_style'] = '1';
    $handler->display->display_options['style_options']['max_items'] = '0';
    $handler->display->display_options['defaults']['style_options'] = FALSE;
    $handler->display->display_options['defaults']['row_plugin'] = FALSE;
    $handler->display->display_options['row_plugin'] = 'civicrm_event_calendar';
    $handler->display->display_options['row_options']['colors']['calendar_colors_type'] = array(
      'article' => '#ffffff',
      'page' => '#ffffff',
    );
    $handler->display->display_options['defaults']['row_options'] = FALSE;
    $handler->display->display_options['defaults']['arguments'] = FALSE;

    /* Contextual filter: CiviCRM Events: Start Date */
    $handler->display->display_options['arguments']['start_date']['id'] = 'start_date';
    $handler->display->display_options['arguments']['start_date']['table'] = 'civicrm_event';
    $handler->display->display_options['arguments']['start_date']['field'] = 'start_date';
    $handler->display->display_options['arguments']['start_date']['default_action'] = 'default';
    $handler->display->display_options['arguments']['start_date']['default_argument_skip_url'] = 0;
    $handler->display->display_options['arguments']['start_date']['summary']['format'] = 'default_summary';
    $handler->display->display_options['arguments']['start_date']['granularity'] = 'week';
    $handler->display->display_options['arguments']['start_date']['add_delta'] = 'yes';
    $handler->display->display_options['arguments']['start_date']['date_fields'] = array(
      'civicrm_event.start_date' => 'civicrm_event.start_date',
    );
    $handler->display->display_options['path'] = 'events/week';

    /* Display: Day */
    $handler = $view->new_display('page', 'Day', 'page_2');
    $handler->display->display_options['display_description'] = 'Daily calendar of events';
    $handler->display->display_options['defaults']['pager'] = FALSE;
    $handler->display->display_options['pager']['type'] = 'date_views_pager';
    $handler->display->display_options['pager']['options']['date_id'] = 'month';
    $handler->display->display_options['defaults']['style_plugin'] = FALSE;
    $handler->display->display_options['style_plugin'] = 'calendar_style';
    $handler->display->display_options['style_options']['calendar_type'] = 'day';
    $handler->display->display_options['style_options']['name_size'] = '3';
    $handler->display->display_options['style_options']['mini'] = '0';
    $handler->display->display_options['style_options']['with_weekno'] = '0';
    $handler->display->display_options['style_options']['multiday_theme'] = '1';
    $handler->display->display_options['style_options']['theme_style'] = '1';
    $handler->display->display_options['style_options']['max_items'] = '0';
    $handler->display->display_options['defaults']['style_options'] = FALSE;
    $handler->display->display_options['defaults']['row_plugin'] = FALSE;
    $handler->display->display_options['row_plugin'] = 'civicrm_event_calendar';
    $handler->display->display_options['row_options']['colors']['calendar_colors_type'] = array(
      'article' => '#ffffff',
      'page' => '#ffffff',
    );
    $handler->display->display_options['defaults']['row_options'] = FALSE;
    $handler->display->display_options['defaults']['arguments'] = FALSE;

    /* Contextual filter: CiviCRM Events: Start Date */
    $handler->display->display_options['arguments']['start_date']['id'] = 'start_date';
    $handler->display->display_options['arguments']['start_date']['table'] = 'civicrm_event';
    $handler->display->display_options['arguments']['start_date']['field'] = 'start_date';
    $handler->display->display_options['arguments']['start_date']['default_action'] = 'default';
    $handler->display->display_options['arguments']['start_date']['default_argument_skip_url'] = 0;
    $handler->display->display_options['arguments']['start_date']['summary']['format'] = 'default_summary';
    $handler->display->display_options['arguments']['start_date']['granularity'] = 'day';
    $handler->display->display_options['arguments']['start_date']['add_delta'] = 'yes';
    $handler->display->display_options['arguments']['start_date']['date_fields'] = array(
      'civicrm_event.start_date' => 'civicrm_event.start_date',
    );
    $handler->display->display_options['path'] = 'events/day';

    /* Display: Year */
    $handler = $view->new_display('page', 'Year', 'page_3');
    $handler->display->display_options['display_description'] = 'Annual calendar of events';
    $handler->display->display_options['defaults']['pager'] = FALSE;
    $handler->display->display_options['pager']['type'] = 'date_views_pager';
    $handler->display->display_options['pager']['options']['date_id'] = 'month';
    $handler->display->display_options['defaults']['style_plugin'] = FALSE;
    $handler->display->display_options['style_plugin'] = 'calendar_style';
    $handler->display->display_options['style_options']['calendar_type'] = 'year';
    $handler->display->display_options['style_options']['name_size'] = '3';
    $handler->display->display_options['style_options']['mini'] = '0';
    $handler->display->display_options['style_options']['with_weekno'] = '0';
    $handler->display->display_options['style_options']['multiday_theme'] = '1';
    $handler->display->display_options['style_options']['theme_style'] = '1';
    $handler->display->display_options['style_options']['max_items'] = '0';
    $handler->display->display_options['defaults']['style_options'] = FALSE;
    $handler->display->display_options['defaults']['row_plugin'] = FALSE;
    $handler->display->display_options['row_plugin'] = 'civicrm_event_calendar';
    $handler->display->display_options['row_options']['colors']['calendar_colors_type'] = array(
      'article' => '#ffffff',
      'page' => '#ffffff',
    );
    $handler->display->display_options['defaults']['row_options'] = FALSE;
    $handler->display->display_options['defaults']['arguments'] = FALSE;

    /* Contextual filter: CiviCRM Events: Start Date */
    $handler->display->display_options['arguments']['start_date']['id'] = 'start_date';
    $handler->display->display_options['arguments']['start_date']['table'] = 'civicrm_event';
    $handler->display->display_options['arguments']['start_date']['field'] = 'start_date';
    $handler->display->display_options['arguments']['start_date']['default_action'] = 'default';
    $handler->display->display_options['arguments']['start_date']['default_argument_skip_url'] = 0;
    $handler->display->display_options['arguments']['start_date']['summary']['format'] = 'default_summary';
    $handler->display->display_options['arguments']['start_date']['granularity'] = 'year';
    $handler->display->display_options['arguments']['start_date']['add_delta'] = 'yes';
    $handler->display->display_options['arguments']['start_date']['date_fields'] = array(
      'civicrm_event.start_date' => 'civicrm_event.start_date',
    );
    $handler->display->display_options['path'] = 'events/year';

    /* Display: Block */
    $handler = $view->new_display('block', 'Block', 'block_1');
    $handler->display->display_options['defaults']['style_plugin'] = FALSE;
    $handler->display->display_options['style_plugin'] = 'calendar_style';
    $handler->display->display_options['style_options']['name_size'] = '3';
    $handler->display->display_options['style_options']['mini'] = '0';
    $handler->display->display_options['style_options']['with_weekno'] = '0';
    $handler->display->display_options['style_options']['multiday_theme'] = '1';
    $handler->display->display_options['style_options']['theme_style'] = '1';
    $handler->display->display_options['style_options']['max_items'] = '0';
    $handler->display->display_options['defaults']['style_options'] = FALSE;
    $handler->display->display_options['defaults']['row_plugin'] = FALSE;
    $handler->display->display_options['row_plugin'] = 'civicrm_event_calendar';
    $handler->display->display_options['row_options']['colors']['calendar_colors_type'] = array(
      'article' => '#ffffff',
      'page' => '#ffffff',
    );
    $handler->display->display_options['defaults']['row_options'] = FALSE;
    $handler->display->display_options['defaults']['arguments'] = FALSE;

    /* Contextual filter: CiviCRM Events: Start Date */
    $handler->display->display_options['arguments']['start_date']['id'] = 'start_date';
    $handler->display->display_options['arguments']['start_date']['table'] = 'civicrm_event';
    $handler->display->display_options['arguments']['start_date']['field'] = 'start_date';
    $handler->display->display_options['arguments']['start_date']['default_action'] = 'default';
    $handler->display->display_options['arguments']['start_date']['default_argument_skip_url'] = 0;
    $handler->display->display_options['arguments']['start_date']['summary']['format'] = 'default_summary';
    $handler->display->display_options['arguments']['start_date']['add_delta'] = 'yes';
    $handler->display->display_options['arguments']['start_date']['date_fields'] = array(
      'civicrm_event.start_date' => 'civicrm_event.start_date',
    );

    /* Display: Upcoming */
    $handler = $view->new_display('block', 'Upcoming', 'block_2');
    $handler->display->display_options['display_description'] = 'Upcoming Events';
    $handler->display->display_options['defaults']['style_plugin'] = FALSE;
    $handler->display->display_options['style_plugin'] = 'list';
    $handler->display->display_options['defaults']['style_options'] = FALSE;
    $handler->display->display_options['defaults']['row_plugin'] = FALSE;
    $handler->display->display_options['row_plugin'] = 'fields';
    $handler->display->display_options['row_options']['hide_empty'] = 0;
    $handler->display->display_options['row_options']['default_field_elements'] = 1;
    $handler->display->display_options['defaults']['row_options'] = FALSE;
    $handler->display->display_options['defaults']['arguments'] = FALSE;
    $translatables['test_events'] = array(
      t('Master'),
      t('CiviCRM Events'),
      t('more'),
      t('Apply'),
      t('Reset'),
      t('Sort by'),
      t('Asc'),
      t('Desc'),
      t('All'),
      t('Month'),
      t('Monthly calendar of events'),
      t('Week'),
      t('Weekly calendar of events'),
      t('Day'),
      t('Daily calendar of events'),
      t('Year'),
      t('Annual calendar of events'),
      t('Block'),
      t('Upcoming'),
      t('Upcoming Events'),
    );
    $views[] = $view;

    $view = new view();
    $view->name = 'civicrm_my_activities';
    $view->description = 'Calendar of CiviCRM Activities - Assigned to logged in user';
    $view->tag = 'civicrm';
    $view->base_table = 'civicrm_activity';
    $view->human_name = 'CiviCRM My Activities';
    $view->core = 7;
    $view->api_version = '3.0';
    $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

    /* Display: Master */
    $handler = $view->new_display('default', 'Master', 'default');
    $handler->display->display_options['title'] = 'CiviCRM My Activities Calendar';
    $handler->display->display_options['use_more_always'] = FALSE;
    $handler->display->display_options['access']['type'] = 'perm';
    $handler->display->display_options['access']['perm'] = 'view all activities';
    $handler->display->display_options['cache']['type'] = 'none';
    $handler->display->display_options['query']['type'] = 'views_query';
    $handler->display->display_options['query']['options']['query_comment'] = FALSE;
    $handler->display->display_options['exposed_form']['type'] = 'basic';
    $handler->display->display_options['exposed_form']['options']['reset_button_label'] = 'Reset';
    $handler->display->display_options['pager']['type'] = 'some';
    $handler->display->display_options['pager']['options']['items_per_page'] = '5';
    $handler->display->display_options['pager']['options']['offset'] = '0';
    $handler->display->display_options['style_plugin'] = 'calendar_style';
    $handler->display->display_options['style_options']['name_size'] = '3';
    $handler->display->display_options['style_options']['mini'] = '0';
    $handler->display->display_options['style_options']['with_weekno'] = '0';
    $handler->display->display_options['style_options']['multiday_theme'] = '1';
    $handler->display->display_options['style_options']['theme_style'] = '1';
    $handler->display->display_options['style_options']['max_items'] = '0';
    $handler->display->display_options['row_plugin'] = 'civicrm_event_calendar';
    $handler->display->display_options['row_options']['colors']['calendar_colors_type'] = array(
      'article' => '#ffffff',
      'page' => '#ffffff',
    );

    /* Relationship: CiviCRM Activity Assignments: Assignee Contact ID */
    $handler->display->display_options['relationships']['assignee_contact_id']['id'] = 'assignee_contact_id';
    $handler->display->display_options['relationships']['assignee_contact_id']['table'] = 'civicrm_activity_assignment';
    $handler->display->display_options['relationships']['assignee_contact_id']['field'] = 'assignee_contact_id';

    /* Field: CiviCRM Activities: Scheduled Activity Date */
    $handler->display->display_options['fields']['activity_date_time']['id'] = 'activity_date_time';
    $handler->display->display_options['fields']['activity_date_time']['table'] = 'civicrm_activity';
    $handler->display->display_options['fields']['activity_date_time']['field'] = 'activity_date_time';
    $handler->display->display_options['fields']['activity_date_time']['label'] = '';
    $handler->display->display_options['fields']['activity_date_time']['element_label_colon'] = FALSE;
    $handler->display->display_options['fields']['activity_date_time']['hide_alter_empty'] = FALSE;
    $handler->display->display_options['fields']['activity_date_time']['date_format'] = 'short';
    $handler->display->display_options['fields']['activity_date_time']['custom_date_format'] = 'g:i';

    /* Field: CiviCRM Activities: Activity Type */
    $handler->display->display_options['fields']['activity_type']['id'] = 'activity_type';
    $handler->display->display_options['fields']['activity_type']['table'] = 'civicrm_activity';
    $handler->display->display_options['fields']['activity_type']['field'] = 'activity_type';
    $handler->display->display_options['fields']['activity_type']['label'] = '';
    $handler->display->display_options['fields']['activity_type']['element_label_colon'] = FALSE;

    /* Field: CiviCRM Activities: Activity Status */
    $handler->display->display_options['fields']['status']['id'] = 'status';
    $handler->display->display_options['fields']['status']['table'] = 'civicrm_activity';
    $handler->display->display_options['fields']['status']['field'] = 'status';
    $handler->display->display_options['fields']['status']['label'] = '';
    $handler->display->display_options['fields']['status']['element_label_colon'] = FALSE;

    /* Field: CiviCRM Activities: Onderwerp */
    $handler->display->display_options['fields']['subject']['id'] = 'subject';
    $handler->display->display_options['fields']['subject']['table'] = 'civicrm_activity';
    $handler->display->display_options['fields']['subject']['field'] = 'subject';
    $handler->display->display_options['fields']['subject']['label'] = '';
    $handler->display->display_options['fields']['subject']['element_label_colon'] = FALSE;
    $handler->display->display_options['fields']['subject']['hide_alter_empty'] = FALSE;

    /* Sort criterion: CiviCRM Activities: Scheduled Activity Date */
    $handler->display->display_options['sorts']['activity_date_time']['id'] = 'activity_date_time';
    $handler->display->display_options['sorts']['activity_date_time']['table'] = 'civicrm_activity';
    $handler->display->display_options['sorts']['activity_date_time']['field'] = 'activity_date_time';

    /* Contextual filter: CiviCRM Activities: Scheduled Activity Date */
    $handler->display->display_options['arguments']['activity_date_time']['id'] = 'activity_date_time';
    $handler->display->display_options['arguments']['activity_date_time']['table'] = 'civicrm_activity';
    $handler->display->display_options['arguments']['activity_date_time']['field'] = 'activity_date_time';
    $handler->display->display_options['arguments']['activity_date_time']['default_action'] = 'default';
    $handler->display->display_options['arguments']['activity_date_time']['exception']['title'] = 'All';
    $handler->display->display_options['arguments']['activity_date_time']['summary']['format'] = 'default_summary';
    $handler->display->display_options['arguments']['activity_date_time']['granularity'] = 'year';
    $handler->display->display_options['arguments']['activity_date_time']['add_delta'] = 'yes';
    $handler->display->display_options['arguments']['activity_date_time']['date_fields'] = array(
      'civicrm_activity.activity_date_time' => 'civicrm_activity.activity_date_time',
    );

    /* Contextual filter: CiviCRM Contacts: Contact ID */
    $handler->display->display_options['arguments']['id']['id'] = 'id';
    $handler->display->display_options['arguments']['id']['table'] = 'civicrm_contact';
    $handler->display->display_options['arguments']['id']['field'] = 'id';
    $handler->display->display_options['arguments']['id']['relationship'] = 'assignee_contact_id';
    $handler->display->display_options['arguments']['id']['default_action'] = 'default';
    $handler->display->display_options['arguments']['id']['exception']['title'] = 'All';
    $handler->display->display_options['arguments']['id']['default_argument_type'] = 'php';
    $handler->display->display_options['arguments']['id']['default_argument_options']['code'] = 'global $user;
	civicrm_initialize( );
	$results=civicrm_api("UFMatch","get", array (version => \'3\',\'sequential\' =>\'1\', \'uf_id\' =>$user->uid));

	return $results["id"]?$results["id"]:"";';
    $handler->display->display_options['arguments']['id']['summary']['number_of_records'] = '0';
    $handler->display->display_options['arguments']['id']['summary']['format'] = 'default_summary';
    $handler->display->display_options['arguments']['id']['summary_options']['items_per_page'] = '25';

    /* Filter criterion: CiviCRM Activities: Activity Type */
    $handler->display->display_options['filters']['activity_type']['id'] = 'activity_type';
    $handler->display->display_options['filters']['activity_type']['table'] = 'civicrm_activity';
    $handler->display->display_options['filters']['activity_type']['field'] = 'activity_type';
    $handler->display->display_options['filters']['activity_type']['value'] = array(
      1 => '1',
      2 => '2',
    );
    $handler->display->display_options['filters']['activity_type']['group'] = 1;

    /* Display: Month */
    $handler = $view->new_display('page', 'Month', 'page');
    $handler->display->display_options['display_description'] = 'Monthly calendar of activities';
    $handler->display->display_options['defaults']['hide_admin_links'] = FALSE;
    $handler->display->display_options['defaults']['pager'] = FALSE;
    $handler->display->display_options['pager']['type'] = 'date_views_pager';
    $handler->display->display_options['pager']['options']['date_id'] = 'month';
    $handler->display->display_options['defaults']['style_plugin'] = FALSE;
    $handler->display->display_options['style_plugin'] = 'calendar_style';
    $handler->display->display_options['style_options']['name_size'] = '3';
    $handler->display->display_options['style_options']['mini'] = '0';
    $handler->display->display_options['style_options']['with_weekno'] = '0';
    $handler->display->display_options['style_options']['multiday_theme'] = '1';
    $handler->display->display_options['style_options']['theme_style'] = '1';
    $handler->display->display_options['style_options']['max_items'] = '0';
    $handler->display->display_options['defaults']['style_options'] = FALSE;
    $handler->display->display_options['defaults']['row_plugin'] = FALSE;
    $handler->display->display_options['row_plugin'] = 'civicrm_activity_calendar';
    $handler->display->display_options['defaults']['row_options'] = FALSE;
    $handler->display->display_options['defaults']['arguments'] = FALSE;

    /* Contextual filter: CiviCRM Activities: Scheduled Activity Date */
    $handler->display->display_options['arguments']['activity_date_time']['id'] = 'activity_date_time';
    $handler->display->display_options['arguments']['activity_date_time']['table'] = 'civicrm_activity';
    $handler->display->display_options['arguments']['activity_date_time']['field'] = 'activity_date_time';
    $handler->display->display_options['arguments']['activity_date_time']['default_action'] = 'default';
    $handler->display->display_options['arguments']['activity_date_time']['exception']['title'] = 'All';
    $handler->display->display_options['arguments']['activity_date_time']['add_delta'] = 'yes';
    $handler->display->display_options['arguments']['activity_date_time']['use_fromto'] = 'no';
    $handler->display->display_options['arguments']['activity_date_time']['date_fields'] = array(
      'civicrm_activity.activity_date_time' => 'civicrm_activity.activity_date_time',
    );

    /* Contextual filter: CiviCRM Contacts: Contact ID */
    $handler->display->display_options['arguments']['id']['id'] = 'id';
    $handler->display->display_options['arguments']['id']['table'] = 'civicrm_contact';
    $handler->display->display_options['arguments']['id']['field'] = 'id';
    $handler->display->display_options['arguments']['id']['relationship'] = 'assignee_contact_id';
    $handler->display->display_options['arguments']['id']['default_action'] = 'default';
    $handler->display->display_options['arguments']['id']['exception']['title'] = 'All';
    $handler->display->display_options['arguments']['id']['default_argument_type'] = 'php';
    $handler->display->display_options['arguments']['id']['default_argument_options']['code'] = 'global $user;
	civicrm_initialize( );
	$results=civicrm_api("UFMatch","get", array (version => \'3\',\'sequential\' =>\'1\', \'uf_id\' =>$user->uid));

	return $results["id"]?$results["id"]:""; ';
    $handler->display->display_options['arguments']['id']['summary']['number_of_records'] = '0';
    $handler->display->display_options['arguments']['id']['summary']['format'] = 'default_summary';
    $handler->display->display_options['arguments']['id']['summary_options']['items_per_page'] = '25';
    $handler->display->display_options['path'] = 'myactivities/month';

    /* Display: Week */
    $handler = $view->new_display('page', 'Week', 'page_1');
    $handler->display->display_options['display_description'] = 'Weekly calendar of activities';
    $handler->display->display_options['defaults']['hide_admin_links'] = FALSE;
    $handler->display->display_options['defaults']['pager'] = FALSE;
    $handler->display->display_options['pager']['type'] = 'date_views_pager';
    $handler->display->display_options['pager']['options']['date_id'] = 'month';
    $handler->display->display_options['defaults']['style_plugin'] = FALSE;
    $handler->display->display_options['style_plugin'] = 'calendar_style';
    $handler->display->display_options['style_options']['calendar_type'] = 'week';
    $handler->display->display_options['style_options']['name_size'] = '3';
    $handler->display->display_options['style_options']['mini'] = '0';
    $handler->display->display_options['style_options']['with_weekno'] = '0';
    $handler->display->display_options['style_options']['multiday_theme'] = '1';
    $handler->display->display_options['style_options']['theme_style'] = '1';
    $handler->display->display_options['style_options']['max_items'] = '0';
    $handler->display->display_options['defaults']['style_options'] = FALSE;
    $handler->display->display_options['defaults']['row_plugin'] = FALSE;
    $handler->display->display_options['row_plugin'] = 'civicrm_activity_calendar';
    $handler->display->display_options['defaults']['row_options'] = FALSE;
    $handler->display->display_options['defaults']['arguments'] = FALSE;

    /* Contextual filter: CiviCRM Activities: Scheduled Activity Date */
    $handler->display->display_options['arguments']['activity_date_time']['id'] = 'activity_date_time';
    $handler->display->display_options['arguments']['activity_date_time']['table'] = 'civicrm_activity';
    $handler->display->display_options['arguments']['activity_date_time']['field'] = 'activity_date_time';
    $handler->display->display_options['arguments']['activity_date_time']['default_action'] = 'default';
    $handler->display->display_options['arguments']['activity_date_time']['exception']['title'] = 'All';
    $handler->display->display_options['arguments']['activity_date_time']['summary']['format'] = 'default_summary';
    $handler->display->display_options['arguments']['activity_date_time']['granularity'] = 'week';
    $handler->display->display_options['arguments']['activity_date_time']['add_delta'] = 'yes';
    $handler->display->display_options['arguments']['activity_date_time']['date_fields'] = array(
      'civicrm_activity.activity_date_time' => 'civicrm_activity.activity_date_time',
    );

    /* Contextual filter: CiviCRM Contacts: Contact ID */
    $handler->display->display_options['arguments']['id']['id'] = 'id';
    $handler->display->display_options['arguments']['id']['table'] = 'civicrm_contact';
    $handler->display->display_options['arguments']['id']['field'] = 'id';
    $handler->display->display_options['arguments']['id']['relationship'] = 'assignee_contact_id';
    $handler->display->display_options['arguments']['id']['default_action'] = 'default';
    $handler->display->display_options['arguments']['id']['exception']['title'] = 'All';
    $handler->display->display_options['arguments']['id']['default_argument_type'] = 'php';
    $handler->display->display_options['arguments']['id']['default_argument_options']['code'] = '
        global $user;
	civicrm_initialize( );
	$results=civicrm_api("UFMatch","get", array (version => \'3\',\'sequential\' =>\'1\', \'uf_id\' =>$user->uid));

	return $results["id"]?$results["id"]:"";
      ';
    $handler->display->display_options['arguments']['id']['summary']['number_of_records'] = '0';
    $handler->display->display_options['arguments']['id']['summary']['format'] = 'default_summary';
    $handler->display->display_options['arguments']['id']['summary_options']['items_per_page'] = '25';
    $handler->display->display_options['path'] = 'myactivities/week';

    /* Display: Day */
    $handler = $view->new_display('page', 'Day', 'page_2');
    $handler->display->display_options['display_description'] = 'Daily calendar of activities';
    $handler->display->display_options['defaults']['hide_admin_links'] = FALSE;
    $handler->display->display_options['defaults']['pager'] = FALSE;
    $handler->display->display_options['pager']['type'] = 'date_views_pager';
    $handler->display->display_options['pager']['options']['date_id'] = 'month';
    $handler->display->display_options['defaults']['style_plugin'] = FALSE;
    $handler->display->display_options['style_plugin'] = 'calendar_style';
    $handler->display->display_options['style_options']['calendar_type'] = 'day';
    $handler->display->display_options['style_options']['name_size'] = '3';
    $handler->display->display_options['style_options']['mini'] = '0';
    $handler->display->display_options['style_options']['with_weekno'] = '0';
    $handler->display->display_options['style_options']['multiday_theme'] = '1';
    $handler->display->display_options['style_options']['theme_style'] = '1';
    $handler->display->display_options['style_options']['max_items'] = '0';
    $handler->display->display_options['defaults']['style_options'] = FALSE;
    $handler->display->display_options['defaults']['row_plugin'] = FALSE;
    $handler->display->display_options['row_plugin'] = 'civicrm_activity_calendar';
    $handler->display->display_options['defaults']['row_options'] = FALSE;
    $handler->display->display_options['defaults']['arguments'] = FALSE;

    /* Contextual filter: CiviCRM Activities: Scheduled Activity Date */
    $handler->display->display_options['arguments']['activity_date_time']['id'] = 'activity_date_time';
    $handler->display->display_options['arguments']['activity_date_time']['table'] = 'civicrm_activity';
    $handler->display->display_options['arguments']['activity_date_time']['field'] = 'activity_date_time';
    $handler->display->display_options['arguments']['activity_date_time']['default_action'] = 'default';
    $handler->display->display_options['arguments']['activity_date_time']['exception']['title'] = 'All';
    $handler->display->display_options['arguments']['activity_date_time']['summary']['format'] = 'default_summary';
    $handler->display->display_options['arguments']['activity_date_time']['granularity'] = 'day';
    $handler->display->display_options['arguments']['activity_date_time']['add_delta'] = 'yes';
    $handler->display->display_options['arguments']['activity_date_time']['date_fields'] = array(
      'civicrm_activity.activity_date_time' => 'civicrm_activity.activity_date_time',
    );

    /* Contextual filter: CiviCRM Contacts: Contact ID */
    $handler->display->display_options['arguments']['id']['id'] = 'id';
    $handler->display->display_options['arguments']['id']['table'] = 'civicrm_contact';
    $handler->display->display_options['arguments']['id']['field'] = 'id';
    $handler->display->display_options['arguments']['id']['relationship'] = 'assignee_contact_id';
    $handler->display->display_options['arguments']['id']['default_action'] = 'default';
    $handler->display->display_options['arguments']['id']['exception']['title'] = 'All';
    $handler->display->display_options['arguments']['id']['default_argument_type'] = 'php';
    $handler->display->display_options['arguments']['id']['default_argument_options']['code'] = 'global $user;
	civicrm_initialize( );
	$results=civicrm_api("UFMatch","get", array (version => \'3\',\'sequential\' =>\'1\', \'uf_id\' =>$user->uid));

	return $results["id"]? $results["id"]:"";';
    $handler->display->display_options['arguments']['id']['summary']['number_of_records'] = '0';
    $handler->display->display_options['arguments']['id']['summary']['format'] = 'default_summary';
    $handler->display->display_options['arguments']['id']['summary_options']['items_per_page'] = '25';
    $handler->display->display_options['path'] = 'myactivities/day';

    /* Display: Year */
    $handler = $view->new_display('page', 'Year', 'page_3');
    $handler->display->display_options['display_description'] = 'Annual calendar of activities';
    $handler->display->display_options['defaults']['hide_admin_links'] = FALSE;
    $handler->display->display_options['defaults']['pager'] = FALSE;
    $handler->display->display_options['pager']['type'] = 'date_views_pager';
    $handler->display->display_options['pager']['options']['date_id'] = 'month';
    $handler->display->display_options['defaults']['style_plugin'] = FALSE;
    $handler->display->display_options['style_plugin'] = 'calendar_style';
    $handler->display->display_options['style_options']['calendar_type'] = 'year';
    $handler->display->display_options['style_options']['name_size'] = '3';
    $handler->display->display_options['style_options']['mini'] = '0';
    $handler->display->display_options['style_options']['with_weekno'] = '0';
    $handler->display->display_options['style_options']['multiday_theme'] = '1';
    $handler->display->display_options['style_options']['theme_style'] = '1';
    $handler->display->display_options['style_options']['max_items'] = '0';
    $handler->display->display_options['defaults']['style_options'] = FALSE;
    $handler->display->display_options['defaults']['row_plugin'] = FALSE;
    $handler->display->display_options['row_plugin'] = 'civicrm_activity_calendar';
    $handler->display->display_options['defaults']['row_options'] = FALSE;
    $handler->display->display_options['defaults']['arguments'] = FALSE;

    /* Contextual filter: CiviCRM Activities: Scheduled Activity Date */
    $handler->display->display_options['arguments']['activity_date_time']['id'] = 'activity_date_time';
    $handler->display->display_options['arguments']['activity_date_time']['table'] = 'civicrm_activity';
    $handler->display->display_options['arguments']['activity_date_time']['field'] = 'activity_date_time';
    $handler->display->display_options['arguments']['activity_date_time']['default_action'] = 'default';
    $handler->display->display_options['arguments']['activity_date_time']['exception']['title'] = 'All';
    $handler->display->display_options['arguments']['activity_date_time']['summary']['format'] = 'default_summary';
    $handler->display->display_options['arguments']['activity_date_time']['granularity'] = 'year';
    $handler->display->display_options['arguments']['activity_date_time']['add_delta'] = 'yes';
    $handler->display->display_options['arguments']['activity_date_time']['date_fields'] = array(
      'civicrm_activity.activity_date_time' => 'civicrm_activity.activity_date_time',
    );

    /* Contextual filter: CiviCRM Contacts: Contact ID */
    $handler->display->display_options['arguments']['id']['id'] = 'id';
    $handler->display->display_options['arguments']['id']['table'] = 'civicrm_contact';
    $handler->display->display_options['arguments']['id']['field'] = 'id';
    $handler->display->display_options['arguments']['id']['relationship'] = 'assignee_contact_id';
    $handler->display->display_options['arguments']['id']['default_action'] = 'default';
    $handler->display->display_options['arguments']['id']['exception']['title'] = 'All';
    $handler->display->display_options['arguments']['id']['default_argument_type'] = 'php';
    $handler->display->display_options['arguments']['id']['default_argument_options']['code'] = 'global $user;
	civicrm_initialize( );
	$results=civicrm_api("UFMatch","get", array (version => \'3\',\'sequential\' =>\'1\', \'uf_id\' =>$user->uid));

	return $results["id"]?$results["id"]:"";';
    $handler->display->display_options['arguments']['id']['summary']['number_of_records'] = '0';
    $handler->display->display_options['arguments']['id']['summary']['format'] = 'default_summary';
    $handler->display->display_options['arguments']['id']['summary_options']['items_per_page'] = '25';
    $handler->display->display_options['path'] = 'myactivities/year';

    /* Display: Block */
    $handler = $view->new_display('block', 'Block', 'block_1');
    $handler->display->display_options['defaults']['hide_admin_links'] = FALSE;
    $handler->display->display_options['defaults']['style_plugin'] = FALSE;
    $handler->display->display_options['style_plugin'] = 'calendar_style';
    $handler->display->display_options['style_options']['name_size'] = '3';
    $handler->display->display_options['style_options']['mini'] = '0';
    $handler->display->display_options['style_options']['with_weekno'] = '0';
    $handler->display->display_options['style_options']['multiday_theme'] = '1';
    $handler->display->display_options['style_options']['theme_style'] = '1';
    $handler->display->display_options['style_options']['max_items'] = '0';
    $handler->display->display_options['defaults']['style_options'] = FALSE;
    $handler->display->display_options['defaults']['row_plugin'] = FALSE;
    $handler->display->display_options['row_plugin'] = 'civicrm_activity_calendar';
    $handler->display->display_options['defaults']['row_options'] = FALSE;

    /* Display: Scheduled */
    $handler = $view->new_display('block', 'Scheduled', 'block_2');
    $handler->display->display_options['display_description'] = 'Scheduled Activities';
    $handler->display->display_options['defaults']['hide_admin_links'] = FALSE;
    $handler->display->display_options['defaults']['style_plugin'] = FALSE;
    $handler->display->display_options['style_plugin'] = 'list';
    $handler->display->display_options['defaults']['style_options'] = FALSE;
    $handler->display->display_options['defaults']['row_plugin'] = FALSE;
    $handler->display->display_options['row_plugin'] = 'fields';
    $handler->display->display_options['defaults']['row_options'] = FALSE;
    $handler->display->display_options['defaults']['fields'] = FALSE;

    /* Field: CiviCRM Activities: Scheduled Activity Date */
    $handler->display->display_options['fields']['activity_date_time']['id'] = 'activity_date_time';
    $handler->display->display_options['fields']['activity_date_time']['table'] = 'civicrm_activity';
    $handler->display->display_options['fields']['activity_date_time']['field'] = 'activity_date_time';
    $handler->display->display_options['fields']['activity_date_time']['label'] = '';
    $handler->display->display_options['fields']['activity_date_time']['element_label_colon'] = FALSE;
    $handler->display->display_options['fields']['activity_date_time']['hide_alter_empty'] = FALSE;
    $handler->display->display_options['fields']['activity_date_time']['date_format'] = 'long';

    /* Field: CiviCRM Activities: Activity Type */
    $handler->display->display_options['fields']['activity_type']['id'] = 'activity_type';
    $handler->display->display_options['fields']['activity_type']['table'] = 'civicrm_activity';
    $handler->display->display_options['fields']['activity_type']['field'] = 'activity_type';
    $handler->display->display_options['fields']['activity_type']['label'] = '';
    $handler->display->display_options['fields']['activity_type']['element_label_colon'] = FALSE;

    /* Field: CiviCRM Activities: Subject */
    $handler->display->display_options['fields']['subject']['id'] = 'subject';
    $handler->display->display_options['fields']['subject']['table'] = 'civicrm_activity';
    $handler->display->display_options['fields']['subject']['field'] = 'subject';
    $handler->display->display_options['fields']['subject']['label'] = '';
    $handler->display->display_options['fields']['subject']['element_label_colon'] = FALSE;
    $handler->display->display_options['fields']['subject']['hide_alter_empty'] = FALSE;
    $handler->display->display_options['defaults']['arguments'] = FALSE;

    /* Contextual filter: CiviCRM Contacts: Contact ID */
    $handler->display->display_options['arguments']['id']['id'] = 'id';
    $handler->display->display_options['arguments']['id']['table'] = 'civicrm_contact';
    $handler->display->display_options['arguments']['id']['field'] = 'id';
    $handler->display->display_options['arguments']['id']['relationship'] = 'assignee_contact_id';
    $handler->display->display_options['arguments']['id']['default_action'] = 'default';
    $handler->display->display_options['arguments']['id']['exception']['title'] = 'All';
    $handler->display->display_options['arguments']['id']['default_argument_type'] = 'php';
    $handler->display->display_options['arguments']['id']['default_argument_options']['code'] = 'global $user;
	civicrm_initialize( );
	$results=civicrm_api("UFMatch","get", array (version => \'3\',\'sequential\' =>\'1\', \'uf_id\' =>$user->uid));

	return $results["id"]?$results["id"]:"";';
    $handler->display->display_options['arguments']['id']['summary']['number_of_records'] = '0';
    $handler->display->display_options['arguments']['id']['summary']['format'] = 'default_summary';
    $handler->display->display_options['arguments']['id']['summary_options']['items_per_page'] = '25';
    $handler->display->display_options['defaults']['filter_groups'] = FALSE;
    $handler->display->display_options['defaults']['filters'] = FALSE;

    /* Filter criterion: CiviCRM Activities: Activity Type */
    $handler->display->display_options['filters']['activity_type']['id'] = 'activity_type';
    $handler->display->display_options['filters']['activity_type']['table'] = 'civicrm_activity';
    $handler->display->display_options['filters']['activity_type']['field'] = 'activity_type';
    $handler->display->display_options['filters']['activity_type']['value'] = array(
      1 => '1',
      2 => '2',
    );
    $handler->display->display_options['filters']['activity_type']['group'] = 1;

    /* Filter criterion: CiviCRM Activities: Activity Status */
    $handler->display->display_options['filters']['status']['id'] = 'status';
    $handler->display->display_options['filters']['status']['table'] = 'civicrm_activity';
    $handler->display->display_options['filters']['status']['field'] = 'status';
    $handler->display->display_options['filters']['status']['value'] = array(
      1 => '1',
    );
    $handler->display->display_options['filters']['status']['group'] = 1;
    $translatables['civicrm_my_activities'] = array(
      t('Master'),
      t('CiviCRM My Activities Calendar'),
      t('More'),
      t('Apply'),
      t('Reset'),
      t('Sort by'),
      t('Asc'),
      t('Desc'),
      t('CiviCRM Assignee Contact ID'),
      t('All'),
      t('Month'),
      t('Monthly calendar of activities'),
      t('Week'),
      t('Weekly calendar of activities'),
      t('Day'),
      t('Daily calendar of activities'),
      t('Year'),
      t('Annual calendar of activities'),
      t('Block'),
      t('Scheduled'),
      t('Scheduled Activities'),
    );

    $views[] = $view;
  }

  return $views;
}
