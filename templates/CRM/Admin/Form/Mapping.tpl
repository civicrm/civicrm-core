{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for adding/editing a saved mapping *}
<div class="crm-block crm-form-block crm-mapping-form-block">
    {if $action eq 1 or $action eq 2}
      <table class="form-layout-compressed">
       <tr class="crm-mapping-form-block-name">
          <td class="label">{$form.name.label}</td>
          <td>{$form.name.html}</td>
       </tr>
       <tr class="crm-mapping-form-block-description">
          <td class="label">{$form.description.label}</td>
          <td>{$form.description.html}</td>
       </tr>
       <tr class="crm-mapping-form-block-mapping_type_id">
          <td class="label">{$form.mapping_type_id.label}</td>
          <td>{$form.mapping_type_id.html}</td>
       </tr>
      </table>
    {else}
        <div class="messages status no-popup">
            {icon icon="fa-info-circle"}{/icon}
            {ts 1=$mappingName}WARNING: Are you sure you want to delete mapping '<b>%1</b>'?{/ts} {ts}This action cannot be undone.{/ts}
        </div>
        <br />
    {/if}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" loaction="bottom"}</div>
</div>
