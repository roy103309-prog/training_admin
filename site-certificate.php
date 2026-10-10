<?php
require __DIR__ . '/includes/site-bootstrap.php';
$navSolid = true;
$extraCss = ['assets/css/site-app-pages.css'];
$pageTitle = 'Old Certificate Verification | New Life Welding Training Center';
require __DIR__ . '/includes/site-header.php';
?>
<div class="min-h-screen bg-gradient-to-br from-emerald-50 via-white to-blue-50 pt-32 pb-20">
 <div class="container mx-auto px-4 max-w-5xl">
  <div class="bg-white rounded-3xl shadow-xl border border-emerald-100 overflow-hidden" data-from="o:0;y:20" data-onload>
   <div class="bg-gradient-to-r from-emerald-600 to-emerald-500 p-8 text-white">
    <div class="flex items-center gap-4">
     <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center"><?= icon('file-check', 'w-7 h-7') ?></div>
     <div>
      <h1 class="text-3xl font-bold">Old Certificate Verification</h1>
      <p class="text-emerald-100 text-sm">Enter Roll &amp; Registration Number</p>
     </div>
    </div>
   </div>
   <div class="p-8">
    <form class="space-y-6" id="oldCertForm" data-endpoint="<?= e(asset_url('api/site-old-certificate.php')) ?>">
     <div class="grid md:grid-cols-2 gap-6">
      <div><label class="block text-sm font-semibold mb-1">Roll Number</label><input name="rollNumber" required class="w-full px-4 py-3 border rounded-xl" placeholder="Enter Roll Number"></div>
      <div><label class="block text-sm font-semibold mb-1">Registration Number</label><input name="regNumber" required class="w-full px-4 py-3 border rounded-xl" placeholder="Enter Registration Number"></div>
     </div>
     <div class="p-4 bg-red-50 border border-red-200 rounded-xl text-red-600" data-error hidden></div>
     <button type="submit" class="w-full flex items-center justify-center gap-2 py-4 bg-emerald-600 text-white rounded-xl font-semibold" data-submit><span data-idle class="flex items-center gap-2"><?= icon('search', 'w-5 h-5') ?>Search Certificate</span><span data-busy hidden>Searching…</span></button>
    </form>
    <div class="mt-10 space-y-8" data-result hidden>
     <h2 class="text-2xl font-bold flex items-center gap-2"><?= icon('award', 'w-6 h-6 text-emerald-600') ?>Student Details</h2>
     <div class="grid md:grid-cols-2 gap-6">
      <div class="p-4 bg-emerald-50 rounded-xl"><?= icon('user', 'inline w-4 h-4 mr-2') ?><span data-f="name"></span></div>
      <div class="p-4 bg-blue-50 rounded-xl"><?= icon('calendar', 'inline w-4 h-4 mr-2') ?><span data-f="dob"></span></div>
      <div class="p-4 bg-purple-50 rounded-xl">Roll: <span data-f="roll"></span></div>
      <div class="p-4 bg-orange-50 rounded-xl">Reg: <span data-f="reg"></span></div>
      <div class="md:col-span-2 p-4 bg-pink-50 rounded-xl" data-wrap="address"><?= icon('map-pin', 'inline w-4 h-4 mr-2') ?><span data-f="address"></span></div>
     </div>
     <div class="relative w-full aspect-[1.414/1] bg-gray-100 rounded-xl overflow-hidden" data-wrap="img"><img alt="Certificate" class="object-contain" style="position:absolute;height:100%;width:100%;inset:0" data-f="img"></div>
    </div>
   </div>
  </div>
 </div>
</div>
<?php require __DIR__ . '/includes/site-footer.php'; ?>
