<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 *
 * The traditional CRM_Utils_Cache_Interface did not support has().
 * To get drop-in compliance with PSR-16, we use a naive adapter.
 *
 * Ideally, these should be replaced with more performant/native versions.
 */
trait CRM_Utils_Cache_NaiveHasTrait {

  public function has($key) {
    // This is crazy-talk. If you've got an environment setup where you might
    // be investigating this, fix your preferred cache driver by
    // replacing `NaiveHasTrait` with a decent function.
    $hasDefaultA = ($this->get($key, NULL) === NULL);
    $hasDefaultB = ($this->get($key, 123) === 123);
    return !($hasDefaultA && $hasDefaultB);
  }

}
