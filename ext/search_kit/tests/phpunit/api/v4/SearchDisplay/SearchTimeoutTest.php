<?php
namespace Civi\ext\search_kit\tests\phpunit\api\v4\SearchDisplay;

// Not sure why this is needed but without it Jenkins crashed
require_once __DIR__ . '/../../../../../../../tests/phpunit/api/v4/Api4TestBase.php';

use api\v4\Api4TestBase;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class SearchTimeoutTest extends Api4TestBase implements TransactionalInterface {
  use \Civi\Test\ACLPermissionTrait;

  /**
   * @var int|null
   */
  private $assertedMaxExecutionTime = NULL;

  /**
   * @var \Exception|null
   */
  private $exceptionToThrowInHook = NULL;

  /**
   * Helper to check if the backtrace contains the query execution phase of SearchDisplay.
   */
  private function isInsideProcessResult(): bool {
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $inProcessResult = FALSE;
    foreach ($trace as $frame) {
      $class = $frame['class'] ?? '';
      $function = $frame['function'];
      if ($class === 'Civi\Api4\Action\SearchDisplay\Run' && $function === 'processResult') {
        $inProcessResult = TRUE;
      }
      if (in_array($function, ['preprocessLinks', 'preprocessLink', 'loadSearchDisplay', 'augmentSelectClause', 'applyFilters'], TRUE)) {
        return FALSE;
      }
    }
    return $inProcessResult;
  }

  /**
   * Implements hook_civicrm_selectWhereClause().
   */
  public function _hook_selectWhereClause($entity, &$clauses) {
    if ($entity === 'Contact' && $this->isInsideProcessResult()) {
      $this->assertedMaxExecutionTime = \CRM_Core_DAO::getMaxExecutionTime();
      if ($this->exceptionToThrowInHook) {
        throw $this->exceptionToThrowInHook;
      }
    }
  }

  public function setUpHeadless(): CiviEnvBuilder {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * Test the query timeout logic (setting and saved search overrides).
   */
  public function testQueryTimeout(): void {
    // 1. Verify default timeout matches the original database execution time (no change is made during search).
    $this->assertedMaxExecutionTime = NULL;
    $this->exceptionToThrowInHook = NULL;

    \CRM_Utils_Hook::singleton()->setHook('civicrm_selectWhereClause', [$this, '_hook_selectWhereClause']);

    try {
      $originalDbTimeout = \CRM_Core_DAO::getMaxExecutionTime();

      $lastName = uniqid(__FUNCTION__);
      $cid = $this->createTestRecord('Individual', ['last_name' => $lastName])['id'];

      $params = [
        'checkPermissions' => FALSE,
        'return' => 'page:1',
        'savedSearch' => [
          'api_entity' => 'Contact',
          'api_params' => [
            'version' => 4,
            'select' => ['id'],
            'where' => [['last_name', '=', $lastName]],
          ],
        ],
        'display' => NULL,
      ];

      // Ensure site setting is 0 initially.
      \Civi::settings()->revert('search_kit_timeout');

      civicrm_api4('SearchDisplay', 'run', $params);
      // Since timeout was 0, it should not have swapped the execution time (matching the original value).
      $this->assertEquals($originalDbTimeout, $this->assertedMaxExecutionTime);

      // 2. Test site-wide setting.
      \Civi::settings()->set('search_kit_timeout', 123);
      civicrm_api4('SearchDisplay', 'run', $params);
      $this->assertEquals(123, $this->assertedMaxExecutionTime);

      // 3. Test SavedSearch per-search timeout override takes precedence.
      $params['savedSearch']['timeout'] = 45;
      civicrm_api4('SearchDisplay', 'run', $params);
      $this->assertEquals(45, $this->assertedMaxExecutionTime);

      // 4. Test SavedSearch timeout override of 0 disables timeout.
      $params['savedSearch']['timeout'] = 0;
      civicrm_api4('SearchDisplay', 'run', $params);
      $this->assertEquals($originalDbTimeout, $this->assertedMaxExecutionTime);

      // Revert settings.
      \Civi::settings()->revert('search_kit_timeout');

      // 5. Test catching database query timeout exceptions (for both MySQL and MariaDB).
      foreach ([3024, 1969] as $errorCode) {
        $pearError = new \DB_Error(
          \DB_ERROR_NOSUCHDB,
          PEAR_ERROR_RETURN,
          \E_USER_WARNING,
          "SELECT * FROM civicrm_contact [nativecode={$errorCode} ** Query execution was interrupted]"
        );
        $this->exceptionToThrowInHook = new \Civi\Core\Exception\DBQueryException(
          'Query execution was interrupted',
          0,
          ['exception' => $pearError]
        );

        try {
          civicrm_api4('SearchDisplay', 'run', $params);
          $this->fail("Expected search timeout exception for code {$errorCode} was not thrown.");
        }
        catch (\CRM_Core_Exception $e) {
          $this->assertEquals('search_timeout', $e->getErrorCode());
          $this->assertStringContainsString('The search query timed out', $e->getMessage());
        }
      }
    }
    finally {
      \CRM_Utils_Hook::singleton()->setHook('civicrm_selectWhereClause', NULL);
      $this->exceptionToThrowInHook = NULL;
    }
  }

}
