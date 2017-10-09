<?php

/**
 * @file: This file is no longer needed and does not work with the current version of calendar
 * @todo: Remove in future version of CiviCRM
 */

/**
 * Plugin which creates a view on the resulting object
 * and formats it as a Calendar node.
 */
class calendar_plugin_row_civicrm_event extends calendar_plugin_row_node {
  // Basic properties that let the row style follow relationships.
  var $base_table = 'civicrm_event';
  var $base_field = 'id';

  // Stores the nodes loaded with pre_render.
  var $nodes = array();
  public function construct() {
    parent::construct();
    if (!civicrm_initialize()) {
      return;
    }
  }

  public function option_definition() {
    $options = parent::option_definition();
    $options['date_fields'] = array('default' => array());
    $options['calendar_date_link'] = array('default' => '');
    $options['colors'] = array(
      'contains' => array(
        'legend' => array('default' => ''),
        'calendar_colors_type' => array('default' => array()),
      ),
    );
    return $options;
  }

  /**
   * Provide a form for setting options.
   */
  public function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['markup']['#markup'] = t("The calendar row plugin will format view results as calendar items. Make sure this display has a 'Calendar' format and uses a 'Date' contextual filter, or this plugin will not work correctly.");
    $form['calendar_date_link'] = array(
      '#title' => t('Add new date link'),
      '#type' => 'select',
      '#default_value' => $this->options['calendar_date_link'],
      '#options' => array('' => t('No link')) + node_type_get_names(),
      '#description' => t('Display a link to add a new date of the specified content type. Displayed only to users with appropriate permissions.'),
    );
    $form['colors'] = array(
      '#type' => 'fieldset',
      '#title' => t('Legend Colors'),
      '#description' => t('Set a hex color value (like #ffffff) to use in the calendar legend for each content type. Items with empty values will have no stripe in the calendar and will not be added to the legend.'),
    );
    $form['colors']['legend'] = array(
      '#title' => t('Stripes'),
      '#description' => t('Add stripes to calendar items.'),
      '#type' => 'select',
      '#options' => array('' => t('None')),
      '#default_value' => $this->options['colors']['legend'],
    );
    $colors = $this->options['colors']['calendar_colors_type'];
    $type_names = node_type_get_names();
    foreach ($type_names as $key => $name) {
      $form['colors']['calendar_colors_type'][$key] = array(
        '#title' => $name,
        '#type' => 'textfield',
        '#default_value' => isset($colors[$key]) ? $colors[$key] : '#ffffff',
        '#size' => 7,
        '#maxlength' => 7,
        '#element_validate' => array('calendar_validate_hex_color'),
        '#dependency' => array('edit-row-options-colors-legend' => array('type')),
        '#prefix' => '<div class="calendar-colorpicker-wrapper">',
        '#suffix' => '<div class="calendar-colorpicker"></div></div>',
        '#attributes' => array('class' => array('edit-calendar-colorpicker')),
        '#attached' => array(
          // Add Farbtastic color picker.
          'library' => array(
            array('system', 'farbtastic'),
          ),
          // Add javascript to trigger the colorpicker.
          'js' => array(drupal_get_path('module', 'calendar') . '/js/calendar_colorpicker.js'),
        ),
      );
    }
  }

  public function pre_render($values) {

    $ids = array();
    foreach ($values as $row) {
      // Use the $nid as the key so we don't create more than one value per node.
      $id = $row->{$this->field_alias};
      $ids[$id] = $id;
    }
    // @TODO Move to CiviCRM API, currently API only returns arrays
    // the render() method of this class and the calander_pluing_style are expecting
    // and object, hence using DAO
    foreach ($ids as $key => $id) {
      require_once 'CRM/Event/DAO/Event.php';
      $dao = New CRM_Event_DAO_Event();
      $dao->id = $id;
      $dao->find();
      $dao->fetch();
      // Make sure no NULL records get through
      if (!empty($dao->title)) {
        $this->nodes[$dao->id] = $dao;
      }
      $dao->release();
    }

    // Identify the date argument and fields that apply to this view.
    // Preload the Date Views field info for each field, keyed by the
    // field name, so we know how to retrieve field values from the cached node.
    $data        = date_views_fields('civicrm_event');
    $data        = $data['name'];
    $date_fields = array();
    foreach ($this->view->argument as $handler) {
      if (date_views_handler_is_date($handler, 'argument')) {
        // If this is the complex Date argument, the date fields are stored in the handler options,
        // otherwise we are using the simple date field argument handler.
        if ($handler->definition['handler'] != 'date_views_argument_handler') {
          $alias = $handler->table_alias . '.' . $handler->field;
          $info = $data[$alias];
          $field_name = str_replace(array('_value2', '_value'), '', $info['real_field_name']);
          $date_fields[$field_name] = $info;
        }
        else {
          foreach ($handler->options['date_fields'] as $alias) {
            $info = $data[$alias];
            $field_name = str_replace(array('_value2', '_value'), '', $info['real_field_name']);
            $date_fields[$field_name] = $info;
          }
        }
        $this->date_argument = $handler;
        $this->date_fields = $date_fields;
      }
    }
  }

  public function render($row) {
    global $base_url;
    $date_info = $this->date_argument->view->date_info;

    $nid = $row->{$this->field_alias};
    if (!is_numeric($nid)) {
      return;
    }

    // Load the specified node:
    // We have to clone this or nodes on other views on this page,
    // like an Upcoming block on the same page as a calendar view,
    // will end up acquiring the values we set here.
    $node = clone($this->nodes[$nid]);
    if (empty($node)) {
      return;
    }

    // There could be more than one date field in a view
    // so iterate through all of them to find the right values
    // for this view result.
    $rows = array();
    foreach ($this->date_fields as $field_name => $info) {

      $table_name  = $info['table_name'];
      $delta_field = $info['delta_field'];
      $tz_handling = $info['tz_handling'];
      $tz_field    = $info['timezone_field'];
      $rrule_field = $info['rrule_field'];
      $is_field    = substr($info['real_field_name'], 0, 6) == 'field_';

      // Retrieve the field value that matched our query from the cached node.
      // Find the date and set it to the right timezone.

      $item            = $node->$field_name;
      $node->date_id   = array();
      $item_start_date = NULL;
      $item_end_date   = NULL;

      $db_tz       = date_get_timezone_db($tz_handling, isset($item->$tz_field) ? $item->$tz_field : $date_info->display_timezone_name);
      $to_zone     = date_get_timezone($tz_handling, isset($item->$tz_field) ? $item->$tz_field : $date_info->display_timezone_name);
      $granularity = 'second';
      $increment   = 1;

      if ($is_field) {

        // @TODO Not sure if this is the correct way to retrieve the right language value, but I think so.
        $lang  = $node->language;
        $delta = isset($row->$delta_field) ? $row->$delta_field : 0;
        $item  = array_key_exists($lang, $item) ? $item[$lang][$delta] : $item['und'][$delta];

        // Set the date_id for the node, used to identify which field value to display for
        // fields that have multiple values. The theme expects it to be an array.
        $node->date_id = array('calendar.' . $node->id . '.' . $field_name . '.' . $delta);

        if (!empty($item['value'])) {
          $item_start_date = new dateObject($item['value'], $db_tz);
          $item_end_date = array_key_exists('value2', $item) ? new dateObject($item['value2'], $db_tz) : $item_start_date;
        }

        $cck_field   = field_info_field($field_name);
        $instance    = field_info_instance($this->view->base_table, $field_name, $node->type);
        $granularity = date_granularity_precision($cck_field['settings']['granularity']);
        $increment   = $instance['widget']['settings']['increment'];
      }
      elseif (!$is_field && !empty($item)) {
        $item_start_date = new dateObject($item, $db_tz);
        $item_end_date   = $item_start_date;
        $node->date_id   = array('calendar.' . $node->id . '.' . $field_name . '.0');
      }

      // If we don't have date value, go no further.
      if (empty($item_start_date)) {
        return;
      }

      // Set the item date to the proper display timezone;
      $item_start_date->setTimezone(new dateTimezone($to_zone));
      $item_end_date->setTimezone(new dateTimezone($to_zone));

      $event              = new stdClass();
      $event->nid         = $node->id;
      $event->title       = $node->civicrm_event_title;
      $event->type        = $node->event_type_id;
      $event->date_start  = $item_start_date;
      $event->date_end    = $item_end_date;
      $event->db_tz       = $db_tz;
      $event->to_zone     = $to_zone;
      $event->granularity = $granularity;
      $event->increment   = $increment;
      $event->field       = $is_field ? $item : NULL;
      $event->row         = $row;
      $event->node        = $node;

      // All calendar row plugins should provide a date_id that the theme can use.
      $event->date_id = $node->date_id[0];

      $nodes = $this->explode_values($event);
      foreach ($nodes as $node) {
        $rows[] = $node;
      }
    }
    return $rows;
  }

}
