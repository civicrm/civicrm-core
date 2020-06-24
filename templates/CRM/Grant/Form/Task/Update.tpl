{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Update Grants Via Search Actions *}
<div class="crm-block crm-form-block crm-grants-update-form-block">
    <p>{ts}Enter values for the fields you wish to update. Leave fields blank to preserve existing values.{/ts}</p>
    <table class="form-layout-compressed">
        {* Loop through all defined search criteria fields (defined in the buildForm() function). *}
        {foreach from=$elements item=element}
            <tr class="crm-contact-custom-search-form-row-{$element}">
                <td class="label">{$form.$element.label}</td>
                <td>{$form.$element.html}</td>
            </tr>
        {/foreach}
    </table>
    <p>{ts 1=$totalSelectedGrants}Number of selected grants: %1{/ts}</p>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
