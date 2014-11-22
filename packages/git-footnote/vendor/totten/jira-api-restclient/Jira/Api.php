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

class Jira_Api
{
    const REQUEST_GET    = "GET";
    const REQUEST_POST   = "POST";
    const REQUEST_PUT    = "PUT";
    const REQUEST_DELETE = "DELETE";


    const AUTOMAP_FIELDS = 0x01;

    /** @var string $endpoint */
    protected $endpoint;

    /** @var \Jira_Api_Client_ClientInterface */
    protected $client;

    /** @var \Jira_Api_Authentication_AuthenticationInterface */
    protected $authentication;

    /** @var int $options */
    protected $options = self::AUTOMAP_FIELDS;

    /** @var array $fields */
    protected $fields;
    
    /** @var array $priority */
    protected $priorities;
    
    /** @var array $status */
    protected $statuses;

    /**
     * create a jira api client.
     *
     * @param $endpoint
     * @param Jira_Api_Authentication_AuthenticationInterface $authentication
     * @param Jira_Api_Client_ClientInterface $client
     */
    public function __construct($endpoint,
                                Jira_Api_Authentication_AuthenticationInterface $authentication,
                                Jira_Api_Client_ClientInterface $client = null)
    {
        $this->setEndPoint($endpoint);
        $this->authentication = $authentication;

        if (is_null($client)) {
            $client = new Jira_Api_Client_PHPClient();
        }

        $this->client = $client;
    }

    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * get endpoint url
     *
     * @return mixed
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * set end point url.
     *
     * @param $url
     */
    public function setEndPoint($url)
    {
        $this->fields = array();

        $this->endpoint = $url;
    }

    /**
     * get fields definitions.
     *
     * @return array
     */
    public function getFields()
    {
        if (!count($this->fields)) {
            $fields  = array();
            $_fields = $this->api(self::REQUEST_GET, "/rest/api/2/field", array());

            /* set hash key as custom field id */
            foreach($_fields->getResult() as $k => $v) {
                $fields[$v['id']] = $v;
            }
            $this->fields = $fields;
        }

        return $this->fields;
    }

    /**
     * get specified issue.
     *
     * issue key should be YOURPROJ-221
     *
     * @param $issueKey
     * @return mixed
     */
    public function getIssue($issueKey)
    {
        return $this->api(self::REQUEST_GET, sprintf("/rest/api/2/issue/%s", $issueKey));
    }

    public function editIssue($issueKey, $params)
    {
        return $this->api(self::REQUEST_PUT, sprintf("/rest/api/2/issue/%s", $issueKey), $params);
    }

    /**
     * add a comment to a ticket
     *
     * issue key should be YOURPROJ-221
     *
     * @param $issueKey
     * @param $params
     * @return mixed
     */
    public function addComment($issueKey, $params)
    {
        return $this->api(self::REQUEST_POST, sprintf("/rest/api/2/issue/%s/comment", $issueKey), $params);
    }

    /**
     * get available transitions for a ticket
     *
     * issue key should be YOURPROJ-22
     *
     * @param $issueKey
     * @param $params
     * @return mixed
     */
    public function getTransitions($issueKey, $params)
    {
        return $this->api(self::REQUEST_GET, sprintf("/rest/api/2/issue/%s/transitions", $issueKey), $params);
    }

    /**
     * transation a ticket
     *
     * issue key should be YOURPROJ-22
     *
     * @param $issueKey
     * @param $params
     * @return mixed
     */
    public function transition($issueKey, $params)
    {
        return $this->api(self::REQUEST_POST, sprintf("/rest/api/2/issue/%s/transitions", $issueKey), $params);
    }

    /**
     * get available issue types
     *
     * @return mixed
     */
    public function getIssueTypes()
    {
        $result = array();
        $types = $this->api(self::REQUEST_GET, "/rest/api/2/issuetype",array(), true);

        foreach ($types as $issue_type) {
            $result[] = new Jira_IssueType($issue_type);
        }

        return $result;
    }

    /**
     * get available versions
     *
     * @return mixed
     */
    public function getVersions($projectKey)
    {
        $result = $this->api(self::REQUEST_GET, "/rest/api/2/project/{$projectKey}/versions", array(), true);
        return $result;
    }

    /**
     * get available priorities
     *
     * @return mixed
     */
    public function getPriorties()
    {
    	if (!count($this->priorities)) {
    		$priorities  = array();
    		$result = $this->api(self::REQUEST_GET, "/rest/api/2/priority", array());
    	    /* set hash key as custom field id */
    		foreach($result->getResult() as $k => $v) {
    			$priorities[$v['id']] = $v;
    		}
    		$this->priorities = $priorities;
    	}
    	return $this->priorities;
    }

    /**
     * get available statuses
     *
     * @return mixed
     */
    public function getStatuses()
    {
    	if (!count($this->statuses)) {
    		$statuses  = array();
    		$result = $this->api(self::REQUEST_GET, "/rest/api/2/status", array());
    		/* set hash key as custom field id */
    		foreach($result->getResult() as $k => $v) {
    			$statuses[$v['id']] = $v;
    		}
    		$this->statuses= $statuses;
    	}
    	return $this->statuses;
    }
    

    /**
     * create an issue.
     *
     * @param $projectKey
     * @param $summary
     * @param $issueType
     * @param array $options
     * @return mixed
     */
    public function createIssue($projectKey, $summary, $issueType, $options = array())
    {
        $default = array(
            "project" => array(
                "key"  => $projectKey,
            ),
            "summary"     => $summary,
            "issuetype"   => array(
                "id" => $issueType,
        ));

        $default = array_merge($default, $options);

        $result = $this->api(self::REQUEST_POST, "/rest/api/2/issue/", array(
            "fields" => $default
        ));

        return $result;
    }

    /**
     * query issues
     *
     * @param $jql
     * @param $startAt
     * @param $maxResult
     * @param string $fields
     *
     * @return Jira_API_Result
     */
    public function search($jql, $startAt = 0, $maxResult = 20, $fields = '*navigable')
    {
        $result = $this->api(self::REQUEST_GET, "/rest/api/2/search", array(
            "jql"        => $jql,
            "startAt"    => $startAt,
            "maxResults" => $maxResult,
            "fields"     => $fields,
        ));

        return $result;
    }

    /**
     * create JIRA Version
     *
     * @param $project_id
     * @param $name
     * @param array $options
     * @return mixed
     */
    public function createVersion($project_id, $name,$options = array())
    {
        $options = array_merge(array(
                "name"            => $name,
                "description"     => "",
                "project"         => $project_id,
                //"userReleaseDate" => "",
                //"releaseDate"     => "",
                "released"        => false,
                "archived"        => false,
            ), $options
        );

        return $this->api(self::REQUEST_POST, "/rest/api/2/version", $options);
    }


    /**
     * create JIRA Attachment
     *
     * @param $issue
     * @param $filename
     * @param array $options
     * @return mixed
     */
    public function createAttachment($issue, $filename,$options = array())
    {
    	$options = array_merge(array(
    			"file"            => '@' . $filename,
    	), $options
    	);
    	return $this->api(self::REQUEST_POST, "/rest/api/2/issue/" . $issue . "/attachments", $options, false ,TRUE);
    }
    
    /**
     * send request to specified host
     *
     * @param string $method
     * @param $url
     * @param array $data
     * @param bool $return_as_json
     * @return mixed
     */
    public function api($method = self::REQUEST_GET, $url, $data = array(), $return_as_json = false, $isfile = false, $debug = FALSE)
    {
        	$result = $this->client->sendRequest(
        			$method,
        			$url,
        			$data,
        			$this->getEndpoint(),
        			$this->authentication,
        			$isfile,
        			$debug
        	);
        if (strlen($result)) {
            $json = json_decode($result, true);
            if ($this->options & self::AUTOMAP_FIELDS) {
                if (isset($json['issues'])) {
                    if (!count($this->fields)) {
                        $this->getFields();
                    }

                    foreach ($json['issues'] as $offset => $issue) {
                        $json['issues'][$offset] = $this->automapFields($issue);
                    }
                }

            }

            if ($return_as_json) {
                return $json;
            } else {
                return new Jira_Api_Result($json);
            }
        } else {
            return false;
        }
    }

    protected function automapFields($issue)
    {
        if (isset($issue['fields'])) {
            $x  = array();
            foreach($issue['fields'] as $kk => $vv) {
                if (isset($this->fields[$kk])) {
                    $x[$this->fields[$kk]['name']] = $vv;
                } else {
                    $x[$kk] = $vv;
                }
            }
            $issue['fields'] = $x;
        }

        return $issue;
    }
}
