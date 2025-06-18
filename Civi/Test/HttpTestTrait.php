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
   * The client may include some mix of these middlewares:
   *
   * @see \CRM_Utils_GuzzleMiddleware::authx()
   * @see \CRM_Utils_GuzzleMiddleware::url()
   * @see \CRM_Utils_GuzzleMiddleware::curlLog()
   * @see Middleware::history()
   * @see Middleware::log()
   *
   * @param array $options
   * @return \GuzzleHttp\Client
   */
  protected function createGuzzle($options = []) {
    $handler = HandlerStack::create();
    $handler->unshift(\CRM_Utils_GuzzleMiddleware::authx(), 'civi_authx');
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
      'timeout' => 15,
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
    $method = str_starts_with($action, 'get') ? 'GET' : 'POST';
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
    $method = str_starts_with($action, 'get') ? 'GET' : 'POST';
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
   * @param \Psr\Http\Message\ResponseInterface|null $response
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
   * Assert that the response did NOT produce a normal page-view.
   *
   * This is basically `assertStatusCode(404)`, except that the local configuration
   * (CMS/setings/exts/yaddayadda) may change how the error manifests.
   *
   * @param $response
   * @return void
   */
  protected function assertPageNotShown($response = NULL): void {
    $response = $this->resolveResponse($response);
    $actualCode = $response->getStatusCode();
    switch ($actualCode) {
      case 404: /* Good! Right! */
      case 403: /* Maybe request falls through to `/civicrm/dashboard` */
      case 500: /* Maybe request falls through to `/civicrm/dashboard`, and it's weird */
        // OK, close enough. You convinced that the page was not shown to the user.
        // Bump the assertion-counter and carry on.
        $this->assertTrue(TRUE);
        return;

      case 200:
        // Hypothetically, you might do extra checks on the body to detected misreported errors.
        // But for now, let's pretend that HTTP 200 means "OK, Page Found!"... since that is exactly what it means.
        $this->fail("Expected HTTP response to indicate a failure (e.g. 404). Received HTTP response $actualCode.\n" . $this->formatFailure($response));

      default:
        $this->fail("Expected HTTP response, but the status code makes no sense. Received HTTP response $actualCode.\n" . $this->formatFailure($response));
    }
  }

  /**
   * @param $expectType
   * @param \Psr\Http\Message\ResponseInterface|null $response
   *   If NULL, then it uses the last response.
   *
   * @return $this
   */
  protected function assertContentType($expectType, $response = NULL) {
    $response = $this->resolveResponse($response);
    [$actualType] = explode(';', $response->getHeader('Content-Type')[0]);
    $fmt = $actualType === $expectType ? '' : $this->formatFailure($response);
    $this->assertEquals($expectType, $actualType, "Expected content-type $expectType. Received content-type $actualType.\n$fmt");
    return $this;
  }

  /**
   * Assert that the response body matches a regular-expression.
   *
   * @param string $regexp
   * @param \Psr\Http\Message\ResponseInterface $response
   * @param string $message
   */
  protected function assertBodyRegexp($regexp, $response = NULL, $message = NULL) {
    if ($message) {
      $message .= "\n";
    }

    $response = $this->resolveResponse($response);
    $this->assertMatchesRegularExpression($regexp, (string) $response->getBody(),
      $message . 'Response body does not match pattern' . $this->formatFailure($response));
    return $this;
  }

  /**
   * Assert that the response body DOES NOT match a regular-expression.
   *
   * @param string $regexp
   * @param \Psr\Http\Message\ResponseInterface $response
   * @param string $message
   */
  protected function assertNotBodyRegexp($regexp, $response = NULL, $message = NULL) {
    if ($message) {
      $message .= "\n";
    }

    $response = $this->resolveResponse($response);
    $this->assertDoesNotMatchRegularExpression($regexp, (string) $response->getBody(),
      $message . 'Response body should not match pattern' . $this->formatFailure($response));
    return $this;
  }

  /**
   * @param \Psr\Http\Message\ResponseInterface|null $response
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
