<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */


namespace Civi\Api4\Service\Schema\Joinable;

class OptionValueJoinable extends Joinable {
  /**
   * @var string
   */
  protected $optionGroupName;

  /**
   * @param string $optionGroup
   *   Can be either the option group name or ID
   * @param string|null $alias
   *   The join alias
   * @param string $keyColumn
   *   Which column to use to join, defaults to "value"
   */
  public function __construct($optionGroup, $alias = NULL, $keyColumn = 'value') {
    $this->optionGroupName = $optionGroup;
    $optionValueTable = 'civicrm_option_value';

    // default join alias to option group name, e.g. activity_type
    if (!$alias && !is_numeric($optionGroup)) {
      $alias = $optionGroup;
    }

    parent::__construct($optionValueTable, $keyColumn, $alias);

    if (!is_numeric($optionGroup)) {
      $subSelect = 'SELECT id FROM civicrm_option_group WHERE name = "%s"';
      $subQuery = sprintf($subSelect, $optionGroup);
      $condition = sprintf('%s.option_group_id = (%s)', $alias, $subQuery);
    }
    else {
      $condition = sprintf('%s.option_group_id = %d', $alias, $optionGroup);
    }

    $this->addCondition($condition);
  }

  /**
   * The existing condition must also be re-aliased
   *
   * @param string $alias
   *
   * @return $this
   */
  public function setAlias($alias) {
    foreach ($this->conditions as $index => $condition) {
      $search = $this->alias . '.';
      $replace = $alias . '.';
      $this->conditions[$index] = str_replace($search, $replace, $condition);
    }

    parent::setAlias($alias);

    return $this;
  }

}
