{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

<div class="crm-profile-name-{$ufGroupName}">
{crmRegion name="profile-search-`$ufGroupName`"}

{* make sure there are some fields in the selector *}
{if ! empty($columnHeaders) || $isReset}

{if $search}
<div class="crm-block crm-form-block">
  {include file="$searchTPL"}
</div>
{/if}
<div class="crm-block crm-content-block">
{* show profile listings criteria ($qill) *}
{if $rows}

    {if $qill}
    <div class="crm-search-tasks">
     <div id="search-status">
        {ts}Displaying contacts where:{/ts}
        {include file="CRM/common/displaySearchCriteria.tpl"}
        {if $mapURL}<a href="{$mapURL}"><i class="crm-i fa-map-marker" role="img" aria-hidden="true"></i> {ts}Map these contacts{/ts}</a>{/if}
    </div>
    </div>
    {/if}


    <div class="crm-search-results">
    {include file="CRM/common/pager.tpl" location="top"}
    {* Search criteria are passed to tpl in the $qill array *}


    {strip}
    <table>
      <tr class="columnheader">
      {foreach from=$columnHeaders item=header}
        <th scope="col">
        {if $header.sort}
          {assign var='key' value=$header.sort}
          {$sort->_response.$key.link}
        {else}
          {$header.name}
        {/if}
         </th>
      {/foreach}
      </tr>

      {counter start=0 skip=1 print=false}
      {foreach from=$rows item=row name=listings}
      <tr id="row-{$smarty.foreach.listings.iteration}" class="{cycle values="odd-row,even-row"}">
      {foreach from=$row key=index item=value}
        {if $columnHeaders.$index.field_name}
          <td class="crm-{$columnHeaders.$index.field_name}">{$value}</td>
        {else}
          <td>{$value}</td>
        {/if}
      {/foreach}
      </tr>
      {/foreach}
    </table>
    {/strip}
    {include file="CRM/common/pager.tpl" location="bottom"}
    </div>
{elseif ! $isReset}
    {include file="CRM/Contact/Form/Search/EmptyResults.tpl" context="Profile"}
{/if}


{else}
    <div class="messages status no-popup">
        {icon icon="fa-info-circle"}{/icon}
        {ts}No fields in this Profile have been configured to display as a result column in the search results table. Ask the site administrator to check the Profile setup.{/ts}
    </div>
{/if}
</div>

{/crmRegion}
</div>{* crm-profile-name-NAME *}
