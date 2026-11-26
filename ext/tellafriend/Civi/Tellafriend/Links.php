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

namespace Civi\Tellafriend;

use Civi\Api4\PCP;
use Civi\Core\Attribute\CiviEventListener;
use Civi\Core\Event\GenericHookEvent;
use CRM_Friend_ExtensionUtil as E;

class Links {

  /**
   * Add the Tell Friends link to approved personal campaign pages whose block has Tell a Friend enabled.
   */
  #[CiviEventListener(event: 'hook_civicrm_links')]
  public static function addPcpLink(GenericHookEvent $event): void {
    if ($event->op === 'pcp.user.actions.all' && self::isTellFriendAvailable((int) $event->objectId)) {
      // PCP keys its links by action and reads the keys.
      $event->links[\CRM_Core_Action::DETACH] = [
        'name' => E::ts('Tell Friends'),
        'url' => 'civicrm/friend',
        'qs' => 'eid=%%pcpId%%&blockId=%%pcpBlock%%&reset=1&pcomponent=pcp&component=%%pageComponent%%',
        'title' => E::ts('Send emails inviting your friends to support your campaign!'),
        'weight' => -15,
      ];
    }
  }

  /**
   * @param int $pcpID
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  private static function isTellFriendAvailable(int $pcpID): bool {
    if (!$pcpID) {
      return FALSE;
    }
    $pcp = PCP::get(FALSE)
      ->addSelect('status_id:name', 'pcp_block_id.is_tellfriend_enabled')
      ->addWhere('id', '=', $pcpID)
      ->execute()->first();
    return $pcp && $pcp['status_id:name'] === 'Approved' && $pcp['pcp_block_id.is_tellfriend_enabled'];
  }

}
