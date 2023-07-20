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
 * Page for displaying list of location types.
 */
class CRM_Admin_Page_LocationType extends CRM_Core_Page_Basic {

  public $useLivePageJS = TRUE;

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_Core_BAO_LocationType';
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm() {
    return 'CRM_Admin_Form_LocationType';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page.
   */
  public function editName() {
    return 'Location Types';
  }

  /**
   * Get user context.
   *
   * @param null $mode
   *
   * @return string
   *   user context.
   */
  public function userContext($mode = NULL) {
    return 'civicrm/admin/locationType';
  }

  /**
   * @param $sort
   * @param $action
   * @param array $links
   *
   * @return array
   */
  protected function getRows($sort, $action, array $links): array {
    $rows = parent::getRows($sort, $action, $links);
    foreach ($rows as &$row) {
      // prevent smarty notices.
      foreach (['is_default', 'class', 'vcard_name'] as $expectedField) {
        if (!isset($row[$expectedField])) {
          $row[$expectedField] = NULL;
        }
      }
    }
    return $rows;
  }

}
