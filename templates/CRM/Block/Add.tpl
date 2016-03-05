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
<div id="crm-quick-create" class="crm-container">
<form action="{$postURL}" method="post">

<div class="form-item">
    <div>
        <label for="qa_first_name">{ts}First Name:{/ts}</label>
    </div>
    <div>
        <input type="text" name="first_name" id="qa_first_name" class="form-text" maxlength="64" />
    </div>
</div>

<div class="form-item">
    <div>
        <label for="qa_last_name">{ts}Last Name:{/ts}</label>
    </div>
    <div>
        <input type="text" name="last_name" id="qa_last_name" class="form-text required" maxlength="64" />
    </div>
</div>

<div class="form-item">
    <div>
        <label for="qa_email">{ts}Email:{/ts}</label>
    </div>
    <div>
        <input type="email" name="email[1][email]" id="qa_email" class="form-text" maxlength="64" />
    </div>

    <input type="hidden" name="email[1][location_type_id]" value="{$primaryLocationType}" />
    <input type="hidden" name="email[1][is_primary]" value="1" />
    <input type="hidden" name="ct" value="Individual" />
    <input type="hidden" name="email_greeting_id" value="{$email_greeting_id}" />
    <input type="hidden" name="postal_greeting_id" value="{$postal_greeting_id}" />
    <input type="hidden" name="addressee_id" value="{$addressee_id}" />
    <input type="hidden" name="qfKey" value="{crmKey name='CRM_Contact_Form_Contact' addSequence=1}" />
</div>

<div class="form-item"><input type="submit" name="_qf_Contact_next" value="{ts}Save{/ts}" class="crm-form-submit" /></div>

</form>
</div>
