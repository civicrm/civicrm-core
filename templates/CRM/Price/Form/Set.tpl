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
{* add/update price set *}
<div class="help">
    {ts}Use this form to edit the title and group-level help for a set of Price fields.{/ts}
</div>
{capture assign="enableComponents"}{crmURL p='civicrm/admin/setting/component' q="reset=1"}{/capture}
<div class="crm-form-block">
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>

    <table class="form-layout">
        <tr class="crm-price-set-form-block-title">
           <td class="label">{$form.title.label}</td>
           <td>
     {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_price_set' field='title' id=$sid}{/if}{$form.title.html}
           </td>
        </tr>
        <tr>
           <td>&nbsp;</td>
           <td class="description">{ts}The name of this Price Set{/ts}
           </td>
        <tr class="crm-price-set-form-block-extends">
           <td class="label">{$form.extends.label}</td>
           <td>
           {if $extends eq false}
              <div class="status message">{ts 1=$enableComponents}No Components have been enabled for your site that can be configured with the price sets. Click <a href='%1'>here</a> if you want to enable CiviEvent/CiviContribute for your site.{/ts}</div>
          {else}
              {$form.extends.html}
          {/if}
          </td>
        </tr>
        <tr id="min_amount" class="crm-price-set-form-block-min_amount">
           <td class="label">{$form.min_amount.label}</td>
           <td>{$form.min_amount.html}</td>
        </tr>
        <tr id="financial_type_id_row" class="crm-price-set-form-block-contribution_type_id crm-price-set-form-block-financial_type_id">
          <td class="label">{$form.financial_type_id.label}</td>
           <td>{$form.financial_type_id.html}</td>
           <td>&nbsp;</td>
        </tr>
        <tr class="crm-price-set-form-block-help_pre">
     <td class="label">{$form.help_pre.label}</td>
           <td>{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_price_set' field='help_pre' id=$sid}{/if}{$form.help_pre.html}
           </td>
           <td>&nbsp;</td>
        </tr>
        <tr>
          <td>&nbsp;</td>
          <td class="description">{ts}Explanatory text displayed at the beginning of this group of fields.{/ts}
          </td>
        </tr>
        <tr class="crm-price-set-form-block-help_post">
           <td class="label">{$form.help_post.label}</td>
           <td>{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_price_set' field='help_post' id=$sid}{/if}{$form.help_post.html}
           </td>
        </tr>
        <tr>
           <td>&nbsp;</td>
           <td class="description">{ts}Explanatory text displayed below this group of fields.{/ts}
           </td>
        </tr>
        <tr class="crm-price-set-form-block-is_active">
           <td class="label">{$form.is_active.label}</td>
           <td>{$form.is_active.html}</td>
        </tr>
     </table>
     <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>

</div>
{if $action eq 2 or $action eq 4} {* Update or View*}
    <p></p>
    <div class="action-link">
    <a href="{crmURL p='civicrm/admin/price/field' q="action=browse&reset=1&sid=$sid"}" class="button"><span>{ts}Fields for this Set{/ts}</span></a>
    </div>
{/if}

