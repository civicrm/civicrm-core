<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */


namespace Civi\Api4\Event\Subscriber;

use Civi\Api4\Generic\AbstractAction;

class CustomFieldPreSaveSubscriber extends Generic\PreSaveSubscriber {

  public $supportedOperation = 'create';

  public function modify(&$field, AbstractAction $request) {
    if (!empty($field['option_values'])) {
      $weight = 0;
      foreach ($field['option_values'] as $key => $value) {
        // Translate simple key/value pairs into full-blown option values
        if (!is_array($value)) {
          $value = [
            'label' => $value,
            'value' => $key,
            'is_active' => 1,
            'weight' => $weight,
          ];
          $key = $weight++;
        }
        $field['option_label'][$key] = $value['label'];
        $field['option_value'][$key] = $value['value'];
        $field['option_status'][$key] = $value['is_active'];
        $field['option_weight'][$key] = $value['weight'];
      }
    }
    $field['option_type'] = !empty($field['option_values']);
  }

  public function applies(AbstractAction $request) {
    return $request->getEntityName() === 'CustomField';
  }

}
