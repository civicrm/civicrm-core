{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Enduser Tell-a-Friend form. *}
{if $status eq 'thankyou'} {* Form has been submitted. *}
    <div class="crm-section tell_friend_thankyou-section">
        {$thankYouText}
    </div>

    {* Add button for donor to create their own Personal Campaign page *}
    {if $linkText}
   <div class="crm-section create_pcp_link-section">
        <a href="{$linkTextUrl}" title="{$linkText|escape}" class="button"><span><i class="crm-i fa-chevron-right" role="img" aria-hidden="true"></i> {$linkText}</span></a>
    </div><br /><br />
    {/if}

{else}
<div class="crm-group tell_friend_form-group">
<table class="form-layout-compressed">
  <tr>
    <td colspan=2>
            <p>
                {if $context EQ 'pcp'}
                    {ts 1=$pcpTitle}Spread the word about this fundraising page (%1). Add your personal message below. A link to the fundraising page will be automatically included in the email.{/ts}
                {else}
                    {$intro}
                {/if}
            </p>
    </td>
  </tr>

  <tr>
    <td class="right">{$form.from_name.label}&nbsp;&nbsp;</td>
    <td>{$form.from_name.html} &lt;{$form.from_email.html}&gt;</td>
  </tr>
  <tr>
    <td class="label">{$form.suggested_message.label}</td>
    <td>{$form.suggested_message.html}</td>
  </tr>

  <tr>
    <td></td>
    <td>
    <fieldset class="crm-group tell_friend_emails-group">
        <legend>{ts}Send to these Friend(s){/ts}</legend>
        <table>
          <tr class="columnheader">
            <td>{ts}First Name{/ts}</td>
            <td>{ts}Last Name{/ts}</td>
            <td>{ts}Email Address{/ts}</td>
          </tr>
          {section name=mail start=1 loop=$mailLimit}
          {assign var=idx  value=$smarty.section.mail.index}
          <tr>
            <td class="even-row">{$form.friend.$idx.first_name.html}</td>
            <td class="even-row">{$form.friend.$idx.last_name.html}</td>
            <td class="even-row">{$form.friend.$idx.email.html}</td>
          </tr>
          {/section}
        </table>
    </fieldset>
    </td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td>{$form.buttons.html}</td>
  </tr>
</table>
</div>
{/if}

{if $isShare}
  {if $context EQ 'event'}
    {capture assign=pageURL}{crmURL p='civicrm/event/info' q="id=`$entityID`&amp;reset=1" a=1 fe=1 h=1}{/capture}
  {else}
    {capture assign=pageURL}{crmURL p='civicrm/contribute/transact' q="reset=1&amp;id=`$entityID`" a=1 fe=1 h=1}{/capture}
  {/if}
  {include file="CRM/common/SocialNetwork.tpl" url=$pageURL title=$title pageURL=$pageURL emailMode=false}
{/if}
