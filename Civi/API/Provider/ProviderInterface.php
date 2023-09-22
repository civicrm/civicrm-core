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

namespace Civi\API\Provider;

/**
 * An API "provider" provides a means to execute API requests.
 */
interface ProviderInterface {

  /**
   * @param array $apiRequest
   *   The full description of the API request.
   * @return array
   *   structured response data (per civicrm_api3_create_success)
   * @see civicrm_api3_create_success
   * @throws \CRM_Core_Exception
   */
  public function invoke($apiRequest);

  /**
   * @param int $version
   *   API version.
   * @return array<string>
   */
  public function getEntityNames($version);

  /**
   * @param int $version
   *   API version.
   * @param string $entity
   *   API entity.
   * @return array<string>
   */
  public function getActionNames($version, $entity);

}
