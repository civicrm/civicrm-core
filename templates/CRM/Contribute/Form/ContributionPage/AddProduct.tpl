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
{capture assign=managePremiumsURL}{crmURL p='civicrm/admin/contribute/managePremiums' q="reset=1"}{/capture}
<h3>{if $action eq 2 }{ts}Add Products to This Page{/ts} {elseif $action eq 1024}{ts}Preview{/ts}{else} {ts}Remove Products from this Page{/ts}{/if}</h3>
<div class="crm-block crm-form-block crm-contribution-add_product-form-block">
  <div id="help">
    {if $action eq 1024}
      {ts}This is a preview of this product as it will appear on your Contributions page(s).{/ts}
    {else}
      {ts}Use this form to select a premium to be offered on this Online Contribution Page.{/ts}
    {/if}
  </div>

  {if $action eq 8}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
      {ts}Are you sure you want to remove this premium product from this Contribution page?{/ts}
    </div>
  {elseif $action eq 1024}
     {include file="CRM/Contribute/Form/Contribution/PremiumBlock.tpl"}
  {else}
  <table class="form-layout-compressed">
    <tr class="crm-contribution-form-block-product_id"><td class="label">{$form.product_id.label}</td><td class="html-adjust">{$form.product_id.html}<br />
    <span class="description">{ts 1=$managePremiumsURL}Pick a premium to include on this Contribution Page. Use <a href='%1'>Manage Premiums</a> to create or enable additional premium choices for your site.{/ts}</span></td></tr>
    <tr class="crm-contribution-form-block-financial_type">
      <td class="label">{$form.financial_type_id.label}</td>
      <td>
      {if !$financialType }
		    {capture assign=ftUrl}{crmURL p='civicrm/admin/financial/financialType' q="reset=1"}{/capture}
        {ts 1=$ftUrl}There are no financial types configured with linked 'Cost of Sales Premiums' and 'Premiums Inventory Account' accounts. If you want to generate accounting transactions which track the cost of premiums used <a href='%1'>click here</a> to configure financial types and accounts.{/ts}
	    {else}		
        {$form.financial_type_id.html}{help id="id-financial_type-product"}
      {/if}		
      </td>
    </tr>
    <tr class="crm-contribution-form-block-weight"><td class="label">{$form.weight.label}</td><td class="html-adjust">{$form.weight.html}<br />
     <span class="description">{ts}Weight controls the order that premiums are displayed on the Contribution Page.{/ts}</span></td></tr>
  </table>
  {/if}

{if $action ne 4}
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
{else}
  <div class="crm-done-button">
      {include file="CRM/common/formButtons.tpl"}
  </div>
{/if} {* $action ne view *}
</div>

{* include jscript to warn if unsaved form field changes *}
{include file="CRM/common/formNavigate.tpl"}
<script language="JavaScript" type="text/javascript">
{literal}
function getFinancialType()
{
{/literal}
	 productID         = "#product_id";
	 financialTypeID    = "#financial_type_id"	 
	 callbackURL        = "{crmURL p='civicrm/ajax/rest' h=0 q='className=CRM_Financial_Page_AJAX&fnName=jqFinancialType'}"	
{literal}
            
    	    var check          = cj(productID).val();
	        callbackURL = callbackURL+"&_value="+check;
                cj.ajax({
                         url: callbackURL,
                         context: document.body,
                         success: function( data, textStatus ){
			 data = eval(data);//get json array
                              if ( data != null ) {
			       cj(financialTypeID).val(data);
			         
			     }
			    
			}
	       	});
		
	}

cj(document).ready(function(){ 
		getFinancialType(); 

		cj("#product_id").change( function(){
			   getFinancialType(); 
		});		    
});	
{/literal}
</script>