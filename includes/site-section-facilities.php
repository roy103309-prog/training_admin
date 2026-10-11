<?php defined('SITE_ROOT') || exit; ?>
<?php
// Facilities: Admin -> Settings -> General Settings -> Facilities (web_facilities)
$facilities = db_all("SELECT * FROM web_facilities WHERE is_active = 1 ORDER BY sort_order, id");
if (!$facilities) { return; }
?>
<section id="facilities" class="relative py-20 md:py-32 bg-gradient-to-br from-emerald-50 via-green-50 to-teal-50 overflow-hidden">
 <div class="absolute inset-0 bg-[linear-gradient(rgba(16,185,129,0.03)_1px,transparent_1px),linear-gradient(90deg,rgba(16,185,129,0.03)_1px,transparent_1px)] bg-[size:40px_40px]"></div>
 <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
  <div class="text-center mb-20" data-from="o:0" data-section>
   <div class="inline-block mb-4" data-from="o:0;s:.9"><span class="px-4 py-2 rounded-full bg-emerald-100 text-emerald-600 text-sm font-semibold lb"><?= t('facilities.badge') ?></span></div>
   <h2 class="heading-large text-dark-900 mb-6 lb"><?= t('facilities.title') ?> <span class="bg-gradient-to-r from-emerald-500 to-teal-500 bg-clip-text text-transparent"><?= t('facilities.titleHighlight') ?></span></h2>
   <p class="text-lg md:text-xl text-dark-600 max-w-3xl mx-auto lb"><?= t('facilities.subtitle') ?></p>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-20" data-stagger=".1">
   <?php foreach ($facilities as $fac):
       $specsBn = array_values(array_filter(array_map("trim", preg_split("/\R/u", (string)$fac["specs_bn"])), "strlen"));
       $specsEn = array_values(array_filter(array_map("trim", preg_split("/\R/u", (string)$fac["specs_en"])), "strlen"));
   ?>
   <div class="group h-full hv-lift" data-from="o:0;y:60" data-variant>
    <div class="h-full" data-tilt='{"max":5,"glare":0.1,"scale":1.02,"speed":400}'>
     <div class="relative bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500 h-full flex flex-col">
      <div class="relative h-64 overflow-hidden flex-shrink-0">
       <img alt="<?= e($fac['title_en'] !== '' ? $fac['title_en'] : $fac['title_bn']) ?>" loading="lazy" width="400" height="300" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" src="<?= e(asset_url($fac['image'])) ?>" style="color: transparent;">
       <div class="absolute inset-0 bg-gradient-to-t from-dark-900 via-dark-900/50 to-transparent"></div>
       <div class="absolute top-4 left-4 w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center shadow-lg hv-spin6"><?= icon($fac['icon'], 'w-6 h-6 text-white') ?></div>
      </div>
      <div class="p-6 flex-1 flex flex-col">
       <h3 class="text-2xl font-bold text-dark-900 mb-3 lb"><?= tx($fac['title_bn'] !== '' ? $fac['title_bn'] : $fac['title_en'], $fac['title_en']) ?></h3>
       <p class="text-dark-600 mb-4 lb"><?= tx((string)($fac['description_bn'] ?: $fac['description_en']), (string)$fac['description_en']) ?></p>
       <div class="space-y-2 mt-auto">
        <?php foreach (($specsBn ?: $specsEn) as $s => $spec): ?><div class="flex items-center gap-2 text-sm text-dark-600"><div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div><span class="lb"><?= tx($spec, $specsEn[$s] ?? '') ?></span></div><?php endforeach; ?>
       </div>
      </div>
     </div>
    </div>
   </div>
   <?php endforeach; ?>
  </div>
 </div>
</section>
