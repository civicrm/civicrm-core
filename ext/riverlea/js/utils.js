(function (CRM) {

  CRM.riverlea = CRM.riverlea || {};

  // TODO: move to a general CRM util?
  CRM.riverlea.createButton = (label, btnClass, icon, clickHandler) => {
    const button = document.createElement('button');
    button.type = 'button';
    button.classList.add('btn', btnClass);
    button.innerHTML = `<i class="crm-i fa-${icon}"></i>${ts(label)}`;
    button.onclick = clickHandler;

    return button;
  };

})(CRM);


