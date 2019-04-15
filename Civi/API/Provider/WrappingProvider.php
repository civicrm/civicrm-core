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

namespace Civi\API\Provider;

use Civi\API\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A wrapping provider overrides an existing API. It has discretion to pass-through
 * to the original API (0 or many times) or to substitute with entirely different
 * behavior.
 *
 * The WrappingProvider does yield any metadata of its own. It's primarily
 * intended for dynamically decorating an existing API.
 */
class WrappingProvider implements ProviderInterface {

  /**
   * @var callable
   *   Function($apiRequest, callable $continue)
   */
  protected $callback;

  /**
   * @var ProviderInterface
   */
  protected $original;

  /**
   * WrappingProvider constructor.
   * @param callable $callback
   * @param \Civi\API\Provider\ProviderInterface $original
   */
  public function __construct($callback, \Civi\API\Provider\ProviderInterface $original) {
    $this->callback = $callback;
    $this->original = $original;
  }

  public function invoke($apiRequest) {
    // $continue = function($a) { return $this->original->invoke($a); };
    $continue = [$this->original, 'invoke'];
    return call_user_func($this->callback, $apiRequest, $continue);
  }

  public function getEntityNames($version) {
    // return $version == $this->version ? [$this->entity] : [];
    throw new \API_Exception("Not support: WrappingProvider::getEntityNames()");
  }

  public function getActionNames($version, $entity) {
    // return $version == $this->version && $this->entity == $entity ? [$this->action] : [];
    throw new \API_Exception("Not support: WrappingProvider::getActionNames()");
  }

}
