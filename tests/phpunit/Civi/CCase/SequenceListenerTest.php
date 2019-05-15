<?php
namespace Civi\CCase;

/**
 * Class SequenceListenerTest
 *
 * @package Civi\CCase
 * @group headless
 */
class SequenceListenerTest extends \CiviCaseTestCase {

  public function setUp() {
    parent::setUp();
    $this->_params = array(
      'case_type' => $this->caseType,
      'subject' => 'Test case',
      'contact_id' => 17,
    );
    //Add an activity status with Type = Completed
    $this->callAPISuccess('OptionValue', 'create', array(
      'option_group_id' => "activity_status",
      'filter' => \CRM_Activity_BAO_Activity::COMPLETED,
      'label' => "Skip Activity",
    ));
  }

  public function testSequence() {
    $actStatuses = array_flip(\CRM_Core_PseudoConstant::activityStatus('name'));
    $caseStatuses = array_flip(\CRM_Case_PseudoConstant::caseStatus('name'));
    $actTypes = array_flip(\CRM_Activity_BAO_Activity::buildOptions('activity_type_id', 'create'));

    // Create case; schedule first activity
    \CRM_Utils_Time::setTime('2013-11-30 01:00:00');
    $case = $this->callAPISuccess('case', 'create', $this->_params);
    $analyzer = new \Civi\CCase\Analyzer($case['id']);
    $this->assertEquals($caseStatuses['Open'], self::ag($analyzer->getCase(), 'status_id'));
    $this->assertApproxTime('2013-11-30 01:00:00', self::ag($analyzer->getSingleActivity('Medical evaluation'), 'activity_date_time'));
    $this->assertEquals($actStatuses['Scheduled'], self::ag($analyzer->getSingleActivity('Medical evaluation'), 'status_id'));
    $this->assertFalse($analyzer->hasActivity('Mental health evaluation'));
    $this->assertFalse($analyzer->hasActivity('Secure temporary housing'));

    // Edit details of first activity -- but don't finish it yet!
    \CRM_Utils_Time::setTime('2013-11-30 01:30:00');
    $this->callApiSuccess('Activity', 'create', array(
      'id' => self::ag($analyzer->getSingleActivity('Medical evaluation'), 'id'),
      'subject' => 'This is the new subject',
    ));
    $analyzer = new \Civi\CCase\Analyzer($case['id']);
    $this->assertEquals($caseStatuses['Open'], self::ag($analyzer->getCase(), 'status_id'));
    $this->assertApproxTime('2013-11-30 01:00:00', self::ag($analyzer->getSingleActivity('Medical evaluation'), 'activity_date_time'));
    $this->assertEquals($actStatuses['Scheduled'], self::ag($analyzer->getSingleActivity('Medical evaluation'), 'status_id'));
    $this->assertFalse($analyzer->hasActivity('Mental health evaluation'));
    $this->assertFalse($analyzer->hasActivity('Secure temporary housing'));

    // Complete first activity; schedule second
    \CRM_Utils_Time::setTime('2013-11-30 02:00:00');
    $this->callApiSuccess('Activity', 'create', array(
      'id' => self::ag($analyzer->getSingleActivity('Medical evaluation'), 'id'),
      'status_id' => $actStatuses['Completed'],
    ));
    $analyzer->flush();
    $this->assertEquals($caseStatuses['Open'], self::ag($analyzer->getCase(), 'status_id'));
    $this->assertApproxTime('2013-11-30 01:00:00', self::ag($analyzer->getSingleActivity('Medical evaluation'), 'activity_date_time'));
    $this->assertEquals($actStatuses['Completed'], self::ag($analyzer->getSingleActivity('Medical evaluation'), 'status_id'));
    $this->assertApproxTime('2013-11-30 02:00:00', self::ag($analyzer->getSingleActivity('Mental health evaluation'), 'activity_date_time'));
    $this->assertEquals($actStatuses['Scheduled'], self::ag($analyzer->getSingleActivity('Mental health evaluation'), 'status_id'));
    $this->assertFalse($analyzer->hasActivity('Secure temporary housing'));

    //Complete second activity using "Skip Activity"(Completed); schedule third
    \CRM_Utils_Time::setTime('2013-11-30 03:00:00');
    $this->callApiSuccess('Activity', 'create', array(
      'id' => self::ag($analyzer->getSingleActivity('Mental health evaluation'), 'id'),
      'status_id' => $actStatuses['Skip Activity'],
    ));
    $analyzer->flush();
    $this->assertEquals($caseStatuses['Open'], self::ag($analyzer->getCase(), 'status_id'));
    $this->assertApproxTime('2013-11-30 01:00:00', self::ag($analyzer->getSingleActivity('Medical evaluation'), 'activity_date_time'));
    $this->assertEquals($actStatuses['Completed'], self::ag($analyzer->getSingleActivity('Medical evaluation'), 'status_id'));
    $this->assertApproxTime('2013-11-30 02:00:00', self::ag($analyzer->getSingleActivity('Mental health evaluation'), 'activity_date_time'));
    $this->assertEquals($actStatuses['Skip Activity'], self::ag($analyzer->getSingleActivity('Mental health evaluation'), 'status_id'));
    $this->assertApproxTime('2013-11-30 03:00:00', self::ag($analyzer->getSingleActivity('Secure temporary housing'), 'activity_date_time'));
    $this->assertEquals($actStatuses['Scheduled'], self::ag($analyzer->getSingleActivity('Secure temporary housing'), 'status_id'));

    //Add an Activity before the case is closed
    \CRM_Utils_Time::setTime('2013-11-30 04:00:00');
    $this->callApiSuccess('Activity', 'create', array(
      'activity_name' => 'Follow up',
      'activity_type_id' => $actTypes['Follow up'],
      'status_id' => $actStatuses['Scheduled'],
      'case_id' => $case['id'],
      'activity_date_time' => \CRM_Utils_Time::getTime(),
    ));
    $analyzer->flush();
    $this->assertApproxTime('2013-11-30 01:00:00', self::ag($analyzer->getSingleActivity('Medical evaluation'), 'activity_date_time'));
    $this->assertEquals($actStatuses['Completed'], self::ag($analyzer->getSingleActivity('Medical evaluation'), 'status_id'));
    $this->assertApproxTime('2013-11-30 02:00:00', self::ag($analyzer->getSingleActivity('Mental health evaluation'), 'activity_date_time'));
    $this->assertEquals($actStatuses['Skip Activity'], self::ag($analyzer->getSingleActivity('Mental health evaluation'), 'status_id'));
    $this->assertApproxTime('2013-11-30 03:00:00', self::ag($analyzer->getSingleActivity('Secure temporary housing'), 'activity_date_time'));
    $this->assertEquals($actStatuses['Scheduled'], self::ag($analyzer->getSingleActivity('Secure temporary housing'), 'status_id'));
    $this->assertApproxTime('2013-11-30 04:00:00', self::ag($analyzer->getSingleActivity('Follow up'), 'activity_date_time'));
    $this->assertEquals($actStatuses['Scheduled'], self::ag($analyzer->getSingleActivity('Follow up'), 'status_id'));

    // Complete third activity; Case should remain open because of the Follow up activity
    \CRM_Utils_Time::setTime('2013-11-30 04:00:00');
    $this->callApiSuccess('Activity', 'create', array(
      'id' => self::ag($analyzer->getSingleActivity('Secure temporary housing'), 'id'),
      'status_id' => $actStatuses['Completed'],
    ));
    $analyzer->flush();
    $this->assertApproxTime('2013-11-30 01:00:00', self::ag($analyzer->getSingleActivity('Medical evaluation'), 'activity_date_time'));
    $this->assertEquals($actStatuses['Completed'], self::ag($analyzer->getSingleActivity('Medical evaluation'), 'status_id'));
    $this->assertApproxTime('2013-11-30 02:00:00', self::ag($analyzer->getSingleActivity('Mental health evaluation'), 'activity_date_time'));
    $this->assertEquals($actStatuses['Skip Activity'], self::ag($analyzer->getSingleActivity('Mental health evaluation'), 'status_id'));
    $this->assertApproxTime('2013-11-30 03:00:00', self::ag($analyzer->getSingleActivity('Secure temporary housing'), 'activity_date_time'));
    $this->assertEquals($actStatuses['Completed'], self::ag($analyzer->getSingleActivity('Secure temporary housing'), 'status_id'));
    $this->assertApproxTime('2013-11-30 04:00:00', self::ag($analyzer->getSingleActivity('Follow up'), 'activity_date_time'));
    $this->assertEquals($actStatuses['Scheduled'], self::ag($analyzer->getSingleActivity('Follow up'), 'status_id'));
    $this->assertEquals($caseStatuses['Open'], self::ag($analyzer->getCase(), 'status_id'));

    // Complete the additional Activity; Case closed
    \CRM_Utils_Time::setTime('2013-11-30 04:00:00');
    $this->callApiSuccess('Activity', 'create', array(
      'id' => self::ag($analyzer->getSingleActivity('Follow up'), 'id'),
      'status_id' => $actStatuses['Completed'],
    ));
    $analyzer->flush();
    $this->assertApproxTime('2013-11-30 01:00:00', self::ag($analyzer->getSingleActivity('Medical evaluation'), 'activity_date_time'));
    $this->assertEquals($actStatuses['Completed'], self::ag($analyzer->getSingleActivity('Medical evaluation'), 'status_id'));
    $this->assertApproxTime('2013-11-30 02:00:00', self::ag($analyzer->getSingleActivity('Mental health evaluation'), 'activity_date_time'));
    $this->assertEquals($actStatuses['Skip Activity'], self::ag($analyzer->getSingleActivity('Mental health evaluation'), 'status_id'));
    $this->assertApproxTime('2013-11-30 03:00:00', self::ag($analyzer->getSingleActivity('Secure temporary housing'), 'activity_date_time'));
    $this->assertEquals($actStatuses['Completed'], self::ag($analyzer->getSingleActivity('Secure temporary housing'), 'status_id'));
    $this->assertApproxTime('2013-11-30 04:00:00', self::ag($analyzer->getSingleActivity('Follow up'), 'activity_date_time'));
    $this->assertEquals($actStatuses['Completed'], self::ag($analyzer->getSingleActivity('Follow up'), 'status_id'));
    $this->assertEquals($caseStatuses['Closed'], self::ag($analyzer->getCase(), 'status_id'));
  }

  /**
   * @param $caseTypes
   * @see \CRM_Utils_Hook::caseTypes
   */
  public function hook_caseTypes(&$caseTypes) {
    $caseTypes[$this->caseType] = array(
      'module' => 'org.civicrm.hrcase',
      'name' => $this->caseType,
      'file' => __DIR__ . '/HousingSupportWithSequence.xml',
    );
  }

  /**
   * @param $expected
   * @param $actual
   * @param int $tolerance
   */
  public function assertApproxTime($expected, $actual, $tolerance = 1) {
    $diff = abs(strtotime($expected) - strtotime($actual));
    $this->assertTrue($diff <= $tolerance, sprintf("Check approx time equality. expected=[%s] actual=[%s] tolerance=[%s]",
      $expected, $actual, $tolerance
    ));
  }

  /**
   * Get a value from an array. This is syntactic-sugar to work-around PHP 5.3's limited syntax.
   *
   * @param $array
   * @param $key
   * @return mixed
   */
  public static function ag($array, $key) {
    return $array[$key];
  }

}
