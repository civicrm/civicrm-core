<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 *
 * Generated from {$table.sourceFile}
 * {$generated}
 */

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * {$table.className}
 *
 * {$table.tableInfo}
 * @ORM\Entity
 */
class {$table.className} extends \Civi\Core\Entity {ldelim}

  /**
   * @var integer
   *
   * @ORM\Column(name="id", type="integer", nullable=false)
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="IDENTITY")
   */
  private $id;
{foreach from=$table.fields item=field}
  {if $field.name eq 'id'}{continue}{/if}

  /**
   * @var {$field.columnType}
   *
   * {$field.columnInfo}
   * {$field.columnJoin}
   */
  private ${$field.propertyName}{if isset($field.default)} = '{$field.default}'{/if};
{/foreach}

  /**
   * Get id
   *
   * @return integer
   */
  public function getId() {ldelim}
    return $this->id;
  {rdelim}
{foreach from=$table.fields item=field}
  {if $field.name eq 'id'}{continue}{/if}

  /**
   * Set {$field.propertyName}
   *
   * @param {$field.phpType} ${$field.propertyName}
   * @return {$table.className}
   */
  public function set{$field.functionName}(${$field.propertyName}) {ldelim}
    $this->{$field.propertyName} = ${$field.propertyName};
    return $this;
  {rdelim}

  /**
   * Get {$field.propertyName}
   *
   * @return string
   */
  public function get{$field.functionName}() {ldelim}
    return $this->{$field.propertyName};
  {rdelim}
{/foreach}

{rdelim}


