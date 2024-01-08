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
 * Page for displaying list of categories.
 */
class CRM_Admin_Page_Mapping extends CRM_Core_Page_Basic {

  public $useLivePageJS = TRUE;

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  public static $_links = NULL;

  /**
   * Get BAO.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_Core_BAO_Mapping';
  }

  /**
   * Get action Links.
   *
   * @return array
   *   (reference) of action links
   */
  public function &links() {
    if (!(self::$_links)) {
      // helper variable for nicer formatting
      $deleteExtra = ts('Are you sure you want to delete this mapping?') . ' ' . ts('This operation cannot be undone.');
      self::$_links = [
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/mapping',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Mapping'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::UPDATE),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/mapping',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete Mapping'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DELETE),
        ],
      ];
    }
    return self::$_links;
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm() {
    return 'CRM_Admin_Form_Mapping';
  }

  /**
   * Get form name for edit form.
   *
   * @return string
   *   name of this page.
   */
  public function editName() {
    return 'Mapping';
  }

  /**
   * Get form name for delete form.
   *
   * @return string
   *   name of this page.
   */
  public function deleteName() {
    return 'Mapping';
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
    return 'civicrm/admin/mapping';
  }

  /**
   * Get name of delete form.
   *
   * @return string
   *   Classname of delete form.
   */
  public function deleteForm() {
    return 'CRM_Admin_Form_Mapping';
  }

  /**
   * Run the basic page.
   */
  public function run() {
    $sort = 'mapping_type_id ASC, name ASC';
    return parent::run(NULL, NULL, $sort);
  }

  /**
   * Get any properties that should always be present in each row (null if no value).
   *
   * @return array
   */
  protected function getExpectedRowProperties(): array {
    return ['description'];
  }

}
