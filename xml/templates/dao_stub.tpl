<?php
/**
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Placeholder class retained for legacy compatibility.
 *
{foreach from=$table.fields item=field}
 * @property {$field.phpType}{if $field.phpNullable}|null{/if} ${$field.name}
{/foreach}
 */
class {$table.className} extends CRM_Core_DAO_Base {ldelim}

{rdelim}
