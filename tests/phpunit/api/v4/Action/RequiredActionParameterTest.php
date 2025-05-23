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

use api\v4\Api4TestBase;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Api4\MockBasicEntity;

/**
 * @group headless
 */
class RequiredActionParameterTest extends Api4TestBase {

  public function testArray(): void {
    $action = $this->createArrayAction();

    $e = NULL;
    try {
      $action->execute();
    }
    catch (\CRM_Core_Exception $e) {
      static::assertSame('Parameter "array" is required.', $e->getMessage());
    }
    static::assertNotNull($e);

    $action->setArray(['foo' => 'bar']);
    $action->execute();

    static::expectException(\CRM_Core_Exception::class);
    static::expectExceptionMessage('Parameter "array" is required.');
    $action->setArray([]);
    $action->execute();
  }

  public function testBool(): void {
    $action = $this->createBoolAction();

    $e = NULL;
    try {
      $action->execute();
    }
    catch (\CRM_Core_Exception $e) {
      static::assertSame('Parameter "bool" is required.', $e->getMessage());
    }
    static::assertNotNull($e);

    $action->setBool(TRUE);
    $action->execute();

    static::expectException(\CRM_Core_Exception::class);
    static::expectExceptionMessage('Parameter "bool" is required.');
    $action->setBool(FALSE);
    $action->execute();
  }

  public function testFloat(): void {
    $action = $this->createFloatAction();

    $e = NULL;
    try {
      $action->execute();
    }
    catch (\CRM_Core_Exception $e) {
      static::assertSame('Parameter "float" is required.', $e->getMessage());
    }
    static::assertNotNull($e);

    $action->setFloat(0.0);
    static::assertSame([], $action->execute()->getArrayCopy());
  }

  public function testInt(): void {
    $action = $this->createIntAction();

    $e = NULL;
    try {
      $action->execute();
    }
    catch (\CRM_Core_Exception $e) {
      static::assertSame('Parameter "int" is required.', $e->getMessage());
    }
    static::assertNotNull($e);

    $action->setInt(0);
    static::assertSame([], $action->execute()->getArrayCopy());
  }

  public function testString(): void {
    $action = $this->createStringAction();

    $e = NULL;
    try {
      $action->execute();
    }
    catch (\CRM_Core_Exception $e) {
      static::assertSame('Parameter "string" is required.', $e->getMessage());
    }
    static::assertNotNull($e);

    $action->setString('0');
    static::assertSame([], $action->execute()->getArrayCopy());

    $this->expectException(\CRM_Core_Exception::class);
    $this->expectExceptionMessage('Parameter "string" is required.');
    $action->setString('');
    $action->execute();
  }

  private function createArrayAction(): AbstractAction {
    /**
     * @method array|null getArray()
     * @method $this setArray(array|null $array)
     */
    return new class(MockBasicEntity::getEntityName(), 'array') extends AbstractAction {

      /**
       * @var array
       * @required
       */
      protected $array;

      /**
       * @param \Civi\Api4\Generic\Result $result
       */
      public function _run(Result $result) {
      }

    };
  }

  private function createBoolAction(): AbstractAction {
    /**
     * @method bool|null getBool()
     * @method $this setBool(bool|null $bool)
     */
    return new class(MockBasicEntity::getEntityName(), 'bool') extends AbstractAction {

      /**
       * @var bool
       * @required
       */
      protected $bool;

      /**
       * @param \Civi\Api4\Generic\Result $result
       */
      public function _run(Result $result) {
      }

    };
  }

  private function createFloatAction(): AbstractAction {
    /**
     * @method float|null getFloat()
     * @method $this setFloat(float|null $float)
     */
    return new class(MockBasicEntity::getEntityName(), 'float') extends AbstractAction {

      /**
       * @var float
       * @required
       */
      protected $float;

      /**
       * @param \Civi\Api4\Generic\Result $result
       */
      public function _run(Result $result) {
      }

    };
  }

  private function createIntAction(): AbstractAction {
    /**
     * @method int|null getInt()
     * @method $this setInt(int|null $int)
     */
    return new class(MockBasicEntity::getEntityName(), 'int') extends AbstractAction {

      /**
       * @var int
       * @required
       */
      protected $int;

      /**
       * @param \Civi\Api4\Generic\Result $result
       */
      public function _run(Result $result) {
      }

    };
  }

  private function createStringAction(): AbstractAction {
    /**
     * @method string|null getString()
     * @method $this setString(string|null $string)
     */
    return new class(MockBasicEntity::getEntityName(), 'string') extends AbstractAction {

      /**
       * @var string
       * @required
       */
      protected $string;

      /**
       * @param \Civi\Api4\Generic\Result $result
       */
      public function _run(Result $result) {
      }

    };
  }

}
