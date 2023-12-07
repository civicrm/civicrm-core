{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for adding/editing Premium Information *}
<div id="id-premium" class="section-shown crm-contribution-additionalinfo-premium-form-block">
  <table class="form-layout-compressed">
    <tr class="crm-contribution-form-block-product_name">
      <td class="label">{$form.product_name.label}</td>
      <td class="html-adjust">{$form.product_name.html|smarty:nodefaults}</td>
    </tr>
  </table>

  <div id="premium_contri">
    <table class="form-layout-compressed">
      <tr class="crm-contribution-form-block-min_amount">
        <td class="label">{$form.min_amount.label}</td>
        <td class="html-adjust">{$form.min_amount.html}</td>
      </tr>
    </table>
    <div class="spacer"></div>
  </div>
  <table class="form-layout-compressed">
    <tr class="crm-contribution-form-block-fulfilled_date">
      <td class="label">{$form.fulfilled_date.label}</td>
      <td class="html-adjust">{$form.fulfilled_date.html}</td>
    </tr>
  </table>
</div>

{literal}
  <script type="text/javascript">
    var min_amount = document.getElementById("min_amount");
      min_amount.readOnly = 1;
      function showMinContrib( ) {
        var product = document.getElementsByName("product_name[0]")[0];
        var product_id = product.options[product.selectedIndex].value;
        var min_amount = document.getElementById("min_amount");
        var amount = [];
        amount[0] = '';
        if (product_id > 0) {
          cj('#premium_contri').show();
        } else {
          cj('#premium_contri').hide();
        }
{/literal}
       var index = 1;
       {foreach from=$mincontribution item=amt key=id}
         {literal}amount[index]{/literal} = "{$amt}"
         {literal}index = index + 1{/literal}
       {/foreach}
{literal}
       if (amount[product_id]) {
         min_amount.value = amount[product_id];
       } else {
         min_amount.value = "";
      }
    }
  </script>
{/literal}
{if $action eq 1 or $action eq 2 or $action eq null}
   <script type="text/javascript">
    showMinContrib();
  </script>
{/if}
{if $action ne 2 or $showOption eq true}
  {$initHideBoxes|smarty:nodefaults}
{/if}
