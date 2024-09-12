document.addEventListener('DOMContentLoaded', () => {
    if (
        (CRM.vars.riverlea.dark_mode === 'dark') ||
        (CRM.vars.riverlea.dark_mode === 'inherit' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)
    ) {
        document.getElementsByTagName('html')[0].classList.add('crm-dark');
    }
});