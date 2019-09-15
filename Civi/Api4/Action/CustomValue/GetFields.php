<?php

namespace Civi\Api4\Action\CustomValue;

use Civi\Api4\Service\Spec\SpecFormatter;

/**
 * Get fields for a custom group.
 */
class GetFields extends \Civi\Api4\Generic\DAOGetFieldsAction {
  use \Civi\Api4\Generic\Traits\CustomValueActionTrait;

  protected function getRecords() {
    $fields = $this->_itemsToGet('name');
    /** @var \Civi\Api4\Service\Spec\SpecGatherer $gatherer */
    $gatherer = \Civi::container()->get('spec_gatherer');
    $spec = $gatherer->getSpec('Custom_' . $this->getCustomGroup(), $this->getAction(), $this->includeCustom);
    return SpecFormatter::specToArray($spec->getFields($fields), $this->loadOptions);
  }

  /**
   * @inheritDoc
   */
  public function getParamInfo($param = NULL) {
    $info = parent::getParamInfo($param);
    if (!$param) {
      // This param is meaningless here.
      unset($info['includeCustom']);
    }
    return $info;
  }

}
