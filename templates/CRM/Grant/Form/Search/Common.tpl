{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
    {include file="CRM/Core/DatePickerRange.tpl" from='_low' to='_high'}
  </td>
  <td>
    &nbsp;{$form.$notSetFieldName.html}&nbsp;&nbsp;{$form.$notSetFieldName.label}
  </td>
</tr>
{/foreach}
{if $grantGroupTree}
<tr>
    <td colspan="3">
    {include file="CRM/Custom/Form/Search.tpl" groupTree=$grantGroupTree showHideLinks=false}</td>
</tr>
{/if}
