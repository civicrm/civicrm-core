{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

<div class="crm-block crm-form-block crm-miscellaneous-form-block">

    <p>{ts}You are requesting to opt out this email address from all mailing lists:{/ts}</p>
    <h3>{$email_masked}</h3>

    <p>{ts}If this is not your email address, there is no need to do anything. You have <i><b>not</b></i> been added to any mailing lists. If this is your email address and you <i><b>wish to opt out</b></i> please enter your email address below for verification purposes:{/ts}</p>

    <table class="form-layout">
      <tbody>
      <tr>
        <td class="label">{$form.email_confirm.label}</td>
        <td class="content">{$form.email_confirm.html}
      </tr>
      </tbody>
    </table>

  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>

<br/>
</div>

