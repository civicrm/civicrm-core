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

namespace api\v4\Request;

use api\v4\Api4TestBase;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class AjaxTest extends Api4TestBase implements TransactionalInterface {

  /**
   * Original get/post/request values
   *
   * We are messing with globals so fix afterwards.
   *
   * @var array
   */
  protected $originalRequest = [];

  public function setUp(): void {
    parent::setUp();
    $this->originalRequest = [
      'get' => $_GET,
      'post' => $_POST,
      'request' => $_REQUEST,
      'method' => $_SERVER['REQUEST_METHOD'] ?? NULL,
      'httpx' => $_SERVER['HTTP_X_REQUESTED_WITH'] ?? NULL,
    ];
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
    $_SERVER['HTTP_REFERER'] ??= NULL;
    http_response_code(200);
  }

  public function tearDown(): void {
    $_GET = $this->originalRequest['get'];
    $_POST = $this->originalRequest['post'];
    $_REQUEST = $this->originalRequest['request'];
    $_SERVER['REQUEST_METHOD'] = $this->originalRequest['method'];
    $_SERVER['HTTP_X_REQUESTED_WITH'] = $this->originalRequest['httpx'];
    http_response_code(200);
    parent::tearDown();
  }

  /**
   * Check that api can only be accessed with XMLHttpRequest
   */
  public function testAjaxMethodCheck(): void {
    $_SERVER['HTTP_X_REQUESTED_WITH'] = NULL;
    $response = $this->runAjax([
      'path' => 'civicrm/ajax/api4/Contact/get',
    ]);
    $this->assertEquals(400, http_response_code());
    $this->assertStringContainsString('SECURITY', $response['error_message']);
  }

  /**
   * Check that create api action is not allowed over http GET
   */
  public function testCreateUsingGet(): void {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'add contacts'];
    $response = $this->runAjax([
      'path' => 'civicrm/ajax/api4/Contact/create',
      'get' => [
        'params' => json_encode([
          'values' => ['first_name' => 'Hello'],
        ]),
      ],
    ]);
    $this->assertEquals(405, http_response_code());
    $this->assertStringContainsString('SECURITY', $response['error_message']);
  }

  /**
   * Check that multiple api calls are not allowed over http GET
   */
  public function testCallsUsingGet(): void {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'view all contacts'];
    $response = $this->runAjax([
      'get' => [
        'calls' => json_encode([
          ['Contact', 'get'],
          ['Activity', 'get'],
        ]),
      ],
    ]);
    $this->assertEquals(405, http_response_code());
    $this->assertStringContainsString('SECURITY', $response['error_message']);
  }

  public function testContactGetAndCreatePermissions(): void {
    $firstName = uniqid();

    // With no permissions, user cannot create contacts
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [];
    $response = $this->runAjax([
      'path' => 'civicrm/ajax/api4/Contact/create',
      'post' => [
        'params' => json_encode([
          'values' => ['first_name' => $firstName],
          // Bad guys might try disabling permissions like this but it won't work
          'checkPermissions' => FALSE,
        ]),
      ],
    ]);
    $this->assertEquals(403, http_response_code());
    $this->assertStringContainsString('Error ID:', $response['error_message']);

    // With permissions, it works
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'add contacts'];
    $response = $this->runAjax([
      'path' => 'civicrm/ajax/api4/Contact/create',
      'post' => [
        'params' => json_encode([
          'values' => ['first_name' => $firstName],
        ]),
      ],
    ]);
    $this->assertEquals(200, http_response_code());
    $this->assertEquals(1, $response['count']);

    // With no permissions, Contact.get will work but nothing will be returned
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [];
    $response = $this->runAjax([
      'path' => 'civicrm/ajax/api4/Contact/get',
      'get' => [
        'params' => json_encode([
          'where' => [['first_name', '=', $firstName]],
        ]),
      ],
    ]);
    $this->assertEquals(200, http_response_code());
    $this->assertEquals(0, $response['count']);

    // Adding 'view all contacts' permission will allow contacts to be returned even for anonymous users
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['view all contacts'];
    $response = $this->runAjax([
      'path' => 'civicrm/ajax/api4/Contact/get',
      'get' => [
        'params' => json_encode([
          'where' => [['first_name', '=', $firstName]],
        ]),
      ],
    ]);
    $this->assertEquals(200, http_response_code());
    $this->assertEquals(1, $response['count']);
  }

  public function testMultipleCallPermissions(): void {
    $firstName = uniqid();

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'add contacts'];
    $response = $this->runAjax([
      'path' => 'civicrm/ajax/api4',
      'post' => [
        'calls' => json_encode([
          ['Contact', 'create', ['values' => ['first_name' => $firstName, 'email_primary.email' => 'me@test.er']]],
          ['Contact', 'delete', ['where' => [['first_name', '=', $firstName]]]],
          ['Email', 'get', ['where' => [['contact_id.first_name', '=', $firstName]]]],
        ]),
      ],
    ]);
    // Response code indicates success because not all the calls failed
    $this->assertEquals(200, http_response_code());
    // First call should succeed
    $this->assertEquals(1, $response[0]['count']);
    // Delete call should fail
    $this->assertStringContainsString('Error ID:', $response[1]['error_message']);
    // Email.get should succeed but return no results because of lack of permission
    $this->assertEquals(0, $response[2]['count']);

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'view all contacts', 'edit all contacts'];
    $response = $this->runAjax([
      'path' => 'civicrm/ajax/api4',
      'post' => [
        'calls' => json_encode([
          ['Contact', 'create', ['values' => ['first_name' => 'Failure']]],
          ['Contact', 'delete', ['where' => [['first_name', '=', $firstName]]]],
          ['Email', 'get', ['where' => [['contact_id.first_name', '=', $firstName]]]],
          ['Email', 'delete', ['where' => [['contact_id.first_name', '=', $firstName]]]],
          ['Email', 'get', ['where' => [['contact_id.first_name', '=', $firstName]]]],
        ]),
      ],
    ]);
    // Response code indicates success because not all the calls failed
    $this->assertEquals(200, http_response_code());
    // Contact.create call should fail
    $this->assertStringContainsString('Error ID:', $response[0]['error_message']);
    // Contact.delete call should fail
    $this->assertStringContainsString('Error ID:', $response[1]['error_message']);
    // Email.get should succeed and return 1 result
    $this->assertEquals(1, $response[2]['count']);
    $this->assertEquals('me@test.er', $response[2]['values'][0]['email']);
    // Email.delete should succeed because we have 'edit all contacts'
    $this->assertEquals(0, $response[4]['count']);

    $response = $this->runAjax([
      'path' => 'civicrm/ajax/api4',
      'post' => [
        'calls' => json_encode([
          ['Activity', 'create', ['values' => ['first_name' => 'Failure']]],
          ['Contact', 'delete', ['where' => [['first_name', '=', $firstName]]]],
        ]),
      ],
    ]);
    // Response code indicates that all requests failed due to permissions
    $this->assertEquals(403, http_response_code());
  }

  /**
   * Simulate an APIv4 ajax call
   *
   * @param array{path: string, get: array, post: array} $request
   * @return array{values: array, count: int, countFetched: int, countMatched: int, error_code: int, error_message: string}
   */
  private function runAjax(array $request) {
    $request += [
      'path' => 'civicrm/ajax/api4',
    ];
    $_GET = $request['get'] ?? [];
    $_POST = $request['post'] ?? [];
    $_REQUEST = $_GET + $_POST;
    $_SERVER['REQUEST_METHOD'] = isset($request['post']) ? 'POST' : 'GET';
    $page = new \CRM_Api4_Page_AJAX();
    $page->urlPath = explode('/', $request['path']);
    try {
      ob_start();
      $page->run();
    }
    catch (\CRM_Core_Exception_PrematureExitException $e) {
      $output = ob_get_clean();
      return json_decode($output, TRUE);
    }
    $this->fail('Ajax page should have responded with json and called civiExit()');
  }

}
