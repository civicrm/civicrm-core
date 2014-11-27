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
<script type="text/javascript">
    {literal}
    function submitForm( ) {
        var text  = document.getElementById('text').value;
        var table = document.getElementById('fulltext_table').value;
        var url = {/literal}'{crmURL p="civicrm/contact/search/custom" h=0 q="csid=`$fullTextSearchID`&reset=1&force=1&text="}'{literal} + text;
        if ( table ) {
            url = url + '&table=' + table;
        }
        document.getElementById('id_fulltext_search').action = url;
    }
    {/literal}
</script>

<div class="block-crm crm-container">
    <form method="post" id="id_fulltext_search">
    <div style="margin-bottom: 8px;">
    <input type="text" name="text" id='text' value="" style="width: 10em;" />&nbsp;<input type="submit" name="submit" id="fulltext_submit" value="{ts}Go{/ts}" class="crm-form-submit"/ onclick='submitForm();'>
    <input type="hidden" name="qfKey" value="{crmKey name='CRM_Contact_Controller_Search' addSequence=1}" />
  </div>
  <select class="form-select" id="fulltext_table" name="fulltext_table">
{if call_user_func(array('CRM_Core_Permission','giveMeAllACLs'))}
      <option value="">{ts}All{/ts}</option>
      <option value="Contact">{ts}Contacts{/ts}</option>
{/if}
      <option value="Activity">{ts}Activities{/ts}</option>
{if call_user_func(array('CRM_Core_Permission','access'), 'CiviCase')}
      <option value="Case">{ts}Cases{/ts}</option>
{/if}
{if call_user_func(array('CRM_Core_Permission','access'), 'CiviContribute')}
        <option value="Contribution">{ts}Contributions{/ts}</option>
{/if}
{if call_user_func(array('CRM_Core_Permission','access'), 'CiviEvent')}
        <option value="Participant">{ts}Participants{/ts}</option>
{/if}
{if call_user_func(array('CRM_Core_Permission','access'), 'CiviMember')}
        <option value="Membership">{ts}Memberships{/ts}</option>
{/if}
    </select> {help id="id-fullText" file="CRM/Contact/Form/Search/Custom/FullText.hlp"}
    </form>
</div>
