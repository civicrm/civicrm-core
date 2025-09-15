<?php

namespace Civi\Test;

use Civi\Schema\Traits\MagicGetterSetterTrait;
use Civi\Test\Exception\ProcessErrorException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Laravel\SerializableClosure\Support\ReflectionClosure;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * RemoteTestFunctions may be used by tests to define (inline) code that will run on
 * a remote server.
 *
 * @code
 * // Register a function. Send a request and parse the response as PHP data.
 * // Responses MUST be JSON-serializable data.
 * $getBaseUrl = RemoteTestFunction::register('getBaseUrl', fn() => CIVICRM_UF_BASEURL);
 * $data = $getBaseUrl->execute();
 * @endCode
 *
 * @code
 * // Register a function. Send HTTP call and inspect response.
 * $getBaseUrl = RemoteTestFunction::register('getBaseUrl', fn() => CIVICRM_UF_BASEURL);
 * $response = $getBaseUrl->httpRequest();
 * assertEquals(200, $response->getStatusCode());
 * @endCode
 *
 * TODO: Continue kicking-around method signatures. How best to distinguish functions which
 *       are meant to return JSON data and functions which are meant as mini page-controllers?
 *
 * @method string getRequestMethod()
 * @method $this setRequestMethod(string $method)
 * @method string getRequestChannel()
 * @method $this setRequestChannel(string $channel)
 * @method string getResponseType()
 * @method $this setResponseType(string $type)
 * @method $this setResponseDecoder(callable $decoder)
 * @method ?ClientInterface getClient()
 * @method $this setClient(?ClientInterface $client)
 * @method callable|null getResponseDecoder()
 */
class RemoteTestFunction {

  use HttpTestTrait;
  use MagicGetterSetterTrait;

  // ---------------------------------------------------------------------------------------
  // Static methods: Create and locate remote test functions.
  // ---------------------------------------------------------------------------------------

  /**
   * Declare a remote-test-function.
   *
   * This is used by a test-class to prepare the server to execute some code.
   *
   * @param string $class
   *   Who is creating this function.
   * @param string $name
   *   Name of the function. (Each test-function should be unique within its test-class.)
   * @param \Closure $closure
   *   Logic to execute. Should be a Closure. Must not have any `use()` properties.
   *   The result should be a JSON-friendly array-tree.
   *   Alternatively, it may emit a custom HTTP response via \CRM_Utils_System::sendResponse($result);
   * @return \Civi\Test\RemoteTestFunction
   */
  public static function register(string $class, string $name, \Closure $closure): RemoteTestFunction {
    $id = md5("{$class}::{$name}");
    $instance = new static($class, $name, $id, self::getIndexPath($id), self::getCodePath($class, $name));
    if (!class_exists($class)) {
      throw new \RuntimeException("RemoteTestFunction: Invalid registration class");
    }
    $instance->save(new ReflectionClosure($closure));
    return $instance;
  }

  /**
   * Lookup the implementation of a remote-test-function.
   *
   * @param string $class
   * @param string $name
   * @return \Civi\Test\RemoteTestFunction|null
   */
  public static function byName(string $class, string $name): ?RemoteTestFunction {
    $id = md5("{$class}::{$name}");
    $instance = new static($class, $name, $id, self::getIndexPath($id), self::getCodePath($class, $name));
    if (!file_exists($instance->codeFile)) {
      throw new \RuntimeException("Cannot find RemoteTestFunction($class, $name)");
    }
    return $instance;
  }

  public static function byId(string $id): ?RemoteTestFunction {
    $indexPath = self::getIndexPath($id);
    if (!file_exists($indexPath)) {
      throw new \RuntimeException("RemoteTestFunction: Invalid ID");
    }
    $about = json_decode(file_get_contents($indexPath), TRUE);
    if ($about['id'] !== $id) {
      throw new \RuntimeException("RemoteTestFunction: Mismatched ID. Stop trying to pull my leg.");
    }
    $instance = new static($about['class'], $about['name'], $about['id'], $indexPath, $about['codeFile']);
    return $instance;
  }

  private static function assertTestEnvironment(): void {
    if (!class_exists('PHPUnit\Framework\TestCase')) {
      throw new \LogicException("RemoteTestFunction::save() can only run in the test framework.");
    }
  }

  // ---------------------------------------------------------------------------------------
  // Data model
  // ---------------------------------------------------------------------------------------

  /**
   * Name of the test-class which defined the RTF.
   *
   * @var string
   */
  private string $class;

  /**
   * Logical name of the remote executable.
   *
   * This is often eponymous with the test-function but it may vary.
   *
   * @var string
   */
  private string $name;

  /**
   * @var string
   */
  private string $id;

  /**
   * Path to a file with local metadata about the RTF.
   *
   * @var string
   */
  private string $indexFile;

  /**
   * Path to a file with the minimal/extracted version of the RTF.
   *
   * @var string
   */
  private string $codeFile;

  /**
   * How to submit the request. One of: "POST", "GET", "LOCAL"
   * @var string
   */
  protected string $requestMethod = 'POST';

  protected string $responseType = 'application/json';

  /**
   * @var callable|null
   */
  protected $responseDecoder;

  /**
   * @var string
   *   Either 'HTTP' or 'LOCAL'
   */
  protected string $requestChannel = 'HTTP';

  protected ?ClientInterface $client = NULL;

  // ---------------------------------------------------------------------------------------
  // Primary APIs for calling remote test functions
  // ---------------------------------------------------------------------------------------

  /**
   * Generate an HTTP request template.
   *
   * If you send this HTTP request, it will run the remote test-function.
   *
   * @param array $args
   *   Data to pass through to the test function.
   * @param string $method
   * @return \Psr\Http\Message\RequestInterface
   */
  public function httpRequest(array $args = [], string $method = 'POST') {
    $token = \Civi::service('crypto.jwt')->encode([
      'civi.remote-test-function' => [
        'id' => $this->id,
        'args' => $args,
        'response-type' => $this->getResponseType(),
      ],
      'exp' => \CRM_Utils_Time::strtotime('+2 hour'), /* Handy for debugging */
    ]);
    $url = \Civi::url('backend://civicrm/dev/rtf')->addQuery([
      't' => $token,
    ]);

    return new Request($method, (string) $url);
  }

  /**
   * Execute the method.
   *
   * This is intended for use with RTF's that simply return JSON data.
   * If your RTF intends to emit some other response, then use httpRequest() and Guzzle Client.
   *
   * @param array $args
   *   Data to pass through to the test function.
   * @return mixed
   */
  public function execute(array $args = []) {
    $request = $this->httpRequest($args, $this->requestMethod);
    $client = $this->client ?: $this->createClient();
    $method = ($client instanceof \Psr\Http\Client\ClientInterface)
      ? 'sendRequest' : 'send'; /* PSR-18 vs Guzzle 6 */
    $response = $client->{$method}($request);
    return call_user_func($this->createDecoder(), $response);
  }

  protected function createDecoder(): callable {
    if ($this->responseDecoder !== NULL) {
      return $this->responseDecoder;
    }
    switch ($this->responseType) {
      case 'text/html':
        return fn(ResponseInterface $r) => (string) $r->getBody();

      case 'application/json':
        return function(ResponseInterface $response) {
          if ($response->getHeader('Content-type')[0] === 'application/json') {
            $data = (string) $response->getBody();
            return json_decode($data, TRUE);
          }
          else {
            throw new \LogicException("RemoteTestFunction::execute(): Malformed response. Expected JSON. Use DEBUG=2 to inspect the full interaction, or use a custom httpRequest() to handle non-JSON response-types.");
          }
        };

      default:
        throw new \LogicException("RemoteTestFunction: No decoder available for $this->responseType");
    }
  }

  /**
   * @return \GuzzleHttp\Client|\Psr\Http\Client\ClientInterface
   */
  private function createClient() {
    switch ($this->requestChannel) {
      case 'HTTP':
        return $this->createGuzzle();

      case 'LOCAL':
        return new class implements ClientInterface {

          public function sendRequest(RequestInterface $request): ResponseInterface {
            parse_str($request->getUri()->getQuery(), $query);
            $result = \CRM_Core_Page_RemoteTestFunction::handleJwt($query['t']);
            if (is_string($result)) {
              $result = new Response(200, ['Content-Type' => 'text/html'], "<html><body>$result</body></html>");
            }
            return $result;
          }

        };

      case 'CV':
        return $this->createCvClient();

      default:
        throw new \LogicException("Unrecognized request channel: {$this->requestChannel}");
    }
  }

  // ---------------------------------------------------------------------------------------
  // Internal helpers
  // ---------------------------------------------------------------------------------------

  /**
   * @param string $class
   * @param string $name
   * @param string $id
   * @param string $indexFile
   * @param string $codeFile
   */
  private function __construct(string $class, string $name, string $id, string $indexFile, string $codeFile) {
    // If the constant is missing, then your system is misconfigured.
    // if (!\CRM_Utils_Constant::value('CIVICRM_REMOTE_TEST_FUNC')) {
    //   throw new \RuntimeException("RemoteTestFunction can only be used if CIVICRM_REMOTE_TEST_FUNC is enabled.");
    // }

    $this->class = $class;
    $this->name = $name;
    $this->id = $id;
    $this->indexFile = $indexFile;
    $this->codeFile = $codeFile;
  }

  private function save(ReflectionClosure $refl): void {
    self::assertTestEnvironment();

    $upstreamTimestamp = max(filemtime($refl->getFileName()), filemtime(__FILE__));

    if (!file_exists($this->indexFile) || filemtime($this->indexFile) < $upstreamTimestamp) {
      $about = ['class' => $this->class, 'name' => $this->name, 'id' => $this->id, 'codeFile' => $this->codeFile];
      $this->writeFile($this->indexFile, json_encode($about));
    }

    if (!file_exists($this->codeFile) || filemtime($this->codeFile) < $upstreamTimestamp) {
      $this->writeFile($this->codeFile, $this->render($refl));
    }
  }

  private function writeFile(string $path, string $content): void {
    $parent = dirname($path);
    if (!is_dir($parent)) {
      if (!mkdir($parent, 0777, TRUE)) {
        throw new \RuntimeException("RemoteTestFunction: Failed to storage ($parent)");
      }
    }

    if (FALSE === file_put_contents($path, $content)) {
      throw new \RuntimeException("RemoteTestFunction: Failed to write file ($path)");
    }
  }

  public function _run($args = []) {
    if ($this->codeFile === NULL || !file_exists($this->codeFile)) {
      throw new \LogicException("Cannot load function for ({$this->class}::{$this->name}). Closure has not been extracted.");
    }
    if (!preg_match(';\.rtf\.php$;', $this->codeFile)) {
      throw new \RuntimeException("Malformed filename ($this->codeFile) for ({$this->class}::{$this->name})");
    }
    $f = include $this->codeFile;
    return $f(...$args);
  }

  private function render(ReflectionClosure $refl): string {
    if (!empty($refl->getUseVariables())) {
      throw new \LogicException(sprintf("Callback at %s::%d cannot used by %s. The \"use\" operator is unsupported.",
        $refl->getFileName(), $refl->getStartLine(), __CLASS__
      ));
    }

    // In practice, this would be inconvenient and not really add much.
    // if (!$refl->isStatic()) {
    //   throw new \LogicException(sprintf("Callback at %s::%d cannot used by %s. The function must be static.",
    //     $refl->getFileName(), $refl->getStartLine(), __CLASS__
    //   ));
    // }

    $message = sprintf("// This code is automatically extracted from %s.\n// Updates should be made in the main class.\n// You may wish to use this file for debugging.\n", $refl->getName());

    return '<' . "?php\n\n$message\nreturn " . $refl->getCode() . ";\n";
  }

  /**
   * The folder which stores information about test functions.
   *
   * @return string
   */
  private static function getIndexPath(string $id): string {
    return implode(DIRECTORY_SEPARATOR, [dirname(__DIR__, 2), 'tests', 'tmp', $id . '.json']);
  }

  private static function getCodePath(string $className, string $name): string {
    $class = new \ReflectionClass($className);
    $base = preg_replace('/\.php/', '', $class->getFileName());
    return $base . DIRECTORY_SEPARATOR . $name . '.rtf.php';
  }

  /**
   * @param array $env
   *   List of environment-variables to pass to the subprocess.
   * @return \Psr\Http\Client\ClientInterface
   */
  public function createCvClient(array $env = []) {
    return new class($env) implements ClientInterface {

      protected array $env;

      protected ?string $lastStdErr = NULL;

      public function __construct(array $env) {
        $this->env = $env;
      }

      public function sendRequest(RequestInterface $request): ResponseInterface {
        $requestFile = \CRM_Utils_File::tempnam('cv-http-req-');

        file_put_contents($requestFile, serialize($request));
        $php = implode("", [
          '$request = unserialize(file_get_contents(getenv("REQUEST")));',
          'parse_str($request->getUri()->getQuery(), $query);',
          '$c = "CRM_Core_Page_RemoteTestFunction";',
          'return $c::convertResponseToArray($c::handleJwt($query["t"]));',
        ]);
        $env = $this->env;
        $env['REQUEST'] = $requestFile;

        $cmdParts = [];
        $cmdParts[] = 'env';
        foreach ($env as $name => $value) {
          $cmdParts[] = $name . '=' . escapeshellarg($value);
        }
        $cmdParts[] = 'cv';
        $cmdParts[] = 'ev';
        $cmdParts[] = '--out=json';
        $cmdParts[] = escapeshellarg($php);
        $cmd = implode(' ', $cmdParts);

        ProcessHelper::run($cmd, $stdout, $stderr, $exit);
        $this->lastStdErr = $stderr;
        if ($exit !== 0) {
          throw new ProcessErrorException($cmd, $stdout, $stderr, $exit);
        }

        $result = \CRM_Core_Page_RemoteTestFunction::convertArrayToResponse(json_decode($stdout, TRUE));
        @unlink($requestFile); /* Keep until successful. Useful for debugging. */
        return $result;
      }

      public function getLastStdErr(): ?string {
        return $this->lastStdErr;
      }

    };
  }

}
