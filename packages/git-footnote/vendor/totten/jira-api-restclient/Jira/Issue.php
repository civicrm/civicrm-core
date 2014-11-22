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
//namespace Jira;

class Jira_Issue
{
    /* @var array $expand */
    protected $expand;

    /* @var string $id */
    protected $id;

    /* @var string $self */
    protected $self;

    /* @var string $key */
    protected $key;

    /* @var array $fields */
    protected $fields;

    /**
     * @param array $issue
     */
    public function __construct($issue = array())
    {
        if (isset($issue['expand'])) {
            $this->expand = explode(",", $issue['expand']);
        }
        if (isset($issue['id'])) {
            $this->id = $issue['id'];
        }

        if (isset($issue['self'])) {
            $this->self = $issue['self'];
        }
        if (isset($issue['key'])) {
            $this->key = $issue['key'];
        }
        if (isset($issue['fields'])) {
            $this->fields = $issue['fields'];
        }
    }

    /**
     * get issue key (YOURPROJ-123)
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * get jira's internal issue id
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * get this issue api url
     *
     * @return string
     */
    public function getSelf()
    {
        return $this->self;
    }

    /**
     * get current fields
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * get issue summary
     *
     * @return mixed
     */
    public function getSummary()
    {
        if (isset($this->fields['Summary'])) {
            return $this->fields['Summary'];
        }
    }

    /**
     * get issue type
     *
     * @return mixed
     */
    public function getIssueType()
    {
        if (isset($this->fields['issuetype'])) {
            return $this->fields['issuetype'];
        }
    }

    /**
     * get issue reporter
     *
     * @return mixed
     */
    public function getReporter()
    {
        if (isset($this->fields['reporter'])) {
            return $this->fields['reporter'];
        }
    }

    /**
     * get issue created time
     *
     * @return mixed
     */
    public function getCreated()
    {
        if (isset($this->fields['created'])) {
            return $this->fields['created'];
        }
    }

    /**
     * get the current assignee
     *
     * @return mixed
     */

    public function getAssignee()
    {
        if (isset($this->fields['assignee'])) {
            return $this->fields['assignee'];
        }
    }    

    /**
     * get issue updated time
     *
     * @return mixed
     */
    public function getUpdated()
    {
        if (isset($this->fields['updated'])) {
            return $this->fields['updated'];
        }
    }

    /**
     * get priority
     *
     * @return mixed
     */
    public function getPriority()
    {
        if (isset($this->fields['priority'])) {
            return $this->fields['priority'];
        }
    }

    /**
     * get description
     *
     * @return mixed
     */
    public function getDescription()
    {
        if (isset($this->fields['Description'])) {
            return $this->fields['Description'];
        }
    }

    /**
     * get issue status
     *
     * @return mixed
     */
    public function getStatus()
    {
        if (isset($this->fields['Status'])) {
            return $this->fields['Status'];
        }
    }

    /**
     * get labels
     *
     * @return mixed
     */
    public function getLabels()
    {
        if (isset($this->fields['Labels'])) {
            return $this->fields['Labels'];
        }
    }

    /**
     * get project info
     *
     * @return mixed
     */
    public function getProject()
    {
        if (isset($this->fields['Project'])) {
            return $this->fields['Project'];
        }
    }

    /**
     * get fix versions.
     *
     * @return mixed
     */
    public function getFixVersions()
    {
        if (isset($this->fields['Fix Version/s'])) {
            return $this->fields['Fix Version/s'];
        }
    }

    /**
     * get resolutions
     *
     * @return mixed
     */
    public function getResolution()
    {
        if (isset($this->fields['resolution'])) {
            return $this->fields['resolution'];
        }
    }

    /**
     * get resolution date
     *
     * @return mixed
     */
    public function getResolutionDate()
    {
        if (isset($this->fields['resolutiondate'])) {
            return $this->fields['resolutiondate'];
        }
    }

    /**
     * get watches
     *
     * @return mixed
     */
    public function getWatches()
    {
        if (isset($this->fields['watches'])) {
            return $this->fields['watches'];
        }
    }

    /**
     * @param $key
     * @return array
     */
    public function get($key)
    {
        if (isset($this->fields[$key])) {
            return $this->fields[$key];
        }
    }
}