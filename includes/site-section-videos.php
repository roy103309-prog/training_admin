<?php defined('SITE_ROOT') || exit; ?>
<?php $videos = db_all('SELECT * FROM videos WHERE is_active = 1 AND video_url <> "" ORDER BY sort_order, id'); ?>
<section id="videos" class="relative py-20 md:py-32 bg-gradient-to-b from-emerald-50 via-teal-50 to-green-50 overflow-hidden">
 <div class="absolute inset-0">
  <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,rgba(16,185,129,0.08),transparent_50%)]"></div>
  <div class="absolute inset-0 bg-[radial-gradient(circle_at_70%_80%,rgba(5,150,105,0.08),transparent_50%)]"></div>
  <div class="absolute inset-0 bg-[linear-gradient(rgba(16,185,129,0.05)_1px,transparent_1px),linear-gradient(90deg,rgba(16,185,129,0.05)_1px,transparent_1px)] bg-[size:50px_50px]"></div>
 </div>
 <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
  <div class="text-center mb-20" data-from="o:0" data-section>
   <div class="inline-block mb-4" data-from="o:0;s:.9"><span class="px-6 py-3 rounded-full bg-gradient-to-r from-emerald-600/20 via-teal-600/20 to-emerald-600/20 border border-emerald-500/30 text-emerald-600 text-sm font-bold flex items-center gap-2 backdrop-blur-sm lb"><?= icon('youtube', 'w-5 h-5 animate-pulse') ?><?= t('videos.badge') ?></span></div>
   <h2 class="heading-large text-dark-900 mb-6 lb"><?= t('videos.title') ?> <span class="bg-gradient-to-r from-emerald-500 to-teal-500 bg-clip-text text-transparent"><?= t('videos.titleHighlight') ?></span></h2>
   <p class="text-lg md:text-xl text-dark-600 max-w-3xl mx-auto lb"><?= t('videos.subtitle') ?></p>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-7xl mx-auto" data-stagger=".1" id="videoGrid">
   <?php foreach ($videos as $i => $v):
       $yt = youtube_id($v['video_url']);
       $isFile = (bool)preg_match('/\.(mp4|webm|ogg)(?:[?#].*)?$/i', trim($v['video_url']));
       $embed = $yt ? 'https://www.youtube.com/embed/' . $yt . '?autoplay=1&rel=0&modestbranding=1' : asset_url($v['video_url']);
       $thumb = $yt ? 'https://i.ytimg.com/vi/' . $yt . '/hqdefault.jpg' : 'https://placehold.co/800x450?text=Video';
   ?>
   <div class="group relative hv-lift video-item<?= $i >= 4 ? ' is-hidden' : '' ?>" data-from="o:0;y:60" data-variant>
    <div class="relative bg-white/80 backdrop-blur-xl shadow-lg rounded-3xl overflow-hidden border border-emerald-200 hover:border-emerald-500/50 transition-all duration-500 shadow-2xl">
     <div class="relative aspect-video cursor-pointer group/thumb bg-dark-800" role="button" tabindex="0" data-video-src="<?= e($embed) ?>" data-video-file="<?= $isFile ? '1' : '0' ?>" data-video-title="<?= e($v['title']) ?>">
      <img src="<?= e($thumb) ?>" alt="<?= e($v['title']) ?>" class="absolute inset-0 w-full h-full object-cover transition-all duration-500 group-hover/thumb:scale-110" loading="lazy">
      <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent"></div>
      <div class="absolute inset-0 flex items-center justify-center hv-scale110">
       <div class="relative flex items-center justify-center w-20 h-20 rounded-full bg-emerald-500/90 backdrop-blur-sm border-4 border-white/50 shadow-2xl group-hover/thumb:bg-emerald-400 transition-colors hv-scale110"><?= icon('play', 'w-10 h-10 text-white ml-1 relative z-10', ['fill' => 'white']) ?></div>
      </div>
      <div class="absolute inset-0 bg-gradient-to-t from-emerald-500/0 via-emerald-500/0 to-emerald-500/10 opacity-0 group-hover/thumb:opacity-100 transition-opacity duration-500"></div>
     </div>
     <div class="p-6">
      <h3 class="text-2xl font-bold text-dark-900 mb-3 group-hover:text-emerald-500 transition-colors lb"><?= e($v['title']) ?></h3>
      <p class="text-dark-600 leading-relaxed lb"><?= e($v['description'] ?? '') ?></p>
     </div>
    </div>
   </div>
   <?php endforeach; ?>
  </div>
  <?php if (count($videos) > 4): ?>
  <div class="flex justify-center mt-12" data-from="o:0;y:30" id="videoMoreWrap">
   <button type="button" id="videoLoadMore" class="inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-2xl font-semibold shadow-xl hover:shadow-2xl hover:shadow-emerald-500/25 transition-all lb hv-scale105 tap95"><?= t('videos.loadMore') ?><?= icon('eye', 'w-5 h-5') ?></button>
  </div>
  <?php endif; ?>
 </div>
</section>
