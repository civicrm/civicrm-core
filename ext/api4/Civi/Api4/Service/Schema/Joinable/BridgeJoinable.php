<?php

namespace Civi\Api4\Service\Schema\Joinable;

class BridgeJoinable extends Joinable {
  /**
   * @var Joinable
   */
  protected $middleLink;

  public function __construct($targetTable, $targetColumn, $alias, Joinable $middleLink) {
    parent::__construct($targetTable, $targetColumn, $alias);
    $this->middleLink = $middleLink;
  }

  /**
   * @return Joinable
   */
  public function getMiddleLink() {
    return $this->middleLink;
  }

}
