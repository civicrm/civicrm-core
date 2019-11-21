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
class CRM_Contact_Page_SavedSearch extends CRM_Core_Page {

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  public static $_links = NULL;

  /**
   * Delete a saved search.
   *
   * @param int $id
   *   Id of saved search.
   */
  public function delete($id) {
    // first delete the group associated with this saved search
    $group = new CRM_Contact_DAO_Group();
    $group->saved_search_id = $id;
    if ($group->find(TRUE)) {
      CRM_Contact_BAO_Group::discard($group->id);
    }

    $savedSearch = new CRM_Contact_DAO_SavedSearch();
    $savedSearch->id = $id;
    $savedSearch->is_active = 0;
    $savedSearch->save();
  }

  /**
   * Browse all saved searches.
   *
   * @return mixed
   *   content of the parents run method
   */
  public function browse() {
    $rows = [];

    $savedSearch = new CRM_Contact_DAO_SavedSearch();
    $savedSearch->is_active = 1;
    $savedSearch->selectAdd();
    $savedSearch->selectAdd('id, form_values');
    $savedSearch->find();
    $properties = ['id', 'name', 'description'];
    while ($savedSearch->fetch()) {
      // get name and description from group object
      $group = new CRM_Contact_DAO_Group();
      $group->saved_search_id = $savedSearch->id;
      if ($group->find(TRUE)) {
        $permissions = CRM_Contact_BAO_Group::checkPermission($group->id, TRUE);
        if (!CRM_Utils_System::isNull($permissions)) {
          $row = [];

          $row['name'] = $group->title;
          $row['description'] = $group->description;

          $row['id'] = $savedSearch->id;
          $formValues = CRM_Utils_String::unserialize($savedSearch->form_values);
          $query = new CRM_Contact_BAO_Query($formValues);
          $row['query_detail'] = $query->qill();

          $action = array_sum(array_keys(self::links()));
          $action = $action & CRM_Core_Action::mask($permissions);
          $row['action'] = CRM_Core_Action::formLink(
            self::links(),
            $action,
            ['id' => $row['id']],
            ts('more'),
            FALSE,
            'savedSearch.manage.action',
            'SavedSearch',
            $row['id']
          );

          $rows[] = $row;
        }
      }
    }

    $this->assign('rows', $rows);
    return parent::run();
  }

  /**
   * Run this page (figure out the action needed and perform it).
   */
  public function run() {
    $action = CRM_Utils_Request::retrieve('action', 'String',
      $this, FALSE, 'browse'
    );

    $this->assign('action', $action);

    if ($action & CRM_Core_Action::DELETE) {
      $id = CRM_Utils_Request::retrieve('id', 'Positive',
        $this, TRUE
      );
      $this->delete($id);
    }
    $this->browse();
  }

  /**
   * Get action Links.
   *
   * @return array
   *   (reference) of action links
   */
  public static function &links() {

    if (!(self::$_links)) {

      $deleteExtra = ts('Do you really want to remove this Smart Group?');

      self::$_links = [
        CRM_Core_Action::VIEW => [
          'name' => ts('Search'),
          'url' => 'civicrm/contact/search/advanced',
          'qs' => 'reset=1&force=1&ssID=%%id%%',
          'title' => ts('Search'),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/contact/search/saved',
          'qs' => 'action=delete&id=%%id%%',
          'extra' => 'onclick="return confirm(\'' . $deleteExtra . '\');"',
        ],
      ];
    }
    return self::$_links;
  }

}
