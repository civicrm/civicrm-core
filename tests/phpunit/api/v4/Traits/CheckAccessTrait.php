<?php
namespace api\v4\Traits;

/**
 * Define an implementation of `hook_civicrm_checkAccess` in which access-control decisions are
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
   * @param string $entity
   * @param string $action
   * @param array $record
   * @param int|null $contactID
   * @param bool $granted
   * @see \CRM_Utils_Hook::checkAccess()
   */
  public function hook_civicrm_checkAccess(string $entity, string $action, array $record, ?int $contactID, ?bool &$granted) {
    $key = "{$entity}::{$action}";
    if (isset($this->checkAccessGrants[$key])) {
      $granted = $this->checkAccessGrants[$key];
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
  }

}
