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
 * The Base class of the CRM hierarchy. Currently does not provide
 * any useful functionality. As such we dont require anyone to derive
 * from this class. However it includes a few common files
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id$
 *
 */

require_once 'CRM/Core/I18n.php';

/**
 * Class CRM_Core_Base
 */
class CRM_Core_Base {

  /**
   * Constructor.
   */
  public function __construct() {
  }

}
