<?php

/*
 * This file is part of php-cache organization.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\IntegrationTests;

use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
abstract class HierarchicalCachePoolTest extends TestCase
{
    /**
     * @type array with functionName => reason.
     */
    protected $skippedTests = [];

    /**
     * @type CacheItemPoolInterface
     */
    protected $cache;

    /**
     * @return CacheItemPoolInterface that is used in the tests
     */
    abstract public function createCachePool();

    protected function setUp()
    {
        $this->cache = $this->createCachePool();
    }

    protected function tearDown()
    {
        if ($this->cache !== null) {
            $this->cache->clear();
        }
    }

    public function testBasicUsage()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $user = 4711;
        for ($i = 0; $i < 10; $i++) {
            $item = $this->cache->getItem(sprintf('|users|%d|followers|%d|likes', $user, $i));
            $item->set('Justin Bieber');
            $this->cache->save($item);
        }

        $this->assertTrue($this->cache->hasItem('|users|4711|followers|4|likes'));
        $this->cache->deleteItem('|users|4711|followers');
        $this->assertFalse($this->cache->hasItem('|users|4711|followers|4|likes'));
    }

    public function testChain()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $item = $this->cache->getItem('|aaa|bbb|ccc|ddd');
        $item->set('value');
        $this->cache->save($item);

        $item = $this->cache->getItem('|aaa|bbb|ccc|xxx');
        $item->set('value');
        $this->cache->save($item);

        $item = $this->cache->getItem('|aaa|bbb|zzz|ddd');
        $item->set('value');
        $this->cache->save($item);

        $this->assertTrue($this->cache->hasItem('|aaa|bbb|ccc|ddd'));
        $this->assertTrue($this->cache->hasItem('|aaa|bbb|ccc|xxx'));
        $this->assertTrue($this->cache->hasItem('|aaa|bbb|zzz|ddd'));
        $this->assertFalse($this->cache->hasItem('|aaa|bbb|ccc'));
        $this->assertFalse($this->cache->hasItem('|aaa|bbb|zzz'));
        $this->assertFalse($this->cache->hasItem('|aaa|bbb'));
        $this->assertFalse($this->cache->hasItem('|aaa'));
        $this->assertFalse($this->cache->hasItem('|'));

        // This is a different thing
        $this->cache->deleteItem('|aaa|bbb|cc');
        $this->assertTrue($this->cache->hasItem('|aaa|bbb|ccc|ddd'));
        $this->assertTrue($this->cache->hasItem('|aaa|bbb|ccc|xxx'));
        $this->assertTrue($this->cache->hasItem('|aaa|bbb|zzz|ddd'));

        $this->cache->deleteItem('|aaa|bbb|ccc');
        $this->assertFalse($this->cache->hasItem('|aaa|bbb|ccc|ddd'));
        $this->assertFalse($this->cache->hasItem('|aaa|bbb|ccc|xxx'));
        $this->assertTrue($this->cache->hasItem('|aaa|bbb|zzz|ddd'));

        $this->cache->deleteItem('|aaa');
        $this->assertFalse($this->cache->hasItem('|aaa|bbb|zzz|ddd'));
    }

    public function testRemoval()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $item = $this->cache->getItem('foo');
        $item->set('value');
        $this->cache->save($item);

        $item = $this->cache->getItem('|aaa|bbb');
        $item->set('value');
        $this->cache->save($item);

        $this->cache->deleteItem('|');
        $this->assertFalse($this->cache->hasItem('|aaa|bbb'), 'Hierarchy items should be removed when deleting root');
        $this->assertTrue($this->cache->hasItem('foo'), 'All cache should not be cleared when deleting root');
    }

    public function testRemovalWhenDeferred()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $item = $this->cache->getItem('|aaa|bbb');
        $item->set('value');
        $this->cache->saveDeferred($item);

        $this->cache->deleteItem('|');
        $this->assertFalse($this->cache->hasItem('|aaa|bbb'), 'Deferred hierarchy items should be removed');

        $this->cache->commit();
        $this->assertFalse($this->cache->hasItem('|aaa|bbb'), 'Deferred hierarchy items should be removed');
    }
}
