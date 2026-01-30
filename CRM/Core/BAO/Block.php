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
 * Add static functions to include some common functionality used across location sub object BAO classes.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_BAO_Block {

  /**
   * Fields that are required for a valid block.
   * @var array
   */
  public static $requiredBlockFields = [
    'email' => ['email'],
    'phone' => ['phone'],
    'im' => ['name'],
    'openid' => ['openid'],
  ];

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param string $blockName
   *   Name of the above object.
   * @param array $params
   *   Input parameters to find object.
   *
   * @return array
   *   Array of $block objects.
   * @throws CRM_Core_Exception
   */
  public static function &getValues($blockName, $params) {
    if (empty($params)) {
      return NULL;
    }

    $blockName = self::fixBlockName($blockName);
    $apiName = '\Civi\Api4\\' . $blockName;
    $block = new $apiName();

    $blocks = [];
    if (!isset($params['entity_table'])) {
      if (!$params['contact_id']) {
        throw new CRM_Core_Exception('Invalid Contact ID parameter passed');
      }
      $request = $block::get(TRUE)->addWhere('contact_id', '=', $params['contact_id']);
      $blocks = self::retrieveBlock($request);
    }
    else {
      $blockIds = self::getBlockIds($blockName, NULL, $params);

      if (empty($blockIds)) {
        return $blocks;
      }

      $count = 1;
      foreach ($blockIds as $blockId) {
        $block = new $apiName();
        $request = $block::get(TRUE)->addWhere('id', '=', $blockId['id']);
        $getBlocks = self::retrieveBlock($request);
        $blocks[$count++] = array_pop($getBlocks);
      }
    }

    return $blocks;
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param Civi\Api4\Generic\DAOGetAction $request
   *   An API4 Get request, typically of Phone|Email|IM|OpenID.
   */
  public static function retrieveBlock(Civi\Api4\Generic\DAOGetAction $request): array {
    $unescapedBlocks = (array) $request->addOrderBy('is_primary', 'DESC')->addOrderBy('id')->execute();
    // Escape the values for HTML output (to prevent XSS).
    $blocks = [];
    foreach ($unescapedBlocks as $block) {
      $blocks[] = array_map(function ($value) {
        return is_string($value) ? htmlspecialchars($value) : $value;
      }, $block);
    }
    // Apparently we need a 1-based index for the block array. Gross.
    $blocks = array_merge([0 => 1], $blocks);
    unset($blocks[0]);

    return $blocks;
  }

  /**
   * Check if the current block object has any valid data.
   *
   * @param array $blockFields
   *   Array of fields that are of interest for this object.
   * @param array $params
   *   Associated array of submitted fields.
   *
   * @return bool
   *   true if the block has data, otherwise false
   */
  public static function dataExists($blockFields, &$params) {
    foreach ($blockFields as $field) {
      if (CRM_Utils_System::isNull($params[$field] ?? NULL)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Check if the current block exits.
   *
   * @param string $blockName
   *   Block name.
   * @param array $params
   *   Array of submitted fields.
   *
   * @return bool
   *   true if the block is in the params and is an array
   */
  public static function blockExists($blockName, $params) {
    return !empty($params[$blockName]) && is_array($params[$blockName]);
  }

  /**
   * Get all block ids for a contact.
   *
   * @param string $blockName
   *   Block name.
   * @param int $contactId
   *   Contact id.
   *
   * @param null $entityElements
   * @param bool $updateBlankLocInfo
   *
   * @return array
   *   formatted array of block ids
   *
   */
  public static function getBlockIds($blockName, $contactId = NULL, $entityElements = NULL, $updateBlankLocInfo = FALSE) {
    $allBlocks = [];

    $name = self::fixBlockName($blockName);

    $baoString = 'CRM_Core_BAO_' . $name;
    if ($contactId) {
      //@todo a cleverer way to do this would be to use the same fn name on each
      // BAO rather than constructing the fn
      // it would also be easier to grep for
      // e.g $bao = new $baoString;
      // $bao->getAllBlocks()
      $baoFunction = 'all' . $name . 's';
      $allBlocks = $baoString::$baoFunction($contactId, $updateBlankLocInfo);
    }
    elseif (!empty($entityElements) && $blockName != 'OpenID') {
      $baoFunction = 'allEntity' . $name . 's';
      $allBlocks = $baoString::$baoFunction($entityElements);
    }

    return $allBlocks;
  }

  /**
   * Takes an associative array and creates a block.
   *
   * @param string $blockName
   *   Block name.
   * @param array $params
   *   Array of name/value pairs.
   *
   * @return array|null
   *   Array of created location entities or NULL if none to create.
   */
  public static function create($blockName, $params) {
    if (!self::blockExists($blockName, $params)) {
      return NULL;
    }

    $name = ucfirst($blockName);
    $isPrimary = $isBilling = TRUE;
    $entityElements = $blocks = [];
    $resetPrimaryId = NULL;
    $primaryId = FALSE;

    $contactId = $params['contact_id'];

    $updateBlankLocInfo = $params['updateBlankLocInfo'] ?? FALSE;
    $isIdSet = $params[$blockName]['isIdSet'] ?? FALSE;

    //get existing block ids.
    $blockIds = self::getBlockIds($blockName, $contactId, $entityElements);
    foreach ($params[$blockName] as $count => $value) {
      $blockId = $value['id'] ?? NULL;
      if ($blockId) {
        if (is_array($blockIds) && array_key_exists($blockId, $blockIds)) {
          unset($blockIds[$blockId]);
        }
        else {
          unset($value['id']);
        }
      }
    }
    $baoString = 'CRM_Core_BAO_' . $name;
    foreach ($params[$blockName] as $count => $value) {
      if (!is_array($value)) {
        continue;
      }
      // if in some cases (eg. email used in Online Conribution Page, Profiles, etc.) id is not set
      // lets try to add using the previous method to avoid any false creation of existing data.
      foreach ($blockIds as $blockId => $blockValue) {
        if (empty($value['id']) && $blockValue['locationTypeId'] == ($value['location_type_id'] ?? NULL) && !$isIdSet) {
          $valueId = FALSE;
          if ($blockName == 'phone') {
            $phoneTypeBlockValue = $blockValue['phoneTypeId'] ?? NULL;
            if ($phoneTypeBlockValue == ($value['phone_type_id'] ?? NULL)) {
              $valueId = TRUE;
            }
          }
          elseif ($blockName == 'im') {
            $providerBlockValue = $blockValue['providerId'] ?? NULL;
            if (!empty($value['provider_id']) && $providerBlockValue == $value['provider_id']) {
              $valueId = TRUE;
            }
          }
          else {
            $valueId = TRUE;
          }
          if ($valueId) {
            $value['id'] = $blockValue['id'];
            if (!$primaryId && !empty($blockValue['is_primary'])) {
              $value['is_primary'] = $blockValue['is_primary'];
            }
            break;
          }
        }
      }
      $dataExists = self::dataExists(self::$requiredBlockFields[$blockName], $value);
      // Note there could be cases when block info already exist ($value[id] is set) for a contact/entity
      // BUT info is not present at this time, and therefore we should be really careful when deleting the block.
      // $updateBlankLocInfo will help take appropriate decision. CRM-5969
      if (!empty($value['id']) && !$dataExists && $updateBlankLocInfo) {
        //delete the existing record
        $baoString::deleteRecord($value);
        continue;
      }
      elseif (!$dataExists) {
        continue;
      }
      $contactFields = [
        'contact_id' => $contactId,
        'location_type_id' => $value['location_type_id'] ?? NULL,
      ];

      $contactFields['is_billing'] = 0;
      if ($isBilling && !empty($value['is_billing'])) {
        $contactFields['is_billing'] = $value['is_billing'];
        $isBilling = FALSE;
      }

      $blockFields = array_merge($value, $contactFields);
      if ($baoString === 'CRM_Core_BAO_Address') {
        CRM_Core_BAO_Address::fixAddress($blockFields);
      }
      $blocks[] = $baoString::writeRecord($blockFields);
    }

    return $blocks;
  }

  public static function fixBlockName($blockName) {
    if ($blockName == 'im') {
      return 'IM';
    }
    elseif ($blockName == 'openid') {
      return 'OpenID';
    }
    return ucfirst($blockName);
  }

  /**
   * Delete block.
   * @deprecated - just call the BAO / api directly.
   *
   * @param string $blockName
   *   Block name.
   * @param int $params
   *   Associates array.
   */
  public static function blockDelete($blockName, $params) {
    $name = self::fixBlockName($blockName);

    $baoString = 'CRM_Core_BAO_' . $name;
    $baoString::deleteRecord($params);
  }

  /**
   * Handling for is_primary.
   * $params is_primary could be
   *  #  1 - find other entries with is_primary = 1 &  reset them to 0
   *  #  0 - make sure at least one entry is set to 1
   *            - if no other entry is 1 change to 1
   *            - if one other entry exists change that to 1
   *            - if more than one other entry exists change first one to 1
   * @fixme - perhaps should choose by location_type
   *  #  empty - same as 0 as once we have checked first step
   *             we know if it should be 1 or 0
   *
   *  if $params['id'] is set $params['contact_id'] may need to be retrieved
   *
   * @param array $params
   * @param $class
   *
   * @throws CRM_Core_Exception
   */
  public static function handlePrimary(&$params, $class) {
    if (isset($params['id']) && CRM_Utils_System::isNull($params['is_primary'] ?? NULL)) {
      // if id is set & is_primary isn't we can assume no change)
      return;
    }
    $table = CRM_Core_DAO_AllCoreTables::getTableForClass($class);
    if (!$table) {
      throw new CRM_Core_Exception("Failed to locate table for class [$class]");
    }

    // contact_id in params might be empty or the string 'null' so cast to integer
    $contactId = (int) ($params['contact_id'] ?? 0);
    // If id is set & we haven't been passed a contact_id, retrieve it
    if (!empty($params['id']) && !isset($params['contact_id'])) {
      $entity = new $class();
      $entity->id = $params['id'];
      $entity->find(TRUE);
      $contactId = $params['contact_id'] = $entity->contact_id;
    }
    // If entity is not associated with contact, concept of is_primary not relevant
    if (!$contactId) {
      return;
    }

    // if params is_primary then set all others to not be primary & exit out
    // if is_primary = 1
    if (!empty($params['is_primary'])) {
      $sql = "UPDATE $table SET is_primary = 0 WHERE contact_id = %1";
      $sqlParams = [1 => [$contactId, 'Integer']];
      // we don't want to create unnecessary entries in the log_ tables so exclude the one we are working on
      if (!empty($params['id'])) {
        $sql .= " AND id <> %2";
        $sqlParams[2] = [$params['id'], 'Integer'];
      }
      CRM_Core_DAO::executeQuery($sql, $sqlParams);
      return;
    }

    //Check what other emails exist for the contact
    $existingEntities = new $class();
    $existingEntities->contact_id = $contactId;
    $existingEntities->orderBy('is_primary DESC');
    if (!$existingEntities->find(TRUE) || (!empty($params['id']) && $existingEntities->id == $params['id'])) {
      // ie. if  no others is set to be primary then this has to be primary set to 1 so change
      $params['is_primary'] = 1;
      return;
    }
    else {
      /*
       * If the only existing email is the one we are editing then we must set
       * is_primary to 1
       * @see https://issues.civicrm.org/jira/browse/CRM-10451
       */
      if ($existingEntities->N == 1 && $existingEntities->id == ($params['id'] ?? NULL)) {
        $params['is_primary'] = 1;
        return;
      }

      if ($existingEntities->is_primary == 1) {
        return;
      }
      // so at this point we are only dealing with ones explicity setting is_primary to 0
      // since we have reverse sorted by email we can either set the first one to
      // primary or return if is already is
      $existingEntities->is_primary = 1;
      $existingEntities->save();
      if ($class === 'CRM_Core_BAO_Email') {
        CRM_Core_BAO_Email::updateContactName($contactId, $existingEntities->email);
      }
    }
  }

  /**
   * Handling for is_billing.
   * This process is a variation of handlePrimary above
   * Find other entries with is_billing = 1 and reset them to 0
   *
   * @param array $params
   * @param $class
   *
   * @throws CRM_Core_Exception
   */
  public static function handleBilling(&$params, $class) {
    if (isset($params['id']) && CRM_Utils_System::isNull($params['is_billing'] ?? NULL)) {
      // if id is set & is_billing isn't we can assume no change)
      return;
    }
    $table = CRM_Core_DAO_AllCoreTables::getTableForClass($class);
    if (!$table) {
      throw new CRM_Core_Exception("Failed to locate table for class [$class]");
    }

    // contact_id in params might be empty or the string 'null' so cast to integer
    $contactId = (int) ($params['contact_id'] ?? 0);
    // If id is set & we haven't been passed a contact_id, retrieve it
    if (!empty($params['id']) && !isset($params['contact_id'])) {
      $entity = new $class();
      $entity->id = $params['id'];
      $entity->find(TRUE);
      $contactId = $entity->contact_id;
    }
    // If entity is not associated with contact, concept of is_billing not relevant
    if (!$contactId) {
      return;
    }

    // if params is_billing then set all others to not be billing & exit out
    // if is_billing = 1
    if (!empty($params['is_billing'])) {
      $sql = "UPDATE $table SET is_billing = 0 WHERE contact_id = %1";
      $sqlParams = [1 => [$contactId, 'Integer']];
      // we don't want to create unnecessary entries in the log_ tables so exclude the one we are working on
      if (!empty($params['id'])) {
        $sql .= " AND id <> %2";
        $sqlParams[2] = [$params['id'], 'Integer'];
      }
      CRM_Core_DAO::executeQuery($sql, $sqlParams);
      return;
    }

  }

  /**
   * Sort location array so primary element is first.
   *
   * @param array $locations
   */
  public static function sortPrimaryFirst(&$locations) {
    uasort($locations, [__CLASS__, 'primaryComparison']);
  }

  /**
   * compare 2 locations to see which should go first based on is_primary
   * (sort function for sortPrimaryFirst)
   * @param array $location1
   * @param array $location2
   * @return int
   */
  public static function primaryComparison($location1, $location2) {
    $l1 = $location1['is_primary'] ?? NULL;
    $l2 = $location2['is_primary'] ?? NULL;
    if ($l1 == $l2) {
      return 0;
    }
    return ($l1 < $l2) ? -1 : 1;
  }

}
