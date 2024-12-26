{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<tr>
    <td>
        {$form.grant_report_received.label}<br />
        {$form.grant_report_received.html}
    </td>
    <td>
        <label>{ts}Grant Status(s){/ts}</label>
        <br>
        {$form.grant_status_id.html}
    </td>
    <td>
        <label>{ts}Grant Type(s){/ts}</label>
        <br>
        {$form.grant_type_id.html}
    </td>
</tr>
<tr>
    <td>
        {$form.grant_amount_low.label}<br />
        {$form.grant_amount_low.html}
    </td>
    <td colspan="2">
        {$form.grant_amount_high.label}<br />
        {$form.grant_amount_high.html}
    </td>
</tr>
{foreach from=$grantSearchFields key=fieldName item=fieldSpec}
  {assign var=notSetFieldName value=$fieldName|cat:'_notset'}
<tr>
  <td>
    {include file="CRM/Core/DatePickerRange.tpl" from='_low' to='_high' hideRelativeLabel=0}
  </td>
  <td>
    &nbsp;{$form.$notSetFieldName.html}&nbsp;&nbsp;{$form.$notSetFieldName.label}
  </td>
</tr>
{/foreach}
{if !empty($grantGroupTree)}
<tr>
    <td colspan="3">
    {include file="CRM/Custom/Form/Search.tpl" groupTree=$grantGroupTree showHideLinks=false}</td>
</tr>
{/if}
