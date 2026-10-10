<?php defined('SITE_ROOT') || exit; ?>
<?php
$slides = db_all('SELECT id, title, image_url FROM sliders WHERE is_active = 1 AND image_url <> "" ORDER BY sort_order, id');
if (!$slides): ?>
<div class="min-h-screen bg-gray-900 flex items-center justify-center"><div class="text-white">No sliders available</div></div>
<?php return; endif; ?>
<section id="home" class="relative min-h-screen overflow-hidden bg-gray-900" data-hero>
 <div class="hero-slides">
  <?php foreach ($slides as $i => $s): ?>
  <div class="absolute inset-0 hero-slide<?= $i === 0 ? ' is-active' : '' ?>" data-index="<?= $i ?>">
   <div class="absolute inset-0"><img alt="<?= e($s['title'] !== '' ? $s['title'] : 'Hero background slide') ?>" class="object-cover" <?= $i === 0 ? 'src' : 'data-src' ?>="<?= e(asset_url($s['image_url'])) ?>" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;"<?= $i === 0 ? ' fetchpriority="high"' : '' ?>></div>
   <div class="absolute inset-0 bg-gradient-to-r from-gray-900/70 via-gray-900/30 to-transparent"></div>
   <div class="absolute inset-0 bg-gradient-to-b from-gray-900/40 via-transparent to-gray-900/30"></div>
  </div>
  <?php endforeach; ?>
 </div>
 <button type="button" aria-label="Previous slide" data-hero-prev class="absolute left-2 sm:left-4 top-1/2 -translate-y-1/2 z-20 p-2 sm:p-3 rounded-full bg-white/20 backdrop-blur-sm border border-white/30 text-white hover:bg-emerald-500 hover:border-emerald-500 transition-all duration-300 group shadow-lg"><?= icon('chevron-left', 'w-5 h-5 sm:w-6 sm:h-6 group-hover:scale-110 transition-transform') ?></button>
 <button type="button" aria-label="Next slide" data-hero-next class="absolute right-2 sm:right-4 top-1/2 -translate-y-1/2 z-20 p-2 sm:p-3 rounded-full bg-white/20 backdrop-blur-sm border border-white/30 text-white hover:bg-emerald-500 hover:border-emerald-500 transition-all duration-300 group shadow-lg"><?= icon('chevron-right', 'w-5 h-5 sm:w-6 sm:h-6 group-hover:scale-110 transition-transform') ?></button>
 <div class="relative z-10 container mx-auto px-4 min-h-screen flex items-center justify-center">
  <div class="max-w-3xl text-center">
   <p class="text-lg md:text-xl text-emerald-400 font-semibold mb-3 lb" data-from="o:0;y:30" data-delay=".1" data-onload><?= t('hero.banglaTtitle') ?></p>
   <h1 class="text-3xl md:text-5xl lg:text-6xl font-extrabold text-white leading-tight mb-6 lb" data-from="o:0;y:30" data-delay=".2" data-onload><?= t('hero.title1') ?><span class="block mt-1 bg-gradient-to-r from-emerald-400 via-emerald-500 to-teal-400 bg-clip-text text-transparent"><?= t('hero.title2') ?></span></h1>
   <p class="text-base md:text-lg text-gray-200 mb-8 max-w-2xl mx-auto leading-relaxed lb" data-from="o:0;y:30" data-delay=".3" data-onload><?= t('hero.description') ?></p>
   <div class="mb-8 flex justify-center" data-from="o:0;y:30" data-delay=".4" data-onload><a class="group inline-flex items-center gap-3 px-7 py-3.5 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white text-lg font-semibold rounded-full hover:from-emerald-600 hover:to-emerald-700 transition-all duration-300 shadow-lg shadow-emerald-500/30 hover:shadow-emerald-500/50 hover:scale-105 lb" href="#programs"><?= t('hero.startJourney') ?><?= icon('arrow-right', 'w-5 h-5 group-hover:translate-x-1 transition-transform') ?></a></div>
   <div class="p-5 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 shadow-lg max-w-2xl mx-auto" data-from="o:0;y:30" data-delay=".5" data-onload>
    <p class="text-lg md:text-xl text-emerald-400 font-semibold italic text-center font-bengali"><?= t('hero.quote') ?></p>
   </div>
  </div>
 </div>
 <div class="absolute bottom-4 sm:bottom-8 left-1/2 -translate-x-1/2 z-20 flex gap-2 sm:gap-3">
  <?php foreach ($slides as $i => $s): ?><button type="button" aria-label="Slide <?= $i + 1 ?>" data-hero-dot="<?= $i ?>" class="w-2 h-2 sm:w-3 sm:h-3 rounded-full transition-all duration-300 <?= $i === 0 ? 'bg-emerald-500 w-5 sm:w-8' : 'bg-white/50 hover:bg-white/70' ?>"></button><?php endforeach; ?>
 </div>
</section>
