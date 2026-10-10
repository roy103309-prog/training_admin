<?php defined('SITE_ROOT') || exit; ?>
<?php $phones = setting('phone_primary', '01712-577508') . (setting('phone_secondary') !== '' ? ', ' . setting('phone_secondary') : ''); ?>
<section id="contact" class="relative py-20 md:py-32 bg-gradient-to-br from-emerald-50 via-white to-teal-50 overflow-hidden">
 <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-emerald-100/50 via-transparent to-transparent"></div>
 <div class="absolute inset-0 bg-[linear-gradient(rgba(16,185,129,0.03)_1px,transparent_1px),linear-gradient(90deg,rgba(16,185,129,0.03)_1px,transparent_1px)] bg-[size:50px_50px]"></div>
 <div class="absolute -left-20 top-20 w-72 h-72 bg-emerald-200/30 rounded-full blur-3xl"></div>
 <div class="absolute -right-20 bottom-20 w-80 h-80 bg-teal-200/30 rounded-full blur-3xl"></div>
 <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
  <div class="text-center mb-16" data-from="o:0" data-section>
   <div class="inline-block mb-4" data-from="o:0;s:.9"><span class="px-4 py-2 rounded-full bg-emerald-500/20 text-emerald-600 text-sm font-semibold border border-emerald-500/30 lb"><?= t('contact.badge') ?></span></div>
   <h2 class="heading-large text-dark-900 mb-6 lb"><?= t('contact.title') ?> <span class="bg-gradient-to-r from-emerald-500 to-teal-500 bg-clip-text text-transparent"><?= t('contact.titleHighlight') ?></span></h2>
   <p class="text-lg md:text-xl text-dark-600 max-w-3xl mx-auto lb"><?= t('contact.description') ?></p>
  </div>
  <div class="max-w-6xl mx-auto">
   <div class="grid lg:grid-cols-5 gap-12">
    <div class="lg:col-span-2 space-y-6">
     <div class="p-6 bg-white/80 backdrop-blur-lg rounded-2xl shadow-lg border border-emerald-100 hover:border-emerald-300 transition-all hv-scale102" data-from="o:0;x:-50">
      <div class="flex items-start gap-4">
       <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center flex-shrink-0 shadow-lg"><?= icon('map-pin', 'w-6 h-6 text-white') ?></div>
       <div><h3 class="font-bold text-dark-900 mb-1 lb"><?= t('contact.address') ?></h3><p class="text-dark-600 text-sm lb"><?= tx(setting('address_bn'), setting('address_en')) ?></p></div>
      </div>
     </div>
     <a href="tel:<?= e(setting('call_number', '+8801712577508')) ?>" class="block p-6 bg-white/80 backdrop-blur-lg rounded-2xl shadow-lg border border-emerald-100 hover:border-emerald-300 transition-all hv-scale102" data-from="o:0;x:-50" data-delay=".1">
      <div class="flex items-start gap-4">
       <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center flex-shrink-0 shadow-lg"><?= icon('phone', 'w-6 h-6 text-white') ?></div>
       <div><h3 class="font-bold text-dark-900 mb-1 lb"><?= t('contact.callUs') ?></h3><p class="text-dark-600 text-sm"><?= e($phones) ?></p></div>
      </div>
     </a>
     <a href="mailto:<?= e(setting('email')) ?>" class="block p-6 bg-white/80 backdrop-blur-lg rounded-2xl shadow-lg border border-emerald-100 hover:border-emerald-300 transition-all hv-scale102" data-from="o:0;x:-50" data-delay=".2">
      <div class="flex items-start gap-4">
       <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center flex-shrink-0 shadow-lg"><?= icon('mail', 'w-6 h-6 text-white') ?></div>
       <div><h3 class="font-bold text-dark-900 mb-1 lb"><?= t('contact.emailUs') ?></h3><p class="text-dark-600 text-sm break-all"><?= e(setting('email')) ?></p></div>
      </div>
     </a>
    </div>
    <div class="lg:col-span-3" data-from="o:0;x:50">
     <div class="bg-white/90 backdrop-blur-xl rounded-3xl shadow-2xl border border-emerald-100 p-8 md:p-10 relative overflow-hidden">
      <div class="absolute -left-16 -top-16 w-40 h-40 bg-emerald-100 rounded-full blur-3xl opacity-60"></div>
      <div class="absolute -right-16 -bottom-16 w-48 h-48 bg-teal-100 rounded-full blur-3xl opacity-60"></div>
      <form class="relative grid grid-cols-1 md:grid-cols-2 gap-6" id="contactForm" action="<?= e(asset_url('api/site-contact.php')) ?>" method="post">
       <div class="md:col-span-1"><label class="block text-sm font-semibold text-dark-700 mb-2 lb"><?= t('contact.name') ?></label><input name="name" required class="w-full rounded-xl border border-emerald-100 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-200 px-4 py-3 bg-white shadow-sm transition-all"></div>
       <div class="md:col-span-1"><label class="block text-sm font-semibold text-dark-700 mb-2 lb"><?= t('contact.email') ?></label><input type="email" name="email" required class="w-full rounded-xl border border-emerald-100 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-200 px-4 py-3 bg-white shadow-sm transition-all"></div>
       <div class="md:col-span-2"><label class="block text-sm font-semibold text-dark-700 mb-2 lb"><?= t('contact.phone') ?></label><input name="phone" required class="w-full rounded-xl border border-emerald-100 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-200 px-4 py-3 bg-white shadow-sm transition-all"></div>
       <div class="md:col-span-2"><label class="block text-sm font-semibold text-dark-700 mb-2 lb"><?= t('contact.message') ?></label><textarea name="message" required rows="4" class="w-full rounded-xl border border-emerald-100 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-200 px-4 py-3 bg-white shadow-sm resize-none transition-all"></textarea></div>
       <div class="md:col-span-2 flex flex-col gap-4">
        <button type="submit" class="inline-flex items-center justify-center gap-3 px-8 py-4 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-semibold shadow-lg hover:shadow-xl hover:shadow-emerald-500/25 transition-all lb hv-scale102 tap98"><?= icon('send', 'w-5 h-5') ?><?= t('contact.send') ?></button>
        <p class="text-emerald-700 font-semibold text-center lb form-msg" id="contactSuccess" hidden><?= t('contact.success') ?></p>
        <p class="text-red-600 font-semibold text-center lb form-msg" id="contactError" hidden><?= t('contact.error') ?></p>
       </div>
      </form>
     </div>
    </div>
   </div>
  </div>
 </div>
</section>
