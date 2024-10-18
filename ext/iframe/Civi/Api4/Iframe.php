<?php

namespace Civi\Api4;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\BasicEntity;
use Civi\Api4\Generic\BasicGetFieldsAction;
use Civi\Api4\Generic\Result;

class Iframe extends BasicEntity {

  public static function getFields(): BasicGetFieldsAction {
    return new BasicGetFieldsAction('Iframe', __FUNCTION__, fn() => []);
  }

  public static function installScript(): AbstractAction {
    return new class('Iframe', __FUNCTION__) extends AbstractAction {

      public function _run(Result $result) {
        \Civi::service('iframe.script')->install();
      }

    };
  }

  public static function renderScript(): AbstractAction {
    return new class('Iframe', __FUNCTION__) extends AbstractAction {

      public function _run(Result $result) {
        $iframe = \Civi::service('iframe');
        $scriptMgr = \Civi::service('iframe.script');
        $result[] = [
          'content' => $scriptMgr->render($iframe->getTemplate()),
        ];
      }

    };
  }

  public static function permissions(): array {
    return [
      'installScript' => ['administer iframe'],
    ];
  }

}
