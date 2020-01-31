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
class CRM_Core_BAO_Website extends CRM_Core_DAO_Website {

  /**
   * Takes an associative array and adds im.
   *
   * @param array $params
   *   an assoc array of name/value pairs.
   *
   * @return CRM_Core_BAO_Website
   */
  public static function add($params) {
    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'Website', CRM_Utils_Array::value('id', $params), $params);

    $website = new CRM_Core_DAO_Website();
    $website->copyValues($params);
    $website->save();

    CRM_Utils_Hook::post($hook, 'Website', $website->id, $website);
    return $website;
  }

  /**
   * Create website.
   *
   * If called in a legacy manner this, temporarily, fails back to calling the legacy function.
   *
   * @param array $params
   * @param int $contactID
   * @param bool $skipDelete
   *
   * @return bool|CRM_Core_BAO_Website
   */
  public static function create($params, $contactID = NULL, $skipDelete = NULL) {
    if ($skipDelete !== NULL || ($contactID && !is_array($contactID))) {
      \Civi::log()->warning(ts('Calling website:create with vars other than $params is deprecated. Use process'), ['civi.tag' => 'deprecated']);
      return self::process($params, $contactID, $skipDelete);
    }
    foreach ($params as $key => $value) {
      if (is_numeric($key)) {
        \Civi::log()->warning(ts('Calling website:create for multiple websites $params is deprecated. Use process'), ['civi.tag' => 'deprecated']);
        return self::process($params, $contactID, $skipDelete);
      }
    }
    return self::add($params);
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
  public static function process($params, $contactID, $skipDelete) {
    if (empty($params)) {
      return FALSE;
    }

    $ids = self::allWebsites($contactID);
    foreach ($params as $key => $values) {
      $id = CRM_Utils_Array::value('id', $values);
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
        self::add($values);
      }
      elseif ($skipDelete && !empty($values['id'])) {
        self::del($values['id']);
      }
    }
  }

  /**
   * Delete website.
   *
   * @param int $id
   *
   * @return bool
   */
  public static function del($id) {
    $obj = new self();
    $obj->id = $id;
    $obj->find();
    if ($obj->fetch()) {
      $params = [];
      CRM_Utils_Hook::pre('delete', 'Website', $id, $params);
      $obj->delete();
    }
    else {
      return FALSE;
    }
    CRM_Utils_Hook::post('delete', 'Website', $id, $obj);
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
  public static function &getValues(&$params = [], &$values = []) {
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
