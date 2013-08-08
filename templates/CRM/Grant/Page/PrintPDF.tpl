{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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
{if $values}
<table class="pdf-grant">
<tr>
<td><b>Contact Name</b></td>
<td>{$values.display_name}</td>
</tr>
<tr>
<td><b>Grant Application Received Date</b></td>
<td>{$values.app_rec_date}</td>
</tr>
<tr>
<td><b>Grant Decision Date</b></td>
<td>{$values.dec_date}</td>
</tr>
<tr>
<td><b>Grant Money Transferred Date</b></td>
<td>{$values.money_trxn_date}</td>
</tr>
<tr>
<td><b>Grant Due Date</b></td>
<td>{$values.due_date}</td>
</tr>
<tr>
<td><b>Total Amount</b></td>
<td>{$values.amount_total|crmMoney}</td>
</tr>
<tr>
<td><b>Amount Requested</b></td>
<td>{$values.amount_requested|crmMoney}</td>
</tr>
<tr>
<td><b>Amount Granted</b></td>
<td>{$values.amount_granted|crmMoney}</td>
</tr>
<tr>
<td><b>Rationale</b></td>
<td>{$values.rationale}</td>
</tr>
<tr>
<td><b>Notes</b></td>
<td>{$values.noteId}</td>
</tr>
{/if}