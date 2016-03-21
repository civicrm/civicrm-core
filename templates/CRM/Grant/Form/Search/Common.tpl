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
<tr>
    <td>
        {$form.grant_report_received.label}<br />
        {$form.grant_report_received.html}
    </td>
    <td>
        <label>{ts}Grant Status(s){/ts}</label>
        {$form.grant_status_id.html}
    </td>
    <td>
        <label>{ts}Grant Type(s){/ts}</label>
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
<tr>
    <td>
        {$form.grant_application_received_date_low.label}<br />
        {include file="CRM/common/jcalendar.tpl" elementName=grant_application_received_date_low}
    </td>
    <td colspan="2">
        {$form.grant_application_received_date_high.label}<br />
        {include file="CRM/common/jcalendar.tpl" elementName=grant_application_received_date_high}
        &nbsp;{$form.grant_application_received_notset.html}&nbsp;&nbsp;{ts}Date is not set{/ts}
    </td>
</tr>
<tr>
    <td>
        {$form.grant_decision_date_low.label}<br />
        {include file="CRM/common/jcalendar.tpl" elementName=grant_decision_date_low}
    </td>
    <td colspan="2">
        {$form.grant_decision_date_high.label}<br />
        {include file="CRM/common/jcalendar.tpl" elementName=grant_decision_date_high}
        &nbsp;{$form.grant_decision_date_notset.html}&nbsp;&nbsp;{ts}Date is not set{/ts}
    </td>
</tr>
<tr>
    <td>
        {$form.grant_money_transfer_date_low.label}<br />
        {include file="CRM/common/jcalendar.tpl" elementName=grant_money_transfer_date_low}
    </td>
    <td colspan="2">
        {$form.grant_money_transfer_date_high.label}<br />
        {include file="CRM/common/jcalendar.tpl" elementName=grant_money_transfer_date_high}
        &nbsp;{$form.grant_money_transfer_date_notset.html}&nbsp;&nbsp;{ts}Date is not set{/ts}
    </td>
</tr>
<tr>
    <td>
        {$form.grant_due_date_low.label}<br />
        {include file="CRM/common/jcalendar.tpl" elementName=grant_due_date_low}
    </td>
    <td colspan="2">
        {$form.grant_due_date_high.label}<br />
        {include file="CRM/common/jcalendar.tpl" elementName=grant_due_date_high}
        &nbsp;{$form.grant_due_date_notset.html}&nbsp;&nbsp;{ts}Date is not set{/ts}
    </td>
</tr>
{if $grantGroupTree}
<tr>
    <td colspan="3">
    {include file="CRM/Custom/Form/Search.tpl" groupTree=$grantGroupTree showHideLinks=false}</td>
</tr>
{/if}
