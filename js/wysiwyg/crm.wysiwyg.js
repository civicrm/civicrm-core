// https://civicrm.org/licensing
(function($, _) {
  function openWysiwyg(item) {
    $(item).show();
    $(item).next('.replace-plain').hide();
    CRM.wysiwyg.create(item);
    $(item).on('blur', function() {
      CRM.wysiwyg.destroy(item);
      $(item).hide().next('.replace-plain').show().html($(item).val());
    });
  }
  CRM.wysiwyg = {};
  CRM.wysiwyg.supportsFileUploads =  false;
  CRM.wysiwyg.create = _.noop;
  CRM.wysiwyg.destroy = _.noop;
  CRM.wysiwyg.updateElement = _.noop;
  CRM.wysiwyg.getVal = function(item) {
    return $(item).val();
  };
  CRM.wysiwyg.setVal = function(item, val) {
    return $(item).val(val);
  };
  CRM.wysiwyg.insert = function(item, text) {
    CRM.wysiwyg.insertIntoTextarea(item, text);
  };
  CRM.wysiwyg.insertIntoTextarea = function(item, text) {
    var origVal = $(item).val();
    var origPos = item[0].selectionStart;
    var newVal = origVal + text;
    $(item).val(newVal);
    var newPos = (origPos + text.length);
    item[0].selectionStart = newPos;
    item[0].selectionEnd = newPos;
    $(item).triggerHandler('change');
    CRM.wysiwyg.focus(item);
  };
  CRM.wysiwyg.focus = function(item) {
    $(item).focus();
  };
  CRM.wysiwyg.createPlain = function(item) {
    $(item)
      .hide()
      .after('<div class="replace-plain" tabindex="0" title="Click to edit"></div>');
    $(item).next('.replace-plain').click(function(){
      openWysiwyg(item);
    });
    $(item).next('.replace-plain').keypress(function(){
      openWysiwyg(item);
    });
  };

})(CRM.$, CRM._);
