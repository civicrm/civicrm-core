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
  {* Provide link to modify smart group search criteria if we are viewing a smart group (ssID = saved search ID) *}
  {if $permissionEditSmartGroup && !empty($editSmartGroupURL)}
      <div class="crm-submit-buttons">
        <a href="{$editSmartGroupURL}" class="button no-popup"><span><i class="crm-i fa-pencil" aria-hidden="true"></i> {ts 1=$group.title}Edit Smart Group Search Criteria for %1{/ts}</span></a>
        {help id="id-edit-smartGroup"}
      </div>
  {/if}

  {if $permissionedForGroup}
    {capture assign=addMembersURL}{crmURL q="context=amtg&amtgID=`$group.id`&reset=1"}{/capture}
    <div class="crm-submit-buttons">
      <a href="{$addMembersURL}" class="button no-popup"><span><i class="crm-i fa-user-plus" aria-hidden="true"></i> {ts 1=$group.title}Add Contacts to %1{/ts}</span></a>
      {if $ssID}{help id="id-add-to-smartGroup"}{/if}
    </div>
  {/if}
{/if}
