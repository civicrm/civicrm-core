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
  <h3>{ts}System-generated Mail Settings{/ts}</h3>
    <table class="form-layout-compressed">
    <tr>
      <td>
        {$form.email_name.label} {help id="from-name"}<br />
        {$form.email_name.html}
      </td>
      <td class="">
        {$form.email_address.label} {help id="from-email"}<br />
        {$form.email_address.html}
           <br /><span class="description">(info@example.org)</span>
      </td>
    </tr>
    </table>

    <h3>{ts}Default Organization Address{/ts}</h3>
        <div class="description">{ts 1=&#123;domain.address&#125;}CiviMail mailings must include the sending organization's address. This is done by putting the %1 token in either the body or footer of the mailing. This token may also be used in regular 'Send Email to Contacts' messages and in other Message Templates. The token is replaced by the address entered below when the message is sent.{/ts}</div>
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
