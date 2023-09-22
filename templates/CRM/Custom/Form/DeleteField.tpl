{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for confirmation of delete for a Fields  *}
<div class="crm-block crm-form-block crm-custom-deletefield-form-block">
    <div class="messages status no-popup">
         {icon icon="fa-info-circle"}{/icon}
            {ts 1=$title}WARNING: Deleting this custom field will result in the loss of all '%1' data. Any Profile form and listings field(s) linked with '%1' will also be deleted.{/ts} {ts}This action cannot be undone.{/ts} {ts}Do you want to continue?{/ts}
         </div>
 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
