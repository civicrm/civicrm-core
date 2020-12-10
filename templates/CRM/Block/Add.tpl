{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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

<div class="form-item"><button type="submit" name="_qf_Contact_next" class="crm-button crm-form-submit">{ts}Save{/ts}</button></div>

</form>
</div>
