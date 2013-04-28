<?php

require_once 'CiviTest/CiviUnitTestCase.php';
class CRM_Core_DAOTest extends CiviUnitTestCase {
  function get_info() {
    return array(
      'name'    => 'DAO',
      'description' => 'Test core DAO functions',
      'group'     => 'Core',
    );
  }

  function testGetReferenceColumns() {
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
    $this->assertEquals(FALSE, $contactRef->isGeneric());
  }

  function testGetReferencesToTable() {
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
    $this->assertEquals(TRUE, $genericRef->isGeneric());
  }

  function testFindReferences() {
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
}
