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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


namespace api\v4;

use Civi\Test;
use Civi\Test\Api4TestTrait;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group headless
 */
class Api4TestBase extends TestCase implements HeadlessInterface {

  use Api4TestTrait;
  use Test\GenericAssertionsTrait;

  /**
   * Components to enable for this test class, or ['*'] for all components.
   * NULL means use the default enabled components.
   *
   * @var array|null
   */
  protected ?array $enableComponents = NULL;

  /**
   * @see CiviUnitTestCase
   *
   * @param string $name
   * @param array $data
   * @param string $dataName
   */
  public function __construct($name = NULL, array $data = [], $dataName = '') {
    parent::__construct($name, $data, $dataName);
    error_reporting(E_ALL);
  }

  public function setUpHeadless(): CiviEnvBuilder {
    $env = Test::headless()->apply();
    $this->applyComponentSettings();
    return $env;
  }

  protected function applyComponentSettings(): void {
    \CRM_Core_DAO::init(CIVICRM_DSN);
    $currentComponents = \Civi::settings()->get('enable_components') ?: [];
    if ($this->enableComponents === ['*']) {
      \CRM_Core_BAO_ConfigSetting::enableAllComponents();
    }
    elseif (is_array($this->enableComponents)) {
      if (array_diff($this->enableComponents, $currentComponents) || array_diff($currentComponents, $this->enableComponents)) {
        \CRM_Core_BAO_ConfigSetting::setEnabledComponents($this->enableComponents);
      }
    }
    elseif ($currentComponents != \Civi::settings()->getDefault('enable_components')) {
      \CRM_Core_BAO_ConfigSetting::setEnabledComponents(\Civi::settings()->getDefault('enable_components'));
    }
  }

  public static function tearDownAfterClass(): void {
    \CRM_Core_DAO::init(CIVICRM_DSN);
    if (\Civi::settings()->get('enable_components') != \Civi::settings()->getDefault('enable_components')) {
      \CRM_Core_BAO_ConfigSetting::setEnabledComponents(\Civi::settings()->getDefault('enable_components'));
    }
    parent::tearDownAfterClass();
  }

  /**
   * Post test cleanup.
   */
  public function tearDown(): void {
    $this->conditionallyDeleteTestRecords();
    \CRM_Utils_Time::resetTime();
    parent::tearDown();
  }

  /**
   * Quick clean by emptying tables created for the test.
   *
   * @param array{tablesToTruncate: array} $params
   */
  public function cleanup(array $params): void {
    $params += [
      'tablesToTruncate' => [],
    ];
    \CRM_Core_DAO::executeQuery('SET FOREIGN_KEY_CHECKS = 0;');
    foreach ($params['tablesToTruncate'] as $table) {
      \Civi::log()->info('truncating: ' . $table);
      $sql = "TRUNCATE TABLE $table";
      \CRM_Core_DAO::executeQuery($sql);
    }
    \CRM_Core_DAO::executeQuery('SET FOREIGN_KEY_CHECKS = 1;');
  }

}
