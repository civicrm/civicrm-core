<?php

/**
 * @package CiviCRM
 * @group headless
 */
class CRM_Utils_CheckTest extends CiviUnitTestCase {

  /**
   * When TRUE, the check hook injects one WARNING-level message.
   *
   * @var bool
   */
  private $injectWarning = FALSE;

  protected function tearDown(): void {
    $this->injectWarning = FALSE;
    Civi::cache('checks')->delete('systemStatusCheckResult');
    unset(Civi::$statics['CRM_Utils_Check']);
    parent::tearDown();
  }

  /**
   * hook_civicrm_check: add a WARNING-level message when $injectWarning is set.
   */
  public function hook_civicrm_check(&$messages, $statusNames = [], $includeDisabled = FALSE): void {
    if ($this->injectWarning && (!$statusNames || in_array('checkTestInjected', $statusNames, TRUE))) {
      $messages[] = new CRM_Utils_Check_Message(
        'checkTestInjected',
        'injected by CRM_Utils_CheckTest',
        'Injected',
        \Psr\Log\LogLevel::WARNING,
        'fa-bug'
      );
    }
  }

  /**
   * A completed, unfiltered sweep publishes its max visible severity to the cache.
   */
  public function testFullSweepPublishesMaxSeverity(): void {
    $this->hookClass->setHook('civicrm_check', [$this, 'hook_civicrm_check']);
    $this->injectWarning = TRUE;

    // A stale sentinel a full sweep must overwrite. 7 (EMERGENCY) is above any real check, so a
    // pass cannot be vacuous: the published value must differ from it.
    Civi::cache('checks')->set('systemStatusCheckResult', 7, CRM_Utils_Check::CHECK_TIMER);
    unset(Civi::$statics['CRM_Utils_Check']);

    $messages = CRM_Utils_Check::checkStatus();
    $expected = 1;
    foreach ($messages as $message) {
      if ($message->isVisible()) {
        $expected = max($expected, $message->getLevel());
      }
    }

    // The injected warning guarantees the live max is at least WARNING (3), and below the sentinel.
    $this->assertGreaterThanOrEqual(3, $expected);
    $this->assertLessThan(7, $expected);
    $this->assertEquals($expected, Civi::cache('checks')->get('systemStatusCheckResult'));
  }

  /**
   * A partial sweep must not publish: a name-filtered run summarises a subset, and an
   * includeDisabled run counts checks the site has switched off.
   */
  public function testPartialSweepsDoNotPublish(): void {
    // Name-filtered.
    Civi::cache('checks')->set('systemStatusCheckResult', 7, CRM_Utils_Check::CHECK_TIMER);
    unset(Civi::$statics['CRM_Utils_Check']);
    CRM_Utils_Check::checkStatus(['checkDefaultMailbox']);
    $this->assertEquals(7, Civi::cache('checks')->get('systemStatusCheckResult'));

    // includeDisabled.
    Civi::cache('checks')->set('systemStatusCheckResult', 7, CRM_Utils_Check::CHECK_TIMER);
    unset(Civi::$statics['CRM_Utils_Check']);
    CRM_Utils_Check::checkStatus([], TRUE);
    $this->assertEquals(7, Civi::cache('checks')->get('systemStatusCheckResult'));
  }

  /**
   * getMaxSeverity() returns the cached value on a hit and recomputes with $force.
   */
  public function testGetMaxSeverityCaching(): void {
    // 7 (EMERGENCY) is above any real check, so a recompute must return something different: an
    // ignored $force would leave 7 in place and fail these assertions instead of passing vacuously.
    Civi::cache('checks')->set('systemStatusCheckResult', 7, CRM_Utils_Check::CHECK_TIMER);
    unset(Civi::$statics['CRM_Utils_Check']);

    // Cache hit: returned as-is, no recompute.
    $this->assertEquals(7, CRM_Utils_Check::getMaxSeverity());

    // Force: recomputes to the real (lower) value and re-caches it.
    $forced = CRM_Utils_Check::getMaxSeverity(TRUE);
    $this->assertLessThan(7, $forced);
    $this->assertEquals($forced, Civi::cache('checks')->get('systemStatusCheckResult'));
  }

}
