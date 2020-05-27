{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

<div class="crm-mailing-selector">
  <table class="contact-mailing-selector crm-ajax-table">
    <thead>
      <tr>
        <th data-data="subject" class="crm-mailing-contact-subject">{ts}Subject{/ts}</th>
        <th data-data="creator_name" class="crm-mailing-contact_created">{ts}Added by{/ts}</th>
        <th data-data="recipients" data-orderable="false" class="crm-contact-activity_contact">{ts}Recipients{/ts}</th>
        <th data-data="start_date" class="crm-mailing-contact-date">{ts}Date{/ts}</th>
        <th data-data="openstats" data-orderable="false" class="crm-mailing_openstats">{ts}Opens/ Clicks{/ts}</th>
        <th data-data="links" data-orderable="false" class="crm-mailing-contact-links">&nbsp;</th>
      </tr>
    </thead>
  </table>
  {literal}
    <script type="text/javascript">
      (function($) {
        CRM.$('table.contact-mailing-selector').data({
          "ajax": {
            "url": {/literal}'{crmURL p="civicrm/ajax/contactmailing" h=0 q="contact_id=$contactId"}'{literal}
          }
        });
      })(CRM.$);
    </script>
  {/literal}
</div>
