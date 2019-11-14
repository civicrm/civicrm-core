{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* @todo with a small amount of tinkering most of this can be replaced by re-using the foreach loop in CRM_Core_EntityForm.tpl *}
<table class="form-layout">

  {foreach from=$fields item=fieldSpec}
    {assign var=fieldName value=$fieldSpec.name}
    <tr class="crm-{$entityInClassFormat}-form-block-{$fieldName}">
      {include file="CRM/Core/Form/Field.tpl"}
    </tr>
  {/foreach}
</table>
