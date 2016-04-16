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

   **
   * @file Provides Views integration for CiviCRM Multi-Select Country custom fields
   *
   * @author DaveJ
   * based on civicrm_handler_filter_custom_option and civicrm_handler_filter_state
   */
class civicrm_handler_filter_state_multi extends views_handler_filter_in_operator {
  function construct() {
    parent::construct();
    if (!civicrm_initialize()) {
      return;
    }
  }

  function get_value_options() {
    if (!isset($this->value_options)) {
      $states = CRM_Core_PseudoConstant::stateProvince();
      $options = array();
      if (is_array($states)) {
        foreach ($states as $id => $name) {
          $options[$id] = $name;
        }
      }
      $this->value_options = $options;
    }
      /*
     * code to add country->state/province conditional options first determine if there's a country filter on this view if there is, load the country->state options into a JSON object we can attach via javascript. Remove the state options set above and attach the javascript handler
     */

    // get the country filter
    $filters = is_array($this->view->filter) ? $this->view->filter : array();
    foreach ($filters as $name => $handler) {
      if ($handler->definition['handler'] == 'civicrm_handler_filter_country_multi') {
        $country_field = $handler->field;
        $selected_countries = $loaded_list = array();
        if(isset($this->view->exposed_input[$country_field])
         && count((array) $this->view->exposed_input[$country_field])) {
          $selected_countries = (array) $this->view->exposed_input[$country_field];
        }
        $allowedCountries = array_keys($handler->value_options);
        if(empty($allowedCountries)) {
          $allowedCountries = array_keys(CRM_Core_PseudoConstant::country());
        }

        $result = db_query('SELECT id, name, country_id FROM {civicrm_state_province} WHERE country_id IN (:countries)',
          array(':countries' => $allowedCountries))
        ->fetchAllAssoc('id');

        $state_html = array();
        foreach ($result as $state_id => $state) {
          if (empty($state_html[$state->country_id])) {
            $state_html[$state->country_id] = '';
          }
          $state_html[$state->country_id] .= '<option value="' . $state_id . '">' . $state->name . '</option>';
          if(in_array($state->country_id, $selected_countries)) {
            $loaded_list[$state_id] = $state->name;
          }
        }
        drupal_add_js(array('civicrm_views' => array('states' => $state_html)), 'setting');
        $c_object = json_encode($state_html);
      }
    } // end foreach

    // country filter exists, remove options, add js
    if (!empty($country_field)) {
      $this->value_options = $loaded_list;

      $fid=md5($country_field);
      $js = '(function ($) {
               Drupal.behaviors.civicrm_views_' . $fid . ' = {
                 attach: function (context, settings) {
                   $("#edit-' . $country_field . '").change( function() {
                     var stateList = \'<option value="All">&lt;Any&gt;</option>\';
                     var selectedCountries = $(this).val();
                     // get the string of options
                     if(jQuery.type(selectedCountries) === "string") {
                       selectedCountries = [selectedCountries];
                     }
                     $.each(selectedCountries, function(index, selectedCountry) {
                       stateList = stateList + ((Drupal.settings.civicrm_views.states[selectedCountry])? Drupal.settings.civicrm_views.states[selectedCountry] : \'\');
                     });
                     $("select[name=' . $this->field . ']").empty();
                     $("select[name=' . $this->field . ']").html(stateList);
                   });
                 }
               }
             })(jQuery); ';
      drupal_add_js($js, 'inline', 'footer');
    } // end if country filter
  }

  function operators() {
    $operators = parent::operators();
    $operators += array(
      'all' => array(
        'title' => t('Is all of'),
        'short' => t('all'),
        'method' => 'op_simple',
        'values' => 1,
        ),
      );

    return $operators;
  }

  function op_simple() {
    if (empty($this->value)) {
      return;
    }

    $this->ensure_my_table();

    // negated operator uses AND, positive uses OR
    $op = $glue = null;
    switch ($this->operator) {
      case 'in':
        $op = ' = ';
        $glue = ' OR ';
        break;
      case 'not in':
        $op = ' != ';
        $glue = ' AND ';
        break;
      case 'all' :
        $clause = "$this->table_alias.$this->real_field IN (" . implode(',', $this->value) . ")";
        break;
    }

    if ($op) {
      foreach ($this->value as $value) {
        $clauses[] = "$this->table_alias.$this->real_field " . $op . $value;
      }
      $clause = implode($glue, $clauses);
    }

    $this->query->add_where_expression($this->options['group'], $clause);
  }
}
