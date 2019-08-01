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
 * CiviCRM Views Integration
 */
class civicrm_handler_filter_group_name extends views_handler_filter_in_operator {
  private $civi_groups;
  protected $is_standard = TRUE;

  public function construct() {
    parent::construct();

    if (empty($this->civi_groups)) {
      if (!civicrm_initialize()) {
        return;
      }
      $standard_or_smart = ($this->is_standard ? 'IS NULL' : 'IS NOT NULL');
      $result = civicrm_api3('Group', 'get', [
        'return' => ["name", "title"],
        'is_hidden' => 0,
        'is_active' => 1,
        'saved_search_id' => [$standard_or_smart => 1],
        'options' => ['limit' => 0, 'sort' => "title"],
      ]);
      $this->civi_groups = [];
      foreach ($result['values'] as $group) {
        if (isset($group['name'])) {
          $this->civi_groups[$group['name']] = $group['title'];
        }
      }
    }
  }

  public function get_value_options() {
    if (!isset($this->value_options)) {
      $this->value_title = t('Contact Group Title');
      $options = [];
      foreach ($this->civi_groups as $name => $title) {
        $options[$name] = $title;
      }
      $this->value_options = $options;
    }
  }

}
