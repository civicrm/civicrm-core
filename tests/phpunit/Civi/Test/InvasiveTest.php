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

namespace Civi\Test;

class InvasiveTest extends \CiviUnitTestCase {

  public function testPrivate() {
    $tgt = new InvasiveExample();
    $this->assertEquals(10, Invasive::get([$tgt, 'privateField']));
    $this->assertEquals(10, Invasive::call([$tgt, 'getPrivateField']));
    Invasive::call([$tgt, 'setPrivateField'], [11]);
    $this->assertEquals(11, Invasive::call([$tgt, 'getPrivateField']));

    $theRef = NULL;
    $this->assertEquals(110000, Invasive::call([$tgt, 'twiddlePrivateField'], [&$theRef]));
    $this->assertEquals(1100, $theRef);
  }

  public function testProtectedStatic() {
    $tgt = InvasiveExample::class;
    $this->assertEquals(20, Invasive::get([$tgt, 'protectedStaticField']));
    $this->assertEquals(20, Invasive::call([$tgt, 'getProtectedStaticField']));
    Invasive::call([$tgt, 'setProtectedStaticField'], [21]);
    $this->assertEquals(21, Invasive::call([$tgt, 'getProtectedStaticField']));
  }

}
