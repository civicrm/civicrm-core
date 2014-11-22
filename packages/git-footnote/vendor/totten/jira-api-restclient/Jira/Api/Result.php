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
class Jira_Api_Result
{
    protected $expand;
    protected $startAt;
    protected $maxResults;
    protected $total;

    protected $result;

    public function __construct($result)
    {
        if (isset($result['expand'])) {
            $this->expand = explode(",", $result['expand']);
        }

        if (isset($result['startAt'])) {
            $this->startAt = $result['startAt'];
        }

        if (isset($result['maxResults'])) {
            $this->maxResults = $result['maxResults'];
        }

        if (isset($result['total'])) {
            $this->total = $result['total'];
        }

        $this->result = $result;
    }

    public function getTotal()
    {
        return $this->total;
    }

    public function getIssuesCount()
    {
        return count($this->getIssues());
    }

    public function getIssues()
    {
        if (isset($this->result['issues'])) {
            $result = array();
            foreach ($this->result['issues'] as $issue) {
                $result[] = new Jira_Issue($issue);
            }
            return $result;
        }
    }

    public function getResult()
    {
        return $this->result;
    }
}