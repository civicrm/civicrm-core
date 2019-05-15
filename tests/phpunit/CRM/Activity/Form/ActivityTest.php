<?php

/**
 *  Include dataProvider for tests
 * @group headless
 */
class CRM_Activity_Form_ActivityTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
    $this->assignee1 = $this->individualCreate(array(
      'first_name' => 'testassignee1',
      'last_name' => 'testassignee1',
      'email' => 'testassignee1@gmail.com',
    ));
    $this->assignee2 = $this->individualCreate(array(
      'first_name' => 'testassignee2',
      'last_name' => 'testassignee2',
      'email' => 'testassignee2@gmail.com',
    ));
    $this->target = $this->individualCreate();
    $this->source = $this->individualCreate();
  }

  public function testActivityCreate() {
    Civi::settings()->set('activity_assignee_notification', TRUE);
    //Reset filter to none.
    Civi::settings()->set('do_not_notify_assignees_for', array());
    $mut = new CiviMailUtils($this, TRUE);
    $mut->clearMessages();

    $form = new CRM_Activity_Form_Activity();
    $activityTypeId = CRM_Core_PseudoConstant::getKey('CRM_Activity_DAO_Activity', 'activity_type_id', 'Meeting');
    $params = array(
      'source_contact_id' => $this->source,
      'assignee_contact_id' => array($this->assignee1),
      'target_contact_id' => array($this->target),
      'followup_assignee_contact_id' => array(),
      'activity_type_id' => $activityTypeId,
    );

    $activityRef = new ReflectionClass('CRM_Activity_Form_Activity');
    $method = $activityRef->getMethod('processActivity');
    $method->setAccessible(TRUE);
    $method->invokeArgs($form, array(&$params));

    $msg = $mut->getMostRecentEmail();
    $this->assertNotEmpty($msg);
    $mut->clearMessages();

    //Block Meeting notification.
    Civi::settings()->set('do_not_notify_assignees_for', array($activityTypeId));
    $params['assignee_contact_id'] = array($this->assignee2);
    $method->invokeArgs($form, array(&$params));
    $msg = $mut->getMostRecentEmail();
    $this->assertEmpty($msg);
  }

}
