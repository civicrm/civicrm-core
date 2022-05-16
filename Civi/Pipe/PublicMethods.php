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

use Civi\Authx\AuthxException;

/**
 * Collection of methods to expose to the pipe session. Any public method will be accessible.
 */
class PublicMethods {

  /**
   * How should API errors be reported?
   *
   * @var string
   *   - 'array': Traditional array format from civicrm_api(). Maximizes consistency of error data.
   *   - 'exception': Converted to an exception. Somewhat lossy. Improves out-of-box DX on stricter JSON-RPC clients.
   */
  protected $apiError = 'exception';

  /**
   * Should API calls use permission checks?
   *
   * Note: This property is only consulted on trusted connections. It is ignored on untrusted connections.
   *
   * @var bool
   */
  protected $apiCheckPermissions = TRUE;

  /**
   * Send a request to APIv3.
   *
   * @param \Civi\Pipe\PipeSession $session
   * @param array $request
   *   Tuple: [$entity, $action, $params]
   * @return array|\Civi\Api4\Generic\Result|int
   */
  public function api3(PipeSession $session, array $request) {
    $request[2] = array_merge($request[2] ?? [], ['version' => 3]);
    $request[2]['check_permissions'] = !$session->isTrusted() || $this->isCheckPermissions($request[2], 'check_permissions');
    // ^^ Untrusted sessions MUST check perms. All sessions DEFAULT to checking perms. Trusted sessions MAY disable perms.
    switch ($this->apiError) {
      case 'array':
        return civicrm_api(...$request);

      case 'exception':
        return civicrm_api3(...$request);

      default:
        throw new \CRM_Core_Exception("Invalid API error-handling mode: $this->apiError");
    }
  }

  /**
   * Send a request to APIv4.
   *
   * @param \Civi\Pipe\PipeSession $session
   * @param array $request
   *   Tuple: [$entity, $action, $params]
   * @return array|\Civi\Api4\Generic\Result|int
   */
  public function api4(PipeSession $session, array $request) {
    $request[2] = array_merge($request[2] ?? [], ['version' => 4]);
    $request[2]['checkPermissions'] = !$session->isTrusted() || $this->isCheckPermissions($request[2], 'checkPermissions');
    // ^^ Untrusted sessions MUST check perms. All sessions DEFAULT to checking perms. Trusted sessions MAY disable perms.
    switch ($this->apiError) {
      case 'array':
        return civicrm_api(...$request);

      case 'exception':
        return civicrm_api4(...$request);

      default:
        throw new \CRM_Core_Exception("Invalid API error-handling mode: $this->apiError");
    }
  }

  /**
   * Simple test; send/receive a fragment of data.
   *
   * @param \Civi\Pipe\PipeSession $session
   * @param array $request
   * @return array
   */
  public function echo(PipeSession $session, array $request) {
    return $request;
  }

  /**
   * Set active user.
   *
   * @param \Civi\Pipe\PipeSession $session
   * @param array{contactId: int, userId: int, user: string, cred: string} $request
   * @return array|\Civi\Api4\Generic\Result|int
   */
  public function login(PipeSession $session, array $request) {
    if (!function_exists('authx_login')) {
      throw new \CRM_Core_Exception('Cannot authenticate. Authx is not configured.');
    }

    $redact = function(?array $authx) {
      return $authx ? \CRM_Utils_Array::subset($authx, ['contactId', 'userId']) : FALSE;
    };

    $principal = \CRM_Utils_Array::subset($request, ['contactId', 'userId', 'user']);
    if ($principal && $session->isTrusted()) {
      return $redact(authx_login(['flow' => 'script', 'principal' => $principal]));
    }
    elseif ($principal && !$session->isTrusted()) {
      throw new AuthxException('Session is not trusted.');
    }
    elseif (isset($request['cred'])) {
      $authn = new \Civi\Authx\Authenticator();
      $authn->setRejectMode('exception');
      if ($authn->auth(NULL, ['flow' => 'pipe', 'cred' => $request['cred']])) {
        return $redact(\CRM_Core_Session::singleton()->get('authx'));
      }
    }

    throw new AuthxException('Cannot authenticate. Must specify principal/credentials.');
  }

  /**
   * Set ephemeral session options.
   *
   * @param \Civi\Pipe\PipeSession $session
   * @param array{bufferSize: int, responsePrefix: int} $request
   *   Any updates to perform. May be empty/omitted.
   * @return array{bufferSize: int, responsePrefix: int}
   *   List of updated options.
   *   If the list of updates was empty, then return all options.
   */
  public function options(PipeSession $session, array $request) {
    $storageMap = [
      'apiCheckPermissions' => $this,
      'apiError' => $this,
      'bufferSize' => $session,
      'responsePrefix' => $session,
    ];

    if (!$session->isTrusted() && array_key_exists('apiCheckPermissions', $request)) {
      unset($request['apiCheckPermissions']);
    }

    $get = function($storage, $name) {
      if (method_exists($storage, 'get' . ucfirst($name))) {
        return $storage->{'get' . ucfirst($name)}();
      }
      else {
        return $storage->{$name};
      }
    };

    $set = function($storage, $name, $value) use ($get) {
      if (method_exists($storage, 'set' . ucfirst($name))) {
        $storage->{'set' . ucfirst($name)}($value);
      }
      else {
        $storage->{$name} = $value;
      }
      return $get($storage, $name);
    };

    $result = [];
    if (!empty($request)) {
      foreach ($request as $name => $value) {
        if (isset($storageMap[$name])) {
          $result[$name] = $set($storageMap[$name], $name, $value);
        }
      }
    }
    else {
      foreach ($storageMap as $name => $storage) {
        $result[$name] = $get($storage, $name);
      }
    }
    return $result;
  }

  private function isCheckPermissions(array $params, string $field) {
    return isset($params[$field]) ? $params[$field] : $this->apiCheckPermissions;
  }

}
