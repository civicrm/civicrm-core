// https://civicrm.org/licensing
(function($, _) {
  function openWysiwyg(item) {
    $(item).show();
    $(item).next('.replace-plain').hide();
    CRM.wysiwyg.create(item);
    $(item).on( 'blur', function( e ) {
      CRM.wysiwyg.updateElement(item);
      CRM.wysiwyg.destroy(item);
      $(item).hide().next('.replace-plain').show().html($(item).val());
    });
  };
  CRM.wysiwyg = {};
  CRM.wysiwyg['supportsFileUploads'] =  false;
  CRM.wysiwyg.create = _.noop;
  CRM.wysiwyg.destroy = _.noop;
  CRM.wysiwyg.updateElement = _.noop;
  CRM.wysiwyg.val = function(item) {
    return $(item).val();
  };
  CRM.wysiwyg.insertText = _.noop;
  CRM.wysiwyg.insertHTML = _.noop;
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
