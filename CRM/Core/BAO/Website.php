<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * This class contain function for Website handling.
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
   * @param bool $skipDelete
   *
   * @return bool
   */
  public static function create(&$params, $contactID, $skipDelete) {
    if (empty($params)) {
      return FALSE;
    }

    $ids = self::allWebsites($contactID);
    foreach ($params as $key => $values) {
      if (empty($values['id']) && is_array($ids) && !empty($ids)) {
        foreach ($ids as $id => $value) {
          if (($value['website_type_id'] == $values['website_type_id'])) {
            $values['id'] = $id;
          }
        }
      }
      if (!empty($values['url'])) {
        $values['contact_id'] = $contactID;
        self::add($values);
      }
      elseif ($skipDelete && !empty($values['id'])) {
        self::del(array($values['id']));
      }
    }
  }

  /**
   * Delete website.
   *
   * @param array $ids
   *   Website ids.
   *
   * @return bool
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
