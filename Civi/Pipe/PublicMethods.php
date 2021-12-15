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
   * Send a request to APIv3.
   *
   * @param $session
   * @param array $request
   *   Tuple: [$entity, $action, $params]
   * @return array|\Civi\Api4\Generic\Result|int
   */
  public function api3($session, $request) {
    $request[2] = array_merge(['version' => 3, 'check_permissions' => TRUE], $request[2] ?? []);
    return civicrm_api(...$request);
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
    return civicrm_api(...$request);
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
   * @param array{maxLines: int, responsePrefix: int} $request
   *   Any updates to perform. May be empty/omitted.
   * @return array{maxLines: int, responsePrefix: int}
   *   List of updated options.
   *   If the list of updates was empty, then return all options.
   */
  public function options($session, $request) {
    $map = [
      'responsePrefix' => $session,
      'maxLine' => $session,
    ];

    $result = [];
    if (!empty($request)) {
      foreach ($request as $option => $value) {
        if (isset($map[$option])) {
          $storage = $map[$option];
          $storage->{'set' . ucfirst($option)}($value);
          $result[$option] = $storage->{'get' . ucfirst($option)}();
        }
      }
    }
    else {
      foreach ($map as $option => $storage) {
        $result[$option] = $storage->{'get' . ucfirst($option)}();
      }
    }
    return $result;
  }

}
