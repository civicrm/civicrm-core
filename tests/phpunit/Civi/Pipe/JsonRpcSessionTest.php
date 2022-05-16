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
class JsonRpcSessionTest extends \CiviUnitTestCase {

  protected $standardHeader = '{"Civi::pipe":{"t":"trusted"}}';
  protected $input;
  protected $output;
  protected $server;

  protected function setUp(): void {
    parent::setUp();
  }

  protected function tearDown(): void {
    if ($this->input) {
      fclose($this->input);
    }
    if ($this->output) {
      fclose($this->output);
    }
    $this->input = $this->output = $this->server = NULL;
    parent::tearDown();
  }

  public function testInvalid_BadMethod() {
    $responseLines = $this->runLines([
      '{"jsonrpc":"2.0","method":"wiggum"}',
    ]);
    $decode = json_decode($responseLines[1], 1);
    $this->assertEquals('2.0', $decode['jsonrpc']);
    $this->assertEquals('Method not found', $decode['error']['message']);
    $this->assertEquals(-32601, $decode['error']['code']);
  }

  public function testInvalid_MalformedParams() {
    $responseLines = $this->runLines([
      '{"jsonrpc":"2.0","id":"a","method":"echo","params":123}',
    ]);
    $decode = json_decode($responseLines[1], 1);
    $this->assertEquals('2.0', $decode['jsonrpc']);
    $this->assertEquals('Invalid params', $decode['error']['message']);
    $this->assertEquals(-32602, $decode['error']['code']);
  }

  public function testEcho() {
    $this->assertRequestResponse([
      '{"jsonrpc":"2.0","id":null,"method":"echo"}' => '{"jsonrpc":"2.0","result":[],"id":null}',
      '{"jsonrpc":"2.0","id":"a","method":"echo","params":{"color":"blue"}}' => '{"jsonrpc":"2.0","result":{"color":"blue"},"id":"a"}',
      '{"jsonrpc":"2.0","id":"a","method":"echo","params":[1,4,9]}' => '{"jsonrpc":"2.0","result":[1,4,9],"id":"a"}',
      '{"jsonrpc":"2.0","id":null,"method":"echo","params":[123]}' => '{"jsonrpc":"2.0","result":[123],"id":null}',
    ]);
  }

  public function testBatch() {
    $batchLine = '[' .
      '{"jsonrpc":"2.0","id":"a","method":"wiggum"},' .
      '{"jsonrpc":"2.0","id":"b","method": "echo","params":[123]}' .
      ']';
    $responseLines = $this->runLines([$batchLine]);
    $decode = json_decode($responseLines[1], 1);

    $this->assertEquals('2.0', $decode[0]['jsonrpc']);
    $this->assertEquals('a', $decode[0]['id']);
    $this->assertEquals('Method not found', $decode[0]['error']['message']);
    $this->assertEquals(-32601, $decode[0]['error']['code']);

    $this->assertEquals('2.0', $decode[1]['jsonrpc']);
    $this->assertEquals('b', $decode[1]['id']);
    $this->assertEquals([123], $decode[1]['result']);
  }

  public function testInvalidControl() {
    $responses = $this->runLines(['{"jsonrpc":"2.0","id":"b","method":"session.nope","params":[]}']);
    $decode = json_decode($responses[1], 1);
    $this->assertEquals('2.0', $decode['jsonrpc']);
    $this->assertEquals('Method not found', $decode['error']['message']);
  }

  public function testControl() {
    $this->assertRequestResponse([
      '{"jsonrpc":"2.0","id":"c","method":"options"}' => '{"jsonrpc":"2.0","result":{"apiCheckPermissions":true,"apiError":"exception","bufferSize":524288,"responsePrefix":null},"id":"c"}',
      '{"jsonrpc":"2.0","id":"c","method":"options","params":{"responsePrefix":"ZZ"}}' => 'ZZ{"jsonrpc":"2.0","result":{"responsePrefix":"ZZ"},"id":"c"}',
      '{"jsonrpc":"2.0","id":"c","method": "echo","params":[123]}' => 'ZZ{"jsonrpc":"2.0","result":[123],"id":"c"}',
    ]);
  }

  public function testApi3() {
    $responses = $this->runLines(['{"jsonrpc":"2.0","id":"a3","method":"api3","params":["System","get"]}']);

    $this->assertEquals($this->standardHeader, $responses[0]);

    $decode = json_decode($responses[1], TRUE);
    $this->assertEquals('2.0', $decode['jsonrpc']);
    $this->assertEquals('a3', $decode['id']);
    $this->assertEquals(\CRM_Utils_System::version(), $decode['result']['values'][0]['version']);
  }

  public function testApi3ErrorModes() {
    $responses = $this->runLines([
      // First call: By default, use JSON-RPC errors.
      '{"jsonrpc":"2.0","id":"bad1","method":"api3","params":["System","zznnzznnzz"]}',
      // Second call: Use traditional API error-arrays
      '{"jsonrpc":"2.0","id":"o","method":"options","params":{"apiError":"array"}}',
      '{"jsonrpc":"2.0","id":"bad2","method":"api3","params":["System","zznnzznnzz"]}',
    ]);

    $this->assertEquals($this->standardHeader, $responses[0]);

    $decode = json_decode($responses[1], TRUE);
    $this->assertEquals('2.0', $decode['jsonrpc']);
    $this->assertEquals('bad1', $decode['id']);
    $this->assertRegexp(';API.*System.*zznnzznnzz.*not exist;', $decode['error']['message']);

    $decode = json_decode($responses[2], TRUE);
    $this->assertEquals('2.0', $decode['jsonrpc']);
    $this->assertEquals('o', $decode['id']);
    $this->assertEquals('array', $decode['result']['apiError']);

    $decode = json_decode($responses[3], TRUE);
    $this->assertEquals('2.0', $decode['jsonrpc']);
    $this->assertEquals('bad2', $decode['id']);
    $this->assertEquals(1, $decode['result']['is_error']);
    $this->assertRegexp(';API.*System.*zznnzznnzz.*not exist;', $decode['result']['error_message']);
  }

  public function testApi4() {
    $responses = $this->runLines(['{"jsonrpc":"2.0","id":"a4","method":"api4","params":["Contact","getFields"]}']);

    $this->assertEquals($this->standardHeader, $responses[0]);

    $decode = json_decode($responses[1], TRUE);
    $this->assertEquals('2.0', $decode['jsonrpc']);
    $this->assertEquals('a4', $decode['id']);
    $this->assertTrue(is_array($decode['result']));
    $fields = \CRM_Utils_Array::index(['name'], $decode['result']);
    $this->assertEquals('Number', $fields['id']['input_type']);
  }

  public function testApi4Authz() {
    // We try 'Route.get' action with different access levels.
    $useException = '{"jsonrpc":"2.0","id":"o","method":"options","params":{"apiError":"exception"}}';
    $checkPermTrue = '{"jsonrpc":"2.0","id":"m","method":"api4","params":["Route","get",{"checkPermissions":true}]}';
    $checkPermFalse = '{"jsonrpc":"2.0","id":"m","method":"api4","params":["Route","get",{"checkPermissions":false}]}';
    $checkPermDefault = '{"jsonrpc":"2.0","id":"m","method":"api4","params":["Route","get",{}]}';
    $allPerm = ['access CiviCRM', 'administer CiviCRM'];
    $noPerm = [];
    $trusted = 't';
    $untrusted = 'u';

    $apiOk = function($line, $caseId) {
      $decode = json_decode($line, TRUE);
      $this->assertTrue(!isset($decode['error']), "($caseId) Should have no error. Got error: $line");
      $this->assertTrue(is_array($decode['result'] ?? NULL), "($caseId) Should have values. Got: $line");
    };
    $apiFail = function($line, $caseId) {
      $decode = json_decode($line, TRUE);
      $this->assertRegExp(';Authorization failed;', $decode['error']['message'], "($caseId) Should have authorization error. Got: $line");
    };

    $cases = []; /* [ ActivePerms?, Trusted?, CheckPerms?, ExpectResult */

    // For user with no perms, almost everything fails -- unless we explicitly optout on a trusted connection.
    $cases['nut'] = [$noPerm, $untrusted, $checkPermTrue, $apiFail];
    $cases['nud'] = [$noPerm, $untrusted, $checkPermDefault, $apiFail];
    $cases['nuf'] = [$noPerm, $untrusted, $checkPermFalse, $apiFail]; /* not allowed to optout */
    $cases['ntt'] = [$noPerm, $trusted, $checkPermTrue, $apiFail];
    $cases['ntd'] = [$noPerm, $trusted, $checkPermDefault, $apiFail];
    $cases['ntf'] = [$noPerm, $trusted, $checkPermFalse, $apiOk]; /* allowed to optout */

    // For user with all perms, you have success regardless of the permutation of trusted/checkPerms.
    $cases['aut'] = [$allPerm, $untrusted, $checkPermTrue, $apiOk];
    $cases['aud'] = [$allPerm, $untrusted, $checkPermDefault, $apiOk];
    $cases['auf'] = [$allPerm, $untrusted, $checkPermFalse, $apiOk];
    $cases['att'] = [$allPerm, $trusted, $checkPermTrue, $apiOk];
    $cases['atd'] = [$allPerm, $trusted, $checkPermDefault, $apiOk];
    $cases['atf'] = [$allPerm, $trusted, $checkPermFalse, $apiOk];

    foreach ($cases as $caseId => $case) {
      [$inActivePerms, $inTrusted, $inApiCall, $expect] = $case;
      $this->setPermissions($inActivePerms);
      $responses = $this->runLines([$useException, $inApiCall], $inTrusted);
      $this->assertRegExp($inTrusted === 'u' ? ';"untrusted";' : ';"trusted";', $responses[0], "($caseId) Header should indicate trust level");
      $expect($responses[2], $caseId);
    }
  }

  public function testApi4ErrorModes() {
    $responses = $this->runLines([
      // First call: By default, use JSON-RPC errors.
      '{"jsonrpc":"2.0","id":"bad1","method":"api4","params":["System","zznnzznnzz"]}',
      // Second call: Use traditional API error-arrays
      '{"jsonrpc":"2.0","id":"o","method":"options","params":{"apiError":"array"}}',
      '{"jsonrpc":"2.0","id":"bad2","method":"api4","params":["System","zznnzznnzz"]}',
    ]);

    $this->assertEquals($this->standardHeader, $responses[0]);

    $decode = json_decode($responses[1], TRUE);
    $this->assertEquals('2.0', $decode['jsonrpc']);
    $this->assertEquals('bad1', $decode['id']);
    $this->assertRegexp(';Api.*System.*zznnzznnzz.*not exist;', $decode['error']['message']);

    $decode = json_decode($responses[2], TRUE);
    $this->assertEquals('2.0', $decode['jsonrpc']);
    $this->assertEquals('o', $decode['id']);
    $this->assertEquals('array', $decode['result']['apiError']);

    $decode = json_decode($responses[3], TRUE);
    $this->assertEquals('2.0', $decode['jsonrpc']);
    $this->assertEquals('bad2', $decode['id']);
    $this->assertEquals(1, $decode['result']['is_error']);
    $this->assertRegexp(';Api.*System.*zznnzznnzz.*not exist;', $decode['result']['error_message']);
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
   * @param string $negotiationFlags
   *   Flags to set when opening connection - eg (t)rusted, (u)ntrusted
   * @return string[]
   *   List of responses. (Does not include the line-delimiter.)
   */
  protected function runLines(array $lines, string $negotiationFlags = 't'): array {
    $this->input = fopen('php://memory', 'w');
    $this->output = fopen('php://memory', 'w');
    $this->server = new PipeSession($this->input, $this->output);

    foreach ($lines as $line) {
      fwrite($this->input, $line . "\n");
    }
    fseek($this->input, 0);

    $this->server->run($negotiationFlags);

    fseek($this->output, 0);
    return explode("\n", stream_get_contents($this->output));
  }

}
