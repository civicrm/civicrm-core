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

use Civi\Api4\Relationship;
use Civi\Api4\RelationshipType;
use Civi\Core\Event\PreEvent;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Contact_BAO_RelationshipType extends CRM_Contact_DAO_RelationshipType implements \Civi\Core\HookInterface {

  /**
   * @deprecated
   * @param array $params
   * @param array $defaults
   * @return self|null
   */
  public static function retrieve($params, &$defaults) {
    return self::commonRetrieve(self::class, $params, $defaults);
  }

  /**
   * @deprecated - this bypasses hooks.
   * @param int $id
   * @param bool $is_active
   * @return bool
   */
  public static function setIsActive($id, $is_active) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return CRM_Core_DAO::setFieldValue('CRM_Contact_DAO_RelationshipType', $id, 'is_active', $is_active);
  }

  /**
   * @deprecated
   * @param array $params
   * @return CRM_Contact_DAO_RelationshipType
   */
  public static function add($params) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return self::writeRecord($params);
  }

  /**
   * @deprecated
   * @param int $relationshipTypeId
   * @throws CRM_Core_Exception
   * @return mixed
   */
  public static function del($relationshipTypeId) {
    CRM_Core_Error::deprecatedFunctionWarning('deleteRecord');
    return static::deleteRecord(['id' => $relationshipTypeId]);
  }

  /**
   * Callback for hook_civicrm_pre().
   *
   * @param \Civi\Core\Event\PreEvent $event
   * @throws \CRM_Core_Exception
   */
  public static function self_hook_civicrm_pre(PreEvent $event): void {
    if ($event->action === 'create') {
      // Set name to label if not set
      if (empty($event->params['label_a_b']) && !empty($event->params['name_a_b'])) {
        $event->params['label_a_b'] = $event->params['name_a_b'];
      }
      if (empty($event->params['label_b_a']) && !empty($event->params['name_b_a'])) {
        $event->params['label_b_a'] = $event->params['name_b_a'];
      }

      // set label to name if it's not set
      if (empty($event->params['name_a_b']) && !empty($event->params['label_a_b'])) {
        $event->params['name_a_b'] = $event->params['label_a_b'];
      }
      if (empty($event->params['name_b_a']) && !empty($event->params['label_b_a'])) {
        $event->params['name_b_a'] = $event->params['label_b_a'];
      }
    }
    if ($event->action === 'delete') {
      // Delete all existing relationships with this type
      Relationship::delete(FALSE)
        ->addWhere('relationship_type_id', '=', $event->id)
        ->execute();
    }
  }

  /**
   * Callback for hook_civicrm_post().
   * @param \Civi\Core\Event\PostEvent $event
   */
  public static function self_hook_civicrm_post(\Civi\Core\Event\PostEvent $event) {
    CRM_Core_PseudoConstant::relationshipType('label', TRUE);
    CRM_Core_PseudoConstant::relationshipType('name', TRUE);
    CRM_Core_PseudoConstant::flush();
    Civi::cache('metadata')->clear();
  }

  /**
   * Get the id of the employee relationship, checking it is valid.
   * We check that contact_type_a is Individual, but not contact_type_b because there's
   * nowhere in the code that requires it to be Organization.
   *
   * @return int
   *
   * @throws \CRM_Core_Exception
   */
  public static function getEmployeeRelationshipTypeID(): int {
    try {
      if (!Civi::cache('metadata')->has(__CLASS__ . __FUNCTION__)) {
        $relationship = RelationshipType::get(FALSE)
          ->addWhere('name_a_b', '=', 'Employee of')
          ->addWhere('contact_type_a', '=', 'Individual')
          ->addSelect('id')->execute()->first();
        if (empty($relationship)) {
          throw new CRM_Core_Exception('no valid relationship');
        }
        Civi::cache('metadata')->set(__CLASS__ . __FUNCTION__, $relationship['id']);
      }
    }
    catch (CRM_Core_Exception $e) {
      throw new CRM_Core_Exception(ts("You seem to have deleted the relationship type 'Employee of'"));
    }
    return Civi::cache('metadata')->get(__CLASS__ . __FUNCTION__);
  }

}
