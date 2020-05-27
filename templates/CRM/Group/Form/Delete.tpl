{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for confirmation of delete for a group  *}
<div class="crm-block crm-form-block crm-group-delete-form-block">

<h3>{ts}Delete Group{/ts}</h3>
    <div class="messages status no-popup">
        <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/>
    {ts 1=$title}Are you sure you want to delete the group %1?{/ts}<br /><br />
    {if $count}
        {ts count=$count plural='This group currently has %count members in it.'}This group currently has one member in it.{/ts}
    {/if}
    {ts}Deleting this group will NOT delete the member contact records. However, all contact subscription information and history for this group will be deleted.{/ts} {ts}If this group is used in CiviCRM profiles, those fields will be reset.{/ts} {ts}This action cannot be undone.{/ts}
    </div>

<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
