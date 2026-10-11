<?php defined('SITE_ROOT') || exit; ?>
<?php
$gradients = [
    'leadership' => 'from-emerald-500 via-teal-500 to-green-500',
    'management' => 'from-blue-500 via-cyan-500 to-teal-500',
    'marketing' => 'from-emerald-500 via-teal-500 to-green-500',
    'instructors' => 'from-emerald-500 via-teal-500 to-green-500',
    'technical' => 'from-green-500 via-emerald-500 to-teal-500',
];
$members = db_all("SELECT id, name, phone, email, designation AS role, image, category FROM web_team WHERE is_active = 1 ORDER BY sort_order, id");
$byCat = ['leadership' => [], 'management' => [], 'marketing' => [], 'instructors' => [], 'technical' => []];
foreach ($members as $m) {
    $c = strtolower(trim((string)$m['category']));
    if (in_array($c, ['instructor', 'trainer'], true)) { $c = 'instructors'; }
    if (!isset($byCat[$c])) { $c = 'technical'; }
    $m['img'] = asset_url($m['image']);
    $m['gradient'] = $gradients[$c];
    $byCat[$c][] = $m;
}
$photo = function (array $m, string $initialsClass): string {
    if ($m['img'] !== '') {
        return '<img alt="' . e($m['name']) . '" loading="lazy" class="object-cover" style="position:absolute;height:100%;width:100%;left:0;top:0;right:0;bottom:0;color:transparent" src="' . e($m['img']) . '">';
    }
    return '<div class="flex h-full w-full items-center justify-center bg-emerald-50 text-emerald-700 ' . $initialsClass . ' font-bold">' . e(initials($m['name'])) . '</div>';
};
// $title / $subtitle are text keys (Admin -> Website Texts)
$groupHead = function (string $iconName, string $title, string $subtitle): string {
    return '<div class="text-center mb-10 md:mb-16" data-from="o:0;y:20"><div class="inline-flex items-center gap-2 md:gap-4 mb-4"><div>' . icon($iconName, 'w-6 h-6 md:w-8 md:h-8 text-emerald-500') . '</div><h3 class="text-2xl md:text-4xl lg:text-5xl font-bold font-display text-emerald-600">' . t($title) . '</h3><div>' . icon($iconName, 'w-6 h-6 md:w-8 md:h-8 text-emerald-500') . '</div></div><p class="text-base md:text-xl text-emerald-500 font-semibold">' . t($subtitle) . '</p></div>';
};
?>
<section id="team" class="relative py-32 bg-gradient-to-b from-gray-50 via-white to-emerald-50 overflow-hidden">
 <div class="absolute inset-0">
  <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,rgba(16,185,129,0.08),transparent_50%)]"></div>
  <div class="absolute inset-0 bg-[radial-gradient(circle_at_70%_80%,rgba(5,150,105,0.08),transparent_50%)]"></div>
 </div>
 <div class="container-custom relative z-10">
  <div class="text-center mb-16 md:mb-24" data-from="o:0" data-section>
   <div class="inline-flex items-center gap-2 md:gap-3 mb-4 md:mb-6 px-4 md:px-8 py-2 md:py-3 bg-gradient-to-r from-emerald-100 via-emerald-50 to-emerald-100 border border-emerald-300 rounded-full" data-from="s:0" data-ease="spring" data-dur="1"><?= icon('flame', 'w-5 h-5 md:w-6 md:h-6 text-emerald-500 animate-pulse') ?><span class="text-emerald-600 font-bold text-sm md:text-lg"><?= t('teamSection.badge') ?></span><?= icon('flame', 'w-5 h-5 md:w-6 md:h-6 text-emerald-500 animate-pulse') ?></div>
   <h2 class="text-3xl md:text-5xl lg:text-6xl xl:text-7xl font-black mb-4 md:mb-8 font-display leading-tight" data-from="o:0;y:30"><span class="inline-block bg-gradient-to-r from-emerald-500 via-teal-500 to-emerald-500 bg-clip-text text-transparent"><?= t('teamSection.title') ?></span></h2>
   <p class="text-base md:text-xl lg:text-2xl text-gray-600 max-w-4xl mx-auto leading-relaxed px-4" data-from="o:0" data-delay=".3"><?= t('teamSection.subtitle') ?></p>
   <div class="mt-8 md:mt-12 flex justify-center" data-from="o:0;s:.9" data-delay=".5" data-ease="spring">
    <div class="relative p-3 md:p-4 rounded-2xl md:rounded-3xl bg-white border border-emerald-200 shadow-xl"><img alt="<?= e(setting('site_name', 'Logo')) ?>" width="280" height="140" class="object-contain w-[200px] md:w-[280px] h-auto" src="<?= e(asset_url(setting('logo', 'assets/images/site-logo.png'))) ?>" style="color: transparent;"></div>
   </div>
  </div>

  <div class="mb-32" data-from="o:0" data-section>
   <?= $groupHead('award', 'teamSection.leadership', 'teamSection.leadershipSub') ?>
   <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 max-w-6xl mx-auto">
    <?php foreach ($byCat['leadership'] as $i => $m): ?>
    <div class="group relative h-full" data-from="o:0;s:.8" data-delay="<?= 0.2 * $i ?>">
     <div class="relative h-full" data-parallax>
      <div class="relative bg-white border-2 border-emerald-200 rounded-3xl overflow-hidden p-8 hover:border-emerald-400 transition-all duration-700 group-hover:shadow-2xl group-hover:shadow-emerald-200 shadow-xl h-full flex flex-col">
       <div class="absolute inset-0 bg-gradient-to-br <?= $m['gradient'] ?> opacity-0 group-hover:opacity-5 transition-opacity duration-700"></div>
       <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-100 rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
       <div class="relative z-10 flex flex-col md:flex-row gap-8 items-center flex-1">
        <div class="relative flex-shrink-0">
         <div class="relative w-40 h-40 rounded-full overflow-hidden border-4 border-emerald-300"><?= $photo($m, 'text-2xl') ?></div>
         <div class="absolute -bottom-2 -right-2 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-full p-3 shadow-lg" data-from="s:0" data-delay=".5" data-ease="spring"><?= icon('award', 'w-6 h-6 text-white') ?></div>
        </div>
        <div class="flex-1 text-center md:text-left flex flex-col">
         <h4 class="text-3xl md:text-4xl font-black mb-3 font-display text-gray-800 hv-x10"><?= e($m['name']) ?></h4>
         <div class="inline-flex items-center gap-3 mb-6 px-6 py-2 bg-emerald-100 border border-emerald-300 rounded-full self-center md:self-start"><?= icon('briefcase', 'w-5 h-5 text-emerald-500') ?><p class="text-lg font-bold text-emerald-600 uppercase tracking-wider"><?= e($m['role']) ?></p></div>
         <div class="space-y-3 mt-auto">
          <a href="tel:<?= e($m['phone']) ?>" class="flex items-center justify-center md:justify-start gap-3 px-6 py-4 bg-gray-50 hover:bg-emerald-50 border border-gray-200 hover:border-emerald-300 rounded-xl transition-all group/btn hv-s105x10 tap95"><?= icon('phone', 'w-5 h-5 text-emerald-500 group-hover/btn:animate-bounce') ?><span class="text-base font-semibold text-gray-700"><?= e($m['phone']) ?></span></a>
          <?php if ($m['email'] !== ''): ?><a href="mailto:<?= e($m['email']) ?>" class="flex items-center justify-center md:justify-start gap-3 px-6 py-4 bg-gray-50 hover:bg-emerald-50 border border-gray-200 hover:border-emerald-300 rounded-xl transition-all group/btn overflow-hidden hv-s102x5 tap95"><?= icon('mail', 'w-5 h-5 text-emerald-500 flex-shrink-0') ?><span class="text-sm font-medium text-gray-700 truncate"><?= e($m['email']) ?></span></a><?php endif; ?>
         </div>
        </div>
       </div>
      </div>
     </div>
    </div>
    <?php endforeach; ?>
   </div>
  </div>

  <div class="mb-32" data-from="o:0" data-section>
   <?= $groupHead('briefcase', 'teamSection.management', 'teamSection.managementSub') ?>
   <div class="grid grid-cols-1 md:grid-cols-2 gap-12 max-w-5xl mx-auto">
    <?php foreach ($byCat['management'] as $i => $m): ?>
    <div class="group relative hv-lift" data-from="o:0;y:50" data-delay="<?= 0.15 * $i ?>">
     <div class="relative bg-white border border-emerald-200 rounded-2xl overflow-hidden p-8 hover:border-emerald-400 transition-all duration-500 shadow-lg hover:shadow-xl">
      <div class="absolute inset-0 bg-gradient-to-br <?= $m['gradient'] ?> opacity-0 group-hover:opacity-5 transition-opacity duration-500"></div>
      <div class="relative w-48 h-48 mx-auto mb-6 rounded-2xl overflow-hidden border-4 border-gray-200"><?= $photo($m, 'text-xl') ?></div>
      <h4 class="text-2xl font-bold text-center mb-2 text-gray-800 group-hover:text-emerald-600 transition-colors font-display"><?= e($m['name']) ?></h4>
      <div class="flex items-center justify-center gap-2 mb-6"><?= icon('briefcase', 'w-4 h-4 text-emerald-500') ?><p class="text-sm text-emerald-600 font-bold uppercase tracking-wide"><?= e($m['role']) ?></p></div>
      <div class="space-y-2">
       <a href="tel:<?= e($m['phone']) ?>" class="flex items-center justify-center gap-2 px-4 py-3 bg-gray-50 hover:bg-emerald-50 border border-gray-200 hover:border-emerald-300 rounded-lg transition-all text-sm"><?= icon('phone', 'w-4 h-4 text-emerald-500') ?><span class="text-gray-700"><?= e($m['phone']) ?></span></a>
       <?php if ($m['email'] !== ''): ?><a href="mailto:<?= e($m['email']) ?>" class="flex items-center justify-center gap-2 px-4 py-3 bg-gray-50 hover:bg-emerald-50 border border-gray-200 hover:border-emerald-300 rounded-lg transition-all text-xs"><?= icon('mail', 'w-4 h-4 text-emerald-500') ?><span class="text-gray-700 break-all"><?= e($m['email']) ?></span></a><?php endif; ?>
      </div>
     </div>
    </div>
    <?php endforeach; ?>
   </div>
  </div>

  <div class="mb-32" data-from="o:0" data-section>
   <?= $groupHead('star', 'teamSection.marketing', 'teamSection.marketingSub') ?>
   <div class="grid grid-cols-1 md:grid-cols-2 gap-12 max-w-5xl mx-auto">
    <?php foreach ($byCat['marketing'] as $i => $m): ?>
    <div class="group" data-from="o:0;x:<?= $i % 2 === 0 ? -50 : 50 ?>" data-delay="<?= 0.2 * $i ?>">
     <div class="relative bg-white rounded-3xl overflow-hidden border border-emerald-200 hover:border-emerald-400 transition-all duration-500 shadow-lg hover:shadow-xl">
      <div class="h-2 bg-gradient-to-r <?= $m['gradient'] ?>"></div>
      <div class="p-8">
       <div class="relative w-40 h-40 mx-auto mb-6">
        <div class="relative w-full h-full rounded-full overflow-hidden border-4 border-emerald-300"><?= $photo($m, 'text-xl') ?></div>
        <div class="absolute -top-2 -right-2 bg-emerald-500 rounded-full p-2 bob"><?= icon('star', 'w-5 h-5 text-white') ?></div>
       </div>
       <h4 class="text-2xl font-bold text-center mb-2 text-gray-800 group-hover:text-emerald-600 transition-colors font-display"><?= e($m['name']) ?></h4>
       <p class="text-center text-emerald-600 font-semibold text-sm uppercase tracking-wider mb-6"><?= e($m['role']) ?></p>
       <div class="space-y-2">
        <a href="tel:<?= e($m['phone']) ?>" class="flex items-center justify-center gap-2 px-4 py-3 bg-gray-50 hover:bg-emerald-50 border border-gray-200 hover:border-emerald-400 rounded-xl transition-all"><?= icon('phone', 'w-4 h-4 text-emerald-500') ?><span class="text-sm text-gray-700"><?= e($m['phone']) ?></span></a>
        <?php if ($m['email'] !== ''): ?><a href="mailto:<?= e($m['email']) ?>" class="flex items-center justify-center gap-2 px-4 py-3 bg-gray-50 hover:bg-emerald-50 border border-gray-200 hover:border-emerald-400 rounded-xl transition-all"><?= icon('mail', 'w-4 h-4 text-emerald-500') ?><span class="text-xs text-gray-700 break-all"><?= e($m['email']) ?></span></a><?php endif; ?>
       </div>
      </div>
     </div>
    </div>
    <?php endforeach; ?>
   </div>
  </div>

  <div class="mb-32" data-from="o:0" data-section>
   <?= $groupHead('flame', 'teamSection.instructors', 'teamSection.instructorsSub') ?>
   <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
    <?php foreach ($byCat['instructors'] as $i => $m): ?>
    <div class="group h-full hv-instructor" data-from="o:0;s:.5" data-delay="<?= 0.1 * $i ?>" data-ease="spring">
     <div class="relative bg-white rounded-2xl overflow-hidden border border-emerald-200 hover:border-emerald-400 transition-all duration-500 p-6 shadow-lg hover:shadow-xl h-full flex flex-col">
      <div class="absolute -top-20 -right-20 w-40 h-40 bg-gradient-to-br <?= $m['gradient'] ?> rounded-full blur-3xl opacity-0 group-hover:opacity-30 transition-opacity duration-500"></div>
      <div class="relative w-32 h-32 mx-auto mb-4 flex-shrink-0">
       <div class="relative w-full h-full rounded-full overflow-hidden border-4 border-emerald-300"><?= $photo($m, 'text-lg') ?></div>
       <div class="absolute -bottom-1 -right-1 bg-gradient-to-br from-emerald-500 to-teal-500 rounded-full p-2"><?= icon('flame', 'w-4 h-4 text-white') ?></div>
      </div>
      <h4 class="text-lg font-bold text-center mb-1 text-gray-800 group-hover:text-emerald-600 transition-colors font-display"><?= e($m['name']) ?></h4>
      <p class="text-center text-xs text-emerald-600 font-semibold uppercase tracking-wider mb-4 min-h-[32px] flex items-center justify-center"><?= e($m['role']) ?></p>
      <div class="space-y-2 mt-auto">
       <a href="tel:<?= e($m['phone']) ?>" class="flex items-center justify-center gap-2 px-3 py-2 bg-gray-50 hover:bg-emerald-50 border border-gray-200 hover:border-emerald-300 rounded-lg transition-all text-xs"><?= icon('phone', 'w-3 h-3 text-emerald-500') ?><span class="text-gray-700"><?= e($m['phone']) ?></span></a>
       <?php if ($m['email'] !== ''): ?><a href="mailto:<?= e($m['email']) ?>" class="flex items-center justify-center gap-1 px-3 py-2 bg-gray-50 hover:bg-emerald-50 border border-gray-200 hover:border-emerald-300 rounded-lg transition-all min-h-[36px]"><?= icon('mail', 'w-3 h-3 text-emerald-500 flex-shrink-0') ?><span class="text-[10px] text-gray-700 break-all"><?= e($m['email']) ?></span></a><?php else: ?><div class="min-h-[36px]"></div><?php endif; ?>
      </div>
     </div>
    </div>
    <?php endforeach; ?>
   </div>
  </div>

  <div data-from="o:0" data-section>
   <?= $groupHead('briefcase', 'teamSection.technical', 'teamSection.technicalSub') ?>
   <div class="grid grid-cols-1 md:grid-cols-2 gap-12 max-w-5xl mx-auto">
    <?php foreach ($byCat['technical'] as $i => $m): ?>
    <div class="group hv-lift" data-from="o:0;y:30" data-delay="<?= 0.2 * $i ?>">
     <div class="relative bg-white rounded-2xl overflow-hidden border border-emerald-200 hover:border-emerald-400 transition-all duration-500 p-6 shadow-lg hover:shadow-xl">
      <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r <?= $m['gradient'] ?>"></div>
      <div class="flex items-center gap-6">
       <div class="relative w-32 h-32 flex-shrink-0"><div class="relative w-full h-full rounded-xl overflow-hidden border-2 border-gray-200"><?= $photo($m, 'text-lg') ?></div></div>
       <div class="flex-1">
        <h4 class="text-xl font-bold mb-2 text-gray-800 group-hover:text-emerald-600 transition-colors font-display"><?= e($m['name']) ?></h4>
        <div class="flex items-center gap-2 mb-4"><?= icon('briefcase', 'w-4 h-4 text-emerald-500') ?><p class="text-sm text-emerald-600 font-semibold uppercase"><?= e($m['role']) ?></p></div>
        <div class="space-y-2">
         <a href="tel:<?= e($m['phone']) ?>" class="flex items-center gap-2 text-sm text-gray-600 hover:text-emerald-600 transition-colors"><?= icon('phone', 'w-4 h-4') ?><?= e($m['phone']) ?></a>
         <?php if ($m['email'] !== ''): ?><a href="mailto:<?= e($m['email']) ?>" class="flex items-center gap-2 text-xs text-gray-600 hover:text-emerald-600 transition-colors break-all"><?= icon('mail', 'w-4 h-4 flex-shrink-0') ?><?= e($m['email']) ?></a><?php endif; ?>
        </div>
       </div>
      </div>
     </div>
    </div>
    <?php endforeach; ?>
   </div>
  </div>
 </div>
</section>
