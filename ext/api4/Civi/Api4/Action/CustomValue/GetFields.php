<?php

namespace Civi\Api4\Action\CustomValue;

use Civi\Api4\Service\Spec\SpecGatherer;
use Civi\Api4\Generic\Result;
use Civi\Api4\Service\Spec\SpecFormatter;

/**
 * Get fields for a custom group.
 */
class GetFields extends \Civi\Api4\Action\GetFields {

  public function _run(Result $result) {
    /** @var SpecGatherer $gatherer */
    $gatherer = \Civi::container()->get('spec_gatherer');
    $spec = $gatherer->getSpec('Custom_' . $this->getCustomGroup(), $this->getAction(), $this->includeCustom);
    $specArray = SpecFormatter::specToArray($spec->getFields($this->fields), (array) $this->select, $this->getOptions);
    $result->action = 'getFields';
    $result->exchangeArray(array_values($specArray));
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
