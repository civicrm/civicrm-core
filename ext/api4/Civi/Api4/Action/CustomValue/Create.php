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

namespace Civi\Api4\Action\CustomValue;

use Civi\Api4\Utils\FormattingUtil;
use Civi\Api4\Generic\Result;
use Civi\Api4\Action\Create as DefaultCreate;

/**
 * @inheritDoc
 */
class Create extends DefaultCreate {

  /**
   * @inheritDoc
   */
  public function getEntity() {
    return 'Custom_' . $this->getCustomGroup();
  }

  /**
   * @inheritDoc
   */
  protected function fillDefaults(&$params) {
    foreach ($this->getEntityFields() as $name => $field) {
      if (empty($params[$name])) {
        $params[$name] = $field['default_value'];
      }
    }
  }

  /**
   * @inheritDoc
   */
  protected function writeObject($params) {
    FormattingUtil::formatWriteParams($params, $this->getEntity(), $this->getEntityFields());

    return \CRM_Core_BAO_CustomValueTable::setValues($params);
  }

}
