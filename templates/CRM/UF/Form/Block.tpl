{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Edit or display Profile fields, when embedded in an online contribution or event registration form. *}
{if ! empty( $fields )}
  {strip}
    {assign var=zeroField value="Initial Non Existent Fieldset"}
    {assign var=fieldset  value=$zeroField}
    {* Unfortunately uF group information is munged into the uf fields array. We have ot iterate throug
    to extract it. I n future we could migrate to a version of Block.tpl that expects the UFGroup
    to be assigned by itself & remove this *}
    {foreach from=$fields item=field key=fieldName}
      {assign var=groupHelpPost  value=$field.groupHelpPost}
      {assign var=groupHelpPre  value=$field.groupHelpPre}
      {assign var=fieldset  value=$field.groupTitle}
      {assign var=groupDisplayTitle value=$field.groupDisplayTitle}
      {assign var=group_id value=$field.group_id}
      {assign var=groupName value=$field.groupName}
    {/foreach}

    {if $groupHelpPre && $action neq 4}
      <div class="messages help">{$groupHelpPre|smarty:nodefaults|purify}</div>
    {/if}

    {if !$hideFieldset}
      <fieldset class="crm-profile crm-profile-id-{$group_id} crm-profile-name-{$groupName}"><legend>{$groupDisplayTitle}</legend>
    {/if}

    {if ($form.formName eq 'Confirm' OR $form.formName eq 'ThankYou') AND $prefix neq 'honor'}
      <div class="header-dark">{$groupDisplayTitle} </div>
    {/if}
    {include file="CRM/UF/Form/Fields.tpl"}

    {if $groupHelpPost && $action neq 4}
      <div class="messages help">{$groupHelpPost|smarty:nodefaults|purify}</div>
    {/if}
    {if !$hideFieldset}
      </fieldset>
    {/if}

  {/strip}

{/if} {* fields array is not empty *}

{literal}
<script type="text/javascript">
  CRM.$(function($) {
    $('#selector tr:even').addClass('odd-row');
    $('#selector tr:odd ').addClass('even-row');
  });
</script>
{/literal}
