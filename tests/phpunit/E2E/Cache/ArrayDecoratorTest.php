<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Verify that CRM_Utils_Cache_ArrayDecorator complies with PSR-16.
 *
 * @group e2e
 */
class E2E_Cache_ArrayDecoratorTest extends E2E_Cache_CacheTestCase {

  /**
   * @var CRM_Utils_Cache_Interface
   */
  protected $a;

  public function createSimpleCache() {
    return new CRM_Utils_Cache_ArrayDecorator(
      $this->a = CRM_Utils_Cache::create([
        'name' => 'e2e array-dec test',
        'type' => ['ArrayCache'],
      ])
    );
  }

  public function testDoubleLifeWithDelete() {
    $this->assertFalse($this->a->has('foo'));
    $this->assertEquals('dfl-1', $this->a->get('foo', 'dfl-1'));

    $this->cache->set('foo', 100);

    $this->assertTrue($this->a->has('foo'));
    $this->assertEquals(100, $this->a->get('foo', 'dfl-1')[1]);

    $this->cache->set('foo', 200);

    $this->assertTrue($this->a->has('foo'));
    $this->assertEquals(200, $this->a->get('foo', 'dfl-1')[1]);

    $this->cache->delete('foo');

    $this->assertFalse($this->a->has('foo'));
    $this->assertEquals('dfl-1', $this->a->get('foo', 'dfl-1'));
  }

  public function testDoubleLifeWithClear() {
    $this->assertFalse($this->a->has('foo'));
    $this->assertEquals('dfl-1', $this->a->get('foo', 'dfl-1'));

    $this->cache->set('foo', 100);

    $this->assertTrue($this->a->has('foo'));
    $this->assertEquals(100, $this->a->get('foo', 'dfl-1')[1]);

    $this->cache->clear();

    $this->assertFalse($this->a->has('foo'));
    $this->assertEquals('dfl-1', $this->a->get('foo', 'dfl-1'));
  }

}
