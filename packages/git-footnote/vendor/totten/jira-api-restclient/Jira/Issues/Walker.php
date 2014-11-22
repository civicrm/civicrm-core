<?php
/*
 * The MIT License
 *
 * Copyright (c) 2012 Shuhei Tanuma
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
//namespace Jira\Issues;

class Jira_Issues_Walker implements Iterator
{
    /* @var Jira_Api $jira */
    protected $jira;

    protected $jql = null;

    protected $offset = 0;

    protected $current = 0;

    protected $total = 0;

    protected $max = 0;

    protected $start_at = 0;

    protected $per_page = 50;

    protected $executed = false;

    protected $result = array();

    protected $navigable = null;

    protected $callback;

    public function __construct(Jira_Api $api)
    {
        $this->jira = $api;
    }

    /**
     * push jql
     *
     * @param $jql
     * @param null $navigable
     */
    public function push($jql, $navigable = null)
    {
        $this->jql = $jql;
        $this->navigable = $navigable;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
        if (is_callable($this->callback)) {
            $tmp = $this->result[$this->offset];
            $callback = $this->callback;

            return $callback($tmp);
        } else {
            return $this->result[$this->offset];
        }
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        $this->offset++;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        if ($this->start_at > 0) {
            return $this->offset + (($this->start_at-1) * $this->per_page);
        } else {
            return 0;
        }
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        if (is_null($this->jql)) {
            throw new Exception('you have to call Jira_Walker::push($jql, $navigable) at first');
        }

        if (!$this->executed) {
            try {

                $result = $this->jira->search($this->getQuery(), $this->key(), $this->per_page, $this->navigable);

                $this->setResult($result);
                $this->executed = true;

                if ($result->getTotal() == 0) {
                    return false;
                }

                return true;
            } catch (Exception $e) {
                error_log($e->getMessage());

                return false;
            }
        } else if ($this->offset >= $this->max && $this->key() < $this->total){
            try {
                $result = $this->jira->search($this->getQuery(), $this->key(), $this->per_page, $this->navigable);
                $this->setResult($result);

                return true;
            } catch (Exception $e) {
                error_log($e->getMessage());

                return false;
            }
        } else if (($this->start_at-1) * $this->per_page + $this->offset < $this->total) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        $this->offset = 0;
        $this->start_at = 0;
        $this->current = 0;
        $this->max = 0;
        $this->total = 0;
        $this->executed = false;
        $this->result = array();
    }

    /**
     * @param $callable
     * @throws Exception
     */
    public function setDelegate($callable)
    {
        if (is_callable($callable)) {
            $this->callback = $callable;
        } else {
            throw new Exception("passed argument is not callable");
        }
    }

    /**
     * @param $result
     */
    protected function setResult(Jira_Api_Result $result)
    {
        $this->total  = $result->getTotal();
        $this->offset = 0;
        $this->max    = $result->getIssuesCount();
        $this->result = $result->getIssues();
        $this->start_at++;
    }

    /**
     * @return mixed
     */
    protected function getQuery()
    {
        return $this->jql;
    }
}
