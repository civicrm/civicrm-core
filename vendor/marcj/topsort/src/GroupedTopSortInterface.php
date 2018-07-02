<?php

namespace MJS\TopSort;

/**
 * The actual TopSort Interface
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
interface GroupedTopSortInterface
{

    /**
     * Sorts dependencies and returns the array of strings with sorted elements.
     *
     * @return string[]
     *
     * @throws CircularDependencyException if a circular dependency has been found
     * @throws ElementNotFoundException if a dependency can not be found
     */
    public function sort();

    /**
     * Sorts dependencies and returns internal used data structure.
     *
     * @return mixed depends on the actual implementation.
     *
     * @throws CircularDependencyException if a circular dependency has been found
     * @throws ElementNotFoundException if a dependency can not be found
     */
    public function doSort();

    /**
     * @param string   $element
     * @param string   $type we group by this identifier
     * @param string[] $dependencies
     */
    public function add($element, $type, $dependencies = null);

    /**
     * @param boolean $enabled
     */
    public function setThrowCircularDependency($enabled);

    /**
     * @return boolean
     */
    public function isThrowCircularDependency();

    /**
     * Returns the internal list of groups generated during the sort.
     * This is only available after calling `sort()` or `doSort()`.
     *
     * You get a list of objects with following properties:
     *
     *     type, level, position, length
     *
     * where type is the type given by ->add($id, $type, $deps), level
     * is the position of the group within all groups and position
     * is the position of the first element in the list of all elements (sort())
     *
     * $firstElementPosition = $sorter->getGroups()[0]->position;
     *
     * $element = $this->sort()[$firstElementPosition];
     *
     * $firstElementOfSecondGroup = $sorter->getGroups()[1]->position;
     *
     * $element = $this->sort()[$firstElementPosition];
     *
     * @return object[]
     */
    public function getGroups();


    /**
     * Sorts dependencies and returns the groups of types with sorted elements.
     *
     * [
     *    [
     *        type: 'car'
     *        elements: ['element1', 'element2']
     *    ],[
     *        type: 'brand'
     *        elements: ['brand1']
     *    ]
     * ]
     *
     * @return array
     */
    public function sortGrouped();


    /**
     * When active the sorter creates a new group when a element has a dependency to the same type.
     *
     * @return boolean
     */
    public function isSameTypeExtraGrouping();

    /**
     * When active the sorter creates a new group when a element has a dependency to the same type.
     *
     * @param boolean $sameTypeExtraGrouping
     */
    public function setSameTypeExtraGrouping($sameTypeExtraGrouping);
}