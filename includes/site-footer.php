<?php defined('SITE_ROOT') || exit; ?>
<?php
$logo = asset_url(setting('logo', 'assets/images/site-logo.png'));
$footerPrograms = [
    ['bn' => 'অ্যাডভান্সড MIG/TIG', 'en' => 'Advanced MIG/TIG', 'slug' => 'advanced-mig-tig'],
    ['bn' => 'পাইপ ওয়েল্ডিং', 'en' => 'Pipe Welding', 'slug' => 'pipe-welding'],
    ['bn' => 'স্টিল ফিটিংস', 'en' => 'Steel Fittings', 'slug' => 'steel-fittings'],
    ['bn' => 'কনস্ট্রাকশন ট্রেনিং', 'en' => 'Construction Training', 'slug' => 'construction-training'],
];
$footerCompany = [
    ['key' => 'footer.aboutUs', 'href' => url('#about')],
    ['key' => 'footer.ourTeam', 'href' => url('#team')],
    ['key' => 'footer.contact', 'href' => url('#contact')],
];
$phones = trim(setting('phone_primary', '01712-577508') . (setting('phone_secondary') !== '' ? ' , ' . setting('phone_secondary') : ''));
?>
 </div>
</div>
<footer class="relative overflow-hidden">
 <div class="absolute inset-0 bg-gradient-to-br from-slate-900 via-slate-800 to-zinc-900"></div>
 <div class="absolute top-0 left-1/4 w-96 h-96 bg-gradient-to-br from-violet-600/20 to-purple-600/10 rounded-full blur-3xl"></div>
 <div class="absolute bottom-0 right-1/4 w-80 h-80 bg-gradient-to-br from-cyan-500/15 to-blue-500/10 rounded-full blur-3xl"></div>
 <div class="absolute top-1/2 right-0 w-64 h-64 bg-gradient-to-br from-emerald-500/10 to-teal-500/10 rounded-full blur-3xl"></div>
 <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.02)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.02)_1px,transparent_1px)] bg-[size:50px_50px]"></div>
 <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
  <div class="py-16 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12">
   <div class="lg:col-span-2">
    <a href="<?= e(url('')) ?>">
     <div class="flex items-center gap-4 mb-8 cursor-pointer hv-scale102">
      <div class="relative w-20 h-20 rounded-2xl overflow-hidden shadow-2xl border-2 border-emerald-500/30 bg-white/10 backdrop-blur-sm p-1"><img alt="New Life Logo" class="object-contain" style="position:absolute;height:100%;width:100%;left:0;top:0;right:0;bottom:0;color:transparent" src="<?= e($logo) ?>"></div>
      <div>
       <div class="text-2xl font-bold text-white"><?= e(setting('site_name', 'New Life')) ?></div>
       <div class="text-sm text-emerald-400 font-medium lb"><?= t('footer.brandSub') ?></div>
      </div>
     </div>
    </a>
    <div class="space-y-3">
     <div class="flex items-start gap-4 p-4 bg-white/5 backdrop-blur-sm rounded-xl border border-white/10 hover:border-emerald-500/30 transition-all hv-x5">
      <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center flex-shrink-0 shadow-lg"><?= icon('map-pin', 'w-5 h-5 text-white') ?></div>
      <div>
       <div class="text-xs text-emerald-400 font-semibold mb-1 lb"><?= t('footer.address') ?></div>
       <div class="text-gray-300 text-sm lb"><?= tx(setting('address_bn'), setting('address_en')) ?></div>
      </div>
     </div>
     <a href="tel:<?= e(setting('call_number', '+8801712577508')) ?>" class="flex items-center gap-4 p-4 bg-white/5 backdrop-blur-sm rounded-xl border border-white/10 hover:border-emerald-500/30 transition-all hv-x5">
      <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center flex-shrink-0 shadow-lg"><?= icon('phone', 'w-5 h-5 text-white') ?></div>
      <div>
       <div class="text-xs text-emerald-400 font-semibold mb-1 lb"><?= t('footer.phone') ?></div>
       <div class="text-gray-300 font-medium"><?= e($phones) ?></div>
      </div>
     </a>
     <a href="mailto:<?= e(setting('email')) ?>" class="flex items-center gap-4 p-4 bg-white/5 backdrop-blur-sm rounded-xl border border-white/10 hover:border-emerald-500/30 transition-all hv-x5">
      <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center flex-shrink-0 shadow-lg"><?= icon('mail', 'w-5 h-5 text-white') ?></div>
      <div>
       <div class="text-xs text-emerald-400 font-semibold mb-1 lb"><?= t('footer.email') ?></div>
       <div class="text-gray-300 text-sm"><?= e(setting('email')) ?></div>
      </div>
     </a>
    </div>
    <div class="mt-8 flex gap-3">
     <?php if (setting('facebook_url') !== ''): ?><a href="<?= e(setting('facebook_url')) ?>" target="_blank" rel="noopener noreferrer" class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center shadow-lg hover:shadow-blue-500/30 transition-all hv-social" aria-label="Facebook"><?= icon('facebook', 'w-5 h-5 text-white') ?></a><?php endif; ?>
     <?php if (setting('youtube_url') !== ''): ?><a href="<?= e(setting('youtube_url')) ?>" target="_blank" rel="noopener noreferrer" class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-500 to-red-700 flex items-center justify-center shadow-lg hover:shadow-red-500/30 transition-all hv-social" aria-label="YouTube"><?= icon('youtube', 'w-5 h-5 text-white') ?></a><?php endif; ?>
     <?php if (setting('website_url') !== ''): ?><a href="<?= e(setting('website_url')) ?>" target="_blank" rel="noopener noreferrer" class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg hover:shadow-emerald-500/30 transition-all hv-social" aria-label="Website"><?= icon('globe', 'w-5 h-5 text-white') ?></a><?php endif; ?>
    </div>
   </div>
   <div>
    <h3 class="text-lg font-bold mb-6 text-white flex items-center gap-3 lb"><span class="w-10 h-1 bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full"></span><?= t('footer.programs') ?></h3>
    <ul class="space-y-4">
     <?php foreach ($footerPrograms as $p): ?>
     <li><a href="<?= e(url('programs/' . $p['slug'])) ?>"><div class="text-gray-400 hover:text-emerald-400 transition-all flex items-center gap-2 group lb hv-x8"><?= icon('arrow-right', 'w-4 h-4 opacity-0 group-hover:opacity-100 transition-opacity text-emerald-400') ?><?= tx($p['bn'], $p['en']) ?></div></a></li>
     <?php endforeach; ?>
    </ul>
   </div>
   <div>
    <h3 class="text-lg font-bold mb-6 text-white flex items-center gap-3 lb"><span class="w-10 h-1 bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full"></span><?= t('footer.company') ?></h3>
    <ul class="space-y-4">
     <?php foreach ($footerCompany as $c): ?>
     <li><a href="<?= e($c['href']) ?>"><div class="text-gray-400 hover:text-emerald-400 transition-all flex items-center gap-2 group lb hv-x8"><?= icon('arrow-right', 'w-4 h-4 opacity-0 group-hover:opacity-100 transition-opacity text-emerald-400') ?><?= t($c['key']) ?></div></a></li>
     <?php endforeach; ?>
    </ul>
    <div class="mt-8"><a href="<?= e(url('#contact')) ?>"><button type="button" class="w-full px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold text-sm shadow-lg hover:shadow-emerald-500/30 transition-all flex items-center justify-center gap-2 lb hv-lift2 tap98"><?= t('footer.getInTouch') ?><?= icon('arrow-right', 'w-4 h-4') ?></button></a></div>
   </div>
  </div>
  <div class="border-t border-white/10 py-6">
   <div class="flex flex-col items-center gap-4 text-center">
    <p class="text-gray-500 text-sm lb">© <?= date('Y') ?> New Life Welding Training Center. <?= t('footer.rights') ?></p>
    <div class="flex items-center gap-2 text-sm text-gray-500"><span><?= t('footer.poweredBy') ?></span><a href="<?= e(setting('developer_url', 'https://zyrotech.io')) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 text-emerald-400 hover:text-emerald-300 font-semibold transition-colors hv-scale105"><?= e(setting('developer_name', 'Zyrotech Bangladesh')) ?><?= icon('external-link', 'w-3.5 h-3.5') ?></a></div>
   </div>
  </div>
 </div>
</footer>
<div class="toast-wrap" id="toastWrap"></div>
<script src="https://unpkg.com/lenis@1.1.13/dist/lenis.min.js" defer></script>
<script src="<?= e(asset_url('assets/js/site.js')) ?>?v=2" defer></script>
</body>
</html>
