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
            <form action="{crmURL p='civicrm/contact/search/advanced' h=0 }" name="search_block" id="id_search_block" method="post">
              <div id="quickSearch">
                <input type="text" class="form-text" id="sort_name_navigation" placeholder="{ts}Find Contacts{/ts}" name="sort_name" style="width: 12em;" />
                <input type="text" id="sort_contact_id" style="display: none" />
                <input type="hidden" name="hidden_location" value="1" />
                <input type="hidden" name="qfKey" value="{crmKey name='CRM_Contact_Controller_Search' addSequence=1}" />
                <div style="height:1px; overflow:hidden;"><input type="submit" value="{ts}Go{/ts}" name="_qf_Advanced_refresh" class="form-submit default" /></div>
              </div>
            </form>
          <ul>
            <li><label class="crm-quickSearchField"><input type="radio" checked="" data-tablename="cc" checked="checked" value="" name="quickSearchField">{ts}Name/Email{/ts}</label></li>
            <li><label class="crm-quickSearchField"><input type="radio" data-tablename="cc" value="contact_id" name="quickSearchField">{ts}CiviCRM ID{/ts}</label></li>
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
cj(function( ) {
  cj("#civicrm-menu >li").each(function(i){
    cj(this).attr("tabIndex",i+2);
  });

  var contactUrl = {/literal}"{crmURL p='civicrm/ajax/rest' q='className=CRM_Contact_Page_AJAX&fnName=getContactList&json=1&context=navigation' h=0 }"{literal};

  cj( '#sort_name_navigation' ).autocomplete( contactUrl, {
      width: 200,
      selectFirst: false,
      minChars: 1,
      matchContains: true,
      delay: 400,
      max: {/literal}{crmSetting name="search_autocomplete_count" group="Search Preferences"}{literal},
      extraParams:{
        fieldName:function () {
          return  cj('input[name=quickSearchField]:checked').val();
        },
        tableName:function () {
           return  cj('input[name=quickSearchField]:checked').attr("data-tablename");
        }
      }
  }).result(function(event, data, formatted) {
     document.location = CRM.url('civicrm/contact/view', {reset: 1, cid: data[1]});
     return false;
  });
  cj('#sort_name_navigation').keydown(function() {
    cj.Menu.closeAll();
  });
  cj('.crm-quickSearchField').click(function() {
    var label = cj(this).text();
    var value = cj('input', this).val();
    // These fields are not supported by advanced search
    if (value === 'first_name' || value === 'last_name') {
      value = 'sort_name';
    }
    cj('#sort_name_navigation').attr({name: value, placeholder: label}).flushCache().focus();
  });
  // check if there is only one contact and redirect to view page
  cj('#id_search_block').on('submit', function() {
    var contactId, sortValue = cj('#sort_name_navigation').val();
    if (sortValue && cj('#sort_name_navigation').attr('name') == 'sort_name') {
      {/literal}{*
      // FIXME: async:false == bad,
      // we should just check the autocomplete results instead of firing a new request
      // when we fix this, the civicrm/ajax/contact server-side callback can be removed as well
      // also that would fix the fact that this only works with sort_name search
      // (and we can remove the above conditional)
      *}{literal}
      var dataUrl = {/literal}"{crmURL p='civicrm/ajax/contact' h=0 q='name='}"{literal} + sortValue;
      contactId = cj.ajax({
        url: dataUrl,
        async: false
      }).responseText;
    }
    if (contactId && !isNaN(parseInt(contactId))) {
      var url = {/literal}"{crmURL p='civicrm/contact/view' h=0 q='reset=1&cid='}"{literal} + contactId;
      this.action = url;
    }
  });
});

{/literal}{if $config->userFramework neq 'Joomla' and $config->userFrameworkFrontend ne 1}{literal}
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
{/literal}{elseif $config->userFrameworkFrontend ne 1}{* Special menu hacks for Joomla *}{literal}
  // below div is present in older version of joomla 2.5.x
  var elementExists = cj('div#toolbar-box div.m').length;
  if ( elementExists > 0 ) {
    cj('div#toolbar-box div.m').html(cj("#menu-container").html());
  }
  else {
    cj("#menu-container").show().css({'padding-bottom': '10px'});
  }

  cj('#civicrm-menu').ready(function() {
    cj('#root-menu-div .outerbox').css({ 'margin-top': '6px'});
    cj('#root-menu-div .outerbox').first().css({ 'margin-top': '20px'});
    cj('#root-menu-div .menu-ul li').css({ 'padding-bottom' : '2px', 'margin-top' : '2px' });
    cj('img.menu-item-arrow').css({ 'top' : '4px' });
  });
  {/literal}{/if}{literal}
  cj('#civicrm-menu').menu( {arrowSrc: CRM.config.resourceBase + 'packages/jquery/css/images/arrow.png'} );
</script>
{/literal}
