<?php defined('SITE_ROOT') || exit; ?>
<?php
/** @var string $pageTitle  @var bool $navSolid  @var array $extraCss */
$pageTitle = $pageTitle ?? setting('site_title', tr('brand.full'));
$navSolid = $navSolid ?? false;
$extraCss = $extraCss ?? [];
$logo = asset_url(setting('logo', 'assets/images/site-logo.png'));
$callNumber = setting('call_number', '+8801712577508');
$waNumber = setting('whatsapp_number', '8801748003338');
$waText = setting('whatsapp_message', '');
$navPrograms = programs(true);
$l = lang();
?><!DOCTYPE html>
<html lang="bn" data-lang="bn" data-scroll-behavior="smooth" class="light" style="color-scheme: light;">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e(setting('site_description')) ?>">
<meta name="author" content="<?= e(tr('brand.full')) ?>">
<meta name="keywords" content="<?= e(setting('site_keywords')) ?>">
<meta property="og:title" content="<?= e(setting('site_title', tr('brand.full'))) ?>">
<meta property="og:description" content="<?= e(setting('site_description')) ?>">
<meta property="og:type" content="website">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="<?= e(setting('site_title', tr('brand.full'))) ?>">
<meta name="twitter:description" content="<?= e(setting('site_description')) ?>">
<link rel="icon" href="<?= e(asset_url(setting('favicon', 'assets/images/site-favicon.png'))) ?>">
<link rel="preload" href="<?= e(asset_url('assets/fonts/1f95d4beca86b0f6-s.p.55651a95.woff2')) ?>" as="font" crossorigin type="font/woff2">
<link rel="preload" href="<?= e(asset_url('assets/fonts/7ca7a04daf1e7f98-s.p.4c5efc22.woff2')) ?>" as="font" crossorigin type="font/woff2">
<link rel="preload" href="<?= e(asset_url('assets/fonts/83afe278b6a6bb3c-s.p.3a6ba036.woff2')) ?>" as="font" crossorigin type="font/woff2">
<link rel="stylesheet" href="<?= e(asset_url('assets/css/site-app.css')) ?>">
<?php foreach ($extraCss as $css): ?><link rel="stylesheet" href="<?= e(asset_url($css)) ?>">
<?php endforeach; ?>
<link rel="stylesheet" href="<?= e(asset_url('assets/css/site-styles.css')) ?>">
<link rel="stylesheet" href="<?= e(asset_url('assets/css/site.css')) ?>?v=2">
<script>
  window.SITE = { base: <?= json_encode(base_path()) ?>, i18n: <?= json_encode($l, JSON_UNESCAPED_UNICODE) ?> };
  document.documentElement.classList.add('js-anim');
  (function () { try { var l = localStorage.getItem('nl_lang'); if (l === 'en') { document.documentElement.setAttribute('data-lang', 'en'); document.documentElement.lang = 'en'; } } catch (e) {} })();
</script>
</head>
<body class="inter_c15e96cb-module__0bjUvq__variable hind_siliguri_b053eb27-module__yRVq_a__variable antialiased">
<div class="hidden lg:block fixed w-4 h-4 bg-emerald-500 rounded-full pointer-events-none z-[10000] mix-blend-difference" id="cursorDot" style="transform: translateX(-8px) translateY(-8px);"></div>
<div class="hidden lg:block fixed w-8 h-8 border-2 border-emerald-500 rounded-full pointer-events-none z-[10000] mix-blend-difference" id="cursorRing" style="transform: translateX(-16px) translateY(-16px);"></div>
<div class="fixed top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 via-teal-500 to-emerald-600 origin-left z-[9999]" id="scrollProgress" style="transform: scaleX(0);"></div>

<nav id="siteNav" data-solid="<?= $navSolid ? '1' : '0' ?>" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300<?= $navSolid ? ' border-b border-emerald-100 shadow-sm is-scrolled' : '' ?>" style="background-color:<?= $navSolid ? 'rgba(255, 255, 255, 0.95)' : 'rgba(0, 0, 0, 0)' ?>;backdrop-filter:<?= $navSolid ? 'blur(20px)' : 'blur(0px)' ?>">
 <div class="container mx-auto px-4 sm:px-6 lg:px-8">
  <div class="flex items-center justify-between h-20">
   <a href="<?= e(url('')) ?>">
    <div class="flex items-center gap-3 cursor-pointer hv-scale105">
     <div class="relative w-12 h-12 rounded-xl overflow-hidden shadow-lg border-2 border-emerald-500/30 bg-white/5 backdrop-blur-sm hv-rot5"><img alt="<?= e(setting('site_name', 'Logo')) ?>" class="object-contain p-1" style="position:absolute;height:100%;width:100%;left:0;top:0;right:0;bottom:0;color:transparent" src="<?= e($logo) ?>"></div>
     <div>
      <div class="text-xl font-bold transition-colors duration-300 nav-brand"><?= e(setting('site_name', 'New Life')) ?></div>
      <div class="text-xs transition-colors duration-300 nav-brand-sub"><?= e(setting('site_tagline', 'Welding Training Center')) ?></div>
     </div>
    </div>
   </a>
   <?php
   $navItems = [
       ['key' => 'nav.home', 'href' => url('#home')],
       ['key' => 'nav.about', 'href' => url('#about')],
       ['key' => 'nav.programs', 'href' => url('#programs'), 'sub' => array_map(fn($p) => ['label' => $p['title'], 'href' => url('programs/' . $p['slug'])], $navPrograms)],
       ['key' => 'nav.videos', 'href' => url('#videos')],
       ['key' => 'nav.team', 'href' => url('#team')],
       ['key' => 'nav.contact', 'href' => url('#contact')],
       ['key' => 'nav.certificate', 'href' => url('certificate'), 'sub' => [['key' => 'nav.oldCertificate', 'href' => url('certificate')], ['key' => 'nav.newCertificate', 'href' => url('new-certificate')]]],
   ];
   ?>
   <div class="hidden lg:flex items-center gap-2">
    <?php foreach ($navItems as $i => $item): ?>
    <div class="relative group nav-item"<?= !empty($item['sub']) ? ' data-dropdown' : '' ?>>
     <a href="<?= e($item['href']) ?>"><button class="px-4 py-2 font-medium rounded-lg transition-all flex items-center gap-1 lb nav-link hv-scale105"><?= isset($item['key']) ? t($item['key']) : e($item['label']) ?><?php if (!empty($item['sub'])): ?><div class="nav-chevron"><?= icon('chevron-down', 'w-4 h-4') ?></div><?php endif; ?></button></a>
     <?php if (!empty($item['sub'])): ?>
     <div class="nav-dropdown absolute top-full left-0 mt-2 w-64 bg-white rounded-2xl shadow-2xl border border-emerald-100 overflow-hidden z-50">
      <?php foreach ($item['sub'] as $j => $sub): ?>
      <a href="<?= e($sub['href']) ?>"><div class="nav-dd-item px-6 py-3 text-gray-700 hover:text-emerald-600 text-sm border-b border-emerald-50 last:border-0 transition-all cursor-pointer lb" style="--i:<?= $j ?>"><?= isset($sub['key']) ? t($sub['key']) : e($sub['label']) ?></div></a>
      <?php endforeach; ?>
     </div>
     <?php endif; ?>
    </div>
    <?php endforeach; ?>
   </div>
   <div class="hidden lg:flex items-center gap-3">
    <button type="button" class="flex items-center gap-2 px-4 py-2 rounded-full font-medium transition-all nav-lang hv-scale105 tap95" data-lang-toggle><?= icon('globe', 'w-4 h-4') ?><span class="lb"><?= t('nav.langBtn') ?></span></button>
    <a href="tel:<?= e($callNumber) ?>"><button type="button" class="flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-emerald-500 text-white rounded-full font-semibold text-sm shadow-lg hover:shadow-xl transition-all hv-scale105 tap95"><?= icon('phone', 'w-4 h-4') ?><span class="lb"><?= t('nav.callNow') ?></span></button></a>
   </div>
   <button type="button" id="mobileMenuBtn" aria-label="Menu" class="lg:hidden w-10 h-10 rounded-lg backdrop-blur-lg flex items-center justify-center border transition-all duration-300 nav-burger tap90"><span class="burger-open"><?= icon('menu', 'w-6 h-6') ?></span><span class="burger-close"><?= icon('x', 'w-6 h-6') ?></span></button>
  </div>
  <div id="mobileMenu" class="lg:hidden overflow-hidden mobile-menu">
   <div class="pb-6 bg-white/98 backdrop-blur-xl rounded-2xl mt-2 shadow-2xl border border-emerald-100">
    <?php foreach ($navItems as $item): ?>
    <div class="border-b border-emerald-50 last:border-0">
     <div class="flex items-center justify-between">
      <a href="<?= e($item['href']) ?>" class="flex-1"<?= empty($item['sub']) ? ' data-close-menu' : '' ?>><div class="px-4 py-4 text-gray-700 hover:text-emerald-600 font-medium lb"><?= isset($item['key']) ? t($item['key']) : e($item['label']) ?></div></a>
      <?php if (!empty($item['sub'])): ?><button type="button" class="px-4 py-4 m-sub-toggle tap90"><div class="m-chevron"><?= icon('chevron-down', 'w-5 h-5 text-gray-500') ?></div></button><?php endif; ?>
     </div>
     <?php if (!empty($item['sub'])): ?>
     <div class="overflow-hidden bg-emerald-50/50 m-sub">
      <div class="pl-6 pr-4 py-2 space-y-1">
       <?php foreach ($item['sub'] as $sub): ?>
       <a href="<?= e($sub['href']) ?>" data-close-menu><div class="py-3 px-4 text-sm text-gray-600 hover:text-emerald-600 hover:bg-white/50 rounded-lg transition-all lb"><?= isset($sub['key']) ? t($sub['key']) : e($sub['label']) ?></div></a>
       <?php endforeach; ?>
      </div>
     </div>
     <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <div class="px-4 pt-4 space-y-3">
     <button type="button" data-lang-toggle class="w-full flex items-center justify-center gap-2 px-5 py-3 rounded-xl font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition-all tap98"><?= icon('globe', 'w-5 h-5') ?><span class="lb"><?= t('nav.langBtnMobile') ?></span></button>
     <a href="tel:<?= e($callNumber) ?>" class="block"><button type="button" class="w-full flex items-center justify-center gap-2 px-5 py-3 bg-gradient-to-r from-emerald-600 to-emerald-500 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all lb tap98"><?= icon('phone', 'w-5 h-5') ?><?= t('nav.callNow') ?></button></a>
    </div>
   </div>
  </div>
 </div>
</nav>

<a href="https://wa.me/<?= e($waNumber) ?>?text=<?= rawurlencode($waText) ?>" target="_blank" rel="noopener noreferrer" class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-50 group wa-btn" aria-label="Chat on WhatsApp">
 <span class="absolute inset-0 rounded-full bg-green-500 animate-ping opacity-30"></span>
 <span class="absolute inset-0 rounded-full bg-green-500 animate-pulse opacity-20"></span>
 <div class="relative flex items-center gap-1 sm:gap-2 px-3 py-2.5 sm:px-4 sm:py-3 rounded-full bg-gradient-to-r from-green-500 to-green-600 text-white shadow-2xl cursor-pointer overflow-hidden wa-pill">
  <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent wa-shine"></div>
  <div class="relative"><svg class="w-4 h-4 sm:w-5 sm:h-5" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"></path></svg></div>
  <span class="relative text-xs sm:text-sm font-bold tracking-wide hidden xs:inline">WhatsApp</span>
  <svg class="w-3 h-3 sm:w-4 sm:h-4 hidden sm:block wa-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
 </div>
 <div class="absolute bottom-full right-0 mb-3 px-4 py-2 bg-gray-900 text-white text-xs rounded-lg whitespace-nowrap shadow-lg wa-tip">Click to chat instantly!<span class="absolute top-full right-6 w-0 h-0 border-l-8 border-r-8 border-t-8 border-transparent border-t-gray-900"></span></div>
</a>

<div class="relative page-transition-shell">
 <div class="fixed inset-0 z-50 bg-slate-900 pointer-events-none" id="pageOverlay"></div>
 <div class="page-transition-content" id="pageContent">
