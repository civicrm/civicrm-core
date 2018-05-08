<?php

namespace MJS\TopSort\Implementations;

use MJS\TopSort\CircularDependencyException;
use MJS\TopSort\ElementNotFoundException;

/**
 * A topological sort implementation based on fixed php arrays (\SplFixedArray).
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class FixedArraySort extends ArraySort
{
    /**
     * @var int
     */
    protected $position = 0;

    /**
     * {@inheritDoc}
     */
    protected function addToList($element)
    {
        $this->sorted[$this->position++] = $element->id;
    }

    /**
     * {@inheritDoc}
     */
    public function sort()
    {
        return $this->doSort()->toArray();
    }

    /**
     * Sorts dependencies and returns internal used data structure.
     *
     * @return \SplFixedArray
     *
     * @throws CircularDependencyException if a circular dependency has been found
     * @throws ElementNotFoundException if a dependency can not be found
     */
    public function doSort()
    {
        $this->sorted = new \SplFixedArray(count($this->elements));

        foreach ($this->elements as $element) {
            $parents = array();
            $this->visit($element, $parents);
        }

        return $this->sorted;
    }
}