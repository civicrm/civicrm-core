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
//namespace Jira\Api;

class Jira_Api_Client_MemcacheProxyClient implements Jira_Api_Client_ClientInterface
{
    protected $api;
    protected $mc;

    /**
     * create a traditional php client
     */
    public function __construct(Jira_Api_Client_ClientInterface $api, $server, $port)
    {
        $this->api = $api;
        $this->mc = new Memcached();
        $this->mc->addServer($server, $port);
    }

    /**
     * send request to the api server
     *
     * @param $method
     * @param $url
     * @param array $data
     * @param $endpoint
     * @param $credential
     * @return array|string
     * @throws Exception
     */
    public function sendRequest($method, $url, $data = array(), $endpoint, Jira_Api_Authentication_AuthenticationInterface $credential)
    {
        if ($method == "GET") {
            if ($result = $this->getFromCache($url, $data, $endpoint)) {
                //$this->setCache($url, $data, $endpoint, $result);
                return $result;
            }
        }
        $result = $this->api->sendRequest($method, $url, $data, $endpoint, $credential);

        if ($method == "GET") {
            $this->setCache($url, $data, $endpoint, $result);
        }
        return $result;
    }

    protected function getFromCache($url, $data, $endpoint)
    {
        $key = $endpoint . $url;
        $key .= http_build_query($data);
        $key = sha1($key);

        return $this->mc->get("jira:cache:" . $key);
    }

    protected function setCache($url, $data, $endpoint, $result)
    {
        $key = $endpoint . $url;
        $key .= http_build_query($data);
        $key = sha1($key);

        return $this->mc->set("jira:cache:" . $key, $result, 86400);
    }

}