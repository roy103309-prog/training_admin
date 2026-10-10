<?php defined('SITE_ROOT') || exit; ?>
<?php $homePrograms = programs(true); ?>
<section id="programs" class="relative py-20 md:py-32 bg-white overflow-hidden">
 <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-emerald-100 via-white to-white"></div>
 <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
  <div class="text-center mb-20" data-from="o:0" data-section>
   <div class="inline-block mb-4" data-from="o:0;s:.9"><span class="px-4 py-2 rounded-full bg-emerald-100 text-emerald-600 text-sm font-semibold lb"><?= t('programs.badge') ?></span></div>
   <h2 class="heading-large text-dark-900 mb-6 lb"><?= t('programs.title') ?> <span class="bg-gradient-to-r from-emerald-500 to-teal-500 bg-clip-text text-transparent"><?= t('programs.titleHighlight') ?></span></h2>
   <p class="text-lg md:text-xl text-dark-600 max-w-3xl mx-auto lb"><?= t('programs.subtitle') ?></p>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16" data-stagger=".1">
   <?php foreach ($homePrograms as $p): ?>
   <div class="relative group hv-lift" data-from="o:0;y:60" data-variant>
    <div class="relative bg-white rounded-3xl overflow-hidden shadow-xl hover:shadow-2xl transition-all duration-500">
     <div class="relative h-64 overflow-hidden">
      <img alt="<?= e($p['title']) ?>" loading="lazy" width="400" height="300" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" src="<?= e(asset_url($p['image'])) ?>" style="color: transparent;">
      <div class="absolute inset-0 bg-gradient-to-t from-dark-900 via-dark-900/50 to-transparent"></div>
      <div class="absolute top-4 right-4"><span class="px-4 py-2 rounded-full bg-gradient-to-r <?= e($p['color']) ?> text-white text-sm font-semibold shadow-lg"><?= e($p['level']) ?></span></div>
      <div class="absolute bottom-0 left-0 right-0 p-6">
       <h3 class="text-2xl font-bold text-white mb-1"><?= e($p['title']) ?></h3>
       <p class="text-dark-200 text-sm"><?= e($p['subtitle']) ?></p>
      </div>
     </div>
     <div class="p-6 space-y-4">
      <p class="text-dark-600 line-clamp-2"><?= e($p['description'] ?? '') ?></p>
      <a class="block" href="<?= e(url('programs/' . $p['slug'])) ?>"><button type="button" class="w-full py-3 bg-gradient-to-r from-emerald-600 to-emerald-500 text-white rounded-xl font-semibold flex items-center justify-center gap-2 shadow-lg hover:shadow-xl transition-all lb hv-scale102 tap98"><?= t('programs.viewDetails') ?><?= icon('chevron-right', 'w-5 h-5') ?></button></a>
     </div>
    </div>
   </div>
   <?php endforeach; ?>
  </div>
 </div>
</section>
