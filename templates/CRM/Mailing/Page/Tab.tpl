{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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

<div class="crm-mailing-selector">
  <table class="contact-mailing-selector crm-ajax-table">
    <thead>
      <tr>
        <th data-data="subject" class="crm-mailing-contact-subject">{ts}Subject{/ts}</th>
        <th data-data="creator_name" class="crm-mailing-contact_created">{ts}Added By{/ts}</th>
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
