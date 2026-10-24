<?php

/**
 * Class CRM_Utils_RestTest
 * @group headless
 */
class CRM_Utils_RestTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction(TRUE);
  }

  public function testProcessMultiple(): void {
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
  public function testSecurityIssue116(): void {
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
    $params = ['id' => $jobLogID, 'version' => 3, 'sequential' => 1, 'check_permissions' => 0];
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
      ['contact_type' => 'Individual', 'display_name' => 'Sad Face', 'check_permissions' => 0],
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
      ],
    ];
    $output = CRM_Utils_REST::process($args, $params);
    $this->assertEquals($jobLogID, $output['id']);
    $chain = $output['values'][0]['api.job_log.get'];
    $this->assertEquals(1, $chain['count'], "Expected the first chain to work.");
    // Check the inner contact.get returned nothing
    $chain = $chain['values'][0]['api.contact.get'];
    $this->assertEquals(0, $chain['count'], "Vulnerable.");
  }

  /**
   * A PHP error in an api call is returned as an api error, not a fatal page.
   */
  public function testProcessPhpError(): void {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $this->hookClass->setHook('civicrm_alterAPIPermissions', fn() => throw new TypeError('Ruh-roh'));
    $args = ['civicrm', 'Contact', 'get'];
    $output = CRM_Utils_REST::process($args, ['version' => 3, 'check_permissions' => 1]);
    $this->assertEquals(1, $output['is_error']);
    $this->assertStringContainsString('Error ID:', $output['error_message']);
    $this->assertStringNotContainsString('Ruh-roh', $output['error_message']);
  }

  /**
   * A PHP error in an api call made through CRM.api3() gets a json error response.
   */
  public function testAjaxJsonPhpError(): void {
    $this->hookClass->setHook('civicrm_alterAPIPermissions', fn() => throw new TypeError('Ruh-roh'));
    $originalGet = $_GET;
    $originalServer = $_SERVER;
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = ['entity' => 'Contact', 'action' => 'get', 'json' => json_encode(['sequential' => 1])];
    ob_start();
    try {
      CRM_Utils_REST::ajaxJson();
      $this->fail('ajaxJson() should have called civiExit()');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $output = json_decode(ob_get_contents(), TRUE);
    }
    finally {
      ob_end_clean();
      $_GET = $originalGet;
      $_SERVER = $originalServer;
    }
    $this->assertEquals(1, $output['is_error']);
    $this->assertStringContainsString('Error ID:', $output['error_message']);
  }

  /**
   */
  public function alterAPIPermissions($entity, $action, &$params, &$permissions) {
    if ($entity === 'job_log' && $action === 'get') {
      $permissions['job_log']['get'] = [];
    }
  }

}
