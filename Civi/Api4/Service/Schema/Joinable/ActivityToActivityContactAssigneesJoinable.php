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

class ActivityToActivityContactAssigneesJoinable extends Joinable {
  /**
   * @var string
   */
  protected $baseTable = 'civicrm_activity';

  /**
   * @var string
   */
  protected $baseColumn = 'id';

  /**
   * @param $alias
   */
  public function __construct($alias) {
    $optionValueTable = 'civicrm_option_value';
    $optionGroupTable = 'civicrm_option_group';

    $subSubSelect = sprintf(
      'SELECT id FROM %s WHERE name = "%s"',
      $optionGroupTable,
      'activity_contacts'
    );

    $subSelect = sprintf(
      'SELECT value FROM %s WHERE name = "%s" AND option_group_id = (%s)',
      $optionValueTable,
      'Activity Assignees',
      $subSubSelect
    );

    $this->addCondition(sprintf('%s.record_type_id = (%s)', $alias, $subSelect));
    parent::__construct('civicrm_activity_contact', 'activity_id', $alias);
  }

}
