
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