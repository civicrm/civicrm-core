{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
    <input type="text" name="text" id='text' value="" class="crm-form-text" />
    <input type="hidden" name="qfKey" value="{crmKey name='CRM_Legacycustomsearches_Controller_Search' addSequence=1}" />
  </div>
  <select class="form-select" id="fulltext_table" name="fulltext_table">
{if $hasAllACLs}
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
    </select>
    {capture assign='helpTitle'}{ts}Full Text Search{/ts}{/capture}
    {help id="id-fullText" file="CRM/Contact/Form/Search/Custom/FullText.hlp" title=$helpTitle}
    <div class="crm-submit-buttons"><button type="submit" name="submit" id="fulltext_submit" class="crm-button crm-form-submit" onclick='submitForm();'>{ts}Search{/ts}</button></div>
    </form>
</div>
