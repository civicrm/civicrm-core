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

/**
 * Class UserError
 *
 * phpseclib reports errors via user_error(). When running as a server, we
 * often want to catch these so that we can send a well-formed response.
 *
 * @package Civi\Cxn\Rpc
 */
class UserError {

  public static function adapt($class, $callable) {
    $errors = array();

    set_error_handler(function ($errno, $errstr, $errfile, $errline, $errcontext) use (&$errors) {
      if (!(error_reporting() & $errno)) {
        return;
      }
      if ($errno & (E_USER_ERROR | E_USER_NOTICE)) {
        $errors[] = array($errno, $errstr, $errfile, $errline);
      }
    }, E_USER_ERROR | E_USER_NOTICE);

    $e = NULL;
    try {
      $result = call_user_func($callable);
    }
    catch (\Exception $e2) {
      $e = e2;
    }

    restore_error_handler();

    if ($e) {
      throw $e;
    }

    if (!empty($errors)) {
      $msg = '';
      foreach ($errors as $error) {
        $msg .= $error[1] . "\n";
      }
      throw new $class($msg);
    }

    return $result;
  }

}
