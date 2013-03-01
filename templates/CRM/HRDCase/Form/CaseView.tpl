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
{* this template is used for adding/editing/deleting case *}
<div class="form-item">
<fieldset>
    <legend>{ts}View Case Record Details{/ts}</legend>
      <dl class="html-adjust">
            <dt class="font-size12pt">{ts}Name{/ts}</dt><dd class="font-size12pt"><strong>{$contactNames}</strong>&nbsp;</dd>
            <dt>{$form.subject.label}</dt><dd>{$form.subject.html}</dd>
            <dt>{$form.status_id.label}</dt><dd>{$form.status_id.html}</dd>
            <dt>{$form.case_type_id.label}</dt><dd>{$form.case_type_id.html}</dd>
            <dt>{$form.start_date.label}</dt><dd>{$form.start_date.html}</dd>
            <dt>{$form.end_date.label}</dt><dd>{$form.end_date.html}</dd>
            <dt>{$form.details.label}</dt><dd>{$form.details.html}</dd>
            <dt></dt><dd>{$form.buttons.html}
              </dd>
      </dl>
      <div class="spacer"> </div>
      <dl><dd>{include file="CRM/Activity/Selector/Activity.tpl" caseview=1}</dd></dl>
</fieldset>
</div>
