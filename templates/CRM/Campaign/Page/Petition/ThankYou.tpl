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

{if $status_id eq 2} {* Signer needs to confirm signature. *}
  <h2>{ts}STEP 2: Please Check Your Email{/ts}</h2>
  <p>{ts}To complete and confirm your signature, please follow the activation instructions sent to the email address you provided.{/ts}</p>
  <p>

    <strong>{ts}IMPORTANT{/ts}</strong>: {ts}Before we can add your signature, you must validate your email address by clicking on the activation link in the confirmation e-mail. Sometimes our confirmation emails get flagged as spam and are moved to your bulk folder.{/ts}
    <br/>
    {ts}If you haven't received an email within a few minutes, please check your spam folder.{/ts}
  </p>
{/if}

{if $status_id eq 4}
  <p>{ts}You have already signed this petition but we<strong>need to confirm your email address</strong>.{/ts}</p>
  <b>{ts}IMPORTANT{/ts}</b>
  : {ts}Before we can add your signature, you must validate your email address by clicking on the activation link in the confirmation e-mail. Sometimes our confirmation emails get flagged as spam and are moved to your spam folder.{/ts}
  <br/>
  {ts}If you haven't received an email from us, check your spam folder, it might have been wrongly classified.{/ts}
  <br/>
{/if}
{if $status_id eq 5}
  <p>{ts}You have already signed this petition.{/ts}</p>
{/if}

{if $status_id neq 2}{* if asked to confirm the email, focus on that and don't put additional messages *}
  {if $thankyou_text}
    <div id="thankyou_text" class="crm-section thankyou_text-section">
      {$thankyou_text}
    </div>
  {/if}
  {if $is_share}
    {include file="CRM/Campaign/Page/Petition/SocialNetwork.tpl" petition_id=$survey_id petitionTitle=$petitionTitle}
  {/if}
{/if}

