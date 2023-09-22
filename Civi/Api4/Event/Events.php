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

/**
 * @deprecated
 */
class Events {

  /**
   * @deprecated
   * Just use the string instead of the constant when listening for this event
   */
  const SCHEMA_MAP_BUILD = 'api.schema_map.build';

  /**
   * @deprecated
   * Unused - there is no longer an event with this name
   * https://lab.civicrm.org/dev/joomla/-/issues/28#note_39487
   */
  const POST_SELECT_QUERY = 'api.select_query.post';

}
