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
    {if $help_pre && $action neq 4}<div class="messages help">{$help_pre}</div>{/if}
    {assign var=zeroField value="Initial Non Existent Fieldset"}
    {assign var=fieldset  value=$zeroField}
    {include file="CRM/UF/Form/Fields.tpl"}

    {if $field.groupHelpPost && $action neq 4  && $action neq 1028}
      <div class="messages help">{$field.groupHelpPost}</div>
    {/if}

    {if $mode eq 4}
      <div class="crm-submit-buttons">
        {$form.buttons.html}
      </div>
    {/if}

    {if $mode ne 8 && $action neq 1028 && !$hideFieldset}
    </fieldset>
    {/if}

    {if $help_post && $action neq 4}<br /><div class="messages help">{$help_post}</div>{/if}
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
