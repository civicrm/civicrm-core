<?php
namespace Civi\Test\CiviEnvBuilder;
class CallbackStep implements StepInterface {
  private $callback;
  private $sig;

  /**
   * CallbackStep constructor.
   * @param $callback
   * @param $sig
   */
  public function __construct($callback, $sig = NULL) {
    $this->callback = $callback;
    $this->sig = $sig === NULL ? md5(var_export($callback, 1)) : $sig;
  }

  public function getSig() {
    return $this->sig;
  }

  public function isValid() {
    return is_callable($this->callback);
  }

  public function run($ctx) {
    call_user_func($this->callback, $ctx);
  }

}
