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

use Civi\Api4\MembershipBlock;
use Civi\Core\Event\PostEvent;
use Civi\Core\HookInterface;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Member_BAO_MembershipBlock extends CRM_Member_DAO_MembershipBlock implements HookInterface {

  /**
   * Create or update a MembershipBlock.
   *
   * @deprecated
   * @param array $params
   * @return CRM_Member_DAO_MembershipBlock
   */
  public static function create($params) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return self::writeRecord($params);
  }

  /**
   * Delete membership Blocks.
   *
   * @param int $id
   * @deprecated
   * @return bool
   */
  public static function del($id) {
    CRM_Core_Error::deprecatedFunctionWarning('deleteRecord');
    return (bool) self::deleteRecord(['id' => $id]);
  }

  /**
   * Update MembershipBlocks if autorenew option is changed.
   *
   * Available auto-renew options are
   * 0 - Autorenew unavailable
   * 1 - Give option
   * 2 - Force auto-renewal
   *
   * In the case of 0 or 2 we need to ensure that all membership blocks are
   * set to the same value. If the option is 1 no action is required as
   * all 3 options are then valid at the membership block level.
   *
   * https://issues.civicrm.org/jira/browse/CRM-15573
   *
   * @param \Civi\Core\Event\PostEvent $event
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  public static function on_hook_civicrm_post(PostEvent $event): void {
    if ($event->entity === 'MembershipType' && $event->action === 'edit') {
      $autoRenewOption = $event->object->auto_renew;
      if ($event->id && $autoRenewOption !== NULL && ((int) $autoRenewOption) !== 1) {
        $autoRenewOption = (int) $autoRenewOption;
        $membershipBlocks = MembershipBlock::get(FALSE)->execute();
        foreach ($membershipBlocks as $membershipBlock) {
          if ($membershipBlock['membership_types'] && array_key_exists($event->id, $membershipBlock['membership_types'])
            && ((int) $membershipBlock['membership_types'][$event->id]) !== $autoRenewOption
          ) {
            $membershipBlock['membership_types'][$event->id] = $autoRenewOption;
            MembershipBlock::update(FALSE)->setValues($membershipBlock)->execute();
          }
        }
      }
    }
  }

}
