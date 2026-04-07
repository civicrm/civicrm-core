<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Main page for viewing all Saved searches.
 */
class CRM_Contact_Page_CustomSearch extends CRM_Core_Page {

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  public static $_links = NULL;

  /**
   * @return array
   */
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
    $dao = CRM_Core_DAO::executeQuery($sql);

    $rows = [];
    while ($dao->fetch()) {
      if (trim($dao->description ?? '')) {
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
   * @return mixed
   *   content of the parents run method
   */
  public function browse() {
    $rows = self::info();
    $this->assign('rows', $rows);
    return parent::run();
  }

  /**
   * Run this page (figure out the action needed and perform it).
   */
  public function run() {
    $action = CRM_Utils_Request::retrieve('action',
      'String',
      $this, FALSE, 'browse'
    );

    $this->assign('action', $action);
    return $this->browse();
  }

}
