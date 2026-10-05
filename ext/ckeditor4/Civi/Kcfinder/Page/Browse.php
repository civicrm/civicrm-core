<?php

namespace Civi\Kcfinder\Page;

class Browse {

  public static function run() {
    \Civi\Kcfinder::bootstrapPage();
    $browser = new \kcfinder\browser();
    $browser->action();
    exit();
  }

}
