{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}


<script>
{literal}

  if (typeof(cj) === 'undefined') cj = jQuery;
{/literal}
</script>

<div id="intro" class="crm-section">{$petition.instructions}</div>
<div class="crm-block crm-petition-form-block">

{if $duplicate == "confirmed"}
  <p>
  {ts}You have already signed this petition.{/ts}
  </p>
{/if}
{if $duplicate == "unconfirmed"}
  <p>{ts}You have already signed this petition but you still <b>need to verify your email address</b>.{/ts}</br> {ts}Please check your email inbox for the confirmation email. If you don't find it, verify if it isn't in your spam folder.{/ts}</p>
{/if}
{if $duplicate}
  <p>{ts}Thank you for your support.{/ts}</p>
  {if $is_share}
    {include file="CRM/Campaign/Page/Petition/SocialNetwork.tpl" petition_id=$survey_id petitionTitle=$petitionTitle}
  {/if}
{else}
  <div class="crm-section crm-petition-contact-profile">
    {include file="CRM/UF/Form/Block.tpl" fields=$petitionContactProfile hideFieldset=true}
  </div>

  <div class="crm-section crm-petition-activity-profile">
    {include file="CRM/UF/Form/Block.tpl" fields=$petitionActivityProfile hideFieldset=true}
  </div>

  {if $isCaptcha}
      {include file='CRM/common/ReCAPTCHA.tpl'}
  {/if}

  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
{/if}

</div>
