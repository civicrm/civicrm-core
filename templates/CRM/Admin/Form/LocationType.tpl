{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for adding/editing location type  *}
<div class="crm-block crm-form-block crm-location-type-form-block">
{if $action eq 8}
  <div class="messages status no-popup">
     {icon icon="fa-info-circle"}{/icon}
        {ts}WARNING: Deleting this option will result in the loss of all location type records which use the option.{/ts} {ts}This may mean the loss of a substantial amount of data, and the action cannot be undone.{/ts} {ts}Do you want to continue?{/ts}
      </div>
{else}
  <table class="form-layout-compressed">
      <tr class="crm-location-type-form-block-label">
          <td class="label">{$form.name.label}</td>
          <td>{$form.name.html}<br />
               <span class="description">{ts}WARNING: Do NOT use spaces in the Location Name.{/ts}</span>
          </td>
      </tr>
      <tr class="crm-location-type-form-block-display_name">
          <td class="label">{$form.display_name.label}</td>
          <td>{$form.display_name.html}</td>
      </tr>
      <tr class="crm-location-type-form-block-vcard_name">
          <td class="label">{$form.vcard_name.label}</td>
          <td>{$form.vcard_name.html}</td>
      </tr>
      <tr class="crm-location-type-form-block-description">
          <td class="label">{$form.description.label}</td>
          <td>{$form.description.html}</td>
      </tr>
      <tr class="crm-location-type-form-block-is_active">
          <td class="label">{$form.is_active.label}</td>
          <td>{$form.is_active.html}</td>
      </tr>
      <tr  class="crm-location-type-form-block-is_default">
           <td class="label">{$form.is_default.label}</td>
           <td>{$form.is_default.html}</td>
      </tr>
  </table>
{/if}
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
