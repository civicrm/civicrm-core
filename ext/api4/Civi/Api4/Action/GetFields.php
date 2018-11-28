<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

namespace Civi\Api4\Action;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Service\Spec\SpecGatherer;
use Civi\Api4\Generic\Result;
use Civi\Api4\Service\Spec\SpecFormatter;

/**
 * Get fields for an entity.
 *
 * @method $this setIncludeCustom(bool $value)
 * @method bool getIncludeCustom()
 * @method $this setOptions(bool $value)
 * @method bool getOptions()
 * @method $this setAction(string $value)
 * @method $this setSelect(array $value)
 * @method $this addSelect(string $value)
 * @method array getSelect()
 * @method $this setFields(array $value)
 * @method $this addField(string $value)
 * @method array getFields()
 */
class GetFields extends AbstractAction {

  /**
   * Override default to allow open access
   * @inheritDoc
   */
  protected $checkPermissions = FALSE;

  /**
   * Include custom fields for this entity, or only core fields?
   *
   * @var bool
   */
  protected $includeCustom = TRUE;

  /**
   * Fetch option lists for fields?
   *
   * @var bool
   */
  protected $getOptions = FALSE;

  /**
   * Which fields should be returned?
   *
   * Ex: ['contact_type', 'contact_sub_type']
   *
   * @var array
   */
  protected $fields = [];

  /**
   * Which attributes of the fields should be returned?
   *
   * @options name, title, description, default_value, required, options, data_type, fk_entity, serialize, custom_field_id, custom_group_id
   *
   * @var array
   */
  protected $select = [];

  /**
   * @var string
   */
  protected $action = 'get';

  public function _run(Result $result) {
    /** @var SpecGatherer $gatherer */
    $gatherer = \Civi::container()->get('spec_gatherer');
    // Any fields name with a dot in it is custom
    if ($this->fields) {
      $this->includeCustom = strpos(implode('', $this->fields), '.') !== FALSE;
    }
    $spec = $gatherer->getSpec($this->getEntity(), $this->getAction(), $this->includeCustom);
    $fields = SpecFormatter::specToArray($spec->getFields($this->fields), (array) $this->select, $this->getOptions);
    // Fixme - $this->action ought to already be set. Might be a name conflict upstream causing it to be nullified?
    $result->action = 'getFields';
    $result->exchangeArray(array_values($fields));
  }

  /**
   * @return string
   */
  public function getAction() {
    return $this->action;
  }

}
