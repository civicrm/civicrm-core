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
<div class="batch-entry form-item">
  <div id="help">
    {ts}Click Validate & Process below when you've entered all items for the batch. You can also Save & Continue Later at any time. Go to Administer > Customize Data and Screens > Profiles > Reserved Profiles > to add, remove or change the order of columns.{/ts}
  </div>
  {if $batchAmountMismatch}
    <div class="status message status-warning">
      <div
        class="icon alert-icon"></div> {ts}Total for amounts entered below does not match the expected batch total.{/ts}
    </div>
    <div class="crm-button crm-button_qf_Entry_upload_force-save">
      {$form._qf_Entry_upload_force.html}
    </div>
    <div class="clear"></div>
  {/if}
  <table class="form-layout-compressed batch-totals">
    <tr>
      <td class="label">{ts}Total amount expected{/ts}</td>
      <td class="right"><span class="batch-expected-total">{$batchTotal|crmMoney}</span></td>
    </tr>
    <tr>
      <td class="label">{ts}Total amount entered{/ts}</td>
      <td class="right">{$config->defaultCurrencySymbol} <span class="batch-actual-total"></span></td>
    </tr>
  </table>

  <div class="crm-copy-fields crm-grid-table" id="crm-batch-entry-table">
    <div class="crm-grid-header">
      <div class="crm-grid-cell">&nbsp;</div>
      <div class="crm-grid-cell">{ts}Contact{/ts}</div>
      {if $batchType eq 2 }
        <div class="crm-grid-cell">&nbsp;</div>
      {/if}
      {foreach from=$fields item=field key=fieldName}
        <div class="crm-grid-cell">
          <img src="{$config->resourceBase}i/copy.png"
               alt="{ts 1=$field.title}Click to copy %1 from row one to all rows.{/ts}"
               fname="{$field.name}" class="action-icon"
               title="{ts}Click here to copy the value in row one to ALL rows.{/ts}"/>{$field.title}
        </div>
      {/foreach}
    </div>

    {section name='i' start=1 loop=$rowCount}
      {assign var='rowNumber' value=$smarty.section.i.index}
      <div class="{cycle values="odd-row,even-row"} selector-rows crm-grid-row" entity_id="{$rowNumber}">
        <div class="compressed crm-grid-cell"><span class="batch-edit"></span></div>
        {* contact select/create option*}
        <div class="compressed crm-grid-cell">
          {include file="CRM/Contact/Form/NewContact.tpl" blockNo = $rowNumber noLabel=true prefix="primary_" newContactCallback="updateContactInfo($rowNumber, 'primary_')"}
        </div>

        {if $batchType eq 2 }
          {$form.member_option.$rowNumber.html}
        {/if}

        {foreach from=$fields item=field key=fieldName}
          {assign var=n value=$field.name}
          {if ( $fields.$n.data_type eq 'Date') or ( in_array( $n, array( 'thankyou_date', 'cancel_date', 'receipt_date', 'receive_date', 'join_date', 'membership_start_date', 'membership_end_date' ) ) ) }
            <div class="compressed crm-grid-cell">
              <span class="crm-batch-{$n}-{$rowNumber}">
                {include file="CRM/common/jcalendar.tpl" elementName=$n elementIndex=$rowNumber batchUpdate=1}
              </span>
            </div>
          {elseif $n eq 'soft_credit'}
            <div class="compressed crm-grid-cell">
              {include file="CRM/Contact/Form/NewContact.tpl" blockNo = $rowNumber noLabel=true prefix="soft_credit_"}
            </div>
          {elseif in_array( $fields.$n.html_type, array('Radio', 'CheckBox'))}
            <div class="compressed crm-grid-cell">&nbsp;{$form.field.$rowNumber.$n.html}</div>
          {else}
            <div class="compressed crm-grid-cell">{$form.field.$rowNumber.$n.html}</div>
          {/if}
        {/foreach}
      </div>
    {/section}
  </div>
  <div class="crm-submit-buttons">{if $fields}{$form._qf_Batch_refresh.html}{/if} &nbsp; {$form.buttons.html}</div>
</div>

{*include batch copy js js file*}
{include file="CRM/common/batchCopy.tpl"}
