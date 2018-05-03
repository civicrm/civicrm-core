<?php

/**
 * Class CRM_Core_DAOTest
 */
class CRM_Core_DAOTest extends CiviUnitTestCase {

  public function testGetReferenceColumns() {
    // choose CRM_Core_DAO_Email as an arbitrary example
    $emailRefs = CRM_Core_DAO_Email::getReferenceColumns();
    $refsByTarget = array();
    foreach ($emailRefs as $refSpec) {
      $refsByTarget[$refSpec->getTargetTable()] = $refSpec;
    }
    $this->assertTrue(array_key_exists('civicrm_contact', $refsByTarget));
    $contactRef = $refsByTarget['civicrm_contact'];
    $this->assertEquals('contact_id', $contactRef->getReferenceKey());
    $this->assertEquals('id', $contactRef->getTargetKey());
    $this->assertEquals('CRM_Core_Reference_Basic', get_class($contactRef));
  }

  public function testGetReferencesToTable() {
    $refs = CRM_Core_DAO::getReferencesToTable(CRM_Financial_DAO_FinancialType::getTableName());
    $refsBySource = array();
    foreach ($refs as $refSpec) {
      $refsBySource[$refSpec->getReferenceTable()] = $refSpec;
    }
    $this->assertTrue(array_key_exists('civicrm_entity_financial_account', $refsBySource));
    $genericRef = $refsBySource['civicrm_entity_financial_account'];
    $this->assertEquals('entity_id', $genericRef->getReferenceKey());
    $this->assertEquals('entity_table', $genericRef->getTypeColumn());
    $this->assertEquals('id', $genericRef->getTargetKey());
    $this->assertEquals('CRM_Core_Reference_Dynamic', get_class($genericRef));
  }

  public function testFindReferences() {
    $params = array(
      'first_name' => 'Testy',
      'last_name' => 'McScallion',
      'contact_type' => 'Individual',
    );

    $contact = CRM_Contact_BAO_Contact::add($params);
    $this->assertNotNull($contact->id);

    $params = array(
      'email' => 'spam@dev.null',
      'contact_id' => $contact->id,
      'is_primary' => 0,
      'location_type_id' => 1,
    );

    $email = CRM_Core_BAO_Email::add($params);

    $refs = $contact->findReferences();
    $refsByTable = array();
    foreach ($refs as $refObj) {
      $refsByTable[$refObj->__table] = $refObj;
    }

    $this->assertTrue(array_key_exists('civicrm_email', $refsByTable));
    $refDao = $refsByTable['civicrm_email'];
    $refDao->find(TRUE);
    $this->assertEquals($contact->id, $refDao->contact_id);
  }

  /**
   * @return array
   */
  public function composeQueryExamples() {
    $cases = array();
    // $cases[] = array('Input-SQL', 'Input-Params', 'Expected-SQL');

    // CASE: No params
    $cases[] = array(
      'SELECT * FROM whatever',
      array(),
      'SELECT * FROM whatever',
    );

    // CASE: Integer param
    $cases[] = array(
      'SELECT * FROM whatever WHERE id = %1',
      array(
        1 => array(10, 'Integer'),
      ),
      'SELECT * FROM whatever WHERE id = 10',
    );

    // CASE: String param
    $cases[] = array(
      'SELECT * FROM whatever WHERE name = %1',
      array(
        1 => array('Alice', 'String'),
      ),
      'SELECT * FROM whatever WHERE name = \'Alice\'',
    );

    // CASE: Two params
    $cases[] = array(
      'SELECT * FROM whatever WHERE name = %1 AND title = %2',
      array(
        1 => array('Alice', 'String'),
        2 => array('Bob', 'String'),
      ),
      'SELECT * FROM whatever WHERE name = \'Alice\' AND title = \'Bob\'',
    );

    // CASE: Two params with special character (%1)
    $cases[] = array(
      'SELECT * FROM whatever WHERE name = %1 AND title = %2',
      array(
        1 => array('Alice %2', 'String'),
        2 => array('Bob', 'String'),
      ),
      'SELECT * FROM whatever WHERE name = \'Alice %2\' AND title = \'Bob\'',
    );

    // CASE: Two params with special character ($1)
    $cases[] = array(
      'SELECT * FROM whatever WHERE name = %1 AND title = %2',
      array(
        1 => array('Alice $1', 'String'),
        2 => array('Bob', 'String'),
      ),
      'SELECT * FROM whatever WHERE name = \'Alice $1\' AND title = \'Bob\'',
    );

    return $cases;
  }

  /**
   * @dataProvider composeQueryExamples
   * @param $inputSql
   * @param $inputParams
   * @param $expectSql
   */
  public function testComposeQuery($inputSql, $inputParams, $expectSql) {
    $actualSql = CRM_Core_DAO::composeQuery($inputSql, $inputParams);
    $this->assertEquals($expectSql, $actualSql);
  }

  /**
   * CASE: Two params where the %2 is already present in the query
   * NOTE: This case should rightly FAIL, as using strstr in the replace mechanism will turn
   * the query into: SELECT * FROM whatever WHERE name = 'Alice' AND title = 'Bob' AND year LIKE ''Bob'012'
   * So, to avoid such ERROR, the query should be framed like:
   * 'SELECT * FROM whatever WHERE name = %1 AND title = %3 AND year LIKE '%2012'
   * $params[3] = array('Bob', 'String');
   * i.e. the place holder should be unique and should not contain in any other operational use in query
   */
  public function testComposeQueryFailure() {
    $cases[] = array(
      'SELECT * FROM whatever WHERE name = %1 AND title = %2 AND year LIKE \'%2012\' ',
      array(
        1 => array('Alice', 'String'),
        2 => array('Bob', 'String'),
      ),
      'SELECT * FROM whatever WHERE name = \'Alice\' AND title = \'Bob\' AND year LIKE \'%2012\' ',
    );
    list($inputSql, $inputParams, $expectSql) = $cases[0];
    $actualSql = CRM_Core_DAO::composeQuery($inputSql, $inputParams);
    $this->assertFalse(($expectSql == $actualSql));
  }

  /**
   * @return array
   */
  public function sqlNameDataProvider() {
    return array(
      array('this is a long string', 30, FALSE, 'this is a long string'),
      array(
        'this is an even longer string which is exactly 60 character',
        60,
        FALSE,
        'this is an even longer string which is exactly 60 character',
      ),
      array(
        'this is an even longer string which is exactly 60 character',
        60,
        TRUE,
        'this is an even longer string which is exactly 60 character',
      ),
      array(
        'this is an even longer string which is a bit more than 60 character',
        60,
        FALSE,
        'this is an even longer string which is a bit more than 60 ch',
      ),
      array(
        'this is an even longer string which is a bit more than 60 character',
        60,
        TRUE,
        'this is an even longer string which is a bit more th_c1cbd519',
      ),
    );
  }

  /**
   * @dataProvider sqlNameDataProvider
   * @param $inputData
   * @param $length
   * @param $makeRandom
   * @param $expectedResult
   */
  public function testShortenSQLName($inputData, $length, $makeRandom, $expectedResult) {
    $this->assertEquals($expectedResult, CRM_Core_DAO::shortenSQLName($inputData, $length, $makeRandom));
  }

  public function testFindById() {
    $params = $this->sampleContact('Individual', 4);
    $existing_contact = CRM_Contact_BAO_Contact::add($params);
    $contact = CRM_Contact_BAO_Contact::findById($existing_contact->id);
    $this->assertEquals($existing_contact->id, $contact->id);
    $deleted_contact_id = $existing_contact->id;
    CRM_Contact_BAO_Contact::deleteContact($contact->id, FALSE, TRUE);
    $exception_thrown = FALSE;
    try {
      $deleted_contact = CRM_Contact_BAO_Contact::findById($deleted_contact_id);
    }
    catch (Exception $e) {
      $exception_thrown = TRUE;
    }
    $this->assertTrue($exception_thrown);
  }

}
