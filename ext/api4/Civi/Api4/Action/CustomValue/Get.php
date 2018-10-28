<?php

namespace Civi\Api4\Action\CustomValue;

use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Api4\Utils\ReflectionUtils;
use Civi\Api4\Query\Api4SelectQuery;
use Civi\Api4\CustomValue;

/**
 * Get fields for a custom group.
 */
class Get extends \Civi\Api4\Action\Get {

  /**
   * @inheritDoc
   */
  public function getEntity() {
    return 'Custom_' . $this->getCustomGroup();
  }

}
