<?php
namespace Civi\Test\CiviEnvBuilder;

interface StepInterface {
  public function getSig();

  public function isValid();

  public function run($ctx);

}
