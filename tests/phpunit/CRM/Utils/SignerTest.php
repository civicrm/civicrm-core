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

/**
 * Class CRM_Utils_SignerTest
 * @group headless
 */
class CRM_Utils_SignerTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  public function testSignValidate() {
    $cases = [];
    $cases[] = [
      'signParams' => [
        'a' => 'eh',
        'b' => 'bee',
        'c' => NULL,
      ],
      'validateParams' => [
        'a' => 'eh',
        'b' => 'bee',
        'c' => NULL,
      ],
      'isValid' => TRUE,
    ];
    $cases[] = [
      'signParams' => [
        'a' => 'eh',
        'b' => 'bee',
        'c' => NULL,
      ],
      'validateParams' => [
        'a' => 'eh',
        'b' => 'bee',
        'c' => NULL,
        'irrelevant' => 'totally-irrelevant',
      ],
      'isValid' => TRUE,
    ];
    $cases[] = [
      'signParams' => [
        'a' => 'eh',
        'b' => 'bee',
        'c' => NULL,
      ],
      'validateParams' => [
        'a' => 'eh',
        'b' => 'bee',
        'c' => '',
      ],
      'isValid' => TRUE,
    ];
    $cases[] = [
      'signParams' => [
        'a' => 'eh',
        'b' => 'bee',
        'c' => NULL,
      ],
      'validateParams' => [
        'a' => 'eh',
        'b' => 'bee',
        'c' => 0,
      ],
      'isValid' => FALSE,
    ];
    $cases[] = [
      'signParams' => [
        'a' => 'eh',
        'b' => 'bee',
        'c' => 0,
      ],
      'validateParams' => [
        'a' => 'eh',
        'b' => 'bee',
        'c' => NULL,
      ],
      'isValid' => FALSE,
    ];
    $cases[] = [
      'signParams' => [
        'a' => 'eh',
        'b' => 'bee',
        'c' => NULL,
      ],
      'validateParams' => [
        'a' => 'eh',
        'b' => 'bay',
        'c' => NULL,
      ],
      'isValid' => FALSE,
    ];
    $cases[] = [
      'signParams' => [
        'a' => 'eh',
        'b' => 'bee',
        'c' => NULL,
      ],
      'validateParams' => [
        'a' => 'eh',
        'b' => 'bee',
        'c' => FALSE,
      ],
      'isValid' => FALSE,
    ];
    $cases[] = [
      'signParams' => [
        // int
        'a' => 1,
        'b' => 'bee',
      ],
      'validateParams' => [
        // string
        'a' => '1',
        'b' => 'bee',
      ],
      'isValid' => TRUE,
    ];

    foreach ($cases as $caseId => $case) {
      $signer = new CRM_Utils_Signer('secret', ['a', 'b', 'c']);
      $signature = $signer->sign($case['signParams']);
      // arbitrary
      $this->assertTrue(!empty($signature) && is_string($signature));

      // same as $signer but physically separate
      $validator = new CRM_Utils_Signer('secret', ['a', 'b', 'c']);
      $isValid = $validator->validate($signature, $case['validateParams']);

      if ($isValid !== $case['isValid']) {
        $this->fail("Case ${caseId}: Mismatch: " . var_export($case, TRUE));
      }
      $this->assertTrue(TRUE, 'Validation yielded expected result');
    }
  }

}
