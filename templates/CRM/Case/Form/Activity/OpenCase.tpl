{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
{if $context ne 'caseActivity'}
  <tr class="crm-case-opencase-form-block-case_type_id">
    <td class="label">{$form.case_type_id.label}{help id="id-case_type" file="CRM/Case/Form/Case.hlp" activityTypeFile=$activityTypeFile}</td>
    <td>{$form.case_type_id.html}</td>
  </tr>
  <tr class="crm-case-opencase-form-block-status_id">
    <td class="label">{$form.status_id.label}</td>
    <td>{$form.status_id.html}</td>
  </tr>
  <tr class="crm-case-opencase-form-block-start_date">
    <td class="label">{$form.start_date.label}</td>
    <td>
    {include file="CRM/common/jcalendar.tpl" elementName=start_date}
    </td>
  </tr>

{* Add fields for attachments *}
  {if $action eq 4 AND $currentAttachmentURL}
    {include file="CRM/Form/attachment.tpl"}{* For view action the include provides the row and cells. *}
  {elseif $action eq 1 OR $action eq 2}
    <tr class="crm-activity-form-block-attachment">
      <td colspan="2">
      {include file="CRM/Form/attachment.tpl"}
      </td>
    </tr>
  {/if}
{/if}
