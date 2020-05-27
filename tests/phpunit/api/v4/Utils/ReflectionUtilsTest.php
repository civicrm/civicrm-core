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
 * $Id$
 *
 */


namespace api\v4\Utils;

use Civi\Api4\Utils\ReflectionUtils;
use api\v4\Mock\MockV4ReflectionGrandchild;
use api\v4\UnitTestCase;

/**
 * @group headless
 */
class ReflectionUtilsTest extends UnitTestCase {

  /**
   * Test that class annotations are returned across @inheritDoc
   */
  public function testGetDocBlockForClass() {
    $grandChild = new MockV4ReflectionGrandchild();
    $reflection = new \ReflectionClass($grandChild);
    $doc = ReflectionUtils::getCodeDocs($reflection, NULL, ['entity' => "Test"]);

    $this->assertEquals(TRUE, $doc['internal']);
    $this->assertEquals('Grandchild class for Test, with a 2-line description!', $doc['description']);

    $expectedComment = 'This is an extended comment.

  There is a line break in this comment.

This is the base class.';

    $this->assertEquals($expectedComment, $doc['comment']);
  }

  /**
   * Test that property annotations are returned across @inheritDoc
   */
  public function testGetDocBlockForProperty() {
    $grandChild = new MockV4ReflectionGrandchild();
    $reflection = new \ReflectionClass($grandChild);
    $doc = ReflectionUtils::getCodeDocs($reflection->getProperty('foo'), 'Property');

    $this->assertEquals('This is the foo property.', $doc['description']);
    $this->assertEquals("In the child class, foo has been barred.\n\n - In general, you can do nothing with it.", $doc['comment']);
  }

  public function docBlockExamples() {
    return [
      [
        "/**
          * This is a function.
          *
          * Comment.
          * IDK
          * @see 0
          * @param int|string \$foo
          *   Nothing interesting.
          * @see no evil
          * @throws tantrums
          * @param \$bar: - Has a title
          * @return nothing|something
          */
        ",
        [
          'description' => 'This is a function.',
          'comment' => "Comment.\nIDK",
          'params' => [
            '$foo' => [
              'type' => ['int', 'string'],
              'description' => '',
              'comment' => "  Nothing interesting.\n",
            ],
            '$bar' => [
              'type' => NULL,
              'description' => 'Has a title',
              'comment' => '',
            ],
          ],
          'see' => ['0', 'no evil'],
          'throws' => ['tantrums'],
          'return' => ['nothing', 'something'],
        ],
      ],
    ];
  }

  /**
   * @dataProvider docBlockExamples
   * @param $input
   * @param $expected
   */
  public function testParseDocBlock($input, $expected) {
    $this->assertEquals($expected, ReflectionUtils::parseDocBlock($input));
  }

}
