<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
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
 * This class contain function for IM handling
 */
class CRM_Core_BAO_IM extends CRM_Core_DAO_IM {

  /**
   * takes an associative array and adds im
   *
   * @param array  $params         (reference ) an assoc array of name/value pairs
   *
   * @return object       CRM_Core_BAO_IM object on success, null otherwise
   * @access public
   * @static
   */
  static function add(&$params) {
    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'IM', CRM_Utils_Array::value('id', $params), $params);

    $im = new CRM_Core_DAO_IM();
    $im->copyValues($params);
    $im->save();

    CRM_Utils_Hook::post($hook, 'IM', $im->id, $im);
    return $im;
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
  static function &getValues($entityBlock) {
    return CRM_Core_BAO_Block::getValues('im', $entityBlock);
  }

  /**
   * Get all the ims for a specified contact_id, with the primary im being first
   *
   * @param int $id the contact id
   *
   * @param bool $updateBlankLocInfo
   *
   * @return array  the array of im details
   * @access public
   * @static
   */
  static function allIMs($id, $updateBlankLocInfo = FALSE) {
    if (!$id) {
      return NULL;
    }

    $query = "
SELECT civicrm_im.name as im, civicrm_location_type.name as locationType, civicrm_im.is_primary as is_primary,
civicrm_im.id as im_id, civicrm_im.location_type_id as locationTypeId,
civicrm_im.provider_id as providerId
FROM      civicrm_contact
LEFT JOIN civicrm_im ON ( civicrm_im.contact_id = civicrm_contact.id )
LEFT JOIN civicrm_location_type ON ( civicrm_im.location_type_id = civicrm_location_type.id )
WHERE
  civicrm_contact.id = %1
ORDER BY
  civicrm_im.is_primary DESC, im_id ASC ";
    $params = array(1 => array($id, 'Integer'));

    $ims   = $values = array();
    $dao   = CRM_Core_DAO::executeQuery($query, $params);
    $count = 1;
    while ($dao->fetch()) {
      $values = array(
        'locationType' => $dao->locationType,
        'is_primary' => $dao->is_primary,
        'id' => $dao->im_id,
        'name' => $dao->im,
        'locationTypeId' => $dao->locationTypeId,
        'providerId' => $dao->providerId,
      );

      if ($updateBlankLocInfo) {
        $ims[$count++] = $values;
      }
      else {
        $ims[$dao->im_id] = $values;
      }
    }
    return $ims;
  }

  /**
   * Get all the ims for a specified location_block id, with the primary im being first
   *
   * @param array  $entityElements the array containing entity_id and
   * entity_table name
   *
   * @return array  the array of im details
   * @access public
   * @static
   */
  static function allEntityIMs(&$entityElements) {
    if (empty($entityElements)) {
      return NULL;
    }


    $entityId = $entityElements['entity_id'];
    $entityTable = $entityElements['entity_table'];


    $sql = "SELECT cim.name as im, ltype.name as locationType, cim.is_primary as is_primary, cim.id as im_id, cim.location_type_id as locationTypeId
FROM civicrm_loc_block loc, civicrm_im cim, civicrm_location_type ltype, {$entityTable} ev
WHERE ev.id = %1
AND   loc.id = ev.loc_block_id
AND   cim.id IN (loc.im_id, loc.im_2_id)
AND   ltype.id = cim.location_type_id
ORDER BY cim.is_primary DESC, im_id ASC ";

    $params = array(1 => array($entityId, 'Integer'));

    $ims = array();
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    while ($dao->fetch()) {
      $ims[$dao->im_id] = array(
        'locationType' => $dao->locationType,
        'is_primary' => $dao->is_primary,
        'id' => $dao->im_id,
        'name' => $dao->im,
        'locationTypeId' => $dao->locationTypeId,
      );
    }
    return $ims;
  }

  /**
   * Call common delete function
   */
  static function del($id) {
    return CRM_Contact_BAO_Contact::deleteObjectWithPrimary('IM', $id);
  }
}

