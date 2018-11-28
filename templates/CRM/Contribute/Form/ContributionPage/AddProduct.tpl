{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
{crmRegion name="contribute-form-contributionpage-addproduct-main"}
{capture assign=managePremiumsURL}{crmURL p='civicrm/admin/contribute/managePremiums' q="reset=1"}{/capture}
<h3>{if $action eq 2 }{ts}Add Products to This Page{/ts} {elseif $action eq 1024}{ts}Preview{/ts}{else} {ts}Remove Products from this Page{/ts}{/if}</h3>
<div class="crm-block crm-form-block crm-contribution-add_product-form-block">
  <div class="help">
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
        {$form.financial_type_id.html} {help id="id-financial_type-product"} <a name='resetfinancialtype' id="resetfinancialtype" style="display: none;">{ts}Reset to default for selected product{/ts}</a>
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

<script type="text/javascript">
{literal}

  CRM.$(function($) {

    function getFinancialType(set) {
      var callbackURL = CRM.url('civicrm/ajax/rest', {
        className: 'CRM_Financial_Page_AJAX',
        fnName: 'jqFinancialType',
        _value: $("#product_id").val()
      });
      $.ajax({
        url: callbackURL,
        success: function( data, textStatus ){
          data = eval(data);//get json array
          if ((data != null) && (set)) {
            $("#financial_type_id").val(data);
          }

          if (data == $("#financial_type_id").val()) {
            $("#resetfinancialtype").hide();
          }
          else {
            $("#resetfinancialtype").show();
          }
        }
      });
    }

    getFinancialType(false);
    $("#product_id").change(function() { getFinancialType(true); });
    $("#resetfinancialtype").click(function() { getFinancialType(true); });
    $("#financial_type_id").change(function() { getFinancialType(false); });
});
{/literal}
</script>
{/crmRegion}
{crmRegion name="contribute-form-contributionpage-addproduct-post"}
{/crmRegion}
