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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id$
 *
 */


namespace Civi\Api4\Generic;

use Civi\Api4\Service\Spec\SpecFormatter;

/**
 * @inheritDoc
 * @method bool getIncludeCustom()
 */
class DAOGetFieldsAction extends BasicGetFieldsAction {

  /**
   * Include custom fields for this entity, or only core fields?
   *
   * @var bool
   */
  protected $includeCustom = TRUE;

  /**
   * Get fields for a DAO-based entity.
   *
   * @return array
   */
  protected function getRecords() {
    $fields = $this->_itemsToGet('name');
    /** @var \Civi\Api4\Service\Spec\SpecGatherer $gatherer */
    $gatherer = \Civi::container()->get('spec_gatherer');
    // Any fields name with a dot in it is custom
    if ($fields) {
      $this->includeCustom = strpos(implode('', $fields), '.') !== FALSE;
    }
    $spec = $gatherer->getSpec($this->getEntityName(), $this->getAction(), $this->includeCustom, $this->values);
    return SpecFormatter::specToArray($spec->getFields($fields), $this->loadOptions, $this->values);
  }

  public function fields() {
    $fields = parent::fields();
    $fields[] = [
      'name' => 'help_pre',
      'data_type' => 'String',
    ];
    $fields[] = [
      'name' => 'help_post',
      'data_type' => 'String',
    ];
    $fields[] = [
      'name' => 'custom_field_id',
      'data_type' => 'Integer',
    ];
    $fields[] = [
      'name' => 'custom_group_id',
      'data_type' => 'Integer',
    ];
    return $fields;
  }

}
