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
class CRM_Core_BAO_OptionGroup extends CRM_Core_DAO_OptionGroup {

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Core_BAO_OptionGroup object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $optionGroup = new CRM_Core_DAO_OptionGroup();
    $optionGroup->copyValues($params);
    if ($optionGroup->find(TRUE)) {
      CRM_Core_DAO::storeValues($optionGroup, $defaults);
      return $optionGroup;
    }
    return NULL;
  }

  /**
   * update the is_active flag in the db
   *
   * @param int      $id        id of the database record
   * @param boolean  $is_active value we want to set the is_active field
   *
   * @return Object             DAO object on sucess, null otherwise
   * @static
   */
  static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_OptionGroup', $id, 'is_active', $is_active);
  }

  /**
   * function to add the Option Group
   *
   * @param array $params reference array contains the values submitted by the form
   * @param array $ids    reference array contains the id
   *
   * @access public
   * @static
   *
   * @return object
   */
  static function add(&$params, &$ids) {
    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
    $params['is_default'] = CRM_Utils_Array::value('is_default', $params, FALSE);

    // action is taken depending upon the mode
    $optionGroup = new CRM_Core_DAO_OptionGroup();
    $optionGroup->copyValues($params);;

    if ($params['is_default']) {
      $query = "UPDATE civicrm_option_group SET is_default = 0";
      CRM_Core_DAO::executeQuery($query);
    }

    $optionGroup->id = CRM_Utils_Array::value('optionGroup', $ids);
    $optionGroup->save();
    return $optionGroup;
  }

  /**
   * Function to delete Option Group
   *
   * @param  int  $optionGroupId     Id of the Option Group to be deleted.
   *
   * @return void
   *
   * @access public
   * @static
   */
  static function del($optionGroupId) {
    // need to delete all option value field before deleting group
    $optionValue = new CRM_Core_DAO_OptionValue();
    $optionValue->option_group_id = $optionGroupId;
    $optionValue->delete();

    $optionGroup = new CRM_Core_DAO_OptionGroup();
    $optionGroup->id = $optionGroupId;
    $optionGroup->delete();
  }

  /**
   * Function to get title of the option group
   *
   * @param  int  $optionGroupId     Id of the Option Group.
   *
   * @return String title
   *
   * @access public
   * @static
   */
  static function getTitle($optionGroupId) {
    $optionGroup = new CRM_Core_DAO_OptionGroup();
    $optionGroup->id = $optionGroupId;
    $optionGroup->find(TRUE);
    return $optionGroup->name;
  }

  /**
   * Function to copy the option group and values
   *
   * @param  String $component      - component page for which custom
   *                                  option group and values need to be copied
   * @param  int    $fromId         - component page id on which
   *                                  basis copy is to be made
   * @param  int    $toId           - component page id to be copied onto
   * @param  int    $defaultId      - default custom value id on the
   *                                  component page
   * @param  String $discountSuffix - discount suffix for the discounted
   *                                  option group
   *
   * @return int   $id              - default custom value id for the
   *                                 copied component page
   *
   * @access public
   * @static
   */
  static function copyValue($component, $fromId, $toId, $defaultId = FALSE, $discountSuffix = NULL) {
    $page = '_page';
    if ($component == 'event') {
      //fix for CRM-3391.
      //as for event we remove 'page' from group name.
      $page = NULL;
    }
    elseif ($component == 'price') {
      $page = '_field';
    }

    $fromGroupName = 'civicrm_' . $component . $page . '.amount.' . $fromId . $discountSuffix;
    $toGroupName = 'civicrm_' . $component . $page . '.amount.' . $toId . $discountSuffix;

    $optionGroupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup',
      $fromGroupName,
      'id',
      'name'
    );
    if ($optionGroupId) {
      $copyOptionGroup = &CRM_Core_DAO::copyGeneric('CRM_Core_DAO_OptionGroup',
        array('name' => $fromGroupName),
        array('name' => $toGroupName)
      );

      $copyOptionValue = &CRM_Core_DAO::copyGeneric('CRM_Core_DAO_OptionValue',
        array('option_group_id' => $optionGroupId),
        array('option_group_id' => $copyOptionGroup->id)
      );

      if ($discountSuffix) {
        $copyDiscount =& CRM_Core_DAO::copyGeneric( 'CRM_Core_DAO_Discount',
          array(
            'entity_id' => $fromId,
            'entity_table' => 'civicrm_' . $component,
            'option_group_id' => $optionGroupId,
          ),
          array(
            'entity_id' => $toId,
            'option_group_id' => $copyOptionGroup->id,
          )
        );
      }

      if ($defaultId) {
        $query = "
SELECT second.id default_id
FROM civicrm_option_value first, civicrm_option_value second
WHERE second.option_group_id =%1
AND first.option_group_id =%2
AND first.weight = second.weight
AND first.id =%3
";
        $params = array(1 => array($copyOptionGroup->id, 'Int'),
          2 => array($optionGroupId, 'Int'),
          3 => array($defaultId, 'Int'),
        );

        $dao = CRM_Core_DAO::executeQuery($query, $params);

        while ($dao->fetch()) {
          $id = $dao->default_id;
        }
        return $id;
      }
      return FALSE;
    }
  }
}

