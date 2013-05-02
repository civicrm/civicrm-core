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
 * This class contain function for Website handling
 */
class CRM_Core_BAO_Website extends CRM_Core_DAO_Website {

  /**
   * takes an associative array and adds im
   *
   * @param array  $params         (reference ) an assoc array of name/value pairs
   *
   * @return object       CRM_Core_BAO_Website object on success, null otherwise
   * @access public
   * @static
   */
  static function add(&$params) {
    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'Website', CRM_Utils_Array::value('id', $params), $params);

    $website = new CRM_Core_DAO_Website();
    $website->copyValues($params);
    $website->save();

    CRM_Utils_Hook::post($hook, 'Website', $website->id, $website);
    return $website;
  }

  /**
   * process website
   *
   * @param array $params associated array
   * @param int   $contactID contact id
   *
   * @return void
   * @access public
   * @static
   */
  static function create(&$params, $contactID, $skipDelete) {
    if (empty($params)) {
      return FALSE;
    }
    
    $ids = self::allWebsites($contactID);
    foreach ($params as $key => $values) {
      $websiteId = CRM_Utils_Array::value('id', $values);
      if ($websiteId) {
        if (array_key_exists($websiteId, $ids)) {
          unset($ids[$websiteId]);
        }
        else {
          unset($values['id']);
        }
      }

      if (!CRM_Utils_Array::value('id', $values) &&
        is_array($ids) && !empty($ids)
      ) {
        foreach ($ids as $id => $value) {
          if (($value['website_type_id'] == $values['website_type_id']) 
            && CRM_Utils_Array::value('url', $value)) {
            $values['id'] = $id;
            unset($ids[$id]);
            break;
          }
        }
      }
      $values['contact_id'] = $contactID;
      if ( CRM_Utils_Array::value('url', $values) ) {
        self::add($values);
      }
    }
    
    if ($skipDelete && !empty($ids)) {
      self::del(array_keys($ids));
    }
  }

  /**
   * Delete website
   *
   * @param array $ids website ids
   *
   * @return void
   * @static
   */
  static function del($ids) {
    $query = 'DELETE FROM civicrm_website WHERE id IN ( ' . implode(',', $ids) . ')';
    CRM_Core_DAO::executeQuery($query);
    // FIXME: we should return false if the del was unsuccessful
    return TRUE;
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param array entityBlock input parameters to find object
   *
   * @return boolean
   * @access public
   * @static
   */
  static function &getValues(&$params, &$values) {
    $websites            = array();
    $website             = new CRM_Core_DAO_Website();
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
   * Get all the websites for a specified contact_id
   *
   * @param int $id the contact id
   *
   * @return array  the array of website details
   * @access public
   * @static
   */
  static function allWebsites($id, $updateBlankLocInfo = FALSE) {
    if (!$id) {
      return NULL;
    }

    $query = '
SELECT  id, website_type_id
  FROM  civicrm_website
 WHERE  civicrm_website.contact_id = %1';
    $params = array(1 => array($id, 'Integer'));

    $websites = $values = array();
    $dao      = CRM_Core_DAO::executeQuery($query, $params);
    $count    = 1;
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

