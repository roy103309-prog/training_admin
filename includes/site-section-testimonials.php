<?php defined('SITE_ROOT') || exit; ?>
<?php
$reviews = db_all('SELECT * FROM reviews WHERE is_active = 1 ORDER BY sort_order, id');
if (!$reviews): ?>
<section class="py-20 bg-white"><div class="container mx-auto px-4 text-center"><div class="text-emerald-600">Testimonials will appear soon.</div></div></section>
<?php return; endif;
$loop = count($reviews) > 1;
$dur = max(6 * count($reviews), 18);
$cls = min(180, $dur);
$items = $loop ? array_merge($reviews, $reviews) : $reviews;
?>
<?= icon_sprite(['star', 'quote']) ?>
<section id="testimonials" class="relative py-20 md:py-32 bg-gradient-to-b from-emerald-50 via-green-50 to-teal-50 overflow-hidden">
 <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-emerald-100/50 via-transparent to-transparent"></div>
 <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
  <div class="text-center mb-20" data-from="o:0" data-section>
   <div class="inline-block mb-4" data-from="o:0;s:.9"><span class="px-4 py-2 rounded-full bg-emerald-500/20 text-emerald-600 text-sm font-semibold border border-emerald-500/30 lb"><?= t('testimonials2.badge') ?></span></div>
   <h2 class="heading-large text-dark-900 mb-6 lb"><?= t('testimonials2.title') ?> <span class="bg-gradient-to-r from-emerald-500 to-teal-500 bg-clip-text text-transparent"><?= t('testimonials2.titleHighlight') ?></span></h2>
   <p class="text-lg md:text-xl text-dark-600 max-w-3xl mx-auto lb"><?= t('testimonials2.subtitle') ?></p>
  </div>
  <div class="relative overflow-hidden">
   <div class="absolute left-0 top-0 bottom-0 w-20 bg-gradient-to-r from-emerald-50 to-transparent z-10 pointer-events-none"></div>
   <div class="absolute right-0 top-0 bottom-0 w-20 bg-gradient-to-l from-teal-50 to-transparent z-10 pointer-events-none"></div>
   <div class="flex gap-4 sm:gap-8 <?= $loop ? 'marquee-track marquee-duration-' . $cls : 'justify-center' ?>"<?= $loop ? ' data-marquee-duration="' . $dur . '" style="animation-duration:' . $dur . 's"' : '' ?>>
    <?php foreach ($items as $r): ?>
    <div class="flex-shrink-0 w-[240px] sm:w-[280px] md:w-[320px]">
     <div class="relative bg-white backdrop-blur-lg shadow-2xl rounded-2xl sm:rounded-3xl p-4 sm:p-6 border-2 border-emerald-300 flex flex-col h-[260px] sm:h-[280px]">
      <div class="absolute -top-3 -left-3 sm:-top-4 sm:-left-4 w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-xl"><?= icon_use('quote', 'w-3 h-3 sm:w-5 sm:h-5 text-white') ?></div>
      <div class="flex gap-1 mb-2"><?php for ($s = 0; $s < (int)$r['rating']; $s++): ?><?= icon_use('star', 'w-3 h-3 sm:w-4 sm:h-4 fill-emerald-500 text-emerald-500') ?><?php endfor; ?></div>
      <p class="text-xs sm:text-sm text-dark-600 leading-relaxed mb-3 flex-grow overflow-hidden line-clamp-4 font-bengali">"<?= e($r['message']) ?>"</p>
      <div class="flex items-center gap-2 sm:gap-3 pt-3 border-t border-emerald-200">
       <div class="relative w-9 h-9 sm:w-11 sm:h-11 rounded-full overflow-hidden flex-shrink-0">
        <?php if (trim((string)$r['photo_url']) !== ''): ?><img alt="<?= e($r['student_name']) ?>" loading="lazy" class="object-cover" style="position:absolute;height:100%;width:100%;inset:0" src="<?= e(asset_url($r['photo_url'])) ?>">
        <?php else: ?><div class="flex h-full w-full items-center justify-center bg-emerald-100 text-emerald-700 text-sm font-semibold"><?= e(initials($r['student_name'], true)) ?></div><?php endif; ?>
       </div>
       <div>
        <h4 class="text-xs sm:text-sm text-dark-900 font-bold lb"><?= e($r['student_name']) ?></h4>
        <p class="text-dark-400 text-xs lb"><?= e($r['trade']) ?></p>
        <p class="text-emerald-500 text-xs font-medium font-bengali"><?= e($r['country']) ?></p>
       </div>
      </div>
     </div>
    </div>
    <?php endforeach; ?>
   </div>
  </div>
 </div>
</section>
