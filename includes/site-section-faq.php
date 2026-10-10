<?php defined('SITE_ROOT') || exit; ?>
<section id="faq" class="relative py-20 md:py-32 bg-gradient-to-b from-white to-dark-50">
 <div class="container mx-auto px-4 sm:px-6 lg:px-8">
  <div class="text-center mb-16" data-from="o:0" data-section>
   <div class="inline-block mb-4" data-from="o:0;s:.9"><span class="px-4 py-2 rounded-full bg-emerald-100 text-emerald-600 text-sm font-semibold lb"><?= t('faq.badge') ?></span></div>
   <h2 class="heading-large text-dark-900 mb-6 lb"><?= t('faq.title') ?> <span class="bg-gradient-to-r from-emerald-500 to-teal-500 bg-clip-text text-transparent"><?= t('faq.titleHighlight') ?></span></h2>
   <p class="text-lg md:text-xl text-dark-600 max-w-3xl mx-auto lb"><?= t('faq.subtitle') ?></p>
  </div>
  <div class="max-w-4xl mx-auto space-y-4" data-faq>
   <?php for ($i = 1; $i <= 5; $i++): ?>
   <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-dark-100 faq-item" data-from="o:0;y:20" data-delay="<?= 0.1 * ($i - 1) ?>">
    <button type="button" class="w-full p-6 md:p-8 flex items-center justify-between gap-4 text-left hover:bg-dark-50:bg-dark-800 transition-all faq-q">
     <span class="text-lg md:text-xl font-bold text-dark-900 lb"><?= t('faq.q' . $i) ?></span>
     <div class="flex-shrink-0 w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center faq-icon"><span class="faq-plus"><?= icon('plus', 'w-5 h-5 text-emerald-600') ?></span><span class="faq-minus"><?= icon('minus', 'w-5 h-5 text-emerald-600') ?></span></div>
    </button>
    <div class="overflow-hidden faq-a">
     <div class="px-6 md:px-8 pb-6 md:pb-8 text-dark-600 leading-relaxed lb"><?= t('faq.a' . $i) ?><?php if ($i === 4): ?> <a href="<?= e(setting('map_url', 'https://maps.app.goo.gl/F8CRe9hHFAWmTDyB8')) ?>" target="_blank" rel="noopener noreferrer" class="text-emerald-600 hover:text-emerald-700 underline"><?= t('faq.a4link') ?></a><?php endif; ?></div>
    </div>
   </div>
   <?php endfor; ?>
  </div>
 </div>
</section>
