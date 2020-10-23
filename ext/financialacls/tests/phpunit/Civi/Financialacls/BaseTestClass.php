<?php

namespace Civi\Financialacls;

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use Civi\Test\ContactTestTrait;
use Civi\Test\Api3TestTrait;

/**
 * @group headless
 */
class BaseTestClass extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  use ContactTestTrait;
  use Api3TestTrait;

  /**
   * @return \Civi\Test\CiviEnvBuilder
   * @throws \CRM_Extension_Exception_ParseException
   */
  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * Set ACL permissions, overwriting any existing ones.
   *
   * @param array $permissions
   *   Array of permissions e.g ['access CiviCRM','access CiviContribute'],
   */
  protected function setPermissions(array $permissions) {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = $permissions;
    if (isset(\Civi::$statics['CRM_Financial_BAO_FinancialType'])) {
      unset(\Civi::$statics['CRM_Financial_BAO_FinancialType']);
    }
  }

  protected function setupLoggedInUserWithLimitedFinancialTypeAccess(): void {
    $this->setPermissions([
      'access CiviCRM',
      'access CiviContribute',
      'edit contributions',
      'delete in CiviContribute',
      'view contributions of type Donation',
      'delete contributions of type Donation',
      'add contributions of type Donation',
      'edit contributions of type Donation',
    ]);
    \Civi::settings()->set('acl_financial_type', TRUE);
    $this->createLoggedInUser();
  }

}
