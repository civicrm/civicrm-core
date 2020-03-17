<?php
namespace Civi\Setup;

class BasicRunner {

  /**
   * Execute the controller and display the output.
   *
   * Note: This is really just an example which handles input and output using
   * stock PHP variables and functions. Depending on the environment,
   * it may be easier to work directly with `getCtrl()->run(...)` which
   * handles inputs/outputs in a more abstract fashion.
   *
   * @param object $ctrl
   *    A web controller.
   */
  public static function run($ctrl) {
    $method = $_SERVER['REQUEST_METHOD'];
    list ($headers, $body) = $ctrl->run($method, ($method === 'GET' ? $_GET : $_POST));
    foreach ($headers as $k => $v) {
      header("$k: $v");
    }
    echo $body;
  }

}
