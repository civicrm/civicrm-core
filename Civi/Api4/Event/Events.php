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


namespace Civi\Api4\Event;

class Events {

  /**
   * Prepare the specification for a request. Fired from within a request to
   * get fields.
   *
   * @see \Civi\Api4\Event\GetSpecEvent
   */
  const GET_SPEC = 'civi.api.get_spec';

  /**
   * Build the database schema, allow adding of custom joins and tables.
   */
  const SCHEMA_MAP_BUILD = 'api.schema_map.build';

  /**
   * Alter query results of APIv4 select query
   */
  const POST_SELECT_QUERY = 'api.select_query.post';

}
