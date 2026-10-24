<?php
namespace Civi\CCase;

/**
 * Class EventsTest
 *
 * @package Civi\CCase
 * @group headless
 */
class EventsTest extends \CiviCaseTestCase {

  /**
   * A failing caseChange listener does not stop later changes to the case from firing the event.
   */
  public function testCaseChangeFiresAgainAfterListenerError(): void {
    $case = $this->callAPISuccess('Case', 'create', [
      'case_type' => $this->caseType,
      'subject' => 'Test case',
      'contact_id' => $this->individualCreate(),
    ]);
    $calls = 0;
    $listener = function ($event) use (&$calls) {
      if (++$calls === 1) {
        throw new \TypeError('Ruh-roh');
      }
    };
    \Civi::dispatcher()->addListener('hook_civicrm_caseChange', $listener);
    try {
      try {
        Events::fireCaseChangeForRealz($case['id']);
        $this->fail('Expected TypeError');
      }
      catch (\TypeError $e) {
        $this->assertEquals('Ruh-roh', $e->getMessage());
      }
      $this->callAPISuccess('Activity', 'create', [
        'case_id' => $case['id'],
        'activity_type_id' => 'Medical evaluation',
        'subject' => 'After the error',
      ]);
    }
    finally {
      \Civi::dispatcher()->removeListener('hook_civicrm_caseChange', $listener);
    }
    $this->assertEquals(2, $calls);
  }

}
