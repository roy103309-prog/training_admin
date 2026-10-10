<?php
require __DIR__ . '/includes/site-bootstrap.php';

try {
    db();
} catch (Throwable $e) {
    error_log('[Website] Database connection failed: ' . $e->getMessage());
    http_response_code(503);
    exit('Website is temporarily unavailable. Please try again later.');
}

$navSolid = false;
require __DIR__ . '/includes/site-header.php';
?>
<main class="relative overflow-hidden">
<?php
foreach (['hero', 'about', 'programs', 'videos', 'team', 'testimonials', 'facilities', 'faq', 'contact'] as $section) {
    require __DIR__ . '/includes/site-section-' . $section . '.php';
}
?>
</main>
<?php require __DIR__ . '/includes/site-footer.php'; ?>
