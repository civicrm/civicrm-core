<?php

namespace Civi\Test;

/**
 * Class DbTestTrait
 * @package Civi\Test
 *
 * This trait is intended for use with PHPUnit-based test cases.
 */
trait DbTestTrait {

  abstract public function assertAttributesEquals($expectedValues, $actualValues, $message = NULL);

  /**
   * Generic function to compare expected values after an api call to retrieved.
   * DB values.
   *
   * @daoName  string   DAO Name of object we're evaluating.
   * @id       int      Id of object
   * @match    array    Associative array of field name => expected value. Empty if asserting
   *                      that a DELETE occurred
   * @delete   boolean  True if we're checking that a DELETE action occurred.
   * @param $daoName
   * @param $id
   * @param $match
   * @param bool $delete
   */
  public function assertDBState($daoName, $id, $match, $delete = FALSE): void {
    if (empty($id)) {
      // adding this here since developers forget to check for an id
      // and hence we get the first value in the db
      $this->fail('ID not populated. Please fix your assertDBState usage!!!');
    }

    $object = new $daoName();
    $object->id = $id;
    $verifiedCount = 0;

    // If we're asserting successful record deletion, make sure object is NOT found.
    if ($delete) {
      if ($object->find(TRUE)) {
        $this->fail("Object not deleted by delete operation: $daoName, $id");
      }
      return;
    }

    // Otherwise check matches of DAO field values against expected values in $match.
    if ($object->find(TRUE)) {
      $fields = &$object->fields();
      foreach ($fields as $name => $value) {
        $dbName = $value['name'];
        if (isset($match[$name])) {
          $verifiedCount++;
          $this->assertEquals($object->$dbName, $match[$name]);
        }
        elseif (isset($match[$dbName])) {
          $verifiedCount++;
          $this->assertEquals($object->$dbName, $match[$dbName]);
        }
      }
    }
    else {
      $this->fail("Could not retrieve object: $daoName, $id");
    }

    $matchSize = count($match);
    if ($verifiedCount != $matchSize) {
      $this->fail("Did not verify all fields in match array: $daoName, $id. Verified count = $verifiedCount. Match array size = $matchSize");
    }
  }

  /**
   * Request a record from the DB by seachColumn+searchValue. Success if a record is found.
   * @param string $daoName
   * @param $searchValue
   * @param $returnColumn
   * @param $searchColumn
   * @param $message
   *
   * @return null|string
   * @throws \PHPUnit_Framework_AssertionFailedError
   */
  public function assertDBNotNull($daoName, $searchValue, $returnColumn, $searchColumn, $message) {
    if (empty($searchValue)) {
      $this->fail("empty value passed to assertDBNotNull");
    }
    $value = \CRM_Core_DAO::getFieldValue($daoName, $searchValue, $returnColumn, $searchColumn, TRUE);
    $this->assertNotNull($value, $message);

    return $value;
  }

  /**
   * Request a record from the DB by seachColumn+searchValue. Success if returnColumn value is NULL.
   * @param string $daoName
   * @param $searchValue
   * @param $returnColumn
   * @param $searchColumn
   * @param $message
   */
  public function assertDBNull($daoName, $searchValue, $returnColumn, $searchColumn, $message) {
    $value = \CRM_Core_DAO::getFieldValue($daoName, $searchValue, $returnColumn, $searchColumn, TRUE);
    $this->assertNull($value, $message);
  }

  /**
   * Request a record from the DB by id. Success if row not found.
   * @param string $daoName
   * @param int $id
   * @param null $message
   */
  public function assertDBRowNotExist($daoName, $id, $message = NULL) {
    $message = $message ?: "$daoName (#$id) should not exist";
    $value = \CRM_Core_DAO::getFieldValue($daoName, $id, 'id', 'id', TRUE);
    $this->assertNull($value, $message);
  }

  /**
   * Request a record from the DB by id. Success if row not found.
   * @param string $daoName
   * @param int $id
   * @param null $message
   */
  public function assertDBRowExist($daoName, $id, $message = NULL) {
    $message = $message ?: "$daoName (#$id) should exist";
    $value = \CRM_Core_DAO::getFieldValue($daoName, $id, 'id', 'id', TRUE);
    $this->assertEquals($id, $value, $message);
  }

  /**
   * Compare a single column value in a retrieved DB record to an expected value.
   * @param string $daoName
   * @param $searchValue
   * @param $returnColumn
   * @param $searchColumn
   * @param $expectedValue
   * @param $message
   */
  public function assertDBCompareValue(
    $daoName, $searchValue, $returnColumn, $searchColumn,
    $expectedValue, $message
  ) {
    $value = \CRM_Core_DAO::getFieldValue($daoName, $searchValue, $returnColumn, $searchColumn, TRUE);
    $this->assertEquals(trim($expectedValue), trim($value ?? ''), $message);
  }

  /**
   * Compare all values in a single retrieved DB record to an array of expected values.
   * @param string $daoName
   * @param array $searchParams
   * @param $expectedValues
   */
  public function assertDBCompareValues($daoName, $searchParams, $expectedValues) {
    //get the values from db
    $dbValues = [];
    \CRM_Core_DAO::commonRetrieve($daoName, $searchParams, $dbValues);

    // compare db values with expected values
    $this->assertAttributesEquals($expectedValues, $dbValues);
  }

  /**
   * Assert that a SQL query returns a given value.
   *
   * The first argument is an expected value. The remaining arguments are passed
   * to CRM_Core_DAO::singleValueQuery
   *
   * Example: $this->assertSql(2, 'select count(*) from foo where foo.bar like "%1"',
   * array(1 => array("Whiz", "String")));
   *
   * @param string|null|int $expected
   * @param string $query
   * @param array $params
   * @param string $message
   *
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  public function assertDBQuery($expected, string $query, array $params = [], string $message = ''): void {
    if ($message) {
      $message .= ': ';
    }
    $actual = \CRM_Core_DAO::singleValueQuery($query, $params);
    $this->assertEquals($expected, $actual,
      sprintf('%sexpected=[%s] actual=[%s] query=[%s]',
        $message, $expected, $actual, \CRM_Core_DAO::composeQuery($query, $params)
      )
    );
  }

}
