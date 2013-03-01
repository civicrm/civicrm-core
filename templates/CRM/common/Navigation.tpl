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
<div id="menu-container" style="display:none;">
    <ul id="civicrm-menu">
        {if call_user_func(array('CRM_Core_Permission','giveMeAllACLs'))}
        <li id="crm-qsearch" class="menumain crm-link-home">
            <form action="{crmURL p='civicrm/contact/search/basic' h=0 }" name="search_block" id="id_search_block" method="post" onsubmit="getSearchURLValue( );">
              <div id="quickSearch">
                <input type="text" class="form-text" id="sort_name_navigation" placeholder="{ts}Find Contacts{/ts}" name="sort_name" style="width: 12em;" />
                <input type="hidden" id="sort_contact_id" value="" />
                <input type="hidden" name="qfKey" value="{crmKey name='CRM_Contact_Controller_Search' addSequence=1}" />
                <input type="submit" value="{ts}Go{/ts}" name="_qf_Basic_refresh" class="form-submit default" style="display: none;" />
              </div>
            </form>
          <ul>
            <li><label class="crm-quickSearchField"><input type="radio" checked="" data-tablename="cc" checked="checked" value="" name="quickSearchField">{ts}Name/Email{/ts}</label></li>
            <li><label class="crm-quickSearchField"><input type="radio" data-tablename="cc" value="id" name="quickSearchField">{ts}CiviCRM ID{/ts}</label></li>
            <li><label class="crm-quickSearchField"><input type="radio" data-tablename="cc" value="external_identifier" name="quickSearchField">{ts}External ID{/ts}</label></li>
            <li><label class="crm-quickSearchField"><input type="radio" data-tablename="cc" value="first_name" name="quickSearchField">{ts}First Name{/ts}</label></li>
            <li><label class="crm-quickSearchField"><input type="radio" data-tablename="cc" value="last_name" name="quickSearchField">{ts}Last Name{/ts}</label></li>
            <li><label class="crm-quickSearchField"><input type="radio" data-tablename="eml" value="email" name="quickSearchField">{ts}Email{/ts}</label></li>
            <li><label class="crm-quickSearchField"><input type="radio" data-tablename="phe" value="phone_numeric" name="quickSearchField">{ts}Phone{/ts}</label></li>
            <li><label class="crm-quickSearchField"><input type="radio" data-tablename="sts" value="street_address" name="quickSearchField">{ts}Street Address{/ts}</label></li>
            <li><label class="crm-quickSearchField"><input type="radio" data-tablename="sts" value="city" name="quickSearchField">{ts}City{/ts}</label></li>
            <li><label class="crm-quickSearchField"><input type="radio" data-tablename="sts" value="postal_code" name="quickSearchField">{ts}Postal Code{/ts}</label></li>
            <li><label class="crm-quickSearchField"><input type="radio" data-tablename="cc" value="job_title" name="quickSearchField">{ts}Job Title{/ts}</label></li>
          </ul>
        </li>

  {/if}
        {$navigation}
    </ul>
</div>

{literal}
<script type="text/javascript">
cj( document ).ready( function( ) {
  //CRM-6776, enter-to-submit functionality is broken for IE due to hidden field
  cj("#civicrm-menu >li").each(function(i){
    cj(this).attr("tabIndex",i+2);
  });
  var htmlContent = '';
  if ( cj.browser.msie ) {
    if( cj.browser.version.substr( 0,1 ) == '7' ) {
      htmlContent = '<input type="submit" value="Go" name="_qf_Basic_refresh" class="form-submit default" style ="margin-right: -5px" />';
    } else {
      htmlContent = '<input type="submit" value="Go" name="_qf_Basic_refresh" class="form-submit default" />';
    }
    htmlContent += '<input type="text" class="form-text" id="sort_name_navigation" placeholder="{/literal}{ts escape='js'}Find Contacts{/ts}{literal}" name="sort_name" style="width: 12em; margin-left: -45px;" /><input type="text" id="sort_contact_id" style="display: none" />';
    htmlContent += '<input type="hidden" name="qfKey" value="' + {/literal}'{crmKey name='CRM_Contact_Controller_Search' addSequence=1}'{literal} + '" />';
    cj('#quickSearch').html(htmlContent);
  }

  cj( "#admin-menu>ul>li>a" ).each( function( ) {
      if ( cj( this ).html( ) == 'CiviCRM' ) {
          cj( this ).click ( function( ) {
              cj( "#civicrm-menu" ).toggle( );
              return false;
          });
      }
   });

  var contactUrl = {/literal}"{crmURL p='civicrm/ajax/rest' q='className=CRM_Contact_Page_AJAX&fnName=getContactList&json=1&context=navigation' h=0 }"{literal};

  cj( '#sort_name_navigation' ).autocomplete( contactUrl, {
      width: 200,
      selectFirst: false,
      minChars: 1,
      matchContains: true,
      delay: 400,
      max: CRM.config.search_autocomplete_count,
      extraParams:{
        limit: CRM.config.search_autocomplete_count,
        fieldName:function () {
          return  cj('input[name=quickSearchField]:checked').val();
        },
        tableName:function () {
           return  cj('input[name=quickSearchField]:checked').attr("data-tablename");
        }
      }
  }).result(function(event, data, formatted) {
     document.location={/literal}"{crmURL p='civicrm/contact/view' h=0 q='reset=1&cid='}"{literal}+data[1];
     return false;
  });
  cj('#sort_name_navigation').keydown(function() {
    cj.Menu.closeAll();
  });
  cj('.crm-quickSearchField').click(function() {
    var label = cj(this).text();
    cj('#sort_name_navigation').attr('placeholder', label).flushCache().focus();
  });
});
function getSearchURLValue( )
{
    var input = cj('#sort_name_navigation').val();
    var contactId =  cj( '#sort_contact_id' ).val();
    if ( ! contactId || isNaN( contactId ) ) {
      var sortValue = cj( '#sort_name_navigation' ).val();
      if ( sortValue ) {
          //using xmlhttprequest check if there is only one contact and redirect to view page
          var dataUrl = {/literal}"{crmURL p='civicrm/ajax/contact' h=0 q='name='}"{literal} + sortValue;

          var response = cj.ajax({
              url: dataUrl,
              async: false
              }).responseText;

          contactId = response;
      }
    }

    if ( contactId && !isNaN(parseInt(contactId)) ) {
        var url = {/literal}"{crmURL p='civicrm/contact/view' h=0 q='reset=1&cid='}"{literal} + contactId;
        document.getElementById('id_search_block').action = url;
    }
}

if (CRM.config.userFramework != 'Joomla') {
  cj('body').prepend( cj("#menu-container").html() );

  //Track Scrolling
  cj(window).scroll( function () {
     var scroll = document.documentElement.scrollTop || document.body.scrollTop;
     cj('#civicrm-menu').css({top: "scroll", position: "fixed", top: "0px"});
     cj('div.sticky-header').css({ 'top' : "23px", position: "fixed" });
  });

  if ( cj('#edit-shortcuts').length > 0 ) {
     cj('#civicrm-menu').css({ 'width': '97%' });
  }
} else {
     cj('div#toolbar-box div.m').html(cj("#menu-container").html());
     cj('#civicrm-menu').ready( function(){
      cj('.outerbox').css({ 'margin-top': '6px'});
      cj('#root-menu-div .menu-ul li').css({ 'padding-bottom' : '2px', 'margin-top' : '2px' });
      cj('img.menu-item-arrow').css({ 'top' : '4px' });
    });
}
  cj('#civicrm-menu').menu( {arrowSrc: CRM.config.resourceBase + 'packages/jquery/css/images/arrow.png'} );
</script>
{/literal}
