/* New Life Welding Training Center — site interactions */
(function () {
  'use strict';
  var doc = document, root = doc.documentElement;
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var SITE = window.SITE || { base: '', i18n: { bn: {}, en: {} } };
  var $ = function (s, p) { return (p || doc).querySelector(s); };
  var $$ = function (s, p) { return Array.prototype.slice.call((p || doc).querySelectorAll(s)); };

  /* ------------------------------------------------------------------ i18n */
  function currentLang() { return root.getAttribute('data-lang') === 'en' ? 'en' : 'bn'; }
  function applyLang(lang) {
    root.setAttribute('data-lang', lang);
    root.lang = lang;
    var dict = SITE.i18n[lang] || {};
    $$('[data-i18n]').forEach(function (el) {
      var k = el.getAttribute('data-i18n');
      if (dict[k] !== undefined) el.textContent = dict[k];
    });
    $$('[data-en][data-bn]').forEach(function (el) {
      el.textContent = el.getAttribute(lang === 'en' ? 'data-en' : 'data-bn');
    });
    try { localStorage.setItem('nl_lang', lang); } catch (e) {}
  }
  $$('[data-lang-toggle]').forEach(function (b) {
    b.addEventListener('click', function () { applyLang(currentLang() === 'en' ? 'bn' : 'en'); });
  });
  if (currentLang() === 'en') applyLang('en');

  /* ------------------------------------------------------ reveal animations */
  var EASE = 'cubic-bezier(.25,.46,.45,.94)';
  var SPRING = 'cubic-bezier(.34,1.56,.64,1)';
  function parseFrom(str) {
    var o = {};
    (str || '').split(';').forEach(function (p) {
      var kv = p.split(':'); if (kv.length === 2) o[kv[0].trim()] = parseFloat(kv[1]);
    });
    return o;
  }
  function fromTransform(f) {
    var t = '';
    if (f.x !== undefined || f.y !== undefined) t += 'translate(' + (f.x || 0) + 'px,' + (f.y || 0) + 'px) ';
    if (f.s !== undefined) t += 'scale(' + f.s + ') ';
    if (f.r !== undefined) t += 'rotate(' + f.r + 'deg) ';
    return t.trim();
  }
  function prepare(el) {
    var f = parseFrom(el.getAttribute('data-from'));
    el._from = f;
    if (f.o === undefined) el.classList.add('anim-keep-visible');
    el.style.opacity = f.o !== undefined ? f.o : '';
    var t = fromTransform(f);
    if (t) el.style.transform = t;
    if (f.b !== undefined) el.style.filter = 'blur(' + f.b + 'px)';
  }
  function play(el, extraDelay) {
    if (el.classList.contains('anim-run') || el.classList.contains('anim-done')) return;
    var f = el._from || {};
    var dur = parseFloat(el.getAttribute('data-dur') || (el.hasAttribute('data-section') || el.hasAttribute('data-variant') ? '.6' : '.5'));
    var delay = parseFloat(el.getAttribute('data-delay') || '0') + (extraDelay || 0);
    var ease = el.getAttribute('data-ease') === 'spring' ? SPRING : EASE;
    if (el.getAttribute('data-ease') === 'spring' && !el.hasAttribute('data-dur')) dur = .7;
    el.style.transition = 'opacity ' + dur + 's ' + EASE + ' ' + delay + 's, transform ' + dur + 's ' + ease + ' ' + delay + 's, filter ' + dur + 's ' + EASE + ' ' + delay + 's';
    el.classList.add('anim-run');
    requestAnimationFrame(function () {
      el.style.opacity = '1';
      el.style.transform = (f.x !== undefined || f.y !== undefined || f.s !== undefined || f.r !== undefined) ? 'none' : el.style.transform;
      if (f.b !== undefined) el.style.filter = 'blur(0px)';
    });
    setTimeout(function () {
      el.classList.remove('anim-run'); el.classList.add('anim-done');
      el.style.transition = ''; el.style.transform = ''; el.style.opacity = ''; el.style.filter = '';
    }, (dur + delay) * 1000 + 80);
  }

  var animEls = $$('[data-from]');
  if (reduce || !('IntersectionObserver' in window)) {
    animEls.forEach(function (el) { el.classList.add('anim-done'); });
  } else {
    root.classList.add('js-anim');
    animEls.forEach(prepare);
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        var el = en.target; io.unobserve(el);
        if (el.hasAttribute('data-stagger')) {
          var step = parseFloat(el.getAttribute('data-stagger')) || .1;
          $$('[data-variant]', el).forEach(function (c, i) { if (!c.classList.contains('is-hidden')) play(c, i * step); });
        } else {
          play(el);
        }
      });
    }, { rootMargin: '0px 0px -60px 0px', threshold: 0.01 });
    var sectionIo = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { if (en.isIntersecting) { sectionIo.unobserve(en.target); play(en.target); } });
    }, { rootMargin: '-100px 0px -100px 0px', threshold: 0 });
    animEls.forEach(function (el) {
      if (el.hasAttribute('data-onload')) return;
      if (el.hasAttribute('data-variant') && el.closest('[data-stagger]')) return;
      (el.hasAttribute('data-section') ? sectionIo : io).observe(el);
    });
    $$('[data-stagger]').forEach(function (c) { io.observe(c); });
    window.__nlPlay = play;
  }

  /* ------------------------------------------------------- page transition */
  var overlay = $('#pageOverlay'), content = $('#pageContent');
  function startPage() {
    if (overlay) overlay.classList.add('is-done');
    if (content && !reduce) {
      content.classList.add('pt-start');
      requestAnimationFrame(function () { requestAnimationFrame(function () {
        content.classList.add('pt-run');
        setTimeout(function () { content.classList.remove('pt-start', 'pt-run'); }, 800);
      }); });
    }
    $$('[data-onload]').forEach(function (el) { play(el); });
    var wa = $('.wa-btn'); if (wa) setTimeout(function () { wa.classList.add('is-in'); }, 1000);
  }
  startPage();

  /* ------------------------------------------------------- smooth scroll */
  var lenis = null;
  window.addEventListener('load', function () {
    if (window.Lenis && !reduce) {
      try {
        lenis = new window.Lenis({ duration: 1.2, easing: function (t) { return Math.min(1, 1.001 - Math.pow(2, -10 * t)); }, smoothWheel: true });
        root.classList.add('lenis');
        var raf = function (time) { lenis.raf(time); requestAnimationFrame(raf); };
        requestAnimationFrame(raf);
      } catch (e) { lenis = null; }
    }
  });
  doc.addEventListener('click', function (ev) {
    var a = ev.target.closest && ev.target.closest('a[href*="#"]');
    if (!a) return;
    var url = new URL(a.href, location.href);
    if (url.pathname.replace(/\/$/, '') !== location.pathname.replace(/\/(index\.php)?$/, '').replace(/\/$/, '') || !url.hash) return;
    var target = doc.getElementById(url.hash.slice(1));
    if (!target) return;
    ev.preventDefault();
    closeMenu();
    if (lenis) lenis.scrollTo(target, { offset: 0 }); else target.scrollIntoView({ behavior: 'smooth' });
    history.replaceState(null, '', url.hash);
  });

  /* ------------------------------------------------------ scroll effects */
  var nav = $('#siteNav'), bar = $('#scrollProgress');
  var solid = nav && nav.getAttribute('data-solid') === '1';
  var barCurrent = 0, barTarget = 0, barAnimating = false;
  function animateBar() {
    barCurrent += (barTarget - barCurrent) * 0.15;
    if (Math.abs(barTarget - barCurrent) < 0.0005) barCurrent = barTarget;
    bar.style.transform = 'scaleX(' + barCurrent + ')';
    if (barCurrent !== barTarget) requestAnimationFrame(animateBar); else barAnimating = false;
  }
  var parallaxEls = $$('[data-parallax]');
  function onScroll() {
    var y = window.scrollY || window.pageYOffset;
    if (nav && !solid) {
      var p = Math.min(Math.max(y / 100, 0), 1);
      nav.style.backgroundColor = 'rgba(' + Math.round(255 * p) + ', ' + Math.round(255 * p) + ', ' + Math.round(255 * p) + ', ' + (0.95 * p).toFixed(3) + ')';
      nav.style.backdropFilter = 'blur(' + (20 * p).toFixed(1) + 'px)';
      nav.classList.toggle('is-scrolled', y > 50 || nav.classList.contains('menu-open'));
    }
    if (bar) {
      var h = doc.documentElement.scrollHeight - window.innerHeight;
      barTarget = h > 0 ? Math.min(y / h, 1) : 0;
      if (!barAnimating) { barAnimating = true; requestAnimationFrame(animateBar); }
    }
    if (!reduce) parallaxEls.forEach(function (el) {
      var host = el.parentElement, r = host.getBoundingClientRect(), vh = window.innerHeight;
      var prog = (vh - r.top) / (vh + r.height);
      prog = Math.min(Math.max(prog, 0), 1);
      el.style.transform = 'translateY(' + (100 - 200 * prog).toFixed(1) + 'px)';
    });
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', onScroll);
  onScroll();

  /* -------------------------------------------------------------- navbar */
  $$('.nav-item[data-dropdown]').forEach(function (item) {
    var timer = null;
    item.addEventListener('mouseenter', function () {
      clearTimeout(timer);
      $$('.nav-item.is-open').forEach(function (o) { if (o !== item) o.classList.remove('is-open'); });
      item.classList.add('is-open');
    });
    item.addEventListener('mouseleave', function () { timer = setTimeout(function () { item.classList.remove('is-open'); }, 200); });
  });
  var menu = $('#mobileMenu'), menuBtn = $('#mobileMenuBtn');
  function setHeight(el, open) {
    if (open) {
      el.style.height = el.scrollHeight + 'px';
      var done = function () { if (el.style.height !== '0px') el.style.height = 'auto'; el.removeEventListener('transitionend', done); };
      el.addEventListener('transitionend', done);
    } else {
      el.style.height = el.scrollHeight + 'px';
      el.offsetHeight; // reflow
      el.style.height = '0px';
    }
  }
  function closeMenu() {
    if (!menu || !nav.classList.contains('menu-open')) return;
    nav.classList.remove('menu-open'); menu.classList.remove('is-open'); setHeight(menu, false);
    $$('.m-open', menu).forEach(function (m) { m.classList.remove('m-open'); var s = $('.m-sub', m); if (s) { s.style.height = '0px'; s.style.opacity = '0'; } });
    onScroll();
  }
  if (menuBtn && menu) {
    menuBtn.addEventListener('click', function () {
      if (nav.classList.contains('menu-open')) { closeMenu(); return; }
      nav.classList.add('menu-open'); menu.classList.add('is-open'); setHeight(menu, true); onScroll();
    });
    $$('.m-sub-toggle', menu).forEach(function (btn) {
      btn.addEventListener('click', function () {
        var wrap = btn.closest('.border-b'), sub = $('.m-sub', wrap), open = !wrap.classList.contains('m-open');
        $$('.m-open', menu).forEach(function (o) { if (o !== wrap) { o.classList.remove('m-open'); var s = $('.m-sub', o); s.style.height = s.scrollHeight + 'px'; s.offsetHeight; s.style.height = '0px'; s.style.opacity = '0'; } });
        wrap.classList.toggle('m-open', open);
        sub.style.opacity = open ? '1' : '0';
        setHeight(sub, open);
        if (menu.style.height !== 'auto') menu.style.height = 'auto';
      });
    });
    $$('[data-close-menu]', menu).forEach(function (a) { a.addEventListener('click', closeMenu); });
  }

  /* ------------------------------------------------------------- cursor */
  var dot = $('#cursorDot'), ring = $('#cursorRing');
  if (dot && ring && window.matchMedia('(pointer: fine)').matches && !reduce) {
    var mx = -100, my = -100, d = { x: -100, y: -100, s: 1, vx: 0, vy: 0 }, r = { x: -100, y: -100, s: 1, vx: 0, vy: 0 }, hover = false;
    window.addEventListener('mousemove', function (e) {
      mx = e.clientX; my = e.clientY;
      var t = e.target;
      hover = !!(t && (t.tagName === 'A' || t.tagName === 'BUTTON' || (t.closest && t.closest('a,button')) || getComputedStyle(t).cursor === 'pointer'));
    }, { passive: true });
    // Damped spring per frame (approximates framer-motion springs: dot stiff, ring loose)
    var spring = function (o, tx, ty, ts, k, c) {
      var dt = 1 / 60;
      o.vx += ((tx - o.x) * k - o.vx * c) * dt; o.vy += ((ty - o.y) * k - o.vy * c) * dt;
      o.x += o.vx * dt; o.y += o.vy * dt;
      o.s += (ts - o.s) * 0.2;
    };
    (function tick() {
      spring(d, mx, my, hover ? 1.5 : 1, 500, 28);
      spring(r, mx, my, hover ? 1.8 : 1, 150, 20);
      dot.style.transform = 'translateX(' + (d.x - 8) + 'px) translateY(' + (d.y - 8) + 'px) scale(' + d.s.toFixed(3) + ')';
      ring.style.transform = 'translateX(' + (r.x - 16) + 'px) translateY(' + (r.y - 16) + 'px) scale(' + r.s.toFixed(3) + ')';
      requestAnimationFrame(tick);
    })();
  }

  /* -------------------------------------------------------------- tilt */
  if (!reduce && window.matchMedia('(hover: hover)').matches) {
    $$('[data-tilt]').forEach(function (el) {
      var o = {}; try { o = JSON.parse(el.getAttribute('data-tilt')); } catch (e) {}
      var max = o.max || 10, scale = o.scale || 1, speed = o.speed || 400, glareMax = o.glare || 0;
      var radius = getComputedStyle(el.firstElementChild || el).borderRadius;
      var gw, g;
      if (glareMax) {
        gw = doc.createElement('div'); g = doc.createElement('div');
        gw.className = 'glare-wrapper'; g.className = 'glare';
        gw.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;overflow:hidden;border-radius:' + radius + ';-webkit-mask-image:-webkit-radial-gradient(white,black);mask-image:radial-gradient(white,black);pointer-events:none;';
        g.style.cssText = 'position:absolute;top:50%;left:50%;transform-origin:0% 0%;pointer-events:none;opacity:0;';
        gw.appendChild(g); el.appendChild(gw);
      }
      var setT = function () { el.style.transition = 'all ' + speed + 'ms cubic-bezier(.03,.98,.52,.99)'; if (g) g.style.transition = 'opacity ' + speed + 'ms cubic-bezier(.03,.98,.52,.99)'; };
      el.addEventListener('mouseenter', function () {
        setT(); el.style.willChange = 'transform';
        if (g) { var w = Math.sqrt(Math.pow(el.offsetWidth, 2) + Math.pow(el.offsetHeight, 2)); g.style.width = w + 'px'; g.style.height = w + 'px'; }
      });
      el.addEventListener('mousemove', function (e) {
        var rc = el.getBoundingClientRect();
        var yp = Math.min(Math.max((e.clientY - rc.top) / rc.height * 200 - 100, -100), 100);
        var xp = Math.min(Math.max((e.clientX - rc.left) / rc.width * 200 - 100, -100), 100);
        var ax = yp * max / 100, ay = -(xp * max / 100);
        el.style.transform = 'perspective(1000px) rotateX(' + ax + 'deg) rotateY(' + ay + 'deg) scale3d(' + scale + ',' + scale + ',' + scale + ')';
        if (g) {
          var ang = yp ? Math.atan2(xp, -yp) * 180 / Math.PI : 0;
          g.style.transform = 'rotate(' + ang + 'deg) translate(-50%, -50%)';
          g.style.opacity = (Math.min(Math.max(yp, 0), 100) * glareMax / 100).toString();
          g.style.background = 'linear-gradient(0deg, rgba(255,255,255,0) 0%, #ffffff 100%)';
        }
      });
      el.addEventListener('mouseleave', function () {
        setT();
        el.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) scale3d(1,1,1)';
        if (g) g.style.opacity = '0';
      });
    });
  }

  /* --------------------------------------------------------- hero slider */
  var hero = $('[data-hero]');
  if (hero) {
    var slides = $$('.hero-slide', hero), dots = $$('[data-hero-dot]', hero), cur = 0, timer = null, busy = false;
    var load = function (s) { var img = $('img[data-src]', s); if (img) { img.src = img.getAttribute('data-src'); img.removeAttribute('data-src'); } };
    if (slides[1]) load(slides[1]);
    var show = function (n) {
      if (n === cur || !slides[n]) return;
      var prev = slides[cur], next = slides[n];
      load(next); if (slides[(n + 1) % slides.length]) load(slides[(n + 1) % slides.length]);
      prev.classList.remove('is-active'); prev.classList.add('is-leaving');
      dots[cur].className = dots[cur].className.replace('bg-emerald-500 w-5 sm:w-8', 'bg-white/50 hover:bg-white/70');
      dots[n].className = dots[n].className.replace('bg-white/50 hover:bg-white/70', 'bg-emerald-500 w-5 sm:w-8');
      cur = n;
      setTimeout(function () {
        prev.classList.remove('is-leaving');
        next.classList.add('is-active');
      }, reduce ? 0 : 700);
    };
    var restart = function () { clearInterval(timer); timer = setInterval(function () { show((cur + 1) % slides.length); }, 5000); };
    $('[data-hero-prev]', hero).addEventListener('click', function () { show((cur - 1 + slides.length) % slides.length); restart(); });
    $('[data-hero-next]', hero).addEventListener('click', function () { show((cur + 1) % slides.length); restart(); });
    dots.forEach(function (dt) { dt.addEventListener('click', function () { show(parseInt(dt.getAttribute('data-hero-dot'), 10)); restart(); }); });
    if (slides.length > 1) restart();
  }

  /* -------------------------------------------------------------- videos */
  $$('[data-video-src]').forEach(function (box) {
    var original = null;
    var toggle = function () {
      if (original === null) {
        original = box.innerHTML;
        var src = box.getAttribute('data-video-src');
        if (box.getAttribute('data-video-file') === '1') {
          box.innerHTML = '<video src="' + src + '" autoplay controls class="absolute inset-0 w-full h-full object-cover"></video>';
        } else {
          box.innerHTML = '<iframe src="' + src + '" title="' + (box.getAttribute('data-video-title') || '').replace(/"/g, '&quot;') + '" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen class="absolute inset-0 w-full h-full" loading="lazy"></iframe>';
        }
      }
    };
    box.addEventListener('click', toggle);
    box.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); } });
  });
  var more = $('#videoLoadMore');
  if (more) more.addEventListener('click', function () {
    var hidden = $$('.video-item.is-hidden');
    hidden.slice(0, 4).forEach(function (v, i) { v.classList.remove('is-hidden'); if (window.__nlPlay) window.__nlPlay(v, i * 0.1); else v.classList.add('anim-done'); });
    if (hidden.length <= 4) { var w = $('#videoMoreWrap'); if (w) w.remove(); }
  });

  /* ----------------------------------------------------------------- FAQ */
  $$('.faq-item').forEach(function (item) {
    $('.faq-q', item).addEventListener('click', function () {
      var open = !item.classList.contains('is-open');
      $$('.faq-item.is-open').forEach(function (o) { if (o !== item) { o.classList.remove('is-open'); var a = $('.faq-a', o); a.style.opacity = '0'; setHeight(a, false); } });
      item.classList.toggle('is-open', open);
      var ans = $('.faq-a', item); ans.style.opacity = open ? '1' : '0'; setHeight(ans, open);
    });
  });

  /* ------------------------------------------------------------- contact */
  var cf = $('#contactForm');
  if (cf) cf.addEventListener('submit', function (e) {
    e.preventDefault();
    var data = {}; new FormData(cf).forEach(function (v, k) { data[k] = v; });
    var ok = $('#contactSuccess'), bad = $('#contactError');
    ok.hidden = true; bad.hidden = true;
    fetch(cf.getAttribute('action'), { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
      .then(function (res) {
        if (res.ok && res.j.success) { ok.hidden = false; cf.reset(); setTimeout(function () { ok.hidden = true; }, 3000); }
        else { bad.hidden = false; setTimeout(function () { bad.hidden = true; }, 4000); }
      })
      .catch(function () { bad.hidden = false; setTimeout(function () { bad.hidden = true; }, 4000); });
  });

  /* -------------------------------------------------------- certificates */
  function certForm(form, onData) {
    if (!form) return;
    var card = form.parentElement, errBox = $('[data-error]', form), btn = $('[data-submit]', form), result = $('[data-result]', card);
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var data = {}; new FormData(form).forEach(function (v, k) { data[k] = String(v).trim(); });
      errBox.hidden = true; result.hidden = true; btn.disabled = true;
      $('[data-idle]', btn).hidden = true; $('[data-busy]', btn).hidden = false;
      fetch(form.getAttribute('data-endpoint'), { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) })
        .then(function (r) { return r.text().then(function (t) { var j = null; try { j = JSON.parse(t); } catch (x) {} return { ok: r.ok, j: j }; }); })
        .then(function (res) {
          if (!res.j) { errBox.textContent = 'Server returned invalid response'; errBox.hidden = false; return; }
          if (!res.ok) { errBox.textContent = res.j.error || 'Certificate not found'; errBox.hidden = false; return; }
          onData(res.j, card); result.hidden = false;
        })
        .catch(function () { errBox.textContent = 'Failed to fetch certificate'; errBox.hidden = false; })
        .then(function () { btn.disabled = false; $('[data-idle]', btn).hidden = false; $('[data-busy]', btn).hidden = true; });
    });
  }
  var setF = function (card, key, val, isImg) {
    var el = $('[data-f="' + key + '"]', card), wrap = $('[data-wrap="' + key + '"]', card);
    if (!el) return;
    if (isImg) el.src = val || ''; else el.textContent = val || '';
    if (wrap) wrap.hidden = !val;
  };
  certForm($('#oldCertForm'), function (j, card) {
    setF(card, 'name', j.CrtName); setF(card, 'dob', j.CrtDob ? new Date(j.CrtDob).toLocaleDateString() : '');
    setF(card, 'roll', j.CrtRoll); setF(card, 'reg', j.CrtRegi); setF(card, 'address', j.CrtAddress);
    var img = j.CrtImg ? (j.CrtImgType === 'path' ? j.CrtImg : 'data:image/jpeg;base64,' + j.CrtImg) : '';
    setF(card, 'img', img, true);
  });
  certForm($('#newCertForm'), function (j, card) {
    setF(card, 'name', j.student_name); setF(card, 'roll', j.student_id);
    setF(card, 'father', j.student_father_name); setF(card, 'mother', j.student_mother_name);
    setF(card, 'address', j.student_address);
    setF(card, 'photo', j.student_image, true); setF(card, 'cert', j.certificate_image, true);
  });
})();
