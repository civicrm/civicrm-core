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
 * add static functions to include some common functionality
 * used across location sub object BAO classes
 *
 */
class CRM_Core_BAO_Block {

  /**
   * Fields that are required for a valid block.
   */
  static $requiredBlockFields = array(
    'email' => array('email'),
    'phone' => array('phone'),
    'im' => array('name'),
    'openid' => array('openid'),
  );

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
   */
  public static function &getValues($blockName, $params) {
    if (empty($params)) {
      return NULL;
    }
    $BAOString = 'CRM_Core_BAO_' . $blockName;
    $block = new $BAOString();

    $blocks = array();
    if (!isset($params['entity_table'])) {
      $block->contact_id = $params['contact_id'];
      if (!$block->contact_id) {
        CRM_Core_Error::fatal();
      }
      $blocks = self::retrieveBlock($block, $blockName);
    }
    else {
      $blockIds = self::getBlockIds($blockName, NULL, $params);

      if (empty($blockIds)) {
        return $blocks;
      }

      $count = 1;
      foreach ($blockIds as $blockId) {
        $block = new $BAOString();
        $block->id = $blockId['id'];
        $getBlocks = self::retrieveBlock($block, $blockName);
        $blocks[$count++] = array_pop($getBlocks);
      }
    }

    return $blocks;
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param Object $block
   *   Typically a Phone|Email|IM|OpenID object.
   * @param string $blockName
   *   Name of the above object.
   *
   * @return array
   *   Array of $block objects.
   */
  public static function retrieveBlock(&$block, $blockName) {
    // we first get the primary location due to the order by clause
    $block->orderBy('is_primary desc, id');
    $block->find();

    $count = 1;
    $blocks = array();
    while ($block->fetch()) {
      CRM_Core_DAO::storeValues($block, $blocks[$count]);
      //unset is_primary after first block. Due to some bug in earlier version
      //there might be more than one primary blocks, hence unset is_primary other than first
      if ($count > 1) {
        unset($blocks[$count]['is_primary']);
      }
      $count++;
    }

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
      if (CRM_Utils_System::isNull(CRM_Utils_Array::value($field, $params))) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Check if the current block exits.
   *
   * @param string $blockName
   *   Bloack name.
   * @param array $params
   *   Associated array of submitted fields.
   *
   * @return bool
   *   true if the block exits, otherwise false
   */
  public static function blockExists($blockName, &$params) {
    // return if no data present
    if (empty($params[$blockName]) || !is_array($params[$blockName])) {
      return FALSE;
    }

    return TRUE;
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
    $allBlocks = array();

    $name = ucfirst($blockName);
    if ($blockName == 'im') {
      $name = 'IM';
    }
    elseif ($blockName == 'openid') {
      $name = 'OpenID';
    }

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
    elseif (!empty($entityElements) && $blockName != 'openid') {
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
   *   (reference ) an assoc array of name/value pairs.
   * @param null $entity
   * @param int $contactId
   *
   * @return object
   *   CRM_Core_BAO_Block object on success, null otherwise
   */
  public static function create($blockName, &$params, $entity = NULL, $contactId = NULL) {

    if (!self::blockExists($blockName, $params)) {
      return NULL;
    }

    // Set up required information / defaults
    $entityElements = $blocks = array();
    $resetPrimary = $primarySet = $billingSet = FALSE;
    $contactId = NULL;

    $baoString = 'CRM_Core_BAO_' . ucfirst($blockName);
    $updateBlankLocInfo = CRM_Utils_Array::value('updateBlankLocInfo', $params, FALSE);

    if ($entity) {
      $entityElements = array(
        'entity_table' => $params['entity_table'],
        'entity_id' => $params['entity_id'],
      );
    }
    else {
      $contactId = $params['contact_id'];
    }

    // Get current and submitted values
    $existingValues = self::getBlockIds($blockName, $contactId, $entityElements, $updateBlankLocInfo);
    $submittedValues = $params[$blockName];

    // For each submitted value
    foreach ($submittedValues as $count => $submittedValue) {

      // Set the contact ID
      $submittedValue['contact_id'] = $contactId;

      // If this is a primary value, and we haven't unset a primary value yet, and there are values on the contact
      // Then unset any primary value currently on the Contact
      if (!empty($submittedValue['is_primary']) && !$resetPrimary && is_array($existingValues)) {
        foreach ($existingValues as $existingValueId => $existingValue) {
          if (!empty($existingValue['is_primary'])) {

            // @todo Can we refactor this?
            $block = new $baoString();
            $block->selectAdd();
            $block->selectAdd("id, is_primary");
            $block->id = $existingValue['id'];
            if ($block->find(TRUE)) {
              $block->is_primary = FALSE;
              $block->save();
            }
            $block->free();

            // Stop looping since we found a match
            $resetPrimary = TRUE;
            break;
          }
        }
      }

      // If there is already an ID passed in
      if (!empty($submittedValue['id'])) {
        // If the ID already exists on the contact
        // Then we don't want to match on it later, so unset it
        if (array_key_exists($submittedValue['id'], $existingValues)) {
          unset($existingValues[$existingValueId]);
        }
        // Otherwise it is a new value, so ignore the passed in ID
        else {
          unset($submittedValue['id']);
        }
      }

      // Otherwise, if there was no ID passed in
      // Loop through the current values, and find the first match on location type
      else {
        foreach ($existingValues as $existingValueId => $existingValue) {
          if ($existingValue['locationTypeId'] == $submittedValue['location_type_id']) {

            // Also require a match on 'type id' for phone and IM blocks
            $matchFound = FALSE;

            if ($blockName == 'phone') {
              if (CRM_Utils_Array::value('phoneTypeId', $existingValue) == CRM_Utils_Array::value('phone_type_id', $submittedValue)) {
                $matchFound = TRUE;
              }
            }
            elseif ($blockName == 'im') {
              if (CRM_Utils_Array::value('providerId', $existingValue) == CRM_Utils_Array::value('provider_id', $submittedValue)) {
                $matchFound = TRUE;
              }
            }
            else {
              $matchFound = TRUE;
            }

            // If we found a match
            if ($matchFound) {
              // Match up the ID
              $submittedValue['id'] = $existingValue['id'];
              // If the submitted value is not primary, but the matched value is
              // Then set the submitted value to be primary
              if (empty($submittedValue['is_primary']) && !empty($existingValue['is_primary'])) {
                $submittedValue['is_primary'] = 1;
              }
              // Remove the original value from the array so we don't match on it again
              unset($existingValues[$existingValueId]);
              break;
            }
          }
        }
      }

      // Check if data exists in the submitted value
      $dataExists = self::dataExists(self::$requiredBlockFields[$blockName], $submittedValue);

      // If there is data
      if ($dataExists) {

        // "There can be only one" primary / billing block
        if (!$primarySet && !empty($submittedValue['is_primary'])) {
          $submittedValue['is_primary'] = 1;
          $primarySet = TRUE;
        }
        else {
          $contactFields['is_primary'] = 0;
        }

        if (!$billingSet && !empty($submittedValue['is_billing'])) {
          $submittedValue['is_billing'] = 1;
          $billingSet = TRUE;
        }
        else {
          $contactFields['is_billing'] = 0;
        }

        // Add the value to the list of blocks
        $blocks[] = $baoString::add($submittedValue);
      }

      // Otherwise, if there is no data, and there is an ID, and we are deleting 'blanked' values
      // Then delete it
      elseif (!empty($submittedValue['id']) && $updateBlankLocInfo) {
        self::blockDelete($blockName, array('id' => $submittedValue['id']));
      }

      // Otherwise we ignore it
      else {
      }

    }

    return $blocks;
  }

  /**
   * Delete block.
   *
   * @param string $blockName
   *   Block name.
   * @param int $params
   *   Associates array.
   *
   * @return void
   */
  public static function blockDelete($blockName, $params) {
    $name = ucfirst($blockName);
    if ($blockName == 'im') {
      $name = 'IM';
    }
    elseif ($blockName == 'openid') {
      $name = 'OpenID';
    }

    $baoString = 'CRM_Core_DAO_' . $name;
    $block = new $baoString();

    $block->copyValues($params);

    // CRM-11006 add call to pre and post hook for delete action
    CRM_Utils_Hook::pre('delete', $name, $block->id, CRM_Core_DAO::$_nullArray);
    $block->delete();
    CRM_Utils_Hook::post('delete', $name, $block->id, $block);
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
   * @throws API_Exception
   */
  public static function handlePrimary(&$params, $class) {
    $table = CRM_Core_DAO_AllCoreTables::getTableForClass($class);
    if (!$table) {
      throw new API_Exception("Failed to locate table for class [$class]");
    }

    // contact_id in params might be empty or the string 'null' so cast to integer
    $contactId = (int) CRM_Utils_Array::value('contact_id', $params);
    // If id is set & we haven't been passed a contact_id, retrieve it
    if (!empty($params['id']) && !isset($params['contact_id'])) {
      $entity = new $class();
      $entity->id = $params['id'];
      $entity->find(TRUE);
      $contactId = $entity->contact_id;
    }
    // If entity is not associated with contact, concept of is_primary not relevant
    if (!$contactId) {
      return;
    }

    // if params is_primary then set all others to not be primary & exit out
    if (!empty($params['is_primary'])) {
      $sql = "UPDATE $table SET is_primary = 0 WHERE contact_id = %1";
      $sqlParams = array(1 => array($contactId, 'Integer'));
      // we don't want to create unecessary entries in the log_ tables so exclude the one we are working on
      if (!empty($params['id'])) {
        $sql .= " AND id <> %2";
        $sqlParams[2] = array($params['id'], 'Integer');
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
       * CRM-10451
       */
      if ($existingEntities->N == 1 && $existingEntities->id == CRM_Utils_Array::value('id', $params)) {
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
    }
  }

  /**
   * Sort location array so primary element is first.
   *
   * @param array $locations
   */
  public static function sortPrimaryFirst(&$locations) {
    uasort($locations, 'self::primaryComparison');
  }

  /**
   * compare 2 locations to see which should go first based on is_primary
   * (sort function for sortPrimaryFirst)
   * @param array $location1
   * @param array $location2
   * @return int
   */
  public static function primaryComparison($location1, $location2) {
    $l1 = CRM_Utils_Array::value('is_primary', $location1);
    $l2 = CRM_Utils_Array::value('is_primary', $location2);
    if ($l1 == $l2) {
      return 0;
    }
    return ($l1 < $l2) ? -1 : 1;
  }

}
