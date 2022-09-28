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

  <p>
      {ts}If this is not your email address, there is no need to do anything. You have <strong>not</strong> been added to any mailing lists.{/ts}
      {ts}If this is your email address and you <strong>wish to opt out</strong> please click the <strong>Opt Out</strong> button to confirm.{/ts}
  </p>

  <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>

  <br/>
</div>

