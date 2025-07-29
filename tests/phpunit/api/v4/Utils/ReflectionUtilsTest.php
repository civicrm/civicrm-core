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


namespace api\v4\Utils;

use Civi\Api4\Utils\ReflectionUtils;
use api\v4\Mock\MockV4ReflectionGrandchild;
use api\v4\Api4TestBase;

/**
 * @group headless
 */
class ReflectionUtilsTest extends Api4TestBase {

  /**
   * Test that class annotations are returned across @inheritDoc
   */
  public function testGetDocBlockForClass(): void {
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
  public function testGetDocBlockForProperty(): void {
    $grandChild = new MockV4ReflectionGrandchild();
    $reflection = new \ReflectionClass($grandChild);
    $doc = ReflectionUtils::getCodeDocs($reflection->getProperty('foo'), 'Property');

    $this->assertEquals('This is the foo property.', $doc['description']);
    $this->assertEquals("In the child class, foo has been barred.\n\n - In general, you can do nothing with it.", $doc['comment']);
  }

  public static function docBlockExamples(): array {
    return [
      'function' => [
        'doc_block' => "/**
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
        'parsed' => [
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
      'property_with_options' => [
        'doc_block' => '/**
         * Property Array
         *
         * @var array{
         *    a: string,
         *    z: int,
         *    x: float|string|int,
         *    }
         */',
        'parsed' => [
          'description' => 'Property Array',
          'type' => ['array'],
          'comment' => NULL,
          'shape' => ['a' => ['string'], 'z' => ['int'], 'x' => ['float', 'string', 'int']],
        ],
      ],
      'params_with_nested_array_shape' => [
        'doc_block' => '/**
           * @param array{ name: string, phone: string|null } $first
           * @param array{
           *   limit: int,
           *   offset: int,
           *   thresholds: double[],
           *   color: array{red: int, green: int, blue: int}
           * } $second
           * @return array{
           *     id: int,
           *     name: string,
           *     contact: array{
           *         email: string,
           *         phone: string|null
           *     },
           *     preferences: array{notifications: bool, theme: string}
           * }
           */',
        'parsed' => [
          'params' => [
            '$first' => [
              'type' => ['array'],
              'shape' => ['name' => ['string'], 'phone' => ['string', 'null']],
              'description' => '',
              'comment' => '',
            ],
            '$second' => [
              'type' => ['array'],
              'shape' => [
                'limit' => ['int'],
                'offset' => ['int'],
                'thresholds' => ['double[]'],
                'color' => [
                  'type' => ['array'],
                  'shape' => [
                    'red' => ['int'],
                    'green' => ['int'],
                    'blue' => ['int'],
                  ],
                ],
              ],
              'description' => '',
              'comment' => '',
            ],
          ],
          'return' => [
            'type' => ['array'],
            'shape' => [
              'id' => ['int'],
              'name' => ['string'],
              'contact' => [
                'type' => ['array'],
                'shape' => [
                  'email' => ['string'],
                  'phone' => ['string', 'null'],
                ],
              ],
              'preferences' => [
                'type' => ['array'],
                'shape' => [
                  'notifications' => ['bool'],
                  'theme' => ['string'],
                ],
              ],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * @dataProvider docBlockExamples
   * @param string $input
   * @param array $expected
   */
  public function testParseDocBlock(string $input, array $expected) {
    $this->assertEquals($expected, ReflectionUtils::parseDocBlock($input));
  }

  public function testIsMethodDeprecated(): void {
    $mockClass = 'api\v4\Mock\MockV4ReflectionGrandchild';
    $this->assertTrue(ReflectionUtils::isMethodDeprecated($mockClass, 'deprecatedFn'));
    $this->assertFalse(ReflectionUtils::isMethodDeprecated($mockClass, 'nonDeprecatedFn'));
  }

}
