<?php

namespace Civi\Api4\Action\Participant;

/**
 * @inheritDoc
 */
class Get extends \Civi\Api4\Generic\DAOGetAction {

  /**
   * @inheritDoc
   * $example->addWhere('contact_id.contact_type', 'IN', array('Individual', 'Household'))
   */
  protected $where = [
    ['is_test', '=', 0],
  ];

}
