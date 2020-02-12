{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for viewing and editing Domain information (for system-generated emails CiviMail-related values) *}
<div class="crm-block crm-form-block crm-domain-form-block">
{if !($action eq 4)}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
{/if}
    <table class="form-layout-compressed">
  <tr>
        <td>
      {$form.name.label}{help id="domain-name"}<br />
      {$form.name.html}
      <br /><span class="description">{ts}The name of the organization or entity which owns this CiviCRM site or domain. This is the default organization contact record.{/ts}</span>
    </td>
  </tr>
  <tr>
    <td>
      {$form.description.label}<br />
      {$form.description.html}
      <br /><span class="description">{ts}Optional description of this domain (useful for sites with multiple domains).{/ts}</span>
        </td>
    </tr>
    </table>

    <h3>{ts}Default Organization Address{/ts}</h3>
        <div class="description">{ts 1=&#123;domain.address&#125;}CiviMail mailings must include the sending organization's address. This is done by putting the %1 token in either the body or footer of the mailing. This token may also be used in regular 'Email - send now' messages and in other Message Templates. The token is replaced by the address entered below when the message is sent.{/ts}</div>
        {include file="CRM/Contact/Form/Edit/Address.tpl"}
    <h3>{ts}Organization Contact Information{/ts}</h3>
        <div class="description">{ts}You can also include general email and/or phone contact information in mailings.{/ts} {help id="additional-contact"}</div>
        <table class="form-layout-compressed">
            {* Display the email block *}
            {include file="CRM/Contact/Form/Edit/Email.tpl"}

            {* Display the phone block *}
            {include file="CRM/Contact/Form/Edit/Phone.tpl"}
        </table>

    <div class="spacer"></div>

    {if ($action eq 4)}
    <div class="action-link">
    <a href="{crmURL q="action=update&reset=1"}" id="editDomainInfo">&raquo; {ts}Edit Domain Information{/ts}</a>
    </div>
    {/if}
{if !($action eq 4)}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
{/if}
</div>

{* phone_2 a email_2 only included in form if CiviMail enabled. *}
{if array_search('CiviMail', $config->enableComponents)}
    <script type="text/javascript">
    cj('a#addEmail,a#addPhone').hide();
    </script>
{/if}
