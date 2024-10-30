{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-useradd-form-block">
  <table class="form-layout-compressed">
    <tr>
      <td class="label">{$form.name.label}</td><td>{$form.name.html}</td>
    </tr>
    <tr>
      <td class="label">{$form.cms_name.label}</td>
      <td>{$form.cms_name.html} <a id="checkavailability" href="#" onClick="return false;">{ts}<strong>Check Availability</strong>{/ts}</a> <span id="msgbox" style="display:none"></span><br />
        <span class="description">{ts}Select a username; punctuation is not allowed except for periods, hyphens, and underscores.{/ts}</span>
      </td>
    </tr>
    <tr>
      <td class="label">{$form.cms_pass.label}</td><td>{$form.cms_pass.html}</td>
    </tr>
    <tr>
      <td class="label">{$form.cms_confirm_pass.label}</td><td>{$form.cms_confirm_pass.html}</td>
    </tr>
    <tr>
      <td class="label">{$form.email.label}</td><td>{$form.email.html}</td>
    </tr>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
<script type="text/javascript">
{include file="CRM/common/checkUsernameAvailable.tpl"}
</script>
