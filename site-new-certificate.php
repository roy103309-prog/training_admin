<?php
require __DIR__ . '/includes/site-bootstrap.php';
$navSolid = true;
$extraCss = ['assets/css/site-app-pages.css'];
$pageTitle = tr('cert.newTitle') . ' | ' . tr('brand.full');
require __DIR__ . '/includes/site-header.php';
$infoCard = function (string $key, string $label, string $iconName, string $color, string $extra = ''): string {
    return '<div class="p-5 bg-gradient-to-br from-' . $color . '-50 to-' . $color . '-100 border-2 border-' . $color . '-200 rounded-xl shadow-sm ' . $extra . '" data-wrap="' . $key . '"><div class="flex items-center gap-2 text-sm text-' . $color . '-700 font-medium mb-2">' . icon($iconName, 'w-4 h-4') . '<span>' . $label . '</span></div><p class="text-xl font-bold text-gray-900' . ($key === 'address' ? ' whitespace-pre-line' : '') . '" data-f="' . $key . '"></p></div>';
};
?>
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 pt-32 pb-20">
 <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-5xl">
  <a href="<?= e(url('certificate')) ?>"><button type="button" class="flex items-center gap-2 text-gray-600 hover:text-blue-600 mb-8 transition-colors hv-xm5"><?= icon('arrow-left', 'w-5 h-5') ?><span><?= t('cert.backToOld') ?></span></button></a>
  <div class="bg-white rounded-3xl shadow-xl border-2 border-blue-100 overflow-hidden" data-from="o:0;y:20" data-onload>
   <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-8 text-white">
    <div class="flex items-center gap-4 mb-4">
     <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center"><?= icon('award', 'w-8 h-8') ?></div>
     <div><h1 class="text-3xl font-bold"><?= t('cert.newTitle') ?></h1><p class="text-blue-100"><?= t('cert.newSubtitle') ?></p></div>
    </div>
   </div>
   <div class="p-6 sm:p-8">
    <form class="space-y-6 mb-8" id="newCertForm" data-endpoint="<?= e(asset_url('api/site-new-certificate.php')) ?>">
     <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
      <div><label class="block text-sm font-semibold text-gray-700 mb-2"><?= t('cert.rollNumber') ?></label><input type="text" name="studentId" placeholder="Enter Roll Number" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:outline-none transition-colors"></div>
      <div><label class="block text-sm font-semibold text-gray-700 mb-2"><?= t('cert.dob') ?></label><input type="date" name="dob" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:outline-none transition-colors"></div>
     </div>
     <div class="p-4 bg-red-50 border border-red-200 rounded-xl text-red-600" data-error hidden></div>
     <button type="submit" data-submit class="w-full flex items-center justify-center gap-2 px-6 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold text-lg shadow-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed hv-scale102 tap98"><span data-idle class="flex items-center gap-2"><?= icon('search', 'w-5 h-5') ?><?= t('cert.search') ?></span><span data-busy hidden class="flex items-center gap-2"><span class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></span><?= t('cert.searching') ?></span></button>
    </form>
    <div class="space-y-8" data-result hidden>
     <div class="border-t-2 border-gray-200 pt-6">
      <div class="mb-8" data-wrap="photo">
       <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2"><?= icon('user', 'w-5 h-5 text-blue-600') ?><?= t('cert.studentPhoto') ?></h3>
       <div class="flex justify-center"><div class="bg-white border-2 border-blue-100 rounded-2xl shadow-lg p-4 sm:p-6 w-full max-w-xs sm:max-w-sm"><div class="relative w-full aspect-square rounded-2xl overflow-hidden bg-gradient-to-br from-blue-50 to-purple-50"><img alt="Student photo" class="object-cover" style="position:absolute;height:100%;width:100%;inset:0" data-f="photo"></div></div></div>
      </div>
      <div class="mb-8">
       <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2"><?= icon('id-card', 'w-6 h-6 text-blue-600') ?><?= t('cert.studentInfo') ?></h3>
       <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
        <?= $infoCard('name', t('cert.studentName'), 'user', 'blue') ?>
        <?= $infoCard('roll', t('cert.rollNumber'), 'id-card', 'purple') ?>
        <?= $infoCard('father', t('cert.fatherName'), 'user', 'green') ?>
        <?= $infoCard('mother', t('cert.motherName'), 'users', 'pink') ?>
        <?= $infoCard('address', t('cert.address'), 'house', 'amber', 'sm:col-span-2') ?>
       </div>
      </div>
      <div class="space-y-4" data-wrap="cert">
       <h3 class="text-2xl font-bold text-gray-900 flex items-center gap-2"><?= icon('award', 'w-6 h-6 text-blue-600') ?><?= t('cert.certificate') ?></h3>
       <div class="relative w-full aspect-[1.414/1] bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl overflow-hidden border-2 border-gray-200 shadow-lg max-h-[80vh] min-h-[260px] sm:min-h-[320px]"><img alt="Certificate" class="object-contain p-4" style="position:absolute;height:100%;width:100%;inset:0" data-f="cert"></div>
      </div>
     </div>
    </div>
   </div>
  </div>
 </div>
</div>
<?php require __DIR__ . '/includes/site-footer.php'; ?>
