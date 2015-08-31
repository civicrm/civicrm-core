<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 */
class CRM_Contact_BAO_Contact_Optimizer {
  /**
   * Edit function.
   *
   * @param array $newValues
   * @param array $oldValues
   */
  public static function edit(&$newValues, &$oldValues) {
    // still need to do more work on this
    // CRM-10192
    return;

    self::website($newValues, $oldValues);
  }

  /**
   * @param $newValues
   * @param $oldValues
   */
  public static function website(&$newValues, &$oldValues) {
    $oldWebsiteValues = CRM_Utils_Array::value('website', $oldValues);
    $newWebsiteValues = CRM_Utils_Array::value('website', $newValues);

    if ($oldWebsiteValues == NULL || $newWebsiteValues == NULL) {
      return;
    }

    // check if we had a value in the old
    $oldEmpty = $newEmpty = TRUE;
    $old = $new = array();

    foreach ($oldWebsiteValues as $idx => $value) {
      if (!empty($value['url'])) {
        $oldEmpty = FALSE;
        $old[] = array('website_type_id' => $value['website_type_id'], 'url' => $value['url']);
      }
    }

    foreach ($newWebsiteValues as $idx => $value) {
      if (!empty($value['url'])) {
        $newEmpty = FALSE;
        $new[] = array('website_type_id' => $value['website_type_id'], 'url' => $value['url']);
      }
    }

    // if both old and new are empty, we can delete new and avoid a write
    if ($oldEmpty && $newEmpty) {
      unset($newValues['website']);
    }

    // if different number of non-empty entries, return
    if (count($new) != count($old)) {
      return;
    }

    // same number of entries, check if they are exactly the same
    foreach ($old as $oldID => $oldValues) {
      $found = FALSE;
      foreach ($new as $newID => $newValues) {
        if (
          $old['website_type_id'] == $new['website_type_id'] &&
          $old['url'] == $new['url']
        ) {
          $found = TRUE;
          unset($new[$newID]);
          break;
        }
        if (!$found) {
          return;
        }
      }
    }

    // if we've come here, this means old and new are the same
    // we can skip saving new and return
    unset($newValues['website']);
  }

  /**
   * @param $newValues
   * @param $oldValues
   */
  public static function email(&$newValues, &$oldValues) {
    $oldEmailValues = CRM_Utils_Array::value('email', $oldValues);
    $newEmailValues = CRM_Utils_Array::value('email', $newValues);

    if ($oldEmailValues == NULL || $newEmailValues == NULL) {
      return;
    }

    // check if we had a value in the old
    $oldEmpty = $newEmpty = TRUE;
    $old = $new = array();

    foreach ($oldEmailValues as $idx => $value) {
      if (!empty($value['email'])) {
        $oldEmpty = FALSE;
        $old[] = array(
          'email' => $value['email'],
          'location_type_id' => $value['location_type_id'],
          'on_hold' => $value['on_hold'] ? 1 : 0,
          'is_primary' => $value['is_primary'] ? 1 : 0,
          'is_bulkmail' => $value['is_bulkmail'] ? 1 : 0,
          'signature_text' => $value['signature_text'] ? $value['signature_text'] : '',
          'signature_html' => $value['signature_html'] ? $value['signature_html'] : '',
        );
      }
    }

    foreach ($newEmailValues as $idx => $value) {
      if (!empty($value['email'])) {
        $newEmpty = FALSE;
        $new[] = array(
          'email' => $value['email'],
          'location_type_id' => $value['location_type_id'],
          'on_hold' => $value['on_hold'] ? 1 : 0,
          'is_primary' => $value['is_primary'] ? 1 : 0,
          'is_bulkmail' => $value['is_bulkmail'] ? 1 : 0,
          'signature_text' => $value['signature_text'] ? $value['signature_text'] : '',
          'signature_html' => $value['signature_html'] ? $value['signature_html'] : '',
        );
      }
    }

    // if both old and new are empty, we can delete new and avoid a write
    if ($oldEmpty && $newEmpty) {
      unset($newValues['email']);
    }

    // if different number of non-empty entries, return
    if (count($new) != count($old)) {
      return;
    }

    // same number of entries, check if they are exactly the same
    foreach ($old as $oldID => $oldValues) {
      $found = FALSE;
      foreach ($new as $newID => $newValues) {
        if (
          $old['email_type_id'] == $new['email_type_id'] &&
          $old['url'] == $new['url']
        ) {
          $found = TRUE;
          unset($new[$newID]);
          break;
        }
        if (!$found) {
          return;
        }
      }
    }

    // if we've come here, this means old and new are the same
    // we can skip saving new and return
    unset($newValues['email']);
  }

}
