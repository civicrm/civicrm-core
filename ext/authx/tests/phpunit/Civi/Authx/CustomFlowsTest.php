<?php

namespace Civi\Authx;

use Civi\Pipe\BasicPipeClient;
use Civi\Pipe\JsonRpcMethodException;

/**
 * Send requests using customizable flows. These don't use standard HTTP requests.
 * Instead, they may involve authentication by external/third-party agents.
 *
 * The APIs `authx_login()` and `Civi::pipe()` should be focal points for customized
 * flows. To test them, we run them in separate subprocesses (`cv ev ...`)
 *
 * @group e2e
 */
class CustomFlowsTest extends AbstractFlowsTest {

  /**
   * The internal API `authx_login()` should be used by background services to set the active user.
   *
   * To test this, we call `cv ev 'authx_login(...);'` and check the resulting identity.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCliServiceLogin() {
    $withCv = function($phpStmt) {
      $cmd = strtr('cv ev -v @PHP', ['@PHP' => escapeshellarg($phpStmt)]);
      exec($cmd, $output, $val);
      $fullOutput = implode("\n", $output);
      $this->assertEquals(0, $val, "Command returned error ($cmd) ($val):\n\"$fullOutput\"");
      return json_decode($fullOutput, TRUE);
    };

    $principals = [
      'contactId' => $this->getDemoCID(),
      'userId' => $this->getDemoUID(),
      'user' => $GLOBALS['_CV']['DEMO_USER'],
    ];
    foreach ($principals as $principalField => $principalValue) {
      $msg = "Logged in with $principalField=$principalValue. We should see this user as authenticated.";

      $loginArgs = ['principal' => [$principalField => $principalValue]];
      $report = $withCv(sprintf('return authx_login(%s);', var_export($loginArgs, 1)));
      $this->assertEquals($this->getDemoCID(), $report['contactId'], $msg);
      $this->assertEquals($this->getDemoUID(), $report['userId'], $msg);
      $this->assertEquals('script', $report['flow'], $msg);
      $this->assertEquals('assigned', $report['credType'], $msg);
      $this->assertEquals(FALSE, $report['useSession'], $msg);
    }

    $invalidPrincipals = [
      ['contactId', 999999, AuthxException::CLASS, ';Contact ID 999999 is invalid;'],
      ['userId', 999999, AuthxException::CLASS, ';Cannot login. Failed to determine contact ID.;'],
      ['user', 'randuser' . mt_rand(0, 32767), AuthxException::CLASS, ';Must specify principal with valid user, userId, or contactId;'],
    ];
    foreach ($invalidPrincipals as $invalidPrincipal) {
      [$principalField, $principalValue, $expectExceptionClass, $expectExceptionMessage] = $invalidPrincipal;

      $loginArgs = ['principal' => [$principalField => $principalValue]];
      $report = $withCv(sprintf('try { return authx_login(%s); } catch (Exception $e) { return [get_class($e), $e->getMessage()]; }', var_export($loginArgs, 1)));
      $this->assertTrue(isset($report[0], $report[1]), "authx_login() should fail with invalid credentials ($principalField=>$principalValue). Received array: " . json_encode($report));
      $this->assertMatchesRegularExpression($expectExceptionMessage, $report[1], "Invalid principal ($principalField=>$principalValue) should generate exception.");
      $this->assertEquals($expectExceptionClass, $report[0], "Invalid principal ($principalField=>$principalValue) should generate exception.");
    }
  }

  public function testCliPipeTrustedLogin() {
    $rpc = new BasicPipeClient('cv ev \'Civi::pipe("tl");\'');
    $this->assertEquals('trusted', $rpc->getWelcome()['t']);
    $this->assertEquals(['login'], $rpc->getWelcome()['l']);

    $login = $rpc->call('login', ['userId' => $this->getDemoUID()]);
    $this->assertEquals($this->getDemoCID(), $login['contactId']);
    $this->assertEquals($this->getDemoUID(), $login['userId']);

    $me = $rpc->call('api3', ['Contact', 'get', ['id' => 'user_contact_id', 'sequential' => TRUE]]);
    $this->assertEquals($this->getDemoCID(), $me['values'][0]['contact_id']);
  }

  public function testCliPipeUntrustedLogin() {
    $rpc = new BasicPipeClient('cv ev \'Civi::pipe("ul");\'');
    $this->assertEquals('untrusted', $rpc->getWelcome()['u']);
    $this->assertEquals(['login'], $rpc->getWelcome()['l']);

    try {
      $rpc->call('login', ['userId' => $this->getDemoUID()]);
      $this->fail('Untrusted sessions should require authentication credentials');
    }
    catch (JsonRpcMethodException $e) {
      $this->assertMatchesRegularExpression(';not trusted;', $e->getMessage());
    }

    $login = $rpc->call('login', ['cred' => $this->credJwt($this->getDemoCID())]);
    $this->assertEquals($this->getDemoCID(), $login['contactId']);
    $this->assertEquals($this->getDemoUID(), $login['userId']);

    $me = $rpc->call('api3', ['Contact', 'get', ['id' => 'user_contact_id', 'sequential' => TRUE]]);
    $this->assertEquals($this->getDemoCID(), $me['values'][0]['contact_id']);
  }

}
