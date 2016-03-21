{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
{* this template is used for adding/editing/deleting premium  *}
<div class="crm-block crm-form-block crm-contribution-manage_premiums-form-block">
   {if $action eq 8}
      <div class="messages status no-popup">
          <div class="icon inform-icon"></div>
          {ts}Are you sure you want to delete this premium?{/ts} {ts}This action cannot be undone.{/ts} {ts}This will also remove the premium from any contribution pages that currently include it.{/ts}
      </div>
  {elseif $action eq 1024}
     {include file="CRM/Contribute/Form/Contribution/PremiumBlock.tpl" context="previewPremium"}
  {else}
     <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
  {crmRegion name="contribute-form-managepremiums-standard-fields"}
  <table class="form-layout-compressed">
     <tr class="crm-contribution-form-block-name">
  <td class="label">{$form.name.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_product' field='name' id=$productId}{/if}
  </td>
  <td class="html-adjust">{$form.name.html}<br />
     <span class="description">{ts}Name of the premium (product, service, subscription, etc.) as it will be displayed to contributors.{/ts}</span>
  </td>
     </tr>
     <tr>
        <td class="label">{$form.description.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_product' field='description' id=$productId}{/if}
  </td>
  <td class="html-adjust">{$form.description.html}
  </td>
     <tr class="crm-contribution-form-block-sku">
        <td class="label">{$form.sku.label}
  </td>
  <td class="html-adjust">{$form.sku.html}<br />
     <span class="description">{ts}Optional product SKU or code. If used, this value will be included in contributor receipts.{/ts}</span>
  </td>
     </tr>
     <tr class="crm-contribution-form-block-imageOption" >
        <td class="label">{$form.imageOption.label}</td>
      <td>
      <fieldset><div class="description">
        <p>{ts}You can give this premium a picture that will be displayed on the contribution page. Both a 50 x 50 pixel thumbnail image and a 200 x 200 pixel larger image will be displayed. Images must be in GIF, JPEG, or PNG format.{/ts}</p>
        <p>{ts}You can upload an image from your computer OR enter a URL for an image already on the Web. If you chose to upload an image file, a 'thumbnail' version will be automatically created for you. If you don't have an image available at this time, you may also choose to display a 'No Image Available' icon - by selecting the 'default image'.{/ts}</p>
                  </div>
  <table class="form-layout-compressed">
    {if $thumbnailUrl}<tr class="odd-row"><td class="describe-image" colspan="2"><strong>Current Image Thumbnail</strong><br /><img src="{$thumbnailUrl}" /></td></tr>{/if}
    <tr class="crm-contribution-form-block-imageOption"><td>{$form.imageOption.image.html}</td><td>{$form.uploadFile.html}</td></tr>
  <tr class="crm-contribution-form-block-imageOption-thumbnail"><td colspan="2">{$form.imageOption.thumbnail.html}</td></tr>
    <tr id="imageURL"{if $action neq 2} class="hiddenElement"{/if}>
        <td class="label">{$form.imageUrl.label}</td><td>{$form.imageUrl.html|crmAddClass:huge}</td>
    </tr>
    <tr id="thumbnailURL"{if $action neq 2} class="hiddenElement"{/if}>
        <td class="label">{$form.thumbnailUrl.label}</td><td>{$form.thumbnailUrl.html|crmAddClass:huge}</td>
    </tr>
  <tr><td colspan="2">{$form.imageOption.default_image.html}</td></tr>
  <tr><td colspan="2">{$form.imageOption.noImage.html}</td></tr>
  </table>
        </fieldset>
        </td>
    </tr>
    <tr class="crm-contribution-form-block-min_contribution">
       <td class="label">{$form.min_contribution.label}</td>
       <td class="html-adjust">{$form.min_contribution.html|crmMoney}<br />
          <span class="description">{ts}The minimum contribution amount required to be eligible to select this premium. If you want to offer it to all contributors regardless of contribution amount, enter '0'. If display of minimum contribution amounts is enabled then this text is displayed:{/ts} <em>{ts}(Contribute at least X to be eligible for this gift.){/ts}</em></span>
       </td>
    </tr>
    <tr class="crm-contribution-form-block-price">
       <td class="label">{$form.price.label}</td>
       <td class="html-adjust">{$form.price.html|crmMoney}<br />
     <span class="description">{ts}The market value of this premium (e.g. retail price). For tax-deductible contributions, this amount will be used to set the non-deductible amount in the contribution record and receipt.{/ts}</span>
       </td>
    </tr>
    <tr class="crm-contribution-form-block-cost">
       <td class="label">{$form.cost.label}</td>
       <td class="html-adjust">{$form.cost.html|crmMoney}<br />
        <span class="description">{ts}You may optionally record the actual cost of this premium to your organization. This may be useful when evaluating net return for this incentive.{/ts}</span>
       </td>
    </tr>
     <tr class="crm-contribution-form-block-financial_type">
       <td class="label">{$form.financial_type_id.label}</td>
       <td class="html-adjust">
       {if !$financialType}
         {capture assign=ftUrl}{crmURL p='civicrm/admin/financial/financialType' q="reset=1"}{/capture}
         {ts 1=$ftUrl}There are no financial types configured with linked 'Cost of Sales Premiums' and 'Premiums Inventory Account' accounts. If you want to generate accounting transactions which track the cost of premiums used <a href='%1'>click here</a> to configure financial types and accounts.{/ts}
       {else}
         {$form.financial_type_id.html}<br />
   <span class="description">{ts}Select a financial type that is linked to both a 'Cost of Sales Premiums Account' and a 'Premiums Inventory Account' if you want to generate accounting transactions to track the cost of premiums used.{/ts}</span>
       {/if}
       </td>
    </tr>
    <tr class="crm-contribution-form-block-options">
       <td class="label">{$form.options.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_product' field='options' id=$productId}{/if}</td>
      <td class="html-adjust">{$form.options.html}<br />
          <span class="description">{ts}Enter a comma-delimited list of color, size, etc. options for the product if applicable. Contributors will be presented a drop-down menu of these options when they select this product.{/ts}</span>
       </td>
    </tr>
    <tr class="crm-contribution-form-block-is_active">
       <td class="label">{$form.is_active.label}</td>
       <td class="html-adjust">{$form.is_active.html}</td>
    </tr>
  </table>
  {/crmRegion}
  {crmRegion name="contribute-form-managepremiums-other-fields"}
  <fieldset id="time-delimited" class="crm-collapsible {if empty($showSubscriptions)}collapsed{/if}">
    <legend class="collapsible-title">{ts}Subscription or Service Settings{/ts}</legend>
    <div>
      <table class="form-layout-compressed">
        <tr class="crm-contribution-form-block-period_type">
           <td class="label">{$form.period_type.label}</td>
           <td class="html-adjust">{$form.period_type.html}<br />
              <span class="description">{ts}Select 'Rolling' if the subscription or service starts on the current day. Select 'Fixed' if the start date is a fixed month and day within the current year (set this value in the next field).{/ts}</span>
           </td>
        </tr>
        <tr class="crm-contribution-form-block-fixed_period_start_day">
           <td class="label">{$form.fixed_period_start_day.label}</td>
           <td class="html-adjust">{$form.fixed_period_start_day.html}<br />
              <span class="description">{ts}Month and day (MMDD) on which a fixed period subscription or service will start. EXAMPLE: A fixed period subscription with Start Day set to 0101 means that the subscription period would be 1/1/06 - 12/31/06 for anyone signing up during 2006.{/ts}</span>
           </td>
        </tr>
        <tr class="crm-contribution-form-block-duration_interval">
           <td class="label">{$form.duration_interval.label}</td>
           <td class="html-adjust">{$form.duration_interval.html} &nbsp; {$form.duration_unit.html}<br />
              <span class="description">{ts}Duration of subscription or service (e.g. 12-month subscription).{/ts}</span>
           </td>
        </tr>
        <tr class="crm-contribution-form-block-frequency_interval">
           <td class="label">{$form.frequency_interval.label}</td>
           <td class="html-adjust">{$form.frequency_interval.html} &nbsp; {$form.frequency_unit.html}<br />
              <span class="description">{ts}Frequency of subscription or service (e.g. journal delivered every two months).{/ts}</span>
        </td>
        </tr>
      </table>
    </div>
  </fieldset>
  {/crmRegion}
 {/if}
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
{if $action eq 1 or $action eq 2 }

<script type="text/javascript">
{literal}

function add_upload_file_block(parms) {
  if (parms =='thumbnail') {

          document.getElementById("imageURL").style.display="table-row";
        document.getElementById("thumbnailURL").style.display="table-row";

  } else {

        document.getElementById("imageURL").style.display="none";
        document.getElementById("thumbnailURL").style.display="none";

  }
}

{/literal}
</script>

{/if}
