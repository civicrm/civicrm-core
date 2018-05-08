<?php

namespace MJS\TopSort\Implementations;

/**
 * Implements grouped topological-sort based on string manipulation.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class GroupedStringSort extends GroupedArraySort
{
    protected $delimiter = "\0";

    /**
     * @param object  $element
     * @param integer $minLevel
     */
    protected function injectElement($element, $minLevel)
    {
        if ($group = $this->getFirstGroup($element->type, $minLevel)) {
            //add this element into a group
            $this->addItemAt($group, $element);
            $group->length++;

            //increase all following groups +1
            $i = $group->position;
            foreach ($this->groups as $tempGroup) {
                if ($tempGroup->position > $i) {
                    $tempGroup->position += strlen($element->id . $this->delimiter);
                }
            }

            $element->addedAtLevel = $group->level;
        } else {
            //just append this element at the end
            $group = (object)array(
                'type' => $element->type,
                'level' => $this->groupLevel,
                'position' => $this->position,
                'length' => 1,
                'sorted' => ''
            );
            $this->groups[] = $group;
            $element->addedAtLevel = $this->groupLevel;

            $id = $element->id . $this->delimiter;

            $group->sorted .= $id;
            $this->position += strlen($id);
            $this->groupLevel++;
        }
    }

    /**
     * @param integer $position
     * @param object  $element
     */
    public function addItemAt($group, $element)
    {
        $group->sorted .= $element->id . $this->delimiter;
    }

    /**
     * {@inheritDoc}
     */
    public function sort()
    {
        $list = '';
        $this->doSort();
        foreach ($this->groups as $group) {
            $list .= $group->sorted;
        }

        return explode($this->delimiter, rtrim($list, $this->delimiter));
    }

    /**
     * {@inheritDoc}
     */
    public function getGroups()
    {
        $position = 0;
        return array_map(function($group) use (&$position) {
            $groupCloned = clone $group;
            $groupCloned->position = $position;
            unset($groupCloned->sorted);
            $position += $groupCloned->length;
            return $groupCloned;
        }, $this->groups);
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

        $this->position = 0;
        $this->sorted = '';

        foreach ($this->elements as $element) {
            $parents = array();
            $this->visit($element, $parents);
        }

        return $this->sorted;
    }
}