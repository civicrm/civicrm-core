{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
{* $context indicates where we are searching, values = "search,advanced,smog,amtg" *}
{* smog = 'show members of group'; amtg = 'add members to group' *}
{if $context EQ 'smog'}
    {* Provide link to modify smart group search criteria if we are viewing a smart group (ssID = saved search ID) *}
    {if !empty($ssID)}
        {if $ssMappingID}
            {capture assign=editSmartGroupURL}{crmURL p="civicrm/contact/search/builder" q="reset=1&force=1&ssID=`$ssID`"}{/capture}
        {elseif $savedSearch.search_custom_id}
            {capture assign=editSmartGroupURL}{crmURL p="civicrm/contact/search/custom" q="reset=1&force=1&ssID=`$ssID`"}{/capture}
        {else}
            {capture assign=editSmartGroupURL}{crmURL p="civicrm/contact/search/advanced" q="reset=1&force=1&ssID=`$ssID`"}{/capture}
        {/if} 
        <div class="crm-submit-buttons">
            <a href="{$editSmartGroupURL}" class="button no-popup"><span><div class="icon edit-icon"></div> {ts 1=$group.title}Edit Smart Group Search Criteria for %1{/ts}</span></a>
            {help id="id-edit-smartGroup"}
        </div>
    {/if}

    {if $permissionedForGroup}
        {capture assign=addMembersURL}{crmURL q="context=amtg&amtgID=`$group.id`&reset=1"}{/capture}
        <div class="crm-submit-buttons">
            <a href="{$addMembersURL}" class="button no-popup"><span><div class="icon add-icon"></div> {ts 1=$group.title}Add Contacts to %1{/ts}</span></a>
            {if $ssID}{help id="id-add-to-smartGroup"}{/if}
        </div>
    {/if}
{/if}
