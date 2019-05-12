// Loads a copy of shoreditch's bootstrap if bootstrap is missing
CRM.$(function($) {
  if (!$.isFunction($.fn.dropdown)) {
    CRM.loadScript(CRM.vars.api4.basePath + 'lib/shoreditch/dropdown.js');
    $('head').append('<link type="text/css" rel="stylesheet" href="' + CRM.vars.api4.basePath + 'lib/shoreditch/bootstrap.css" />');
  }
});