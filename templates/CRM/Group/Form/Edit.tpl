{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for adding/editing/deleting a group *}
{if $action eq 8}
  {include file="CRM/Group/Form/Delete.tpl"}
{else}
<div class="crm-block crm-form-block crm-group-form-block">
  <div class="help">
    {if $action eq 2}
      {capture assign=crmURL}class="no-popup" href="{crmURL p="civicrm/group/search" q="reset=1&force=1&context=smog&gid=`$group.id`"}"{/capture}
      {ts 1=$crmURL|smarty:nodefaults}You can edit the Name and Description for this group here. Click <a %1>Contacts in this Group</a> to view, add or remove contacts in this group.{/ts}
    {else}
      {ts}Enter a unique name and a description for your new group here. Then click 'Continue' to find contacts to add to your new group.{/ts}
    {/if}
  </div>
  <table class="form-layout">

    <tr class="crm-group-form-block-frontend-title">
      <td class="label">{$form.frontend_title.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_group' field='frontend_title' id=$group.id}{/if}</td>
      <td>{$form.frontend_title.html|crmAddClass:huge}
          {if !empty($group.saved_search_id)}&nbsp;({ts}Smart Group{/ts}){/if}
      </td>
    </tr>

    <tr class="crm-group-form-block-frontend-description">
      <td class="label">{$form.frontend_description.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_group' field='frontend_description' id=$group.id}{/if}</td>
      <td>{$form.frontend_description.html}</td>
    </tr>
    <tr class="crm-group-form-block-title">
      <td class="label">{$form.title.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_group' field='title' id=$group.id}{/if}</td>
      <td>{$form.title.html|crmAddClass:huge}
        {if !empty($group.saved_search_id)}&nbsp;({ts}Smart Group{/ts}){/if}
      </td>
    </tr>

    <tr class="crm-group-form-block-description">
      <td class="label">{$form.description.label}</td>
      <td>{$form.description.html}</td>
    </tr>

    {if !empty($form.group_type)}
      <tr class="crm-group-form-block-group_type">
        <td class="label">{$form.group_type.label}</td>
        <td>{$form.group_type.html} {help id="id-group-type" file="CRM/Group/Page/Group.hlp"}</td>
      </tr>
    {/if}

    <tr class="crm-group-form-block-visibility">
      <td class="label">{$form.visibility.label}</td>
      <td>{$form.visibility.html|crmAddClass:huge} {help id="id-group-visibility" file="CRM/Group/Page/Group.hlp"}</td>
    </tr>

    <tr class="crm-group-form-block-isReserved">
      <td class="label">{$form.is_reserved.label}</td>
      <td>{$form.is_reserved.html}
        <span class="description">{ts}If reserved, only users with 'administer reserved groups' permission can disable, delete, or change settings for this group. The reserved flag does NOT affect users ability to add or remove contacts from a group.{/ts}</span>
      </td>
    </tr>

    <tr class="crm-group-form-block-isActive">
      <td class="label">{$form.is_active.label}</td>
      <td>{$form.is_active.html}</td>
    </tr>

   {if $group.created_by}
      <tr class="crm-group-form-block-created">
        <td class="label">{ts}Created By{/ts}</td>
        <td>{$group.created_by}</td>
      </tr>
    {/if}

    {if !empty($group.modified_by)}
      <tr class="crm-group-form-block-modified">
        <td class="label">{ts}Modified By{/ts}</td>
        <td>{$group.modified_by}</td>
      </tr>
    {/if}


    <tr>
      <td colspan=2>{include file="CRM/common/customDataBlock.tpl" groupID='' customDataType='Group' customDataSubType=false cid=false}</td>
    </tr>
  </table>

  {*CRM-14190*}
  {include file="CRM/Group/Form/GroupsCommon.tpl"}

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
  {if $action neq 1}
    <div class="action-link">
      <a {$crmURL|smarty:nodefaults}><i class="crm-i fa-users" aria-hidden="true"></i> {ts}Contacts in this Group{/ts}</a>
      {if $editSmartGroupURL}
        <br />
        <a class="no-popup" href="{$editSmartGroupURL|smarty:nodefaults}"><i class="crm-i fa-pencil" aria-hidden="true"></i> {ts}Edit Smart Group Criteria{/ts}</a>
      {/if}
    </div>
  {/if}

  {literal}
  <script type="text/javascript">
    {/literal}{if $freezeMailingList}{literal}
    cj('input[type=checkbox][name="group_type[{/literal}{$freezeMailingList}{literal}]"]').prop('disabled',true);
    {/literal}{/if}{literal}
    {/literal}{if $hideMailingList}{literal}
    cj('input[type=checkbox][name="group_type[{/literal}{$hideMailingList}{literal}]"]').hide();
    cj('label[for="group_type[{/literal}{$hideMailingList}{literal}]"]').hide();
    {/literal}{/if}{literal}
  </script>
  {/literal}
</div>
{/if}
