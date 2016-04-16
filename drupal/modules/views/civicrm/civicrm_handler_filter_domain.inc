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
 * Field hanlder to allow filtering by CiviCRM domain.
 */
class civicrm_handler_filter_domain extends views_handler_filter_numeric {
  /**
   * Add an additonal operator which takes no arguments to the existing list provided by views_handler_filter_numeric.
   */
  function operators() {
    $operators = parent::operators();
    $operators['current_domain'] = array(
      'title' => t('Current CiviCRM domain'),
      'method' => 'current_domain',
      'short' => t('current domain'),
      'values' => 0,
    );
    return $operators;
  }

  /**
   * Grab the current domain id using the civicrm api and filter the query.
   */
  function current_domain($field) {
    if (!civicrm_initialize()) {
      return FALSE;
    }

    $domain = civicrm_api('domain', 'getsingle', array(
      'current_domain' => 1,
      'version' => 3,
    ));

    if (!$domain['is_error']) {
      $this->query->add_where($this->options['group'], $field, $domain['id']);
    }
  }
}
