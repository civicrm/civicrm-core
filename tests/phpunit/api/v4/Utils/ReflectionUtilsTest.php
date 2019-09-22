<?php

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
    $doc = ReflectionUtils::getCodeDocs($reflection);

    $this->assertEquals(TRUE, $doc['internal']);
    $this->assertEquals('Grandchild class', $doc['description']);

    $expectedComment = 'This is an extended description.

There is a line break in this description.

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
    $this->assertEquals("In the child class, foo has been barred.\n\nIn general, you can do nothing with it.", $doc['comment']);
  }

}
