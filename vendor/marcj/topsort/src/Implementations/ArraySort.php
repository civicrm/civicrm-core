<?php

namespace MJS\TopSort\Implementations;

use MJS\TopSort\CircularDependencyException;
use MJS\TopSort\ElementNotFoundException;
use MJS\TopSort\TopSortInterface;

/**
 * A topological sort implementation based on php arrays.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class ArraySort extends BaseImplementation implements TopSortInterface
{
    /**
     * @var object[]
     */
    protected $elements = array();

    /**
     * @var string[]
     */
    protected $sorted;

    /**
     * @param array[] $elements ['id' => ['dep1', 'dep2'], 'id2' => ...]
     */
    public function set(array $elements)
    {
        foreach ($elements as $element => $dependencies) {
            $this->add($element, $dependencies);
        }
    }

    /**
     * Adds element.
     *
     * @param string   $element Name of file
     * @param string[] $dependencies
     */
    public function add($element, $dependencies = array())
    {
        // Add
        $this->elements[$element] = (object)array(
            'id' => $element,
            'dependencies' => (array)$dependencies,
            'visited' => false
        );
    }

    /**
     * Visits $element and handles it dependencies, queues to internal sorted list in the right order.
     *
     * @param object   $element
     * @param object[] $parents
     *
     * @throws CircularDependencyException if a circular dependency has been found
     * @throws ElementNotFoundException if a dependency can not be found
     */
    protected function visit($element, &$parents = null)
    {
        $this->throwCircularExceptionIfNeeded($element, $parents);

        // If element has not been visited
        if (!$element->visited) {
            $parents[$element->id] = true;

            // Set that element has been visited
            $element->visited = true;

            foreach ($element->dependencies as $dependency) {
                if (isset($this->elements[$dependency])) {
                    $newParents = $parents;
                    $this->visit($this->elements[$dependency], $newParents);
                } else {
                    throw ElementNotFoundException::create($element->id, $dependency);
                }
            }

            $this->addToList($element);
        }
    }

    /**
     * @param object $element
     */
    protected function addToList($element)
    {
        $this->sorted[] = $element->id;
    }

    /**
     * {@inheritDoc}
     */
    public function sort()
    {
        return $this->doSort();
    }

    /**
     * Sorts dependencies and returns internal used data structure.
     *
     * @return string[]
     *
     * @throws CircularDependencyException if a circular dependency has been found
     * @throws ElementNotFoundException if a dependency can not be found
     */
    public function doSort()
    {
        $this->sorted = array();

        foreach ($this->elements as $element) {
            $parents = array();
            $this->visit($element, $parents);
        }

        return $this->sorted;
    }
}