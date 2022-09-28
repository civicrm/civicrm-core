<?php


namespace Civi\Api4\Action\OAuthContactToken;

class Update extends \Civi\Api4\Generic\DAOUpdateAction {

  use OnlyModifyOwnTokensTrait;

}
