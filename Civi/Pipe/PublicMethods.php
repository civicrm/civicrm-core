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
  protected $apiError = 'array';

  /**
   * Send a request to APIv3.
   *
   * @param $session
   * @param array $request
   *   Tuple: [$entity, $action, $params]
   * @return array|\Civi\Api4\Generic\Result|int
   */
  public function api3($session, $request) {
    $request[2] = array_merge(['version' => 3, 'check_permissions' => TRUE], $request[2] ?? []);
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
   * @param $session
   * @param array $request
   *   Tuple: [$entity, $action, $params]
   * @return array|\Civi\Api4\Generic\Result|int
   */
  public function api4($session, $request) {
    $request[2] = array_merge(['version' => 4, 'checkPermissions' => TRUE], $request[2] ?? []);
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
   * @param $session
   * @param mixed $request
   * @return mixed
   */
  public function echo($session, $request) {
    return $request;
  }

  /**
   * Set active user.
   *
   * @param $session
   * @param array{contactId: int, userId: int, user: string} $request
   * @return array|\Civi\Api4\Generic\Result|int
   */
  public function login($session, $request) {
    if (!function_exists('authx_login')) {
      throw new \CRM_Core_Exception("Cannot authenticate. Authx is not configured.");
    }
    $auth = authx_login($request, FALSE /* Pipe sessions do not need cookies or DB */);
    return \CRM_Utils_Array::subset($auth, ['contactId', 'userId']);
  }

  /**
   * Set ephemeral session options.
   *
   * @param $session
   * @param array{bufferSize: int, responsePrefix: int} $request
   *   Any updates to perform. May be empty/omitted.
   * @return array{bufferSize: int, responsePrefix: int}
   *   List of updated options.
   *   If the list of updates was empty, then return all options.
   */
  public function options($session, $request) {
    $storageMap = [
      'apiError' => $this,
      'bufferSize' => $session,
      'responsePrefix' => $session,
    ];

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

}
