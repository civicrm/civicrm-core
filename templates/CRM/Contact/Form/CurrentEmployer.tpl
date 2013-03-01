{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
{* tpl for current employer js *}
{literal}
<script type="text/javascript">
  var dataUrl        = "{/literal}{$employerDataURL}{literal}";
  var newContactText = "{/literal}({ts}new contact record{/ts}){literal}";
  cj('#current_employer').attr("title","Current employer auto complete");
  cj('#current_employer').autocomplete( dataUrl, { 
    width        : 250, 
    selectFirst  : false,
    matchCase    : true, 
    matchContains: true
  }).result( function(event, data, formatted) {
    var foundContact   = ( parseInt( data[1] ) ) ? cj( "#current_employer_id" ).val( data[1] ) : cj( "#current_employer_id" ).val('');
    if ( ! foundContact.val() ) {
      cj('div#employer_address').html(newContactText).show();    
    } 
    else {
      cj('div#employer_address').html('').hide();    
    }
  }).bind('change blur', function() {
    if ( !cj( "#current_employer_id" ).val( ) ) {
      cj('div#employer_address').html(newContactText).show();    
    }
  });

  // remove current employer id when current employer removed.
  cj("form").submit(function() {
    if ( !cj('#current_employer').val() ) cj( "#current_employer_id" ).val('');
  });

  //current employer default setting
  {/literal}
  var cid        = "{$contactId}";
  var employerId = "{$currentEmployer}";
  {literal}
  if ( employerId ) {
    var dataUrl = "{/literal}{crmURL p='civicrm/ajax/rest' h=0 q="className=CRM_Contact_Page_AJAX&fnName=getContactList&json=1&context=contact&org=1&id=" }{literal}" + employerId + "&employee_id=" + cid ;
    
    cj.ajax({ 
      url     : dataUrl,   
      async   : false,
      success : function(html){
        //fixme for showing address in div
        htmlText = html.split( '|' , 2);
        cj('input#current_employer').val(htmlText[0]);
        cj('input#current_employer_id').val(htmlText[1]);
      }
    }); 
  }

  cj("input#current_employer").click( function( ) {
    cj("input#current_employer_id").val('');
  });
</script>
{/literal}
