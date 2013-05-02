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
 * add static functions to include some common functionality
 * used across location sub object BAO classes
 *
 */
class CRM_Core_BAO_Block {

  /**
   * Fields that are required for a valid block
   */
  static $requiredBlockFields = array(
    'email' => array('email'),
    'phone' => array('phone'),
    'im' => array('name'),
    'openid' => array('openid')
  );

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param Object $block         typically a Phone|Email|IM|OpenID object
   * @param string $blockName     name of the above object
   * @param array  $params        input parameters to find object
   * @param array  $values        output values of the object
   *
   * @return array of $block objects.
   * @access public
   * @static
   */
  static function &getValues($blockName, $params) {
    if (empty($params)) {
      return NULL;
    }
    eval('$block = new CRM_Core_BAO_' . $blockName . '( );');

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
        eval('$block = new CRM_Core_BAO_' . $blockName . '( );');
        $block->id        = $blockId['id'];
        $getBlocks        = self::retrieveBlock($block, $blockName);
        $blocks[$count++] = array_pop($getBlocks);
      }
    }

    return $blocks;
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param Object $block         typically a Phone|Email|IM|OpenID object
   * @param string $blockName     name of the above object
   * @param array  $values        output values of the object
   *
   * @return array of $block objects.
   * @access public
   * @static
   */
  static function retrieveBlock(&$block, $blockName) {
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
   * check if the current block object has any valid data
   *
   * @param array  $blockFields   array of fields that are of interest for this object
   * @param array  $params        associated array of submitted fields
   *
   * @return boolean              true if the block has data, otherwise false
   * @access public
   * @static
   */
  static function dataExists($blockFields, &$params) {
    foreach ($blockFields as $field) {
      if (CRM_Utils_System::isNull(CRM_Utils_Array::value($field, $params))) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * check if the current block exits
   *
   * @param string  $blockName   bloack name
   * @param array   $params      associated array of submitted fields
   *
   * @return boolean             true if the block exits, otherwise false
   * @access public
   * @static
   */
  static function blockExists($blockName, &$params) {
    // return if no data present
    if (!CRM_Utils_Array::value($blockName, $params) || !is_array($params[$blockName])) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Function to get all block ids for a contact
   *
   * @param string $blockName block name
   * @param int    $contactId contact id
   *
   * @return array $contactBlockIds formatted array of block ids
   *
   * @access public
   * @static
   */
  static function getBlockIds($blockName, $contactId = NULL, $entityElements = NULL, $updateBlankLocInfo = FALSE) {
    $allBlocks = array();
    $name = ucfirst($blockName);
    if ($blockName == 'im') {
      $name = 'IM';
    }
    elseif ($blockName == 'openid') {
      $name = 'OpenID';
    }

    if ($contactId) {
      eval('$allBlocks = CRM_Core_BAO_' . $name . '::all' . $name . 's( $contactId, $updateBlankLocInfo );');
    }
    elseif (!empty($entityElements) && $blockName != 'openid') {
      eval('$allBlocks = CRM_Core_BAO_' . $name . '::allEntity' . $name . 's( $entityElements );');
    }

    return $allBlocks;
  }

  /**
   * takes an associative array and creates a block
   *
   * @param string $blockName      block name
   * @param array  $params         (reference ) an assoc array of name/value pairs
   * @param array  $requiredFields fields that's are required in a block
   *
   * @return object       CRM_Core_BAO_Block object on success, null otherwise
   * @access public
   * @static
   */
  static function create($blockName, &$params, $entity = NULL, $contactId = NULL) {
    if (!self::blockExists($blockName, $params)) {
      return NULL;
    }

    $name           = ucfirst($blockName);
    $contactId      = NULL;
    $isPrimary      = $isBilling = TRUE;
    $entityElements = $blocks = array();

    if ($entity) {
      $entityElements = array(
        'entity_table' => $params['entity_table'],
        'entity_id' => $params['entity_id'],
      );
    }
    else {
      $contactId = $params['contact_id'];
    }

    $updateBlankLocInfo = CRM_Utils_Array::value('updateBlankLocInfo', $params, FALSE);

    //get existsing block ids.
    $blockIds = self::getBlockIds($blockName, $contactId, $entityElements, $updateBlankLocInfo);

    if (!$updateBlankLocInfo) {
      $resetPrimaryId = NULL;
      $primaryId = FALSE;
      foreach ($params[$blockName] as $count => $value) {
        $blockId = CRM_Utils_Array::value('id', $value);
        if ($blockId) {
          if (is_array($blockIds)
            && array_key_exists($blockId, $blockIds)
          ) {
            unset($blockIds[$blockId]);
          }
          else {
            unset($value['id']);
          }
        }
        //lets allow to update primary w/ more cleanly.
        if (!$resetPrimaryId &&
          CRM_Utils_Array::value('is_primary', $value)
        ) {
          $primaryId = TRUE;
          if (is_array($blockIds)) {
            foreach ($blockIds as $blockId => $blockValue) {
              if (CRM_Utils_Array::value('is_primary', $blockValue)) {
                $resetPrimaryId = $blockId;
                break;
              }
            }
          }
          if ($resetPrimaryId) {
            eval('$block = new CRM_Core_BAO_' . $blockName . '( );');
            $block->selectAdd();
            $block->selectAdd("id, is_primary");
            $block->id = $resetPrimaryId;
            if ($block->find(TRUE)) {
              $block->is_primary = FALSE;
              $block->save();
            }
            $block->free();
          }
        }
      }
    }

    foreach ($params[$blockName] as $count => $value) {
      if (!is_array($value)) {
        continue;
      }
      $contactFields = array(
        'contact_id' => $contactId,
        'location_type_id' => CRM_Utils_Array::value('location_type_id', $value),
      );

      //check for update
      if (!CRM_Utils_Array::value('id', $value) &&
        is_array($blockIds) && !empty($blockIds)
      ) {
        foreach ($blockIds as $blockId => $blockValue) {
          if ($updateBlankLocInfo) {
            if (CRM_Utils_Array::value($count, $blockIds)) {
              $value['id'] = $blockIds[$count]['id'];
              unset($blockIds[$count]);
            }
          }
          else {
            if ($blockValue['locationTypeId'] == CRM_Utils_Array::value('location_type_id', $value)) {
              $valueId = FALSE;

              if ($blockName == 'phone') {
                $phoneTypeBlockValue = CRM_Utils_Array::value('phoneTypeId', $blockValue);
                if ($phoneTypeBlockValue == $value['phone_type_id']) {
                  $valueId = TRUE;
                }
              }
              elseif ($blockName == 'im') {
                $providerBlockValue = CRM_Utils_Array::value('providerId', $blockValue);
                if ($providerBlockValue == $value['provider_id']) {
                  $valueId = TRUE;
                }
              }
              else {
                $valueId = TRUE;
              }

              if ($valueId) {
                //assigned id as first come first serve basis
                $value['id'] = $blockValue['id'];
                if (!$primaryId && CRM_Utils_Array::value('is_primary', $blockValue)) {
                  $value['is_primary'] = $blockValue['is_primary'];
                }
                unset($blockIds[$blockId]);
                break;
              }
            }
          }
        }
      }

      $dataExits = self::dataExists(self::$requiredBlockFields[$blockName], $value);

      // Note there could be cases when block info already exist ($value[id] is set) for a contact/entity
      // BUT info is not present at this time, and therefore we should be really careful when deleting the block.
      // $updateBlankLocInfo will help take appropriate decision. CRM-5969
      if (CRM_Utils_Array::value('id', $value) && !$dataExits && $updateBlankLocInfo) {
        //delete the existing record
        self::blockDelete($blockName, array('id' => $value['id']));
        continue;
      }
      elseif (!$dataExits) {
        continue;
      }

      if ($isPrimary && CRM_Utils_Array::value('is_primary', $value)) {
        $contactFields['is_primary'] = $value['is_primary'];
        $isPrimary = FALSE;
      }
      else {
        $contactFields['is_primary'] = 0;
      }

      if ($isBilling && CRM_Utils_Array::value('is_billing', $value)) {
        $contactFields['is_billing'] = $value['is_billing'];
        $isBilling = FALSE;
      }
      else {
        $contactFields['is_billing'] = 0;
      }

      $blockFields = array_merge($value, $contactFields);
      eval('$blocks[] = CRM_Core_BAO_' . $name . '::add( $blockFields );');
    }

    // we need to delete blocks that were deleted during update
    if ($updateBlankLocInfo && !empty($blockIds)) {
      foreach ($blockIds as $deleteBlock) {
        if (!CRM_Utils_Array::value('id', $deleteBlock)) {
          continue;
        }
        self::blockDelete($blockName, array('id' => $deleteBlock['id']));
      }
    }

    return $blocks;
  }

  /**
   * Function to delete block
   *
   * @param  string $blockName       block name
   * @param  int    $params          associates array
   *
   * @return void
   * @static
   */
  static function blockDelete($blockName, $params) {
    $name = ucfirst($blockName);
    if ($blockName == 'im') {
      $name = 'IM';
    }
    elseif ($blockName == 'openid') {
      $name = 'OpenID';
    }

    require_once "CRM/Core/DAO/{$name}.php";
    eval('$block = new CRM_Core_DAO_' . $name . '( );');

    $block->copyValues($params);
    /*
     * CRM-11006 add call to pre and post hook for delete action
     */
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
   *              @fixme - perhaps should choose by location_type
   *  #  empty - same as 0 as once we have checked first step
   *             we know if it should be 1 or 0
   *
   *  if $params['id'] is set $params['contact_id'] may need to be retrieved
   *
   *  @param array $params
   * @static
   */
  public static function handlePrimary(&$params, $class) {
    switch ($class) {
      case 'CRM_Core_BAO_Phone':
        $table = 'civicrm_phone';
        break;

      case 'CRM_Core_BAO_Email':
        $table = 'civicrm_email';
        break;

      case 'CRM_Core_BAO_Address':
        $table = 'civicrm_address';
        break;
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
    if (CRM_Utils_Array::value('is_primary', $params)) {
      $sql = "UPDATE $table SET is_primary = 0 WHERE contact_id = %1";
      $sqlParams = array(1 => array($contactId, 'Integer'));
      // we don't want to create unecessary entries in the log_ tables so exclude the one we are working on
      if(!empty($params['id'])){
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
      if ( $existingEntities->N == 1 && $existingEntities->id == CRM_Utils_Array::value( 'id', $params ) ) {
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
   * Sort location array so primary element is first
   * @param Array $location
   */
  static function sortPrimaryFirst(&$locations){
    uasort($locations, 'self::primaryComparison');
  }

/**
 * compare 2 locations to see which should go first based on is_primary
 * (sort function for sortPrimaryFirst)
 * @param array $location1
 * @param array_type $location2
 * @return number
 */
  static function primaryComparison($location1, $location2){
    $l1 = CRM_Utils_Array::value('is_primary', $location1);
    $l2 = CRM_Utils_Array::value('is_primary', $location2);
    if ($l1 == $l2) {
      return 0;
    }
    return ($l1 < $l2) ? -1 : 1;
  }
}

