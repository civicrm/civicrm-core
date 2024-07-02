{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-recurcontrib-form-block">
  {if $changeHelpText}
    <div class="help">
      {$changeHelpText}
      {if $membershipID}
        <br/><strong> {ts}WARNING: This recurring contribution is linked to membership:{/ts}
        <a class="crm-hover-button" href='{crmURL p="civicrm/contact/view/membership" q="action=view&reset=1&cid=`$contactId`&id=`$membershipID`&context=membership&selectedChild=member"}'>{$membershipName|escape}</a>
        </strong>
      {/if}
    </div>
  {/if}
  {if $form.amount.frozen}
  <div class="help">
    {icon icon="fa-info-circle"}{/icon}
    {ts}To change the amount you need to edit the template contribution. Click on "View Template" and then "Edit" from the list of recurring contributions{/ts}
  </div>
  {/if}
  <table class="form-layout">
    <tr>
      <td class="label">{$form.amount.label}</td>
      <td>{$form.currency.html|crmAddClass:eight}&nbsp;{$form.amount.html|crmAddClass:eight} ({ts}every{/ts} {$recur_frequency_interval|escape} {$recur_frequency_unit|escape})</td>
    </tr>
    {if array_key_exists('installments', $form)}
      <tr>
        <td class="label">{$form.installments.label}</td>
        <td>{$form.installments.html}<br />
          <span class="description">{ts}Total number of payments to be made. Set this to 0 if this is an open-ended commitment i.e. no set end date.{/ts}</span>
        </td>
      </tr>
    {/if}
    {foreach from=$editableScheduleFields item='field'}
      <tr><td class="label">{$form.$field.label}</td><td>{$form.$field.html}</td></tr>
    {/foreach}
    {if !$self_service}
    <tr><td class="label">{$form.is_notify.label}</td><td>{$form.is_notify.html}</td></tr>
    <tr><td class="label">{$form.campaign_id.label}</td><td>{$form.campaign_id.html}</td></tr>
    {if array_key_exists('financial_type_id', $form)}<tr><td class="label">{$form.financial_type_id.label}</td><td>{$form.financial_type_id.html}</td></tr>{/if}
    {/if}
  </table>

  {if !$self_service}
    {include file="CRM/common/customDataBlock.tpl" groupID='' customDataType='ContributionRecur' customDataSubType=false entityID=$contributionRecurID cid=false}
  {/if}

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
