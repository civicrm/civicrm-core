{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

{* this template is used for rendering the form for performing actions on extensions *}
<h3>{$title|smarty:nodefaults}</h3>
<div class="crm-block crm-form-block crm-admin-optionvalue-form-block">
   {if $action eq 2}
     <div class="messages status alert-warning no-popup">
        {icon icon="fa-info-circle"}{/icon}
        {ts}Downloading this extension will provide you with new functionality. Please make sure that the extension you're installing or upgrading comes from a trusted source.{/ts} {ts}Do you want to continue?{/ts}
      </div>
   {/if}
   {if $action eq 1}
     <div class="messages status alert-warning no-popup">
        {icon icon="fa-info-circle"}{/icon}
        {ts}Installing this extension will provide you with new functionality.{/ts} {ts}Do you want to continue?{/ts}
      </div>
   {/if}
   {if $action eq 8}
      <div class="messages status alert-warning no-popup">
        {icon icon="fa-info-circle"}{/icon}
        {ts}WARNING: Uninstalling this extension might result in the loss of all records which use the extension.{/ts} {ts}This may mean the loss of a substantial amount of data, and the action cannot be undone.{/ts} {ts}Do you want to continue?{/ts}
      </div>
   {/if}
   {if $action eq 32}
      <div class="messages status alert-warning no-popup">
        {icon icon="fa-info-circle"}{/icon}
        {ts}Enabling this extension will restore the functionality that it provides.{/ts} {ts}Any records which use this extension will be become available again.{/ts} {ts}Do you want to continue?{/ts}
      </div>
   {/if}
   {if $action eq 64}
      <div class="messages status alert-warning no-popup">
        {icon icon="fa-info-circle"}{/icon}
        {ts}Disabling this extension will remove the functionality that it provides.{/ts} {ts}Any records which use this extension will be retained, but will only become available when it is enabled again.{/ts} {ts}Do you want to continue?{/ts}
      </div>
   {/if}
   {if $action eq 2 or $action eq 1 or $action eq 8 or $action eq 32 or $action eq 64}
     {include file="CRM/Admin/Page/ExtensionDetails.tpl"}
   {/if}
   <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
