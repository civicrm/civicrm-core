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
 * This class handle creation of location block elements.
 */
class CRM_Core_BAO_Location extends CRM_Core_DAO {

  /**
   * Location block element array.
   * @var array
   */
  public static $blocks = ['phone', 'email', 'im', 'openid', 'address'];

  /**
   * Create various elements of location block.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param bool $fixAddress
   *   True if you need to fix (format) address values.
   *                               before inserting in db
   *
   * @return array
   */
  public static function create(&$params, $fixAddress = TRUE) {
    $location = [];
    if (!self::dataExists($params)) {
      return $location;
    }

    // create location blocks.
    foreach (self::$blocks as $block) {
      if ($block !== 'address') {
        $location[$block] = CRM_Core_BAO_Block::create($block, $params);
      }
      elseif (is_array($params['address'] ?? NULL)) {
        $location[$block] = CRM_Core_BAO_Address::legacyCreate($params, $fixAddress);
      }
    }

    return $location;
  }

  /**
   * Creates the entry in the civicrm_loc_block.
   *
   * @param array $location
   * @param array $entityElements
   *
   * @return int
   */
  public static function createLocBlock($location, $entityElements) {
    CRM_Core_Error::deprecatedFunctionWarning('Use LocBlock api');
    $locId = self::findExisting($entityElements);
    $locBlock = [];

    if ($locId) {
      $locBlock['id'] = $locId;
    }

    foreach ([
      'phone',
      'email',
      'im',
      'address',
    ] as $loc) {
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

    return self::addLocBlock($locBlock)->id;
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

    $params = [1 => [$eid, 'Integer']];
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
   *
   * @return CRM_Core_DAO_LocBlock
   *   Object on success, null otherwise
   */
  public static function addLocBlock($params) {
    $locBlock = new CRM_Core_DAO_LocBlock();
    $locBlock->copyValues($params);
    $locBlock->save();
    return $locBlock;
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
    $store = [
      'IM_1' => $locBlock->im_id,
      'IM_2' => $locBlock->im_2_id,
      'Email_1' => $locBlock->email_id,
      'Email_2' => $locBlock->email_2_id,
      'Phone_1' => $locBlock->phone_id,
      'Phone_2' => $locBlock->phone_2_id,
      'Address_1' => $locBlock->address_id,
      'Address_2' => $locBlock->address_2_id,
    ];
    $locBlock->delete();
    foreach ($store as $daoName => $id) {
      if ($id) {
        $daoName = 'CRM_Core_DAO_' . substr($daoName, 0, -2);
        $dao = new $daoName();
        $dao->id = $id;
        $dao->find(TRUE);
        $dao->delete();
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
   * Get array of location block BAOs.
   *
   * @param array $entityBlock
   * @param bool $useMarkup
   *   If TRUE, then `address[display]` will be filled with an address summary -- using markup.
   *   If FALSE, then `address[display]` will be filled with an address summary -- using plain-text.
   *   NOTE: Regardless of the flag, `address['display_text']` will have an address summary -- using plain-text.
   * @return CRM_Core_BAO_Location[]|null
   *
   * @throws \CRM_Core_Exception
   */
  public static function getValues($entityBlock, $useMarkup = FALSE): ?array {
    if (empty($entityBlock)) {
      // Can't imagine this is reachable.
      CRM_Core_Error::deprecatedWarning('calling function pointlessly is deprecated');
      return NULL;
    }
    return [
      'im' => CRM_Core_BAO_IM::getValues($entityBlock),
      'email' => CRM_Core_BAO_Email::getValues($entityBlock),
      'openid' => CRM_Core_BAO_OpenID::getValues($entityBlock),
      'phone' => CRM_Core_BAO_Phone::getValues($entityBlock),
      'address' => CRM_Core_BAO_Address::getValues($entityBlock, $useMarkup),
    ];
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
    $primaryLocBlockIds = CRM_Contact_BAO_Contact::getLocBlockIds($contactId, ['is_primary' => 1]);
    $nonPrimaryBlockIds = CRM_Contact_BAO_Contact::getLocBlockIds($contactId, ['is_primary' => 0]);

    foreach ([
      'Email',
      'IM',
      'Phone',
      'Address',
      'OpenID',
    ] as $block) {
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
      return [];
    }
    $values = array_filter((array) $values);
    $elements = [];
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
          $elements[] = [
            'value' => CRM_Core_PseudoConstant::$valueType($val, FALSE),
            'children' => [],
          ];
          $list = &$elements[count($elements) - 1]['children'];
        }
        foreach ($result as $id => $name) {
          $list[] = [
            'value' => $name,
            'key' => $id,
          ];
        }
      }
    }
    return $elements;
  }

}
