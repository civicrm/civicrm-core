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
 * This class contains the functions that are called using AJAX (jQuery)
 */
class CRM_Group_Page_AJAX {

  /**
   * Get list of groups.
   */
  public static function getGroupList() {
    $params = $_GET;
    if (isset($params['parent_id'])) {
      // requesting child groups for a given parent
      $params['page'] = 1;
      $params['rp'] = 0;
      $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    }
    else {
      $requiredParams = [];
      $optionalParams = [
        'title' => 'String',
        'created_by' => 'String',
        'group_type' => 'String',
        'visibility' => 'String',
        'component_mode' => 'String',
        'status' => 'Integer',
        'parentsOnly' => 'Integer',
        'showOrgInfo' => 'Boolean',
        'savedSearch' => 'Integer',
        // Ignore 'parent_id' as that case is handled above
      ];
      $params = CRM_Core_Page_AJAX::defaultSortAndPagerParams();
      $params += CRM_Core_Page_AJAX::validateParams($requiredParams, $optionalParams);

      // get group list
      $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);

      // if no groups found with parent-child hierarchy and logged in user say can view child groups only (an ACL case),
      // go ahead with flat hierarchy, CRM-12225
      if (empty($groups)) {
        $groupsAccessible = CRM_Core_PseudoConstant::group();
        $parentsOnly = $params['parentsOnly'] ?? NULL;
        if (!empty($groupsAccessible) && $parentsOnly) {
          // recompute group list with flat hierarchy
          $params['parentsOnly'] = 0;
          $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
        }
      }
    }

    CRM_Utils_JSON::output($groups);
  }

}
