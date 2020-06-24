{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for adding/editing Contact Type  *}

<div class="crm-block crm-form-block crm-contact-type-form-block">
{if $action eq 8}
  <div class="messages status no-popup">
    <div class="icon inform-icon"></div>
        {ts}WARNING: {ts}This action cannot be undone.{/ts} {ts}Do you want to continue?{/ts}{/ts}
    </div>
{else}
 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
 <table class="form-layout-compressed">
   <tr class="crm-contact-type-form-block-label">
      <td class="label">{$form.label.label}
      {if $action eq 2}
        {include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contact_type' field='label' id= $cid }
      {/if}
      </td>

      <td>{$form.label.html}</td>
   </tr>
   <tr class="crm-contact-type-form-block-parent_id">
      <td class="label">{$form.parent_id.label}</td>
           {if $is_parent OR $action EQ 1}
             <td>{$form.parent_id.html}</td>
           {else}
             <td>{ts}{$contactTypeName}{/ts} {ts}(built-in){/ts}</td>
           {/if}
   </tr>
   <tr class="crm-contact-type-form-block-image_URL">
      <td class="label">{$form.image_URL.label} {help id="id-image_URL"}</td>
      <td>{$form.image_URL.html|crmAddClass:'huge40'}</td>
   </tr>
   <tr class="crm-contact-type-form-block-description">
     <td class="label">{$form.description.label}
     {if $action eq 2}
       {include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contact_type' field='description' id= $cid}
     {/if}
     </td>

     <td>{$form.description.html}</td>
   </tr>
   <tr class="crm-contact-type-form-block-is_active">
     <td class="label">{$form.is_active.label}</td><td>{$form.is_active.html}</td>
   </tr>
 </table>
{/if}
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
