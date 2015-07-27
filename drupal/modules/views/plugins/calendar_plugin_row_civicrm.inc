<?php

/**
 * @file
 * Contains the Calendar row style plugin.
 *
 * This plugin takes the view results, finds the date value for each,
 * then compares that date to the date range for the current view.
 * Items that started before or ended after the current date range
 * are shortened to the current range. Items that extend over more
 * than one day are cloned to create a calendar item for each day.
 * The resulting array of results (which may have a different number
 * of items than the original view result) are then passed back
 * to the style plugin so they can be displayed in a calendar.
 */

/**
 * Plugin which creates a view on the resulting object
 * and formats it as a Calendar node.
 */
class calendar_plugin_row_civicrm extends calendar_plugin_row {
  // Basic properties that let the row style follow relationships.
  var $base_table = '';
  var $base_field = 'id';
  function construct() {
    parent::construct();
    if (!civicrm_initialize()) {
      return;
    }
    $this->base_table = $this->definition['base'][0];
  }
  function option_definition() {
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
  function options_form(&$form, &$form_state) {
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
      '#type' => 'value',
      '#value' => '',
    );
    }

  function pre_render($values) {

    $ids = array();
    foreach ($values as $row) {
      // Use the $id as the key so we don't create more than one value per object.
      $id = $row->{$this->field_alias};
      $ids[$id] = $id;
    }
    // @TODO Move to CiviCRM API, currently API only returns arrays
    // the render() method of this class and the calander_pluing_style are expecting
    // an object, hence using DAO.
    // Sadly neither DAO nor API allows you to load multiple at once eg node_load_multiple()
    foreach ($ids as $key => $id) {
      require_once str_replace('_', DIRECTORY_SEPARATOR, $this->definition['dao class']) . '.php';
      $dao = New $this->definition['dao class'];
      $dao->id = $id;
      $dao->find();
      $dao->fetch();
      // Make sure no NULL records get through
      if (!empty($dao->{$this->definition['title field']})) {
        $this->entities[$dao->id] = $dao;
      }
      $dao->release();
    }


    // Identify the date argument and fields that apply to this view.
    // Preload the Date Views field info for each field, keyed by the
    // field name, so we know how to retrieve field values from the cached node.
    $data        = date_views_fields($this->base_table);
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

  function render($row) {
    global $base_url;
    $date_info = $this->date_argument->view->date_info;
    $id = $row->{$this->field_alias};

    if (!is_numeric($id)) {
      return $rows;
    }

    // Load the specified object:
    $node = (object) $this->entities[$id];
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
      $event        = new stdClass();
      $event->nid   = $node->id;
      $event->title = $node->{$this->definition['title field']};
      //$event->type = $node->event_type_id;
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

