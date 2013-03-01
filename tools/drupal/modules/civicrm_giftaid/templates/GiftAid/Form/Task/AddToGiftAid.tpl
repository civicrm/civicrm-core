{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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
<div class="crm-block crm-form-block crm-export-form-block">

<h2>{ts}Add To Gift Aid{/ts}</h2>
<div class="help">
<p>{ts}Use this form to submit Gift Aid contributions.  Note that this action is irreversible, i.e. you cannot take contributions out of a batch once they have been added.{/ts}</p>
</div>
<table class="form-layout">
     <tr>
		<td>
			<table class="form-layout">
				<tr><td class="label">{$form.title.label}</td><td>{$form.title.html}</td><tr>
				<tr><td class="label">{$form.description.label}</td><td>{$form.description.html}</td></tr>
			</table>
		</td>
     </tr>
</table>
<h3>{ts}Summary{/ts}</h3>
<table class="report" style="width: 100%">
        <tr>
            <td>
               <div class="crm-accordion-header">
               Number of selected contributions: {$selectedContributions}
               </div>
            </td>
        </tr>
	<tr>
           {if $totalAddedContributions}
           <td>
           <div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-closed">
           <div class="crm-accordion-header">
           <div class="icon crm-accordion-pointer"></div>
            Number of contributions that will be added to this batch: {$totalAddedContributions}
           </div><!-- /.crm-accordion-header -->
           <div class="crm-accordion-body">
           <table class="selector">
           <thead >
	      <tr>
                 <th>{ts}Name{/ts}</th>
	         <th>{ts}Amount{/ts}</th>
	         <th>{ts}Type{/ts}</th>
	         <th>{ts}Source{/ts}</th>
	         <th>{ts}Recieved{/ts}</th>
	      </tr>
            </thead>
             {foreach from=$contributionsAddedRows item=row}
             <tr>
                <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`"}">{$row.display_name}</a></td>
                <td>{$row.total_amount}</td>
                <td>{$row.financial_account}</td>
                <td>{$row.source}</td>
                <td>{$row.receive_date}</td>
              </tr>
             {/foreach}
           </table>
	   </div><!-- /.crm-accordion-body -->
           </div><!-- /.crm-accordion-wrapper -->
           </td>
           {else}
             <td>
                 <div class="crm-accordion-header">
                 Number of selected contributions: {$totalAddedContributions}
                 </div>
             </td>
           {/if}
        </tr>
	<tr>
           {if $alreadyAddedContributions}
           <td><div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-closed">
           <div class="crm-accordion-header">
           <div class="icon crm-accordion-pointer"></div>
           Number of contributions already in a batch: {$alreadyAddedContributions}
           </div><!-- /.crm-accordion-header -->
           <div class="crm-accordion-body">
           <table class="selector">
	      <thead class="crm-accordion-header">
	      <tr>
                 <th>{ts}Name{/ts}</th>
	         <th>{ts}Amount{/ts}</th>
	         <th>{ts}Type{/ts}</th>
	         <th>{ts}Source{/ts}</th>
	         <th>{ts}Recieved{/ts}</th>
	         <th>{ts}Batch{/ts}</th>
             </tr>
             </thead>
             {foreach from=$contributionsAlreadyAddedRows item=row}
             <tr>
                <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`"}">{$row.display_name}</a></td>
                <td>{$row.total_amount}</td>
                <td>{$row.financial_account}</td>
                <td>{$row.source}</td>
                <td>{$row.receive_date}</td>
                <td>{$row.batch}</td>
             </tr>
             {/foreach}
	   </table>
           </div><!-- /.crm-accordion-body -->
           </div><!-- /.crm-accordion-wrapper -->
	   </td>
           {else}
               <td>
                  <div class="crm-accordion-header">
                  Number of contributions already in a batch: {$alreadyAddedContributions}
                  </div>
               </td>
           {/if}
        </tr>
	<tr>
           <td>
              <div class="crm-accordion-header">
              Number of contributions not valid for gift aid: {$notValidContributions}
              </div>
           </td>
        </tr>
</table>
<p>{ts}Use this form to submit Gift Aid contributions.  Note that this action is irreversible, i.e. you cannot take contributions out of a batch once they have been added.{/ts}</p>

{$form.buttons.html}

</div>
{literal}
<script type="text/javascript">
cj(function() {
   cj().crmaccordions(); 
});
</script>
{/literal}