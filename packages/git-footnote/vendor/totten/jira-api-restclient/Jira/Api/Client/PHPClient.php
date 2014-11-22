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

class Jira_Api_Client_PHPClient implements Jira_Api_Client_ClientInterface
{
    protected $https_support = false;

    /**
     * create a traditional php client
     */
    public function __construct()
    {
        $wrappers = stream_get_wrappers();
        if (in_array("https", $wrappers)) {
            $this->https_support = true;
        }

    }

    protected function isSupportHttps()
    {
        return $this->https_support;
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
        if (!($credential instanceof Jira_Api_Authentication_Basic) && !($credential instanceof Jira_Api_Authentication_Anonymous)) {
            throw new Exception(sprintf("PHPClient does not support %s authentication.", get_class($credential)));
        }

        $header = array();
        if (!($credential instanceof Jira_Api_Authentication_Anonymous)) {
          $header[] = "Authorization: Basic " . $credential->getCredential();
        }
        $header[] = "Content-Type: application/json";

        $context = array(
            "http" => array(
                "method"  => $method,
                "header"  => join("\r\n", $header),
            ));

        if ($method=="POST" || $method == "PUT") {
            $__data     = json_encode($data);
            $header[]   = sprintf('Content-Length: %d', strlen($__data));

            $context['http']['header']  = join("\r\n", $header);
            $context['http']['content'] = $__data;
        } else {
            $url .= "?" . http_build_query($data);
        }

        if (strpos($endpoint, "https://") === 0 && !$this->isSupportHttps()) {
            throw new Exception("does not support https wrapper. please enable openssl extension");
        }


        set_error_handler(array($this, "errorHandler"));
        $data = file_get_contents($endpoint . $url,
            false,
            stream_context_create($context)
        );
        restore_error_handler();

        if (is_null($data)) {
            throw new Exception("JIRA Rest server returns unexpected result.");
        }

        return $data;
    }

    /**
     * @param $errno
     * @param $errstr
     * @throws Exception
     */
    public function errorHandler($errno, $errstr)
    {
        throw new Exception($errstr);
    }
}