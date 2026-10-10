<?php defined('SITE_ROOT') || exit; ?>
<?php
$milestones = [
    ['2012', 'about.milestones.founded'],
    ['2015', 'about.milestones.expansion'],
    ['2019', 'about.milestones.recognition'],
    ['2022', 'about.milestones.innovation'],
];
?>
<section id="about" class="relative py-20 md:py-32 bg-gradient-to-br from-emerald-50 via-green-50 to-teal-50 overflow-hidden">
 <div class="absolute inset-0 bg-[linear-gradient(rgba(16,185,129,0.03)_1px,transparent_1px),linear-gradient(90deg,rgba(16,185,129,0.03)_1px,transparent_1px)] bg-[size:40px_40px]"></div>
 <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
  <div class="text-center mb-20" data-from="o:0" data-section>
   <div class="inline-block mb-4" data-from="o:0;s:.9"><span class="px-4 py-2 rounded-full bg-emerald-100 text-emerald-600 text-sm font-semibold lb"><?= t('about.badge') ?></span></div>
   <h2 class="heading-large text-dark-900 mb-6 lb"><?= t('about.title') ?> <span class="bg-gradient-to-r from-emerald-500 to-teal-500 bg-clip-text text-transparent"><?= t('about.titleHighlight') ?></span></h2>
   <p class="text-lg md:text-xl text-dark-600 max-w-3xl mx-auto lb"><?= t('about.subtitle') ?></p>
  </div>
  <div class="grid lg:grid-cols-2 gap-16 mb-20 items-center">
   <div data-from="o:0;x:50" data-section>
    <div data-tilt='{"max":10,"glare":0.2,"scale":1.02,"speed":2000}'>
     <div class="relative rounded-3xl overflow-hidden shadow-2xl">
      <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/20 to-teal-500/20 mix-blend-overlay"></div>
      <img alt="Welding Training Facility" loading="lazy" width="800" height="600" class="w-full h-[500px] object-cover" src="<?= e(asset_url(setting('about_image', 'assets/images/site-about.jpeg'))) ?>" style="color: transparent;">
      <div class="absolute inset-0 bg-gradient-to-t from-dark-900/80 via-transparent to-transparent flex items-end p-8" data-from="o:0">
       <div>
        <h3 class="text-3xl font-bold text-white mb-2"><?= e(setting('facility_title', 'State-of-the-Art Facility')) ?></h3>
        <p class="text-dark-200"><?= e(setting('facility_subtitle', '20,000 sq ft of professional training space')) ?></p>
       </div>
      </div>
     </div>
    </div>
   </div>
   <div data-from="o:0;x:-50" data-section>
    <div class="space-y-8">
     <div>
      <h3 class="text-3xl md:text-4xl font-bold text-dark-900 mb-4 lb"><?= t('about.content.heading') ?></h3>
      <p class="text-lg text-dark-600 mb-6 leading-relaxed lb"><?= t('about.content.paragraph1') ?></p>
      <p class="text-lg text-dark-600 leading-relaxed lb"><?= t('about.content.paragraph2') ?></p>
     </div>
     <div class="flex flex-wrap gap-4">
      <div class="flex items-center gap-3 px-6 py-3 bg-emerald-100 rounded-full hv-scale105"><?= icon('book-open', 'w-5 h-5 text-emerald-600') ?><span class="font-semibold text-dark-900 lb"><?= t('about.content.tag1') ?></span></div>
      <div class="flex items-center gap-3 px-6 py-3 bg-teal-100 rounded-full hv-scale105"><?= icon('lightbulb', 'w-5 h-5 text-teal-600') ?><span class="font-semibold text-dark-900 lb"><?= t('about.content.tag2') ?></span></div>
     </div>
    </div>
   </div>
  </div>
  <div data-from="o:0" data-section>
   <h3 class="text-3xl md:text-4xl font-bold text-center text-dark-900 mb-12 lb"><?= t('about.journey.heading') ?> <span class="text-emerald-500"><?= t('about.journey.headingHighlight') ?></span></h3>
   <div class="relative max-w-5xl mx-auto">
    <div class="absolute left-4 md:left-1/2 transform md:-translate-x-1/2 h-full w-1 bg-gradient-to-b from-emerald-500 via-teal-500 to-green-600"></div>
    <div class="space-y-8 md:space-y-16">
     <?php foreach ($milestones as $i => [$year, $key]): $even = $i % 2 === 0; ?>
     <div class="flex items-center gap-4 md:gap-8 <?= $even ? 'md:flex-row' : 'md:flex-row-reverse' ?>" data-from="o:0;x:<?= $even ? -50 : 50 ?>" data-dur=".6" data-delay="<?= 0.2 * $i ?>">
      <div class="flex-1 ml-8 md:ml-0 <?= $even ? 'md:text-right' : 'md:text-left' ?>">
       <div class="inline-block p-4 md:p-6 bg-white rounded-xl md:rounded-2xl shadow-lg hover:shadow-xl transition-all hv-scale105">
        <div class="text-2xl md:text-3xl font-bold bg-gradient-to-r from-emerald-500 to-teal-500 bg-clip-text text-transparent mb-2"><?= e($year) ?></div>
        <h4 class="text-lg md:text-xl font-bold text-dark-900 mb-2 lb"><?= t($key . '.title') ?></h4>
        <p class="text-sm md:text-base text-dark-600 lb"><?= t($key . '.description') ?></p>
       </div>
      </div>
      <div class="absolute left-2 md:relative md:left-auto w-6 h-6 md:w-8 md:h-8 rounded-full bg-gradient-to-br from-emerald-500 to-teal-500 shadow-lg z-10 flex items-center justify-center hv-spin"><div class="w-3 h-3 md:w-4 md:h-4 rounded-full bg-white"></div></div>
      <div class="hidden md:block flex-1"></div>
     </div>
     <?php endforeach; ?>
    </div>
   </div>
  </div>
  <div class="mt-24" data-from="o:0" data-section>
   <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-stretch max-w-6xl mx-auto">
    <div class="h-full" data-from="o:0;x:50" data-section>
     <div class="bg-white rounded-3xl shadow-2xl border border-emerald-100 p-8 md:p-12 relative overflow-hidden h-full flex flex-col justify-center group order-2 lg:order-1">
      <div class="absolute inset-0 bg-gradient-to-br from-emerald-50 via-white to-teal-50"></div>
      <div class="absolute -left-10 -top-10 w-40 h-40 rounded-full bg-emerald-100 blur-3xl opacity-60"></div>
      <div class="absolute -right-10 -bottom-10 w-48 h-48 rounded-full bg-teal-100 blur-3xl opacity-60"></div>
      <div class="absolute top-4 left-6 z-0 select-none pointer-events-none" data-from="o:0;s:.5;x:-20" data-dur=".8" data-repeat><span class="block text-[8rem] md:text-[10rem] leading-none text-emerald-100/80 font-serif float-up">❝</span></div>
      <div class="relative z-10 space-y-6 pt-8">
       <div class="relative inline-block"><span class="relative z-10 inline-flex px-4 py-2 rounded-full bg-emerald-100/80 backdrop-blur-sm text-emerald-700 text-sm font-semibold shadow-sm border border-emerald-200 lb"><?= t('about.quote.badge') ?></span></div>
       <h4 class="text-2xl md:text-3xl lg:text-4xl font-bold text-dark-900 leading-tight relative lb"><?= t('about.quote.title') ?></h4>
       <p class="text-base md:text-lg text-dark-600 leading-relaxed italic relative lb lb-quote"><?= t('about.quote.text') ?></p>
       <div class="w-20 h-1 bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full"></div>
      </div>
      <div class="absolute bottom-4 right-6 z-0 select-none pointer-events-none" data-from="o:0;s:.5;x:20" data-dur=".8" data-delay=".2" data-repeat><span class="block text-[8rem] md:text-[10rem] leading-none text-emerald-100/80 font-serif float-down">❞</span></div>
     </div>
    </div>
    <div class="h-full" data-from="o:0;x:-50" data-section>
     <div class="space-y-6 h-full flex flex-col order-1 lg:order-2">
      <div class="flex-1 min-h-[400px] md:min-h-[500px]" data-tilt='{"max":15,"glare":0.1,"scale":1,"speed":2000}'>
       <div class="relative h-full rounded-3xl overflow-hidden shadow-2xl group border-[6px] border-white/90">
        <div class="absolute inset-0 bg-gradient-to-t from-dark-900/20 via-transparent to-transparent z-10"></div>
        <img alt="Chairman" width="600" height="700" class="w-full h-full object-cover object-top transition-transform duration-700" src="<?= e(asset_url(setting('chairman_image', 'assets/images/site-chairman.png'))) ?>" style="color: transparent;">
       </div>
      </div>
      <div class="relative mx-4 shrink-0" data-from="o:0;y:20" data-delay=".2">
       <div class="bg-white rounded-2xl shadow-xl shadow-emerald-900/5 border border-emerald-100 p-6 text-center relative overflow-hidden group hover:border-emerald-300 transition-all duration-300">
        <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-emerald-50 to-teal-50 rounded-bl-[100px] -z-0 opacity-60"></div>
        <div class="absolute bottom-0 left-0 w-16 h-16 bg-gradient-to-tr from-emerald-50 to-teal-50 rounded-tr-[50px] -z-0 opacity-60"></div>
        <div class="relative z-10">
         <h3 class="text-2xl md:text-3xl font-bold text-dark-900 mb-2 bg-gradient-to-r from-dark-900 to-dark-700 bg-clip-text text-transparent group-hover:from-emerald-600 group-hover:to-teal-600 transition-all duration-300 lb"><?= tx(setting('chairman_name_bn', 'মো: আব্দুর রহমান জনি'), setting('chairman_name_en', 'Abdur Rahman Jony')) ?></h3>
         <div class="flex items-center justify-center gap-3 my-3"><span class="h-px w-8 bg-gradient-to-r from-transparent to-emerald-300"></span><p class="text-emerald-600 font-bold text-lg uppercase tracking-wide px-2 py-1 rounded-md bg-emerald-50/50 lb"><?= t('about.quote.designation') ?></p><span class="h-px w-8 bg-gradient-to-l from-transparent to-emerald-300"></span></div>
         <p class="text-dark-500 font-medium lb"><?= t('about.quote.company') ?></p>
        </div>
       </div>
      </div>
     </div>
    </div>
   </div>
  </div>
 </div>
</section>
