<?php

namespace Civi\Test;

/**
 * Class Api3DocTrait
 * @package Civi\Test
 *
 * This trait defines helper functions for testing and documenting APIv3. In
 * particular, it supports a workflow that links a unit-test file to an
 * example data file:
 *
 * - When defining a new API, write a unit test for it.
 * - As part of the unit test, use `callAPIAndDocument($entity, $action, ...)`.
 * - When the test executes, the inputs and outputs are logged to an example file.
 * - You can commit this file to git.
 * - Whenever the inputs/output change, they'll be visible in SCM/git because
 *   the example file also changes.
 *
 * This trait is intended for use with PHPUnit-based test cases.
 */
trait Api3DocTrait {
  use Api3TestTrait;

  /**
   * This function exists to wrap api functions.
   * so we can ensure they succeed, generate and example & throw exceptions without litterering the test with checks
   *
   * @param string $entity
   * @param string $action
   * @param array $params
   * @param string $function
   *   Pass this in to create a generated example.
   * @param string $file
   *   Pass this in to create a generated example.
   * @param string $description
   * @param string|null $exampleName
   *
   * @return array|int
   */
  public function callAPIAndDocument($entity, $action, $params, $function, $file, $description = "", $exampleName = NULL) {
    $params['version'] = $this->_apiversion;
    $result = $this->callAPISuccess($entity, $action, $params);
    $this->documentMe($entity, $action, $params, $result, $function, $file, $description, $exampleName);
    return $result;
  }

  /**
   * Create test generated example in api/v3/examples.
   *
   * To turn this off (e.g. on the server) set
   * define(DONT_DOCUMENT_TEST_CONFIG ,1);
   * in your settings file
   *
   * @param string $entity
   * @param string $action
   * @param array $params
   *   Array as passed to civicrm_api function.
   * @param array $result
   *   Array as received from the civicrm_api function.
   * @param string $testFunction
   *   Calling function - generally __FUNCTION__.
   * @param string $testFile
   *   Called from file - generally __FILE__.
   * @param string $description
   *   Descriptive text for the example file.
   * @param string $exampleName
   *   Name for this example file (CamelCase) - if omitted the action name will be substituted.
   */
  private function documentMe($entity, $action, $params, $result, $testFunction, $testFile, $description = "", $exampleName = NULL) {
    if (defined('DONT_DOCUMENT_TEST_CONFIG') && DONT_DOCUMENT_TEST_CONFIG) {
      return;
    }
    $entity = _civicrm_api_get_camel_name($entity);
    $action = strtolower($action);

    if (empty($exampleName)) {
      // Attempt to convert lowercase action name to CamelCase.
      // This is clunky/imperfect due to the convention of all lowercase actions.
      $exampleName = CRM_Utils_String::convertStringToCamel($action);
      $knownPrefixes = array(
        'Get',
        'Set',
        'Create',
        'Update',
        'Send',
      );
      foreach ($knownPrefixes as $prefix) {
        if (strpos($exampleName, $prefix) === 0 && $prefix != $exampleName) {
          $exampleName[strlen($prefix)] = strtoupper($exampleName[strlen($prefix)]);
        }
      }
    }

    $this->tidyExampleResult($result);
    if (isset($params['version'])) {
      unset($params['version']);
    }
    // Format multiline description as array
    $desc = array();
    if (is_string($description) && strlen($description)) {
      foreach (explode("\n", $description) as $line) {
        $desc[] = trim($line);
      }
    }
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('testFunction', $testFunction);
    $smarty->assign('function', _civicrm_api_get_entity_name_from_camel($entity) . "_$action");
    foreach ($params as $index => $param) {
      if (is_string($param)) {
        $params[$index] = addslashes($param);
      }
    }
    $smarty->assign('params', $params);
    $smarty->assign('entity', $entity);
    $smarty->assign('testFile', basename($testFile));
    $smarty->assign('description', $desc);
    $smarty->assign('result', $result);
    $smarty->assign('action', $action);

    global $civicrm_root;
    if (file_exists($civicrm_root . '/tests/templates/documentFunction.tpl')) {
      if (!is_dir($civicrm_root . "/api/v3/examples/$entity")) {
        mkdir($civicrm_root . "/api/v3/examples/$entity");
      }
      $f = fopen($civicrm_root . "/api/v3/examples/$entity/$exampleName.php", "w+b");
      fwrite($f, $smarty->fetch($civicrm_root . '/tests/templates/documentFunction.tpl'));
      fclose($f);
    }
  }

  /**
   * Tidy up examples array so that fields that change often ..don't
   * and debug related fields are unset
   *
   * @param array $result
   */
  public function tidyExampleResult(&$result) {
    if (!is_array($result)) {
      return;
    }
    $fieldsToChange = array(
      'hash' => '67eac7789eaee00',
      'modified_date' => '2012-11-14 16:02:35',
      'created_date' => '2013-07-28 08:49:19',
      'create_date' => '20120130621222105',
      'application_received_date' => '20130728084957',
      'in_date' => '2013-07-28 08:50:19',
      'scheduled_date' => '20130728085413',
      'approval_date' => '20130728085413',
      'pledge_start_date_high' => '20130726090416',
      'start_date' => '2013-07-29 00:00:00',
      'event_start_date' => '2013-07-29 00:00:00',
      'end_date' => '2013-08-04 00:00:00',
      'event_end_date' => '2013-08-04 00:00:00',
      'decision_date' => '20130805000000',
    );

    $keysToUnset = array('xdebug', 'undefined_fields');
    foreach ($keysToUnset as $unwantedKey) {
      if (isset($result[$unwantedKey])) {
        unset($result[$unwantedKey]);
      }
    }
    if (isset($result['values'])) {
      if (!is_array($result['values'])) {
        return;
      }
      $resultArray = &$result['values'];
    }
    elseif (is_array($result)) {
      $resultArray = &$result;
    }
    else {
      return;
    }

    foreach ($resultArray as $index => &$values) {
      if (!is_array($values)) {
        continue;
      }
      foreach ($values as $key => &$value) {
        if (substr($key, 0, 3) == 'api' && is_array($value)) {
          if (isset($value['is_error'])) {
            // we have a std nested result format
            $this->tidyExampleResult($value);
          }
          else {
            foreach ($value as &$nestedResult) {
              // this is an alternative syntax for nested results a keyed array of results
              $this->tidyExampleResult($nestedResult);
            }
          }
        }
        if (in_array($key, $keysToUnset)) {
          unset($values[$key]);
          break;
        }
        if (array_key_exists($key, $fieldsToChange) && !empty($value)) {
          $value = $fieldsToChange[$key];
        }
        if (is_string($value)) {
          $value = addslashes($value);
        }
      }
    }
  }

}
