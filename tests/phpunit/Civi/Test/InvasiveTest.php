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

  public function testPrivate(): void {
    $tgt = new InvasiveExample();
    $this->assertSame(10, Invasive::get([$tgt, 'privateField']));
    $this->assertSame(10, Invasive::call([$tgt, 'getPrivateField']));
    Invasive::call([$tgt, 'setPrivateField'], [11]);
    $this->assertSame(11, Invasive::call([$tgt, 'getPrivateField']));

    $theRef = NULL;
    $this->assertSame(110000, Invasive::call([$tgt, 'twiddlePrivateField'], [&$theRef]));
    $this->assertSame(1100, $theRef);
  }

  public function testProtectedStatic(): void {
    $tgt = InvasiveExample::class;
    $this->assertSame(20, Invasive::get([$tgt, 'protectedStaticField']));
    $this->assertSame(20, Invasive::call([$tgt, 'getProtectedStaticField']));
    Invasive::call([$tgt, 'setProtectedStaticField'], [21]);
    $this->assertSame(21, Invasive::call([$tgt, 'getProtectedStaticField']));
  }

}
