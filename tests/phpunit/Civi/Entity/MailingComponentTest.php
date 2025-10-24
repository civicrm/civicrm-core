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

namespace phpunit\Civi\Entity;

use Civi\Api4\Mailing;
use Civi\Core\HookInterface;
use Civi\Test\EntityTrait;
use Civi\Test\HeadlessInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test the core Entity->getOptions functionality
 * @group headless
 */
class MailingComponentTest extends TestCase implements HeadlessInterface, HookInterface {

  use EntityTrait;

  public function setUpHeadless() {
    return \Civi\Test::headless()->apply();
  }

  public function testCacheClear(): void {
    $this->createTestEntity('MailingComponent', [
      'component_type' => 'Footer',
      'name' => 'another',
      'subject' => 'blah',
      'body_html' => '<p>blah</p>',
    ]);
    $mailing = Mailing::getFields()
      ->setLoadOptions(TRUE)
      ->addWhere('name', '=', 'footer_id')
      ->execute()->first();
    $this->assertTrue(in_array('another', $mailing['options']));
  }

}
