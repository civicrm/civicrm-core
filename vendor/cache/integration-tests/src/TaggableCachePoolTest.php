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

use Cache\TagInterop\TaggableCacheItemPoolInterface;
use PHPUnit\Framework\TestCase;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
abstract class TaggableCachePoolTest extends TestCase
{
    /**
     * @type array with functionName => reason.
     */
    protected $skippedTests = [];

    /**
     * @type TaggableCacheItemPoolInterface
     */
    protected $cache;

    /**
     * @return TaggableCacheItemPoolInterface that is used in the tests
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

    public function invalidKeys()
    {
        return CachePoolTest::invalidKeys();
    }

    public function testMultipleTags()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $this->cache->save($this->cache->getItem('key1')->set('value')->setTags(['tag1', 'tag2']));
        $this->cache->save($this->cache->getItem('key2')->set('value')->setTags(['tag1', 'tag3']));
        $this->cache->save($this->cache->getItem('key3')->set('value')->setTags(['tag2', 'tag3']));
        $this->cache->save($this->cache->getItem('key4')->set('value')->setTags(['tag4', 'tag3']));

        $this->cache->invalidateTags(['tag1']);
        $this->assertFalse($this->cache->hasItem('key1'));
        $this->assertFalse($this->cache->hasItem('key2'));
        $this->assertTrue($this->cache->hasItem('key3'));
        $this->assertTrue($this->cache->hasItem('key4'));

        $this->cache->invalidateTags(['tag2']);
        $this->assertFalse($this->cache->hasItem('key1'));
        $this->assertFalse($this->cache->hasItem('key2'));
        $this->assertFalse($this->cache->hasItem('key3'));
        $this->assertTrue($this->cache->hasItem('key4'));
    }

    public function testPreviousTag()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $item = $this->cache->getItem('key')->set('value');
        $tags = $item->getPreviousTags();
        $this->assertTrue(is_array($tags));
        $this->assertCount(0, $tags);

        $item->setTags(['tag0']);
        $this->assertCount(0, $item->getPreviousTags());

        $this->cache->save($item);
        $this->assertCount(0, $item->getPreviousTags());

        $item = $this->cache->getItem('key');
        $this->assertCount(1, $item->getPreviousTags());
    }

    public function testPreviousTagDeferred()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $item = $this->cache->getItem('key')->set('value');
        $item->setTags(['tag0']);
        $this->assertCount(0, $item->getPreviousTags());

        $this->cache->saveDeferred($item);
        $this->assertCount(0, $item->getPreviousTags());

        $item = $this->cache->getItem('key');
        $this->assertCount(1, $item->getPreviousTags());
    }

    /**
     * @expectedException \Psr\Cache\InvalidArgumentException
     */
    public function testTagAccessorWithEmptyTag()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $item = $this->cache->getItem('key')->set('value');
        $item->setTags(['']);
        $this->cache->save($item);
    }

    /**
     * @expectedException \Psr\Cache\InvalidArgumentException
     * @dataProvider invalidKeys
     */
    public function testTagAccessorWithInvalidTag($tag)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $item = $this->cache->getItem('key')->set('value');
        $item->setTags([$tag]);
        $this->cache->save($item);
    }

    public function testTagAccessorDuplicateTags()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $item = $this->cache->getItem('key')->set('value');
        $item->setTags(['tag', 'tag', 'tag']);
        $this->cache->save($item);
        $item = $this->cache->getItem('key');

        $this->assertCount(1, $item->getPreviousTags());
    }

    /**
     * The tag must be removed whenever we remove an item. If not, when creating a new item
     * with the same key will get the same tags.
     */
    public function testRemoveTagWhenItemIsRemoved()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $item = $this->cache->getItem('key')->set('value');
        $item->setTags(['tag1']);

        // Save the item and then delete it
        $this->cache->save($item);
        $this->cache->deleteItem('key');

        // Create a new item (same key) (no tags)
        $item = $this->cache->getItem('key')->set('value');
        $this->cache->save($item);

        // Clear the tag, The new item should not be cleared
        $this->cache->invalidateTags(['tag1']);
        $this->assertTrue($this->cache->hasItem('key'), 'Item key should be removed from the tag list when the item is removed');
    }

    public function testClearPool()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $item = $this->cache->getItem('key')->set('value');
        $item->setTags(['tag1']);
        $this->cache->save($item);

        // Clear the pool
        $this->cache->clear();

        // Create a new item (no tags)
        $item = $this->cache->getItem('key')->set('value');
        $this->cache->save($item);
        $this->cache->invalidateTags(['tag1']);

        $this->assertTrue($this->cache->hasItem('key'), 'Tags should be removed when the pool was cleared.');
    }

    public function testInvalidateTag()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $item = $this->cache->getItem('key')->set('value');
        $item->setTags(['tag1', 'tag2']);
        $this->cache->save($item);
        $item = $this->cache->getItem('key2')->set('value');
        $item->setTags(['tag1']);
        $this->cache->save($item);

        $this->cache->invalidateTag('tag2');
        $this->assertFalse($this->cache->hasItem('key'), 'Item should be cleared when tag is invalidated');
        $this->assertTrue($this->cache->hasItem('key2'), 'Item should be cleared when tag is invalidated');

        // Create a new item (no tags)
        $item = $this->cache->getItem('key')->set('value');
        $this->cache->save($item);
        $this->cache->invalidateTags(['tag2']);
        $this->assertTrue($this->cache->hasItem('key'), 'Item key list should be removed when clearing the tags');

        $this->cache->invalidateTags(['tag1']);
        $this->assertTrue($this->cache->hasItem('key'), 'Item key list should be removed when clearing the tags');
    }

    public function testInvalidateTags()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $item = $this->cache->getItem('key')->set('value');
        $item->setTags(['tag1', 'tag2']);
        $this->cache->save($item);
        $item = $this->cache->getItem('key2')->set('value');
        $item->setTags(['tag1']);
        $this->cache->save($item);

        $this->cache->invalidateTags(['tag1', 'tag2']);
        $this->assertFalse($this->cache->hasItem('key'), 'Item should be cleared when tag is invalidated');
        $this->assertFalse($this->cache->hasItem('key2'), 'Item should be cleared when tag is invalidated');

        // Create a new item (no tags)
        $item = $this->cache->getItem('key')->set('value');
        $this->cache->save($item);
        $this->cache->invalidateTags(['tag1']);

        $this->assertTrue($this->cache->hasItem('key'), 'Item k list should be removed when clearing the tags');
    }

    /**
     * When an item is overwritten we need to clear tags for original item.
     */
    public function testTagsAreCleanedOnSave()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $pool = $this->cache;
        $i    = $pool->getItem('key')->set('value');
        $pool->save($i->setTags(['foo']));
        $i = $pool->getItem('key');
        $pool->save($i->setTags(['bar']));
        $pool->invalidateTags(['foo']);
        $this->assertTrue($pool->getItem('key')->isHit());
    }
}
