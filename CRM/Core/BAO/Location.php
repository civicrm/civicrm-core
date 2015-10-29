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

/**
 * This class handle creation of location block elements.
 */
class CRM_Core_BAO_Location extends CRM_Core_DAO {

  /**
   * Location block element array.
   */
  static $blocks = array('phone', 'email', 'im', 'openid', 'address');

  /**
   * Create various elements of location block.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param bool $fixAddress
   *   True if you need to fix (format) address values.
   *                               before inserting in db
   *
   * @param null $entity
   *
   * @return array
   */
  public static function create(&$params, $fixAddress = TRUE, $entity = NULL) {
    $location = array();
    if (!self::dataExists($params)) {
      return $location;
    }

    // create location blocks.
    foreach (self::$blocks as $block) {
      if ($block != 'address') {
        $location[$block] = CRM_Core_BAO_Block::create($block, $params, $entity);
      }
      else {
        $location[$block] = CRM_Core_BAO_Address::create($params, $fixAddress, $entity);
      }
    }

    if ($entity) {
      // this is a special case for adding values in location block table
      $entityElements = array(
        'entity_table' => $params['entity_table'],
        'entity_id' => $params['entity_id'],
      );

      $location['id'] = self::createLocBlock($location, $entityElements);
    }
    else {
      // when we come from a form which displays all the location elements (like the edit form or the inline block
      // elements, we can skip the below check. The below check adds quite a feq queries to an already overloaded
      // form
      if (!CRM_Utils_Array::value('updateBlankLocInfo', $params, FALSE)) {
        // make sure contact should have only one primary block, CRM-5051
        self::checkPrimaryBlocks(CRM_Utils_Array::value('contact_id', $params));
      }
    }

    return $location;
  }

  /**
   * Creates the entry in the civicrm_loc_block.
   *
   * @param string $location
   * @param array $entityElements
   *
   * @return int
   */
  public static function createLocBlock(&$location, &$entityElements) {
    $locId = self::findExisting($entityElements);
    $locBlock = array();

    if ($locId) {
      $locBlock['id'] = $locId;
    }

    foreach (array(
               'phone',
               'email',
               'im',
               'address',
             ) as $loc) {
      $locBlock["{$loc}_id"] = !empty($location["$loc"][0]) ? $location["$loc"][0]->id : NULL;
      $locBlock["{$loc}_2_id"] = !empty($location["$loc"][1]) ? $location["$loc"][1]->id : NULL;
    }

    $countNull = 0;
    foreach ($locBlock as $key => $block) {
      if (empty($locBlock[$key])) {
        $locBlock[$key] = 'null';
        $countNull++;
      }
    }

    if (count($locBlock) == $countNull) {
      // implies nothing is set.
      return NULL;
    }

    $locBlockInfo = self::addLocBlock($locBlock);
    return $locBlockInfo->id;
  }

  /**
   * Takes an entity array and finds the existing location block.
   *
   * @param array $entityElements
   *
   * @return int
   */
  public static function findExisting($entityElements) {
    $eid = $entityElements['entity_id'];
    $etable = $entityElements['entity_table'];
    $query = "
SELECT e.loc_block_id as locId
FROM {$etable} e
WHERE e.id = %1";

    $params = array(1 => array($eid, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    while ($dao->fetch()) {
      $locBlockId = $dao->locId;
    }
    return $locBlockId;
  }

  /**
   * Takes an associative array and adds location block.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return CRM_Core_BAO_locBlock
   *   Object on success, null otherwise
   */
  public static function addLocBlock(&$params) {
    $locBlock = new CRM_Core_DAO_LocBlock();

    $locBlock->copyValues($params);

    return $locBlock->save();
  }

  /**
   * Delete the Location Block.
   *
   * @param int $locBlockId
   *   Id of the Location Block.
   */
  public static function deleteLocBlock($locBlockId) {
    if (!$locBlockId) {
      return;
    }

    $locBlock = new CRM_Core_DAO_LocBlock();
    $locBlock->id = $locBlockId;

    $locBlock->find(TRUE);

    //resolve conflict of having same ids for multiple blocks
    $store = array(
      'IM_1' => $locBlock->im_id,
      'IM_2' => $locBlock->im_2_id,
      'Email_1' => $locBlock->email_id,
      'Email_2' => $locBlock->email_2_id,
      'Phone_1' => $locBlock->phone_id,
      'Phone_2' => $locBlock->phone_2_id,
      'Address_1' => $locBlock->address_id,
      'Address_2' => $locBlock->address_2_id,
    );
    $locBlock->delete();
    foreach ($store as $daoName => $id) {
      if ($id) {
        $daoName = 'CRM_Core_DAO_' . substr($daoName, 0, -2);
        $dao = new $daoName();
        $dao->id = $id;
        $dao->find(TRUE);
        $dao->delete();
        $dao->free();
      }
    }
  }

  /**
   * Check if there is data to create the object.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return bool
   */
  public static function dataExists(&$params) {
    // return if no data present
    $dataExists = FALSE;
    foreach (self::$blocks as $block) {
      if (array_key_exists($block, $params)) {
        $dataExists = TRUE;
        break;
      }
    }

    return $dataExists;
  }

  /**
   * Get values.
   *
   * @param array $entityBlock
   * @param bool $microformat
   *
   * @return array
   *   array of objects(CRM_Core_BAO_Location)
   */
  public static function &getValues($entityBlock, $microformat = FALSE) {
    if (empty($entityBlock)) {
      return NULL;
    }
    $blocks = array();
    $name_map = array(
      'im' => 'IM',
      'openid' => 'OpenID',
    );
    $blocks = array();
    //get all the blocks for this contact
    foreach (self::$blocks as $block) {
      if (array_key_exists($block, $name_map)) {
        $name = $name_map[$block];
      }
      else {
        $name = ucfirst($block);
      }
      $baoString = 'CRM_Core_BAO_' . $name;
      $blocks[$block] = $baoString::getValues($entityBlock, $microformat);
    }
    return $blocks;
  }

  /**
   * Delete all the block associated with the location.
   *
   * @param int $contactId
   *   Contact id.
   * @param int $locationTypeId
   *   Id of the location to delete.
   */
  public static function deleteLocationBlocks($contactId, $locationTypeId) {
    // ensure that contactId has a value
    if (empty($contactId) ||
      !CRM_Utils_Rule::positiveInteger($contactId)
    ) {
      CRM_Core_Error::fatal();
    }

    if (empty($locationTypeId) ||
      !CRM_Utils_Rule::positiveInteger($locationTypeId)
    ) {
      // so we only delete the blocks which DO NOT have a location type Id
      // CRM-3581
      $locationTypeId = 'null';
    }

    static $blocks = array('Address', 'Phone', 'IM', 'OpenID', 'Email');

    $params = array('contact_id' => $contactId, 'location_type_id' => $locationTypeId);
    foreach ($blocks as $name) {
      CRM_Core_BAO_Block::blockDelete($name, $params);
    }
  }

  /**
   * Copy or update location block.
   *
   * @param int $locBlockId
   *   Location block id.
   * @param int $updateLocBlockId
   *   Update location block id.
   *
   * @return int
   *   newly created/updated location block id.
   */
  public static function copyLocBlock($locBlockId, $updateLocBlockId = NULL) {
    //get the location info.
    $defaults = $updateValues = array();
    $locBlock = array('id' => $locBlockId);
    CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_LocBlock', $locBlock, $defaults);

    if ($updateLocBlockId) {
      //get the location info for update.
      $copyLocationParams = array('id' => $updateLocBlockId);
      CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_LocBlock', $copyLocationParams, $updateValues);
      foreach ($updateValues as $key => $value) {
        if ($key != 'id') {
          $copyLocationParams[$key] = 'null';
        }
      }
    }

    //copy all location blocks (email, phone, address, etc)
    foreach ($defaults as $key => $value) {
      if ($key != 'id') {
        $tbl = explode("_", $key);
        $name = ucfirst($tbl[0]);
        $updateParams = NULL;
        if ($updateId = CRM_Utils_Array::value($key, $updateValues)) {
          $updateParams = array('id' => $updateId);
        }

        $copy = CRM_Core_DAO::copyGeneric('CRM_Core_DAO_' . $name, array('id' => $value), $updateParams);
        $copyLocationParams[$key] = $copy->id;
      }
    }

    $copyLocation = &CRM_Core_DAO::copyGeneric('CRM_Core_DAO_LocBlock',
      array('id' => $locBlock['id']),
      $copyLocationParams
    );
    return $copyLocation->id;
  }

  /**
   * Make sure contact should have only one primary block, CRM-5051.
   *
   * @param int $contactId
   *   Contact id.
   */
  public static function checkPrimaryBlocks($contactId) {
    if (!$contactId) {
      return;
    }

    // get the loc block ids.
    $primaryLocBlockIds = CRM_Contact_BAO_Contact::getLocBlockIds($contactId, array('is_primary' => 1));
    $nonPrimaryBlockIds = CRM_Contact_BAO_Contact::getLocBlockIds($contactId, array('is_primary' => 0));

    foreach (array(
               'Email',
               'IM',
               'Phone',
               'Address',
               'OpenID',
             ) as $block) {
      $name = strtolower($block);
      if (array_key_exists($name, $primaryLocBlockIds) &&
        !CRM_Utils_System::isNull($primaryLocBlockIds[$name])
      ) {
        if (count($primaryLocBlockIds[$name]) > 1) {
          // keep only single block as primary.
          $primaryId = array_pop($primaryLocBlockIds[$name]);
          $resetIds = "(" . implode(',', $primaryLocBlockIds[$name]) . ")";
          // reset all primary except one.
          CRM_Core_DAO::executeQuery("UPDATE civicrm_$name SET is_primary = 0 WHERE id IN $resetIds");
        }
      }
      elseif (array_key_exists($name, $nonPrimaryBlockIds) &&
        !CRM_Utils_System::isNull($nonPrimaryBlockIds[$name])
      ) {
        // data exists and no primary block - make one primary.
        CRM_Core_DAO::setFieldValue("CRM_Core_DAO_" . $block,
          array_pop($nonPrimaryBlockIds[$name]), 'is_primary', 1
        );
      }
    }
  }

  /**
   * Get chain select values (whatever that means!).
   *
   * @param mixed $values
   * @param string $valueType
   * @param bool $flatten
   *
   * @return array
   */
  public static function getChainSelectValues($values, $valueType, $flatten = FALSE) {
    if (!$values) {
      return array();
    }
    $values = array_filter((array) $values);
    $elements = array();
    $list = &$elements;
    $method = $valueType == 'country' ? 'stateProvinceForCountry' : 'countyForState';
    foreach ($values as $val) {
      $result = CRM_Core_PseudoConstant::$method($val);

      // Format for quickform
      if ($flatten) {
        // Option-groups for multiple categories
        if ($result && count($values) > 1) {
          $elements["crm_optgroup_$val"] = CRM_Core_PseudoConstant::$valueType($val, FALSE);
        }
        $elements += $result;
      }

      // Format for js
      else {
        // Option-groups for multiple categories
        if ($result && count($values) > 1) {
          $elements[] = array(
            'value' => CRM_Core_PseudoConstant::$valueType($val, FALSE),
            'children' => array(),
          );
          $list = &$elements[count($elements) - 1]['children'];
        }
        foreach ($result as $id => $name) {
          $list[] = array(
            'value' => $name,
            'key' => $id,
          );
        }
      }
    }
    return $elements;
  }

}
