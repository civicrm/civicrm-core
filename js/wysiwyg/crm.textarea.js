// https://civicrm.org/licensing
(function($, _) {
  CRM.wysiwyg = {
    supportsFileUploads: false,
    create: _.noop,
    destroy: _.noop,
    updateElement: _.noop,
    val: function(item) {
      return $(item).val();
    },
  };
})(CRM.$, CRM._);
