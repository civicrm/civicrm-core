<?php

require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Utils_SignerTest extends CiviUnitTestCase {

  function get_info() {
    return array(
      'name'      => 'Signer Test',
      'description' => 'Test array-signing functions',
      'group'      => 'CiviCRM BAO Tests',
    );
  }

  function setUp() {
    parent::setUp();
  }

  function testSignValidate() {
    $cases = array();
    $cases[] = array(
      'signParams' => array(
        'a' => 'eh',
        'b' => 'bee',
        'c' => NULL,
      ),
      'validateParams' => array(
        'a' => 'eh',
        'b' => 'bee',
        'c' => NULL,
      ),
      'isValid' => TRUE,
    );
    $cases[] = array(
      'signParams' => array(
        'a' => 'eh',
        'b' => 'bee',
        'c' => NULL,
      ),
      'validateParams' => array(
        'a' => 'eh',
        'b' => 'bee',
        'c' => NULL,
        'irrelevant' => 'totally-irrelevant',
      ),
      'isValid' => TRUE,
    );
    $cases[] = array(
      'signParams' => array(
        'a' => 'eh',
        'b' => 'bee',
        'c' => NULL,
      ),
      'validateParams' => array(
        'a' => 'eh',
        'b' => 'bee',
        'c' => '',
      ),
      'isValid' => TRUE,
    );
    $cases[] = array(
      'signParams' => array(
        'a' => 'eh',
        'b' => 'bee',
        'c' => NULL,
      ),
      'validateParams' => array(
        'a' => 'eh',
        'b' => 'bee',
        'c' => 0,
      ),
      'isValid' => FALSE,
    );
    $cases[] = array(
      'signParams' => array(
        'a' => 'eh',
        'b' => 'bee',
        'c' => 0,
      ),
      'validateParams' => array(
        'a' => 'eh',
        'b' => 'bee',
        'c' => NULL,
      ),
      'isValid' => FALSE,
    );
    $cases[] = array(
      'signParams' => array(
        'a' => 'eh',
        'b' => 'bee',
        'c' => NULL,
      ),
      'validateParams' => array(
        'a' => 'eh',
        'b' => 'bay',
        'c' => NULL,
      ),
      'isValid' => FALSE,
    );
    $cases[] = array(
      'signParams' => array(
        'a' => 'eh',
        'b' => 'bee',
        'c' => NULL,
      ),
      'validateParams' => array(
        'a' => 'eh',
        'b' => 'bee',
        'c' => FALSE,
      ),
      'isValid' => FALSE,
    );
    $cases[] = array(
      'signParams' => array(
        'a' => 1, // int
        'b' => 'bee',
      ),
      'validateParams' => array(
        'a' => '1', // string
        'b' => 'bee',
      ),
      'isValid' => TRUE,
    );
    
    foreach ($cases as $caseId => $case) {
      require_once 'CRM/Utils/Signer.php';
      $signer = new CRM_Utils_Signer('secret', array('a', 'b', 'c'));
      $signature = $signer->sign($case['signParams']);
      $this->assertTrue(!empty($signature) && is_string($signature)); // arbitrary
      
      $validator = new CRM_Utils_Signer('secret', array('a', 'b', 'c')); // same as $signer but physically separate
      $isValid = $validator->validate($signature, $case['validateParams']);
      
      if ($isValid !== $case['isValid']) {
        $this->fail("Case ${caseId}: Mismatch: " . var_export($case, TRUE));
      }
      $this->assertTrue(TRUE, 'Validation yielded expected result');
    }
  }
}
