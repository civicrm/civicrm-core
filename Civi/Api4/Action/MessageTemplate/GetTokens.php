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

namespace Civi\Api4\Action\MessageTemplate;

use Civi\Token\TokenProcessor;

/**
 * List the tokens available for a given TokenProcessor schema, formatted
 * for a select2-style token picker.
 *
 * Returns a single row `{tokens: [...]}` -- not a list of MessageTemplate
 * entity records -- so this extends AbstractAction rather than
 * BasicGetAction, which would otherwise filter the result down to
 * recognised MessageTemplate field names.
 *
 * @method $this setSchema(array $schema)
 * @method array getSchema()
 */
class GetTokens extends \Civi\Api4\Generic\AbstractAction {

  /**
   * TokenProcessor schema keys, e.g. ['contactId', 'caseId'].
   *
   * @var array
   */
  protected $schema = ['contactId'];

  public function _run(\Civi\Api4\Generic\Result $result) {
    $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), ['schema' => $this->schema]);
    $result[] = [
      'tokens' => \CRM_Utils_Token::formatTokensForDisplay($tokenProcessor->listTokens()),
    ];
  }

}
