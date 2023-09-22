{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* $context indicates where we are searching, values = "search,advanced,smog,amtg" *}
{* smog = 'show members of group'; amtg = 'add members to group' *}
{if $context EQ 'smog'}
  <div class="crm-submit-buttons">

  {* Provide link to modify smart group search criteria if we are viewing a smart group (ssID = saved search ID) *}
  {if $permissionEditSmartGroup && !empty($editSmartGroupURL)}
      <a href="{$editSmartGroupURL}" class="button no-popup"><span><i class="crm-i fa-pencil" aria-hidden="true"></i> {ts 1=$group.title}Edit Smart Group Search Criteria for %1{/ts}</span></a>
      {help id="id-edit-smartGroup"}
  {/if}

  {if $permissionedForGroup}
    {capture assign=addMembersURL}{crmURL q="context=amtg&amtgID=`$group.id`&reset=1"}{/capture}
      <a href="{$addMembersURL}" class="button no-popup"><span><i class="crm-i fa-user-plus" aria-hidden="true"></i> {ts 1=$group.title}Add Contacts to %1{/ts}</span></a>
      {if $ssID}{help id="id-add-to-smartGroup"}{/if}
  {/if}
  {if $permissionEditSmartGroup}
    {capture assign=groupSettingsURL}{crmURL p='civicrm/group/edit' q="action=update&id=`$group.id`&reset=1"}{/capture}
        <a href="{$groupSettingsURL}" class="action-item button"><span><i class="crm-i fa-wrench" aria-hidden="true"></i> {ts}Edit Group Settings{/ts}</span></a>
  {/if}
  </div>
{/if}


