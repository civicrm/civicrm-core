<?php

class CRM_Shimmy_ShimmyHooks implements \Civi\Core\HookInterface {

  public static function hook_civicrm_shimmyFooBar(array &$data, string $name): void {
    $data[] = "hello $name";
  }

}
