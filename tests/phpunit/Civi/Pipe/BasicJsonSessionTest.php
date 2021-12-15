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

namespace Civi\Pipe;

/**
 * @group headless
 */
class BasicJsonSessionTest extends \CiviUnitTestCase {

  protected $standardHeader = '{"Civi::pipe":["json"]}';
  protected $input;
  protected $output;
  protected $server;

  protected function setUp(): void {
    parent::setUp();
    $this->input = fopen('php://memory', 'w');
    $this->output = fopen('php://memory', 'w');
    $this->server = new BasicJsonSession($this->input, $this->output);
  }

  protected function tearDown(): void {
    fclose($this->input);
    fclose($this->output);
    $this->input = $this->output = $this->server = NULL;
    parent::tearDown();
  }

  public function testInvalid() {
    $responses = $this->runLines(['{"WIGGUM":123}']);
    $this->assertEquals('Invalid request type', json_decode($responses[1], 1)['ERR']['message']);
  }

  public function testEcho() {
    $this->assertRequestResponse([
      '{"ECHO":123}' => '{"OK":123}',
      '{"ECHO":true}' => '{"OK":true}',
      '{"ECHO":[1,4,9]}' => '{"OK":[1,4,9]}',
    ]);
  }

  public function testControl() {
    $this->assertRequestResponse([
      '{"OPTIONS":[]}' => '{"OK":{"bufferSize":524288,"responsePrefix":null}}',
      '{"OPTIONS":{"responsePrefix":"ZZ"}}' => 'ZZ{"OK":{"responsePrefix":"ZZ"}}',
      '{"ECHO":456}' => 'ZZ{"OK":456}',
    ]);
  }

  public function testApi3() {
    $responses = $this->runLines(['{"API3":["System", "get"]}']);

    $this->assertEquals($this->standardHeader, $responses[0]);

    $data = json_decode($responses[1], TRUE);
    $this->assertEquals(\CRM_Utils_System::version(), $data['OK']['values'][0]['version']);
  }

  public function testApi4() {
    $responses = $this->runLines(['{"API4":["Contact", "getFields"]}']);

    $this->assertEquals($this->standardHeader, $responses[0]);

    $data = json_decode($responses[1], TRUE);
    $this->assertTrue(is_array($data['OK']));
    $fields = \CRM_Utils_Array::index(['name'], $data['OK']);
    $this->assertEquals('Number', $fields['id']['input_type']);
  }

  /**
   * @param array $requestResponse
   *   List of requests and the corresponding responses.
   *   Requests are sent in the same order given.
   *   Ex: ['{"ECHO":1}' => '{"OK":1}']
   */
  protected function assertRequestResponse(array $requestResponse) {
    $responses = $this->runLines(array_keys($requestResponse));
    $next = function() use (&$responses) {
      return array_shift($responses);
    };

    $this->assertEquals($this->standardHeader, $next());
    $this->assertNotEmpty($requestResponse);
    foreach ($requestResponse as $request => $expectResponse) {
      $this->assertEquals($expectResponse, $next(), "The request ($request) should return expected response.");
    }
  }

  /**
   * @param string[] $lines
   *   List of statements to send. (Does not include the line-delimiter.)
   * @return string[]
   *   List of responses. (Does not include the line-delimiter.)
   */
  protected function runLines(array $lines): array {
    foreach ($lines as $line) {
      fwrite($this->input, $line . "\n");
    }
    fseek($this->input, 0);

    $this->server->run();

    fseek($this->output, 0);
    return explode("\n", stream_get_contents($this->output));
  }

  protected function getOutputLine() {
    return stream_get_line($this->output, 10000, "\n");
  }

}
