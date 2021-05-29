<?php


namespace Civi\Api4\Action\OAuthContactToken;

class Delete extends \Civi\Api4\Generic\DAODeleteAction {

  use OnlyModifyOwnTokensTrait;

}
