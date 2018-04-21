<?php


namespace MJS\TopSort\Implementations;


use MJS\TopSort\CircularDependencyException;

abstract class BaseImplementation
{
    /**
     * @var bool
     */
    protected $throwCircularDependency = true;

    /**
     * @var callable
     */
    protected $circularInterceptor;

    public function __construct(array $elements = array(), $throwCircularDependency = true)
    {
        $this->set($elements);
        $this->throwCircularDependency = $throwCircularDependency;
    }

    /**
     * @param callable $circularInterceptor
     */
    public function setCircularInterceptor($circularInterceptor)
    {
        $this->circularInterceptor = $circularInterceptor;
    }

    abstract public function set(array $elements);

    /**
     * @param object   $element
     * @param object[] $parents
     *
     * @throws CircularDependencyException
     */
    protected function throwCircularExceptionIfNeeded($element, $parents)
    {
        if (!$this->isThrowCircularDependency()) {
            return;
        }

        if (isset($parents[$element->id])) {

            $nodes = array_keys($parents);
            $nodes[] = $element->id;

            if ($this->circularInterceptor) {
                call_user_func($this->circularInterceptor, $nodes);
            } else {
                throw CircularDependencyException::create($nodes);
            }
        }
    }

    /**
     * @return boolean
     */
    public function isThrowCircularDependency()
    {
        return $this->throwCircularDependency;
    }

    /**
     * @param boolean $throwCircularDependency
     */
    public function setThrowCircularDependency($throwCircularDependency)
    {
        $this->throwCircularDependency = $throwCircularDependency;
    }
}