(function(){
  const body = document.body;
  const adminBtn = document.querySelector('[data-admin-sidebar-toggle]');
  const adminBackdrop = document.querySelector('[data-admin-sidebar-backdrop]');
  if(adminBtn){
    adminBtn.addEventListener('click', () => body.classList.toggle('admin-sidebar-open'));
  }
  if(adminBackdrop){
    adminBackdrop.addEventListener('click', () => body.classList.remove('admin-sidebar-open'));
  }
  document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape') body.classList.remove('admin-sidebar-open'); });

  const checkoutBtn = document.querySelector('.js-place-order-btn');
  if(checkoutBtn && window.matchMedia('(max-width: 575.98px)').matches){
    body.classList.add('has-sticky-checkout');
    checkoutBtn.closest('.checkout-submit-wrap')?.classList.add('sticky-mobile-checkout');
  }
})();
