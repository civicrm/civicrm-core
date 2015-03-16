<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This class contain function for Website handling
 */
class CRM_Core_BAO_Website extends CRM_Core_DAO_Website {

  /**
   * Takes an associative array and adds im.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return object
   *   CRM_Core_BAO_Website object on success, null otherwise
   */
  public static function add(&$params) {
    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'Website', CRM_Utils_Array::value('id', $params), $params);

    $website = new CRM_Core_DAO_Website();
    $website->copyValues($params);
    $website->save();

    CRM_Utils_Hook::post($hook, 'Website', $website->id, $website);
    return $website;
  }

  /**
   * Process website.
   *
   * @param array $params
   * @param int $contactID
   *   Contact id.
   *
   * @return void
   */
  public static function create(&$params, $contactID) {
    
    // CRM-10551
    // Use updateBlankLocInfo to overwrite blanked values of matching type
    $updateBlankLocInfo = CRM_Utils_Array::value('updateBlankLocInfo', $params, FALSE);
    
    // Get the websites submitted in the form
    $submitted_websites = $params['website'];
        
    if (empty($submitted_websites)) {
      return FALSE;
    }
        
    // Get the websites currently on the Contact
    $existing_websites = self::allWebsites($contactID);
    
    // For each website submitted on the form
    foreach ($submitted_websites as $key => $submitted_value) {
      
      // Check for matching IDs on submitted / existing data
      $websiteId = CRM_Utils_Array::value('id', $submitted_value);
      if ($websiteId) {
        if (array_key_exists($websiteId, $existing_websites)) {
          unset($existing_websites[$websiteId]);
        }
        else {
          unset($submitted_value['id']);
        }
      }

      // Match up submitted values to existing ones, based on type
      if (empty($submitted_value['id']) && !empty($existing_websites)) {
        foreach ($existing_websites as $id => $existing_value) {
          if ($existing_value['website_type_id'] == $submitted_value['website_type_id']) {
            $submitted_value['id'] = $id;
            unset($existing_websites[$id]);
            break;
          }
        }
      }
      
      $submitted_value['contact_id'] = $contactID;
      
      // CRM-10551
      // If there is a matching ID, the URL is empty and we are deleting blanked values
      // Then remove it from the contact
      if (!empty($submitted_value['id']) && empty($submitted_value['url']) && $updateBlankLocInfo) {
        self::del(array($submitted_value['id']));
      }
      
      // Otherwise, add the website if the URL isn't empty
      elseif (!empty($submitted_value['url'])) {
        self::add($submitted_value);
      }
    }
  }

  /**
   * Delete website.
   *
   * @param array $ids
   *   Website ids.
   *
   * @return void
   */
  public static function del($ids) {
    $query = 'DELETE FROM civicrm_website WHERE id IN ( ' . implode(',', $ids) . ')';
    CRM_Core_DAO::executeQuery($query);
    // FIXME: we should return false if the del was unsuccessful
    return TRUE;
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param array $params
   * @param $values
   *
   * @return bool
   */
  public static function &getValues(&$params, &$values) {
    $websites = array();
    $website = new CRM_Core_DAO_Website();
    $website->contact_id = $params['contact_id'];
    $website->find();

    $count = 1;
    while ($website->fetch()) {
      $values['website'][$count] = array();
      CRM_Core_DAO::storeValues($website, $values['website'][$count]);

      $websites[$count] = $values['website'][$count];
      $count++;
    }

    return $websites;
  }

  /**
   * Get all the websites for a specified contact_id.
   *
   * @param int $id
   *   The contact id.
   *
   * @param bool $updateBlankLocInfo
   *
   * @return array
   *   the array of website details
   */
  public static function allWebsites($id, $updateBlankLocInfo = FALSE) {
    if (!$id) {
      return NULL;
    }

    $query = '
SELECT  id, website_type_id
  FROM  civicrm_website
 WHERE  civicrm_website.contact_id = %1';
    $params = array(1 => array($id, 'Integer'));

    $websites = $values = array();
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $count = 1;
    while ($dao->fetch()) {
      $values = array(
        'id' => $dao->id,
        'website_type_id' => $dao->website_type_id,
      );

      if ($updateBlankLocInfo) {
        $websites[$count++] = $values;
      }
      else {
        $websites[$dao->id] = $values;
      }
    }
    return $websites;
  }

}
