<?php
namespace Civi\Setup\UI;

interface SetupControllerInterface {

  /**
   * @param string $method
   *   Ex: 'GET' or 'POST'.
   * @param array $fields
   *   List of any HTTP POST fields.
   * @return array
   *   The HTTP headers and response text.
   *   [0 => $headers, 1 => $body].
   */
  public function run($method, $fields = array());

  /**
   * @param array $urls
   *   Some mix of the following:
   *     - res: The base URL for loading resource files (images/javascripts) for this
   *       project. Includes trailing slash.
   *     - ctrl: The URL of this setup controller. May be used for POST-backs.
   * @return $this
   */
  public function setUrls($urls);

}
