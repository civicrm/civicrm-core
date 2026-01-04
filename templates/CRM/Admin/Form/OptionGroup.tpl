{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<h3>{if $action eq 1}{ts}New Option Group{/ts}{elseif $action eq 2}{ts}Edit Option Group{/ts}{else}{ts}Delete Option Group{/ts}{/if}</h3>
<div class="crm-block crm-form-block crm-admin-optiongroup-form-block">
   {if $action eq 8}
      <div class="messages status no-popup">
          {icon icon="fa-info-circle"}{/icon}
          {ts}WARNING: Deleting this option group will result in the loss of all records which use the option.{/ts} {ts}This may mean the loss of a substantial amount of data, and the action cannot be undone.{/ts} {ts}Do you want to continue?{/ts}
      </div>
     {else}
      <table class="form-layout-compressed">
          <tr class="crm-admin-optiongroup-form-block-title">
              <td class="label">{$form.title.label}
            {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_option_group' field='title' id=$id}{/if}</td><td>{$form.title.html}</td>
          </tr>
          <tr class="crm-admin-optiongroup-form-block-description">
              <td class="label">{$form.description.label}
            {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_option_group' field='description' id=$id}{/if}</td><td>{$form.description.html}</td>
          </tr>
          {if !empty($form.name)}
            <tr class="crm-admin-optiongroup-form-block-name">
              <td class="label">{$form.name.label}</td>
              <td>{$form.name.html}</td>
           </tr>
         {/if}
          <tr class="crm-admin-optiongroup-form-block-data-type">
            <td class="label">{$form.data_type.label}</td>
            <td>{$form.data_type.html}</td>
          </tr>
          <tr class="crm-admin-optiongroup-form-block-is_active">
              <td class="label">{$form.is_active.label}</td>
              <td>{$form.is_active.html}</td>
          </tr>
        {if !empty($form.is_reserved)}
          <tr class="crm-admin-optiongroup-form-block-is_reserved">
            <td class="label">{$form.is_reserved.label}</td>
            <td>{$form.is_reserved.html}</td>
          </tr>
        {/if}
      </table>
     {/if}
     <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
