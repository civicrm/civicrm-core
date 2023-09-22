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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


namespace api\v4\Action;

use Civi\Api4\Contact;
use api\v4\Api4TestBase;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class ResultTest extends Api4TestBase implements TransactionalInterface {

  public function testJsonSerialize(): void {
    $result = Contact::getFields(FALSE)->addWhere('type', '=', 'Field')->execute();
    $json = json_encode($result);
    $this->assertStringStartsWith('[{"', $json);
    $this->assertTrue(is_array(json_decode($json)));
  }

  /**
   * Knowing that the db layer HTML-encodes strings, we want to test
   * that this ugliness is hidden from us as users of the API.
   *
   * @see https://issues.civicrm.org/jira/browse/CRM-11532
   * @see https://lab.civicrm.org/dev/core/-/issues/1328
   */
  public function testNoDataCorruptionThroughEncoding(): void {

    $original = 'hello < you';
    $result = Contact::create(FALSE)
      ->setValues(['first_name' => $original])
      ->execute()->first();
    $this->assertEquals($original, $result['first_name'],
      "The value returned from Contact.create is different to the value sent."
    );

    $result = Contact::update(FALSE)
      ->addWhere('id', '=', $result['id'])
      ->setValues(['first_name' => $original])
      ->execute()->first();
    $this->assertEquals($original, $result['first_name'],
      "The value returned from Contact.update is different to the value sent."
    );

    $result = Contact::get(FALSE)
      ->addWhere('id', '=', $result['id'])
      ->execute()->first();
    $this->assertEquals($original, $result['first_name'],
      "The value returned from Contact.get is different to the value sent."
    );
  }

  /**
   * There are various ways to get the count of an API result. Some have particular behaviour, documented here.
   *
   * @dataProvider dataForTestCounts
   */
  public function testCounts(
    string $comment,
    int $matches,
    int $limit,
    array $selects,
    ?int $expectedRowCount,
    int $expectedCount,
    int $expectedCountFetched,
    ?int $expectedCountMatches
  ) {

    $expectedExceptionFromCountMatches = $expectedCountMatches === NULL;

    // Create $matches contacts.
    $records = [];
    for ($i = 0; $i < $matches; $i++) {
      $records[] = [
        'contact_type' => 'Individual',
        'first_name' => "testCounts$i",
        'last_name' => 'testCounts',
      ];
    }
    \Civi\Api4\Contact::save(FALSE)->setRecords($records)->execute();

    // Do a fetch.
    $result = \Civi\Api4\Contact::get(FALSE)
      ->setSelect($selects)
      ->addWhere('last_name', '=', 'testCounts')
      ->setLimit($limit)
      ->execute();

    // Test direct access to rowCount property (naughty) for backwards compatibility.
    $this->assertEquals($expectedRowCount, $result->rowCount, "$comment: Public rowCount failed");

    // Test quirks of count() which sometimes returns the fetched count and sometimes the matched count.
    $this->assertEquals($expectedCount, $result->count(), "$comment: count() method failed");

    // We always have countFetched() available,
    $this->assertEquals($expectedCountFetched, $result->countFetched(), "$comment: countFetched() method failed");

    // We only have countMatched() available if row_count appears in the select count.
    $exceptionThrown = FALSE;
    try {
      $countMatchResult = $result->countMatched();
    }
    catch (\Exception $exceptionThrown) {
      if (!$expectedExceptionFromCountMatches) {
        // Did not expect this!
        throw $exceptionThrown;
      }
    }

    if ($expectedCountMatches === NULL) {
      // We expect this to throw an exception.
      if (!$exceptionThrown) {
        $this->fail("$comment: expected an exception but did not get one.");
      }
    }
    else {
      $this->assertEquals($expectedCountMatches, $countMatchResult, "$comment: countMatched() method failed");
    }
  }

  /**
   *
   */
  public function dataForTestCounts() {

    $withoutRowCount = ['id'];
    $withRowCount = ['id', 'row_count'];
    $rowCountOnly = ['row_count'];
    $expectExceptionFromCountMatches = NULL;

    return [
      ['Limited, with row_count',
        1, 1, $withRowCount, 1, 1, 1, 1,
      ],
      ['Limited, only row_count',
        1, 1, $rowCountOnly, 1, 1, 0, 1,
      ],
      ['Unlimited, no row_count',
        1, 0, $withoutRowCount, 1, 1, 1, $expectExceptionFromCountMatches,
      ],
      ['Unlimited, with row_count',
        1, 0, $withRowCount, 1, 1, 1, 1,
      ],
      ['Unlimited, only row_count',
        1, 0, $rowCountOnly, 1, 1, 0, 1,
      ],
      ['Limit effective, and without row_count',
        2, 1, $withoutRowCount, NULL, 1, 1, $expectExceptionFromCountMatches,
      ],
      ['Limit effective, with row_count',
        2, 1, $withRowCount, 2, 2, 1, 2,
      ],
      ['Limit effective, only row_count',
        2, 1, $rowCountOnly, 2, 2, 0, 2,
      ],
      ['Limit ineffective (fewer rows than limit), without row count',
        2, 10, $withoutRowCount, 2, 2, 2, $expectExceptionFromCountMatches,
      ],
      ['Limit ineffective (fewer rows than limit), with row count',
        2, 10, $withRowCount, 2, 2, 2, 2,
      ],
      ['Limit ineffective (fewer rows than limit), only row count',
        2, 10, $rowCountOnly, 2, 2, 0, 2,
      ],

    ];
  }

}
