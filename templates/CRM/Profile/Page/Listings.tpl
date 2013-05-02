{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
{* make sure there are some fields in the selector *}
{if ! empty( $columnHeaders ) || $isReset }

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
        {if $mapURL}<a href="{$mapURL}">&raquo; {ts}Map these contacts{/ts}</a>{/if}
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
      {foreach from=$row item=value}
        <td>{$value}</td>
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
        <div class="icon inform-icon"></div>
        {ts}No fields in this Profile have been configured to display as a result column in the search results table. Ask the site administrator to check the Profile setup.{/ts}
    </div>
{/if}
</div>