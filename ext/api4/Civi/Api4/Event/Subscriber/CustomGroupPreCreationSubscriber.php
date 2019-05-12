<?php

namespace Civi\Api4\Event\Subscriber;

use Civi\Api4\Generic\DAOCreateAction;

class CustomGroupPreCreationSubscriber extends PreCreationSubscriber {
  /**
   * @param DAOCreateAction $request
   */
  protected function modify(DAOCreateAction $request) {
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

  protected function applies(DAOCreateAction $request) {
    return $request->getEntityName() === 'CustomGroup';
  }

}
