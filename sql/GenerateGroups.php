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

require_once '../civicrm.config.php';

require_once 'CRM/Core/Config.php';
require_once 'CRM/Core/Error.php';
require_once 'CRM/Core/I18n.php';

require_once 'CRM/Contact/BAO/Group.php';

$config = CRM_Core_Config::singleton();

$prefix = 'Automated Generated Group: ';
$query = "DELETE FROM civicrm_group where name like '%{$prefix}%'";
CRM_Core_DAO::executeQuery($query);

$numGroups = 100;

$visibility = array('User and User Admin Only', 'Public Pages');
$groupType = array(NULL, '1', '2', '12');

for ($i = 1; $i <= $numGroups; $i++) {
  $group            = new CRM_Contact_BAO_Group();
  $cnt              = sprintf('%05d', $i);
  $alphabet         = mt_rand(97, 122);
  $group->name      = $group->title = chr($alphabet) . ": $prefix $cnt";
  $group->is_active = 1;

  $v = mt_rand(0, 1);
  $group->visibility = $visibility[$v];

  $t = mt_rand(0, 3);
  $group->group_type = $groupType[$t];

  $group->save();

}
