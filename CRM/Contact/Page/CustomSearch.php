<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Main page for viewing all Saved searches.
 *
 */
class CRM_Contact_Page_CustomSearch extends CRM_Core_Page {

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   * @static
   */
  static $_links = NULL;

  public static function &info() {
    $sql = "
SELECT v.value, v.label, v.description
FROM   civicrm_option_group g,
       civicrm_option_value v
WHERE  v.option_group_id = g.id
AND    g.name = 'custom_search'
AND    v.is_active = 1
ORDER By  v.weight
";
    $dao = CRM_Core_DAO::executeQuery($sql,
      CRM_Core_DAO::$_nullArray
    );

    $rows = array();
    while ($dao->fetch()) {
      if (trim($dao->description)) {
        $rows[$dao->value] = $dao->description;
      }
      else {
        $rows[$dao->value] = $dao->label;
      }
    }
    return $rows;
  }

  /**
   * Browse all custom searches.
   *
   * @return content of the parents run method
   *
   */
  function browse() {
    $rows = self::info();
    $this->assign('rows', $rows);
    return parent::run();
  }

  /**
   * run this page (figure out the action needed and perform it).
   *
   * @return void
   */
  function run() {
    $action = CRM_Utils_Request::retrieve('action',
      'String',
      $this, FALSE, 'browse'
    );

    $this->assign('action', $action);
    return $this->browse();
  }
}

