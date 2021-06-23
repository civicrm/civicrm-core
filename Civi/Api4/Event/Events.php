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

namespace Civi\Api4\Event;

class Events {

  /**
   * Build the database schema, allow adding of custom joins and tables.
   */
  const SCHEMA_MAP_BUILD = 'api.schema_map.build';

  /**
   * Add back POST_SELECT_QUERY const due to Joomla upgrade failure
   * https://lab.civicrm.org/dev/joomla/-/issues/28#note_39487
   */
  const POST_SELECT_QUERY = 'api.select_query.post';

}
