<?php

/**
 * Class CRM_Utils_RestTest
 * @group headless
 */
class CRM_Utils_RestTest extends CiviUnitTestCase  {

  public function setUp() :void {
    parent::setUp();
    $this->useTransaction(TRUE);
  }

  public function testProcessMultiple() {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $input = [
      'cow' => [
        'contact',
        'create',
        [
          'contact_type' => 'Individual',
          'first_name' => 'Cow',
        ],
      ],
      'sheep' => [
        'contact',
        'create',
        [
          'contact_type' => 'Individual',
          'first_name' => 'Sheep',
        ],
      ],
    ];
    $_REQUEST['json'] = json_encode($input);
    $output = CRM_Utils_REST::processMultiple();
    $this->assertGreaterThan(0, $output['cow']['id']);
    $this->assertGreaterThan($output['cow']['id'], $output['sheep']['id']);
  }

  /**
   * Check that check_permissions passed in in chained api calls is ignored.
   */
  public function testSecurityIssue116() {
    $this->hookClass->setHook('civicrm_alterAPIPermissions', [$this, 'alterAPIPermissions']);

    $config = CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = [];

    $contactID = \Civi\Api4\Contact::create(FALSE)
      ->addValue('display_name', 'Wilma')
      ->addValue('contact_type', 'Individual')
      ->execute()->first()['id'];

    $jobLogID = civicrm_api3('JobLog', 'create', [
      'name' => 'test',
      'domain_id' => 1,
    ])['id'];
    $params = [ 'id' => $jobLogID, 'version' => 3, 'sequential' => 1, 'check_permissions' => 0 ];
    $args = ['civicrm', 'JobLog', 'get'];

    // Check we can load the email without checking perms.
    $r = civicrm_api('JobLog', 'get', $params);
    $this->assertEquals(1, $r['count']);

    // Check we can still load it with checking permission (because we allow it in hook)
    $r = civicrm_api('JobLog', 'get', ['check_permissions' => 1] + $params);
    $this->assertEquals(1, $r['count']);

    // Now check we can load it via the rest endpoint which should enforce permissions.
    $output = CRM_Utils_REST::process($args, $params);
    $this->assertEquals(1, $output['count']);

    // Now add a chain, naughtily passing in a check_permissions
    // We do not have permission to access this contact.
    $params['api.contact.get'] = [
      'id' => $contactID,
      'check_permissions' => 0,
      'return' => 'display_name',
    ];
    $output = CRM_Utils_REST::process($args, $params);
    $this->assertEquals($jobLogID, $output['id']);
    $chain = $output['values'][0]['api.contact.get'];
    $this->assertEquals(0, $chain['count'], "Vulnerable.");

    // There is a different codepath when the chained api call is an array
    // (This is designed for multiple chained create/delete calls, but
    // we can just use get for testing.)
    $params['api.contact.get'] = [$params['api.contact.get']];
    $output = CRM_Utils_REST::process($args, $params);
    $this->assertEquals($jobLogID, $output['id']);
    $chainResult = $output['values'][0]['api.contact.get'];
    $this->assertIsArray($chainResult);
    $this->assertCount(1, $chainResult);
    $this->assertEquals(0, $chainResult[0]['count'], "Vulnerable.");

    // Try create call AND using different api chain syntax.
    unset($params['api.contact.get']);
    $params['api_contact_create'] = [
      ['contact_type' => 'Individual', 'display_name' => 'Sad Face', 'check_permissions' => 0]
    ];
    $output = CRM_Utils_REST::process($args, $params);
    $this->assertEquals(1, $output['is_error']);
    $this->assertEquals('unauthorized', $output['error_code']);

    // Test that a nested chain is also forced to use permissions.
    unset($params['api_contact_create']);
    $params['api.job_log.get'] = [
      'id' => $jobLogID,
      'check_permissions' => 0,
      'api.contact.get' => [
        'id' => $contactID,
        'check_permissions' => 0,
        'return' => 'display_name',
      ]];
    $output = CRM_Utils_REST::process($args, $params);
    $this->assertEquals($jobLogID, $output['id']);
    $chain = $output['values'][0]['api.job_log.get'];
    $this->assertEquals(1, $chain['count'], "Expected the first chain to work.");
    // Check the inner contact.get returned nothing
    $chain = $chain['values'][0]['api.contact.get'];
    $this->assertEquals(0, $chain['count'], "Vulnerable.");
  }

  /**
   */
  public function alterAPIPermissions($entity, $action, &$params, &$permissions) {
    if ($entity === 'job_log' && $action === 'get') {
      $permissions['job_log']['get'] = [];
    }
  }
}
