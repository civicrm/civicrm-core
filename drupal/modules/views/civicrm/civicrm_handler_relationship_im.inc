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
 * Field handler to provide relationship to CiviCRM Instant Messaging.
 *
 * @ingroup civicrm_relationship_handlers
 */
class civicrm_handler_relationship_im extends civicrm_handler_relationship_location {

  static $_provider;
  public function construct() {
    parent::construct();
    if (!self::$_provider) {
      if (!civicrm_initialize()) {
        return;
      }
      self::$_provider = CRM_Core_PseudoConstant::get('CRM_Core_DAO_IM', 'provider_id');
    }
  }

  public function option_definition() {
    $options = parent::option_definition();
    $options['provider'] = array('default' => 0);
    return $options;
  }

  public function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $imOptions = array(0 => 'Any');
    foreach (self::$_provider as $id => $type) {
      $imOptions[$id] = $type;
    }
    $form['provider'] = array(
      '#type' => 'radios',
      '#title' => 'Instant Messaging provider for this relationship',
      '#options' => $imOptions,
      '#description' => t('Instant Messaging provider to be used for this relationship'),
      '#default_value' => $this->options['provider'],
      '#fieldset' => 'location_choices',
    );
  }

  public function join_im($join = array()) {
    $extra = array();
    if (isset($join->extra)) {
      $extra = $join->extra;
    }
    if (isset($this->options['provider']) && $this->options['provider']) {
      $extra[] = array(
        'value' => $this->options['provider'],
        'numeric' => TRUE,
        'field' => 'provider_id',
        'operator' => '=',
      );
    }
    if (!empty($extra)) {
      $join->extra = $extra;
    }
    return $join;
  }

  public function get_join() {
    $join = parent::get_join();
    $join = $this->join_im($join);
    return $join;
  }

  /**
   * Called to implement a relationship in a query.
   */
  public function query() {
    $join = $this->get_join();
    $this->alias = $this->query->add_table($this->table, $this->relationship, $join);
    //register relationship
    $this->view->relationship[$this->options['id']]->alias = $this->alias;
    $this->query->relationships[$this->alias] = array(
      'link' => $this->relationship,
      'table' => $this->table,
      'base' => $this->table,
    );
  }

}
