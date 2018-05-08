<?php

namespace MJS\TopSort\Implementations;

use MJS\TopSort\ElementNotFoundException;
use MJS\TopSort\GroupedTopSortInterface;

/**
 * Implements grouped topological-sort based on arrays.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class GroupedArraySort extends BaseImplementation implements GroupedTopSortInterface
{
    protected $elements = array();
    protected $sorted;
    protected $position = 0;
    protected $groups = array();
    protected $groupLevel = 0;

    protected $debugging = false;

    /**
     * When active the sorter creates a new group when a element has a dependency to the same type.
     *
     * @var bool
     */
    protected $sameTypeExtraGrouping = false;

    /**
     * @return boolean
     */
    public function isSameTypeExtraGrouping()
    {
        return $this->sameTypeExtraGrouping;
    }

    /**
     * @param boolean $sameTypeExtraGrouping
     */
    public function setSameTypeExtraGrouping($sameTypeExtraGrouping)
    {
        $this->sameTypeExtraGrouping = $sameTypeExtraGrouping;
    }

    /**
     * @param string   $name
     * @param string   $type
     * @param string[] $dependencies
     */
    public function add($name, $type, $dependencies = array())
    {
        $dependencies = (array)$dependencies;
        $this->elements[$name] = (object)array(
            'id' => $name,
            'type' => $type,
            'dependencies' => $dependencies,
            'dependenciesCount' => count($dependencies),
            'visited' => false,
            'addedAtLevel' => -1
        );
    }


    /**
     * @param array[] $elements ['id' => ['type', ['dep1', 'dep2']], 'id2' => ...]
     */
    public function set(array $elements)
    {
        foreach ($elements as $element => $typeAndDependencies) {
            $this->add(
                $element,
                $typeAndDependencies[0],
                isset($typeAndDependencies[1]) ? $typeAndDependencies[1] : array()
            );
        }
    }

    /**
     * {@inheritDoc}
     *
     * @return integer level of group in which it has been added
     */
    protected function visit($element, &$parents = null)
    {
        $this->throwCircularExceptionIfNeeded($element, $parents);

        // If element has not been visited
        if (!$element->visited) {
            $parents[$element->id] = true;

            $element->visited = true;

            $minLevel = -1;
            foreach ($element->dependencies as $dependency) {

                if (isset($this->elements[$dependency])) {
                    $newParents = $parents;
                    $addedAtGroupLevel = $this->visit($this->elements[$dependency], $newParents, $element);
                    if ($addedAtGroupLevel > $minLevel) {
                        $minLevel = $addedAtGroupLevel;
                    }
                    if ($this->isSameTypeExtraGrouping()) {
                        if ($this->elements[$dependency]->type === $element->type) {
                            //add a new group
                            $minLevel = $this->groupLevel;
                        }
                    }
                } else {
                    throw ElementNotFoundException::create($element->id, $dependency);
                }
            }

//            print "add {$element->id} ({$element->type}), minLevel:$minLevel  \n";
//            $this->printState();

            $this->injectElement($element, $minLevel);

            return $minLevel === -1 ? $element->addedAtLevel : $minLevel;
        }

        return $element->addedAtLevel;
    }

    /**
     * @param object  $element
     * @param integer $minLevel
     */
    protected function injectElement($element, $minLevel)
    {
        if ($group = $this->getFirstGroup($element->type, $minLevel)) {
            $this->addItemAt($group->position + $group->length, $element);
            $group->length++;

//            print "   ->added into group {$group->type}, position: {$group->position}, level: {$group->level}\n";

            //increase all following groups +1
            $i = $group->position;
            foreach ($this->groups as $tempGroup) {
                if ($tempGroup->position > $i) {
                    $tempGroup->position++;
                }
            }
            $element->addedAtLevel = $group->level;
            $this->position++;
        } else {
            $this->groups[] = (object)array(
                'type' => $element->type,
                'level' => $this->groupLevel,
                'position' => $this->position,
                'length' => 1
            );
            $element->addedAtLevel = $this->groupLevel;
            $this->sorted[] = $element->id;
            $this->position++;

//            print "   ->just added. New group {$element->id}, position: {$this->position}, level: {$this->groupLevel}\n";
            $this->groupLevel++;
        }
    }

    /**
     * @param integer $position
     * @param object  $element
     */
    public function addItemAt($position, $element)
    {
        array_splice($this->sorted, $position, 0, $element->id);
    }

//    /**
//     * @debug
//     */
//    protected function printState()
//    {
//        print "   ##state# groups: " . count($this->groups) . ", sorted: " . count($this->sorted) . "\n";
//        foreach ($this->groups as $idx => $group) {
//            print "   group {$group->type}: $idx, position: {$group->position}, level: {$group->level}\n";
//        }
//    }

    /**
     * {@inheritDoc}
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @return array
     */
    public function sortGrouped()
    {
        $items = $this->sort();
        $groups = array();
        foreach ($this->getGroups() as $group) {
            $groups[] = array('type' => $group->type, 'elements' => array_slice($items, $group->position, $group->length));
        }

        return $groups;
    }

    /**
     * @param string  $type
     * @param integer $minLevel
     *
     * @return object|null
     */
    protected function getFirstGroup($type, $minLevel)
    {
        $i = $this->groupLevel;
        while ($i--) {
            $group = $this->groups[$i];

            if ($group->type === $type && $i >= $minLevel) {
                return $group;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function sort()
    {
        return $this->doSort();
    }

    /**
     * {@inheritDoc}
     */
    public function doSort()
    {
        if ($this->sorted) {
            //reset state when already executed
            foreach ($this->elements as $element) {
                $element->visited = false;
            }
        }

        $this->sorted = array();
        $this->groups = array();
        $this->position = 0;
        $this->groupLevel = 0;

        foreach ($this->elements as $element) {
            $parents = array();
            $this->visit($element, $parents);
        }

        return $this->sorted;
    }
}