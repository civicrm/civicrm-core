<?php

namespace Civi\Kcfinder\Page;

class Upload {

  public static function run() {
    \Civi\Kcfinder::bootstrapPage();
    $uploader = new \kcfinder\uploader();
    $uploader->upload();
  }

}
