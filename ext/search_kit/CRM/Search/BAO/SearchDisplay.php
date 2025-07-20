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

use Civi\Api4\Event\AuthorizeRecordEvent;

/**
 * Search Display BAO
 */
class CRM_Search_BAO_SearchDisplay extends CRM_Search_DAO_SearchDisplay implements \Civi\Core\HookInterface {

  /**
   * Event fired after saving a SearchDisplay.
   * @param \Civi\Core\Event\PostEvent $event
   */
  public static function self_hook_civicrm_post(\Civi\Core\Event\PostEvent $event) {
    $display = $event->object;
    $params = $event->params;
    if (!$display->name || !$display->type || !$display->saved_search_id) {
      $display->find(TRUE);
    }
    if ($display->type === 'autocomplete' && isset($params['is_autocomplete_default']) && (empty($params['check_permissions']) || CRM_Core_Permission::check('administer CiviCRM'))) {
      $setting = (array) \Civi::settings()->get('autocomplete_displays');
      $entity = CRM_Contact_BAO_SavedSearch::getDbVal('api_entity', $display->saved_search_id);
      foreach ($setting as $key => $value) {
        // Existing item found for this entity
        if (str_starts_with($value, $entity . ':')) {
          $existing = explode(':', $value)[1];
          if ($params['is_autocomplete_default'] && $existing !== $display->name) {
            unset($setting[$key]);
          }
          elseif (!$params['is_autocomplete_default'] && $existing === $display->name) {
            unset($setting[$key]);
          }
        }
      }
      if ($params['is_autocomplete_default'] && !in_array("$entity:$display->name", $setting)) {
        $setting[] = "$entity:$display->name";
      }
      \Civi::settings()->set('autocomplete_displays', array_values($setting));
    }
  }

  /**
   * @see \Civi\Api4\Utils\CoreUtil::checkAccessRecord
   */
  public static function on_civi_api4_authorizeRecord(AuthorizeRecordEvent $e): void {
    $recordType = $e->getEntityName();
    $record = $e->getRecord();
    $userCID = $e->getUserID();

    // Control access to search displays that have `acl_bypass` set.
    if ($recordType === 'SearchDisplay') {
      // Super-admins can do anything with search displays
      if (CRM_Core_Permission::check('all CiviCRM permissions and ACLs', $userCID)) {
        $e->setAuthorized(TRUE);
        return;
      }
      // Must be at least a SearchKit administrator
      if (!CRM_Core_Permission::check('administer search_kit', $userCID)) {
        $e->setAuthorized(FALSE);
        return;
      }
      if (in_array($e->getActionName(), ['create', 'update'], TRUE)) {
        // Do not allow acl_bypass to be set to TRUE
        if (!empty($record['acl_bypass'])) {
          $e->setAuthorized(FALSE);
        }
        // Do not allow edits to an existing record with acl_bypass = TRUE
        elseif (!empty($record['id'])) {
          $e->setAuthorized(!CRM_Core_DAO::getFieldValue(__CLASS__, $record['id'], 'acl_bypass'));
        }
      }
    }

    // Ensure only super-admins may update SavedSearches linked to displays with `acl_bypass`
    if ($recordType === 'SavedSearch' && $e->getActionName() === 'update' && !CRM_Core_Permission::check('all CiviCRM permissions and ACLs', $userCID)) {
      $id = (int) $e->getRecord()['id'];
      $sql = "SELECT COUNT(id) FROM civicrm_search_display WHERE acl_bypass = 1 AND saved_search_id = $id";
      if (CRM_Core_DAO::singleValueQuery($sql)) {
        $e->setAuthorized(FALSE);
      }
    }
  }

}
