{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for editing Date Preferences *}
<div class="crm-block crm-form-block crm-preferences-date-form-block">
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
            {if !empty($form.time_format.label)}
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
</div>
