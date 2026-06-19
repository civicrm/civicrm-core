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

namespace Civi\Api4\Action\DashboardContact;

use Civi\Api4\Dashboard;
use Civi\Api4\DashboardContact;
use Civi\Api4\Generic\Result;

/**
 * Initialize default dashlets for a contact.
 *
 * @method $this setContactId(int $contactId) Set contact ID
 * @method int getContactId() Get contact ID
 * @method $this setForce(bool $force) Set force flag
 * @method bool getForce() Get force flag
 */
class Initialize extends \Civi\Api4\Generic\AbstractAction {

  /**
   * Contact ID to initialize dashboard for. Defaults to logged-in contact.
   *
   * @var int|null
   */
  protected ?int $contactId = NULL;

  /**
   * Force re-initialization even if already initialized.
   *
   * @var bool
   */
  protected bool $force = FALSE;

  /**
   * @param \Civi\Api4\Generic\Result $result
   */
  public function _run(Result $result) {
    $contactId = $this->contactId ?? \CRM_Core_Session::getLoggedInContactID();
    if (!$contactId) {
      throw new \CRM_Core_Exception("Cannot initialize dashboard: No contact ID found.");
    }

    if (!$this->force) {
      $hasDashlets = (bool) DashboardContact::get(FALSE)
        ->selectRowCount()
        ->addWhere('contact_id', '=', $contactId)
        ->addWhere('dashboard_id.domain_id', '=', 'current_domain')
        ->setLimit(1)
        ->execute()
        ->count();
      if ($hasDashlets) {
        return;
      }
    }
    else {
      DashboardContact::delete(FALSE)
        ->addWhere('contact_id', '=', $contactId)
        ->addWhere('dashboard_id.domain_id', '=', 'current_domain')
        ->execute();
    }

    $allDashlets = (array) Dashboard::get(FALSE)
      ->addWhere('domain_id', '=', 'current_domain')
      ->addWhere('is_active', '=', TRUE)
      ->execute()
      ->indexBy('name');

    $defaultDashlets = [];
    $defaults = ['blog' => 1, 'getting-started' => '0'];
    foreach ($defaults as $name => $column) {
      if (!empty($allDashlets[$name]['id'])) {
        $defaultDashlets[$name] = [
          'dashboard_id' => $allDashlets[$name]['id'],
          'is_active' => 1,
          'column_no' => $column,
        ];
      }
    }
    \CRM_Utils_Hook::dashboard_defaults($allDashlets, $defaultDashlets);
    if (is_array($defaultDashlets) && !empty($defaultDashlets)) {
      $saved = DashboardContact::save(FALSE)
        ->setRecords($defaultDashlets)
        ->setDefaults(['contact_id' => $contactId])
        ->setMatch(['contact_id', 'dashboard_id'])
        ->execute();
      foreach ($saved as $record) {
        $result[] = $record;
      }
    }
  }

  /**
   * @return bool
   */
  public function isAuthorized(): bool {
    $currentUserId = \CRM_Core_Session::getLoggedInContactID();
    $contactId = $this->contactId;

    if ($contactId !== NULL && $contactId !== $currentUserId) {
      return \CRM_Core_Permission::check('administer CiviCRM');
    }

    return TRUE;
  }

}
