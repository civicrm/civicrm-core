/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
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
*/
/*
* Copyright (C) 2009-2010 Xavier Dutoit
* Licensed to CiviCRM under the Academic Free License version 3.0.
*/

var CRM = CRM || {};

(function($, CRM) {
  /**
   * Almost like {crmURL} but on the client side
   * eg: var url = CRM.url('civicrm/contact/view', {reset:1,cid:42});
   * or: $('a.my-link').crmURL();
   */
  var tplURL = '/civicrm/example?placeholder';
  var urlInitted = false;
  CRM.url = function (p, params) {
    if (p == "init") {
      tplURL = params;
      urlInitted = true;
      return;
    }
    if (!urlInitted) {
      console && console.log && console.log('Warning: CRM.url called before initialization');
    }
    params = params || '';
    var frag = p.split ('?');
    var url = tplURL.replace("civicrm/example", frag[0]);

    if (typeof(params) == 'string') {
      url = url.replace("placeholder", params);
    }
    else {
      url = url.replace("placeholder", $.param(params));
    }
    if (frag[1]) {
      url += (url.indexOf('?') === (url.length - 1) ? '' : '&') + frag[1];
    }
    // remove trailing "?"
    if (url.indexOf('?') === (url.length - 1)) {
      url = url.slice(0, (url.length - 1));
    }
    return url;
  };

  // Backwards compatible with jQuery fn
  $.extend ({'crmURL':
    function (p, params) {
      console && console.log && console.log('Calling crmURL from jQuery is deprecated. Please use CRM.url() instead.');
      return CRM.url(p, params);
    }
  });

  $.fn.crmURL = function () {
    return this.each(function() {
      if (this.href) {
        this.href = CRM.url(this.href);
      }
    });
  };

  /**
   * AJAX api
   */
  CRM.api = function(entity, action, params, options) {
    // Default settings
    var json = false,
    settings = {
      context: null,
      success: function(result, settings) {
        return true;
      },
      error: function(result, settings) {
        $().crmError(result.error_message, ts('Error'));
        return false;
      },
      callBack: function(result, settings) {
        if (result.is_error == 1) {
          return settings.error.call(this, result, settings);
        }
        return settings.success.call(this, result, settings);
      },
      ajaxURL: 'civicrm/ajax/rest',
    };
    action = action.toLowerCase();
    // Default success handler
    switch (action) {
      case "update":
      case "create":
      case "setvalue":
      case "replace":
        settings.success = function() {
          CRM.alert('', ts('Saved'), 'success');
          return true;
        };
        break;
      case "delete":
        settings.success = function() {
          CRM.alert('', ts('Removed'), 'success');
          return true;
        };
    }
    for (var i in params) {
      if (i.slice(0, 4) == 'api.' || typeof(params[i]) == 'Object') {
        json = true;
        break;
      }
    }
    if (json) {
      params = {
        entity: entity,
        action: action,
        json: JSON.stringify(params)
      };
    }
    else {
      params.entity = entity;
      params.action = action;
      params.json = 1;
    }
    // Pass copy of settings into closure to preserve its value during multiple requests
    (function(stg) {
      $.ajax({
        url: stg.ajaxURL.indexOf('http') === 0 ? stg.ajaxURL : CRM.url(stg.ajaxURL),
        dataType: 'json',
        data: params,
        type: action.indexOf('get') < 0 ? 'POST' : 'GET',
        success: function(result) {
          stg.callBack.call(stg.context, result, stg);
        }
      });
    })($.extend({}, settings, options));
  };

  // Backwards compatible with jQuery fn
  $.fn.crmAPI = function(entity, action, params, options) {
    console && console.log && console.log('Calling crmAPI from jQuery is deprecated. Please use CRM.api() instead.');
    return CRM.api.call(this, entity, action, params, options);
  };

  $.fn.crmAutocomplete = function (params, options) {
    if (typeof params == 'undefined') params = {};
    if (typeof options == 'undefined') options = {};
    params = $().extend({
      rowCount:35,
      json:1,
      entity:'Contact',
      action:'getquick',
      sequential:1
    }, params);

    options = $().extend({}, {
        field :'name',
        skip : ['id','contact_id','contact_type','contact_is_deleted',"email_id",'address_id', 'country_id'],
        result: function(data){
        return false;
      },
      formatItem: function(data,i,max,value,term){
        var tmp = [];
        for (attr in data) {
          if ($.inArray (attr, options.skip) == -1 && data[attr]) {
            tmp.push(data[attr]);
          }
        }
        return  tmp.join(' :: ');
      },
      parse: function (data){
             var acd = new Array();
             for(cid in data.values){
               delete data.values[cid]["data"];// to be removed once quicksearch doesn't return data
               acd.push({ data:data.values[cid], value:data.values[cid].sort_name, result:data.values[cid].sort_name });
             }
             return acd;
      },
      delay:100,
        width:250,
      minChars:1
      }, options
    );
    var contactUrl = CRM.url('civicrm/ajax/rest', params);

    return this.each(function() {
      var selector = this;
      if (typeof $.fn.autocomplete != 'function')
        $.fn.autocomplete = cj.fn.autocomplete;//to work around the fubar cj
        var extraP = {};
        extraP [options.field] = function () {return $(selector).val();};
        $(this).autocomplete( contactUrl, {
          extraParams:extraP,
          formatItem: function(data,i,max,value,term){
            return options.formatItem(data,i,max,value,term);
          },
          parse: function(data){ return options.parse(data);},
          width: options.width,
          delay:options.delay,
          max:25,
          dataType:'json',
          minChars:options.minChars,
          selectFirst: true
       }).result(function(event, data, formatted) {
            options.result(data);
        });
     });
   }

})(jQuery, CRM);
