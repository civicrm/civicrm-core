<?php

namespace MJS\TopSort;

/**
 * The actual TopSort Interface.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
interface TopSortInterface
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
     * @param string[] $dependencies
     */
    public function add($element, $dependencies = null);

    /**
     * @param boolean $enabled
     */
    public function setThrowCircularDependency($enabled);

    /**
     * @param callable $circularInterceptor
     */
    public function setCircularInterceptor($circularInterceptor);

    /**
     * @return boolean
     */
    public function isThrowCircularDependency();

}