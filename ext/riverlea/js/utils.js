(function (CRM) {

  CRM.riverlea = CRM.riverlea || {};

  CRM.riverlea.createButton = (label, btnClass = null, icon = null, clickHandler = null, href = null, target = null, type = 'button') => {
    const button = document.createElement('button');

    button.type = type;

    button.classList.add('btn');
    if (btnClass) {
      button.classList.add(btnClass);
    }

    button.innerText = label;

    if (icon) {
      const i = document.createElement('i');
      i.classList.add('crm-i', icon);
      i.role = 'img';
      i.ariaHidden = true;
      button.prepend(i);
    }

    if (clickHandler) {
      button.onclick = clickHandler;
    }
    if (href) {
      button.href = href;
    }
    if (target) {
      button.target = target;
    }

    return button;
  };

})(CRM);


