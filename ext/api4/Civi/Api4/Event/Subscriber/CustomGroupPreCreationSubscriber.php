<?php

namespace Civi\Api4\Event\Subscriber;

use Civi\Api4\Action\Create;

class CustomGroupPreCreationSubscriber extends PreCreationSubscriber {
  /**
   * @param Create $request
   */
  protected function modify(Create $request) {
    $extends = $request->getValue('extends');
    $title = $request->getValue('title');
    $name = $request->getValue('name');

    if (is_string($extends)) {
      $request->addValue('extends', [$extends]);
    }

    if (NULL === $title && $name) {
      $request->addValue('title', $name);
    }
  }

  protected function applies(Create $request) {
    return $request->getEntity() === 'CustomGroup';
  }

}
