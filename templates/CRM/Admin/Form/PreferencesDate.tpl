{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
{* this template is used for adding/editing location type  *}
<div class="crm-block crm-form-block crm-preferences-date-form-block">
    <fieldset><legend>{ts}Edit Date Settings{/ts}</legend>
        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
        <table class='form-layout-compressed'>
            <tr class="crm-preferences-date-form-block-name">
                <td class="label">{$form.name.label}</td><td>{$form.name.html}</td>
            </tr>
            <tr class="crm-preferences-date-form-block-description">
                <td class="label">{$form.description.label}</td><td>{$form.description.html}</td>
            </tr>
            <tr class="crm-preferences-date-form-block-date_format">
                <td class="label">{$form.date_format.label}</td><td>{$form.date_format.html}</td>
            </tr>
            {if $form.time_format.label}
            <tr class="crm-preferences-date-form-block-time_format">
                <td class="label">{$form.time_format.label}</td><td>{$form.time_format.html}</td>
            </tr>
            {/if}
            <tr class="crm-preferences-date-form-block-start">
                <td class="label">{$form.start.label}</td><td>{$form.start.html}</td>
            </tr>
            <tr class="crm-preferences-date-form-block-end">
                <td class="label">{$form.end.label}</td><td>{$form.end.html}</td>
            </tr>
        </table>
        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
    </fieldset>
</div>
