<?php

namespace MJS\TopSort\Implementations;

use MJS\TopSort\CircularDependencyException;
use MJS\TopSort\ElementNotFoundException;

/**
 * A topological sort implementation based on string manipulations.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class StringSort extends ArraySort
{

    /**
     * @var string
     */
    protected $sorted;

    /**
     * @var string
     */
    protected $delimiter = "\0";

    /**
     * {@inheritDoc}
     */
    protected function addToList($element)
    {
        $this->sorted .= $element->id . $this->delimiter;
    }

    /**
     * {@inheritDoc}
     */
    public function sort()
    {
        return explode($this->delimiter, rtrim($this->doSort(), $this->delimiter));
    }

    protected function visit($element, &$parents = null)
    {
        $this->throwCircularExceptionIfNeeded($element, $parents);

        if (!$element->visited) {
            $parents[$element->id] = true;

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
     * Sorts dependencies and returns internal used data structure.
     *
     * @return string
     *
     * @throws CircularDependencyException if a circular dependency has been found
     * @throws ElementNotFoundException if a dependency can not be found
     */
    public function doSort()
    {
        $this->sorted = '';

        foreach ($this->elements as $element) {
            $parents = array();
            $this->visit($element, $parents);
        }

        return $this->sorted;
    }
}