<?php

namespace Civi\Test;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;

/**
 * Class HttpTestTrait
 *
 * @package Civi\Test
 *
 * This trait provides helpers/assertions for testing Civi's HTTP interfaces, eg
 *
 * - createGuzzle() - Create HTTP client for sending requests to Civi.
 * - callApi4AjaxSuccess() - Use an HTTP client to send an AJAX-style request to APIv4
 * - callApi4AjaxError() - Use an HTTP client to send an AJAX-style request to APIv4
 * - assertStatusCode() - Check the status code. If it fails, output detailed information.
 * - assertContentType() - Check the content-type. If it fails, output detailed information.
 *
 * Use this in an E2E test for which you need to send inbound HTTP requests to Civi.
 * Alternatively, for a headless test which mocks outbound HTTP, see GuzzleTestTrait.
 */
trait HttpTestTrait {

  /**
   * List of HTTP requests that have been made by this test.
   *
   * @var array
   */
  protected $httpHistory = [];

  /**
   * Create an HTTP client suitable for simulating AJAX requests.
   *
   * @param array $options
   * @return \GuzzleHttp\Client
   */
  protected function createGuzzle($options = []) {
    $handler = HandlerStack::create();
    $handler->unshift(\CRM_Utils_GuzzleMiddleware::url(), 'civi_url');
    $handler->push(Middleware::history($this->httpHistory), 'history');

    if (getenv('DEBUG') >= 2) {
      $handler->push(Middleware::log(new \CRM_Utils_EchoLogger(), new MessageFormatter(MessageFormatter::DEBUG)), 'log');
    }
    elseif (getenv('DEBUG') >= 1) {
      $handler->push(\CRM_Utils_GuzzleMiddleware::curlLog(new \CRM_Utils_EchoLogger()), 'curl_log');
    }

    $defaults = [
      'handler' => $handler,
      'base_uri' => 'auto:',
    ];

    $options = array_merge($defaults, $options);
    return new \GuzzleHttp\Client($options);
  }

  /**
   * @param string $entity
   * @param string $action
   * @param array $params
   *
   * @return mixed
   */
  protected function callApi4AjaxSuccess(string $entity, string $action, $params = []) {
    $method = \CRM_Utils_String::startsWith($action, 'get') ? 'GET' : 'POST';
    $response = $this->createGuzzle()->request($method, "civicrm/ajax/api4/$entity/$action", [
      'headers' => ['X-Requested-With' => 'XMLHttpRequest'],
      // This should probably be 'form_params', but 'query' is more representative of frontend.
      'query' => ['params' => json_encode($params)],
      'http_errors' => FALSE,
    ]);
    $this->assertContentType('application/json', $response);
    $this->assertStatusCode(200, $response);
    $result = json_decode((string) $response->getBody(), 1);
    if (json_last_error() !== JSON_ERROR_NONE) {
      $this->fail("Failed to decode APIv4 JSON.\n" . $this->formatFailure($response));
    }
    return $result;
  }

  /**
   * @param string $entity
   * @param string $action
   * @param array $params
   *
   * @return mixed
   */
  protected function callApi4AjaxError(string $entity, string $action, $params = []) {
    $method = \CRM_Utils_String::startsWith($action, 'get') ? 'GET' : 'POST';
    $response = $this->createGuzzle()->request($method, "civicrm/ajax/api4/$entity/$action", [
      'headers' => ['X-Requested-With' => 'XMLHttpRequest'],
      // This should probably be 'form_params', but 'query' is more representative of frontend.
      'query' => ['params' => json_encode($params)],
      'http_errors' => FALSE,
    ]);
    $this->assertContentType('application/json', $response);
    $this->assertTrue($response->getStatusCode() >= 400, 'Should return an error' . $this->formatFailure($response));
    $result = json_decode((string) $response->getBody(), 1);
    if (json_last_error() !== JSON_ERROR_NONE) {
      $this->fail("Failed to decode APIv4 JSON.\n" . $this->formatFailure($response));
    }
    return $result;
  }

  /**
   * @param $expectCode
   * @param \Psr\Http\Message\ResponseInterface|NULL $response
   *   If NULL, then it uses the last response.
   *
   * @return $this
   */
  protected function assertStatusCode($expectCode, $response = NULL) {
    $response = $this->resolveResponse($response);
    $actualCode = $response->getStatusCode();
    $fmt = $actualCode === $expectCode ? '' : $this->formatFailure($response);
    $this->assertEquals($expectCode, $actualCode, "Expected HTTP response $expectCode. Received HTTP response $actualCode.\n$fmt");
    return $this;
  }

  /**
   * @param $expectType
   * @param \Psr\Http\Message\ResponseInterface|NULL $response
   *   If NULL, then it uses the last response.
   *
   * @return $this
   */
  protected function assertContentType($expectType, $response = NULL) {
    $response = $this->resolveResponse($response);
    list($actualType) = explode(';', $response->getHeader('Content-Type')[0]);
    $fmt = $actualType === $expectType ? '' : $this->formatFailure($response);
    $this->assertEquals($expectType, $actualType, "Expected content-type $expectType. Received content-type $actualType.\n$fmt");
    return $this;
  }

  /**
   * @param \Psr\Http\Message\ResponseInterface|NULL $response
   * @return \Psr\Http\Message\ResponseInterface
   */
  protected function resolveResponse($response) {
    return $response ?: $this->httpHistory[count($this->httpHistory) - 1]['response'];
  }

  /**
   * Given that an HTTP request has yielded a failed response, format a blurb
   * to summarize the details of the request+response.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *
   * @return false|string
   */
  protected function formatFailure(\Psr\Http\Message\ResponseInterface $response) {
    $details = [];

    $condenseArray = function($v) {
      if (is_array($v) && count($v) === 1 && isset($v[0])) {
        return $v[0];
      }
      else {
        return $v;
      }
    };

    /** @var \Psr\Http\Message\RequestInterface $request */
    $request = NULL;
    foreach ($this->httpHistory as $item) {
      if ($item['response'] === $response) {
        $request = $item['request'];
        break;
      }
    }

    if ($request) {
      $details['request'] = [
        'uri' => (string) $request->getUri(),
        'method' => $request->getMethod(),
        // Most headers only have one item. JSON pretty-printer adds several newlines. This output is meant for dev's reading the error-log.
        'headers' => array_map($condenseArray, $request->getHeaders()),
        'body' => (string) $request->getBody(),
      ];
    }

    $details['response'] = [
      'status' => $response->getStatusCode() . ' ' . $response->getReasonPhrase(),
      // Most headers only have one item. JSON pretty-printer adds several newlines. This output is meant for dev's reading the error-log.
      'headers' => array_map($condenseArray, $response->getHeaders()),
      'body' => (string) $response->getBody(),
    ];

    // If we get a full HTML document, then it'll be hard to read the error messages. Give an alternate rendition.
    if (preg_match(';\<(!DOCTYPE|HTML);', $details['response']['body'])) {
      // $details['bodyText'] = strip_tags($details['body']); // too much <style> noise
      $details['response']['body'] = \CRM_Utils_String::htmlToText($details['response']['body']);
    }

    return json_encode($details, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  }

}
