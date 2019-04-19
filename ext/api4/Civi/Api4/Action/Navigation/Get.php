<?php

namespace Civi\Api4\Action\Navigation;

/**
 * @inheritDoc
 *
 * Fetch items from the navigation menu. By default this will fetch items from the current domain.
 */
class Get extends \Civi\Api4\Generic\DAOGetAction {

  /**
   * @inheritDoc
   */
  protected $where = [
    ['domain_id', '=', 'current_domain'],
  ];

}
