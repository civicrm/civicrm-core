<?php

use Civi\Test\EventCheck;
use Civi\Test\HookInterface;

return new class() extends EventCheck implements HookInterface {

  /**
   * Ensure that the hook data is always well-formed.
   *
   * @see \CRM_Utils_Hook::config()
   */
  public function hook_civicrm_config($config, $flags = NULL): void {
    $msg = 'Non-conforming hook_civicrm_config(...)';

    $this->assertType('CRM_Core_Config', $config, "$msg: Bad config object");
    $this->assertType('array', $flags, "$msg: Bad flags array");
    $this->assertType('boolean', $flags['civicrm'], "$msg: civicrm should be boolean");
    $this->assertType('boolean', $flags['uf'], "$msg: uf should be boolean");
    $this->assertType('integer', $flags['instances'], "$msg: instances should be integer");

    static $lastInstanceCount = 1;
    $this->assertTrue($flags['instances'] >= $lastInstanceCount, "$msg: instance count should be monotonic-increasing, starting from 1");
    $lastInstanceCount = $flags['instances'];

    $knownKeys = ['civicrm', 'uf', 'instances'];
    $unknownKeys = array_diff(array_keys($flags), $knownKeys);
    $this->assertEquals([], $unknownKeys, "$msg: Flags array has unknown keys: " . implode(' ', $unknownKeys));
  }

};
