{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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
<div class='crm-container manage-grant-apps' id='crm-container'>
<h3>{ts}Manage Grant Application Pages{/ts}</h3>
{include file="CRM/common/enableDisable.tpl"}
	     {include file="CRM/common/jsortable.tpl"}
             <table id="options" class="display">
               <thead>
               <tr>
                 <th id="sortable">{ts}Title{/ts}</th>
            	 <th>{ts}ID{/ts}</th>
            	 <th>{ts}Enabled?{/ts}</th>
						        <th></th>
               </tr>
               </thead>
	       {if $fields}
	       {foreach from=$fields keys=key item=grows}
	       <tr id="row_{$grows.id}" class="{if NOT $grows.is_active} disabled{/if}">
	       <td id="row_{$grows.id}_title">{$grows.title}</td>	       
	              <td id="row_{$grows.id}_id">{$grows.id}</td>
		      <td id="row_{$grows.id}_status">{if $grows.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
<td class="crm-contribution-page-actions right nowrap">
		
			 {if $grows.configureActionLinks}	
		  	 <div class="crm-contribution-page-configure-actions">
		       	     {$grows.configureActionLinks|replace:'xx':$grows.id}
		  	 </div>
             {/if}

            {if $grows.contributionLinks}	
		  	<div class="crm-contribution-online-contribution-actions">
		       	     {$grows.contributionLinks|replace:'xx':$grows.id}
		  	</div>
		  	{/if}

		  	{if $grows.onlineGrantLinks}	
		  	<div class="crm-contribution-search-contribution-actions">
		       	     {$grows.onlineGrantLinks|replace:'xx':$grows.id}
		  	</div>
		  	{/if}

		  	<div class="crm-contribution-page-more">
                    {$grows.action|replace:'xx':$grows.id}
            </div>

		  </td>
	       </tr>

	       {/foreach} 
	       {/if}
	       </table>
</div>