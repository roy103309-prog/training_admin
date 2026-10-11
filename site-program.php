<?php
require __DIR__ . '/includes/site-bootstrap.php';
$slug = (string)($_GET['id'] ?? '');
$program = $slug !== '' ? db_one('SELECT * FROM programs WHERE slug = ? AND is_active = 1', [$slug]) : null;
if (!$program) {
    http_response_code(404);
}
$navSolid = true;
$extraCss = ['assets/css/site-app-pages.css'];
$pageTitle = ($program ? $program['title'] . ' | ' : 'Program not found | ') . tr('brand.full');
require __DIR__ . '/includes/site-header.php';
$others = db_all('SELECT * FROM programs WHERE is_active = 1 AND show_on_home = 1 AND slug <> ? ORDER BY sort_order LIMIT 3', [$slug]);
$waNumber = setting('whatsapp_number', '8801748003338');
?>
<div class="min-h-screen bg-gradient-to-br from-emerald-50 via-white to-teal-50 pt-28 pb-20">
 <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-6xl">
  <a href="<?= e(url('#programs')) ?>"><button type="button" class="flex items-center gap-2 text-gray-600 hover:text-emerald-600 mb-8 transition-colors hv-xm5 lb"><?= icon('arrow-left', 'w-5 h-5') ?><?= t('program.back') ?></button></a>
  <?php if (!$program): ?>
  <div class="bg-white rounded-3xl shadow-xl border border-emerald-100 p-12 text-center"><h1 class="text-3xl font-bold text-dark-900 mb-2">404</h1><p class="text-dark-600">This page could not be found.</p></div>
  <?php else: ?>
  <div class="relative rounded-3xl overflow-hidden shadow-2xl mb-12" data-from="o:0;y:20" data-onload>
   <img src="<?= e(asset_url($program['image'])) ?>" alt="<?= e($program['title']) ?>" class="w-full h-[320px] md:h-[440px] object-cover">
   <div class="absolute inset-0 bg-gradient-to-t from-dark-900 via-dark-900/50 to-transparent"></div>
   <div class="absolute bottom-0 left-0 right-0 p-6 md:p-10">
    <?php if ($program['level'] !== ''): ?><span class="inline-block mb-4 px-4 py-2 rounded-full bg-gradient-to-r <?= e($program['color']) ?> text-white text-sm font-semibold shadow-lg"><?= e($program['level']) ?></span><?php endif; ?>
    <h1 class="text-3xl md:text-5xl font-bold text-white mb-2"><?php if ($program['title_bn'] !== ''): ?><?= tx($program['title_bn'], $program['title']) ?><?php else: ?><?= e($program['title']) ?><?php endif; ?></h1>
    <?php if ($program['subtitle'] !== ''): ?><p class="text-dark-200 text-lg"><?= e($program['subtitle']) ?></p><?php endif; ?>
   </div>
  </div>
  <div class="grid lg:grid-cols-3 gap-8 mb-16">
   <div class="lg:col-span-2 bg-white rounded-3xl shadow-xl border border-emerald-100 p-8" data-from="o:0;x:-50">
    <?php if (trim((string)$program['description']) !== ''): ?>
    <div class="text-lg text-dark-600 leading-relaxed whitespace-pre-line"><?= e($program['description']) ?></div>
    <?php else: ?>
    <h2 class="text-2xl md:text-3xl font-bold text-dark-900 mb-4 lb"><?= t('program.enroll') ?></h2>
    <p class="text-lg text-dark-600 leading-relaxed lb"><?= t('program.enrollText') ?></p>
    <?php endif; ?>
    <div class="grid sm:grid-cols-2 gap-4 mt-8">
     <?php if ($program['duration'] !== ''): ?><div class="p-4 bg-emerald-50 rounded-xl"><div class="text-sm text-emerald-700 font-medium lb"><?= t('programs.duration') ?></div><div class="text-xl font-bold text-dark-900"><?= e($program['duration']) ?></div></div><?php endif; ?>
     <?php if ($program['price'] !== ''): ?><div class="p-4 bg-teal-50 rounded-xl"><div class="text-sm text-teal-700 font-medium lb"><?= t('programs.price') ?></div><div class="text-xl font-bold text-dark-900"><?= e($program['price']) ?></div></div><?php endif; ?>
    </div>
   </div>
   <div class="space-y-4" data-from="o:0;x:50">
    <a href="tel:<?= e(setting('call_number', '+8801712577508')) ?>" class="flex items-center justify-center gap-3 w-full px-6 py-4 bg-gradient-to-r from-emerald-600 to-emerald-500 text-white rounded-2xl font-semibold shadow-lg hover:shadow-xl transition-all lb hv-scale102"><?= icon('phone', 'w-5 h-5') ?><?= t('program.callNow') ?></a>
    <a href="https://wa.me/<?= e($waNumber) ?>?text=<?= rawurlencode(setting('whatsapp_message') . ' (' . $program['title'] . ')') ?>" target="_blank" rel="noopener" class="flex items-center justify-center gap-3 w-full px-6 py-4 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-2xl font-semibold shadow-lg hover:shadow-xl transition-all lb hv-scale102"><?= icon('send', 'w-5 h-5') ?><?= t('program.whatsapp') ?></a>
    <div class="p-6 bg-white rounded-2xl shadow-lg border border-emerald-100">
     <div class="flex items-center gap-3 mb-3 text-dark-900 font-bold"><?= icon('map-pin', 'w-5 h-5 text-emerald-500') ?><span class="lb"><?= t('contact.address') ?></span></div>
     <p class="text-dark-600 text-sm lb"><?= tx(setting('address_bn'), setting('address_en')) ?></p>
    </div>
   </div>
  </div>
  <?php if ($others): ?>
  <h2 class="text-2xl md:text-3xl font-bold text-dark-900 mb-8 lb"><?= t('program.others') ?></h2>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-8" data-stagger=".1">
   <?php foreach ($others as $p): ?>
   <a href="<?= e(url('programs/' . $p['slug'])) ?>" class="relative group hv-lift block" data-from="o:0;y:60" data-variant>
    <div class="relative bg-white rounded-3xl overflow-hidden shadow-xl hover:shadow-2xl transition-all duration-500">
     <div class="relative h-48 overflow-hidden">
      <img alt="<?= e($p['title']) ?>" loading="lazy" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" src="<?= e(asset_url($p['image'])) ?>">
      <div class="absolute inset-0 bg-gradient-to-t from-dark-900 via-dark-900/50 to-transparent"></div>
      <div class="absolute bottom-0 left-0 right-0 p-5"><h3 class="text-lg font-bold text-white"><?= e($p['title']) ?></h3></div>
     </div>
    </div>
   </a>
   <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
 </div>
</div>
<?php require __DIR__ . '/includes/site-footer.php'; ?>
