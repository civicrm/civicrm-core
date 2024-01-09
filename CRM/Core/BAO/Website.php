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
 * This class contain function for Website handling.
 */
class CRM_Core_BAO_Website extends CRM_Core_DAO_Website implements Civi\Core\HookInterface {
  use CRM_Contact_AccessTrait;

  /**
   * @deprecated
   *
   * @param array $params
   * @return CRM_Core_DAO_Website
   * @throws CRM_Core_Exception
   */
  public static function create($params) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return self::writeRecord($params);
  }

  /**
   * @deprecated
   *
   * @param array $params
   * @return CRM_Core_DAO_Website
   * @throws CRM_Core_Exception
   */
  public static function add($params) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return self::writeRecord($params);
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
   * @throws \CRM_Core_Exception
   */
  public static function process($params, $contactID, $skipDelete) {
    if (empty($params)) {
      return FALSE;
    }

    $ids = self::allWebsites($contactID);
    foreach ($params as $key => $values) {
      $id = $values['id'] ?? NULL;
      if (array_key_exists($id, $ids)) {
        unset($ids[$id]);
      }
      if (empty($values['id']) && is_array($ids) && !empty($ids)) {
        foreach ($ids as $id => $value) {
          if (($value['website_type_id'] == $values['website_type_id'])) {
            $values['id'] = $id;
            unset($ids[$id]);
          }
        }
      }
      if (!empty($values['url'])) {
        $values['contact_id'] = $contactID;
        self::writeRecord($values);
      }
      elseif ($skipDelete && !empty($values['id'])) {
        static::deleteRecord($values);
      }
    }
  }

  /**
   * Delete website.
   *
   * @param int $id
   *
   * @return bool
   *
   * @deprecated
   */
  public static function del($id) {
    CRM_Core_Error::deprecatedFunctionWarning('deleteRecord');
    return (bool) static::deleteRecord(['id' => $id]);
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param array $params
   * @param $values
   *
   * @return array
   */
  public static function &getValues($params = [], &$values = []) {
    $websites = [];
    $website = new CRM_Core_DAO_Website();
    $website->contact_id = $params['contact_id'];
    $website->find();

    $count = 1;
    while ($website->fetch()) {
      $values['website'][$count] = [];
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
    $params = [1 => [$id, 'Integer']];

    $websites = $values = [];
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $count = 1;
    while ($dao->fetch()) {
      $values = [
        'id' => $dao->id,
        'website_type_id' => $dao->website_type_id,
      ];

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
