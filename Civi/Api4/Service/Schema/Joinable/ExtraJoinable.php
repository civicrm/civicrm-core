<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Api4\Service\Schema\Joinable;

/**
 * Like Joinable but without any default conditions so it can be fully customized.
 */
class ExtraJoinable extends Joinable {

  /**
   * This type of join relies entirely on the extra join conditions.
   *
   * @param string $baseTableAlias
   * @param string $targetTableAlias
   * @param array|null $openJoin
   *
   * @return array
   */
  public function getConditionsForJoin(string $baseTableAlias, string $targetTableAlias, ?array $openJoin) {
    $conditions = [];
    $this->addExtraJoinConditions($conditions, $baseTableAlias, $targetTableAlias);
    return $conditions;
  }

}
