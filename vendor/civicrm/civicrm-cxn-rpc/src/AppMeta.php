<?php

/*
 * This file is part of the civicrm-cxn-rpc package.
 *
 * Copyright (c) CiviCRM LLC <info@civicrm.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this package.
 */

namespace Civi\Cxn\Rpc;

use Civi\Cxn\Rpc\Exception\CxnException;

class AppMeta {

  /**
   * @return string
   */
  public static function createId() {
    return 'app:' . BinHex::bin2hex(crypt_random_string(Constants::APP_ID_CHARS));
  }

  public static function validate($appMeta) {
    $errors = self::getValidationMessages($appMeta);
    if (!empty($errors)) {
      throw new CxnException("Invalid AppMeta:" . implode(', ', array_keys($errors)));
    }
  }

  /**
   * @param array $appMeta
   * @return array
   *   List of errors. Empty error if OK.
   */
  public static function getValidationMessages($appMeta) {
    $errors = array();

    if (!is_array($appMeta)) {
      $errors['appMeta'] = 'Not an array';
    }

    foreach (array('title', 'appCert', 'appId') as $key) {
      if (empty($appMeta[$key])) {
        $errors[$key] = 'Required field';
      }
    }

    if (!self::validateAppId($appMeta['appId'])) {
      $errors['appId'] = 'Malformed';
    }

    foreach (array('appUrl') as $key) {
      if (empty($appMeta[$key])) {
        $errors[$key] = 'Required field';
      }
      elseif (!filter_var($appMeta[$key], FILTER_VALIDATE_URL)) {
        $errors[$key] = 'Malformed URL';
      }
    }

    if (!isset($appMeta['perm']) || !is_array($appMeta['perm'])) {
      $errors['perm'] = 'Missing permissions';
    }

    if (!isset($appMeta['perm']['api']) || !is_array($appMeta['perm']['api'])) {
      $errors['perm-api'] = 'Missing permissions (API whitelist)';
    }

    if (!isset($appMeta['perm']['grant'])) {
      $errors['perm-grant'] = 'Missing permissions (grants)';
    }

    return $errors;
  }

  /**
   * @param string $appId
   * @return bool
   */
  public static function validateAppId($appId) {
    return !empty($appId) && preg_match('/^app:[a-zA-Z0-9\.]+$/', $appId);
  }

}
