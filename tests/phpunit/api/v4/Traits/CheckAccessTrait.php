<?php
namespace api\v4\Traits;

use Civi\Api4\Event\AuthorizeRecordEvent;

/**
 * Define an implementation of `civi.api4.authorizeRecord` in which access-control decisions are
 * based on a predefined list. For example:
 *
 *   $this->setCheckAccessGrants(['Contact::create' => TRUE]);
 *   Contact::create()->setValues(...)->execute();
 *
 * Note: Any class which uses this should implement the `HookInterface` so that the hook is picked up.
 */
trait CheckAccessTrait {

  /**
   * Specify whether to grant access to an entity-action via hook_checkAccess.
   *
   * @var array
   *   Array(string $entityAction => bool $grant).
   *   TRUE=>Allow. FALSE=>Deny. Undefined=>No preference.
   */
  private $checkAccessGrants = [];

  /**
   * Number of times hook_checkAccess has fired.
   * @var array
   */
  protected $checkAccessCounts = [];

  /**
   * Listen to 'civi.api4.authorizeRecord'. Override decisions with specified grants.
   *
   * @param \Civi\Api4\Event\AuthorizeRecordEvent $e
   */
  public function on_civi_api4_authorizeRecord(AuthorizeRecordEvent $e): void {
    $key = $e->getEntityName() . '::' . $e->getActionName();
    if (isset($this->checkAccessGrants[$key])) {
      $e->setAuthorized($this->checkAccessGrants[$key]);
      $this->checkAccessCounts[$key]++;
    }
  }

  /**
   * @param array $list
   *   Ex: ["Event::delete" => TRUE, "Contribution::delete" => FALSE]
   */
  protected function setCheckAccessGrants($list) {
    $this->checkAccessGrants = $this->checkAccessCounts = [];
    foreach ($list as $key => $grant) {
      $this->checkAccessGrants[$key] = $grant;
      $this->checkAccessCounts[$key] = 0;
    }
  }

  protected function resetCheckAccess() {
    $this->setCheckAccessGrants([]);
    // Grant the test user all permissions EXCEPT 'all CiviCRM permissions and ACLs' (which bypass ACL checks)
    $allPermissions = \CRM_Core_Permission::basicPermissions(TRUE);
    unset($allPermissions['all CiviCRM permissions and ACLs']);
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = array_keys($allPermissions);
  }

}
