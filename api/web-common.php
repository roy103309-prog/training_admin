<?php
require_once __DIR__ . '/newlife-common.php';

/**
 * Website content tables (sliders, videos, reviews, programs, web_settings,
 * contact_messages) — a public website reads them,
 * so keep their columns.
 */

// Website settings editable on Settings -> General Settings -> Logo & Branding (templates/web-branding.html)
const WEB_SETTING_KEYS = [
    'site_name', 'site_tagline', 'site_tagline_bn', 'site_title', 'site_description', 'site_keywords',
    'phone_primary', 'phone_secondary', 'call_number', 'email', 'address_bn', 'address_en',
    'whatsapp_number', 'whatsapp_message', 'facebook_url', 'youtube_url', 'website_url', 'map_url',
    'developer_name', 'developer_url', 'facility_title', 'facility_subtitle', 'chairman_name_bn', 'chairman_name_en',
];
const WEB_SETTING_IMAGES = ['logo', 'favicon', 'chairman_image', 'about_image'];

// Team sections of the website (web_team.category => heading on the site)
const WEB_TEAM_CATEGORIES = [
    'leadership' => 'Leadership', 'management' => 'Management', 'marketing' => 'Marketing',
    'instructors' => 'Master Instructors', 'technical' => 'Technical Staff',
];

function webAll(mysqli $conn, $table) {
    $res = $conn->query("SELECT * FROM `$table` ORDER BY sort_order, id");
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $res->free();
    return $rows;
}

function webFind(mysqli $conn, $table, $id) {
    $stmt = $conn->prepare("SELECT * FROM `$table` WHERE id = ?");
    $stmt->bind_param('i', $id); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    return $row;
}

// Delete a row and the file it points to
function webDelete(mysqli $conn, $table, $id, $fileColumn = null) {
    $row = webFind($conn, $table, $id);
    if (!$row) jsonResponse(false, 'Record not found');
    $stmt = $conn->prepare("DELETE FROM `$table` WHERE id = ?");
    $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
    if ($fileColumn && !empty($row[$fileColumn])) nlDeleteFile($row[$fileColumn]);
}

function youtubeId($url) {
    return preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{6,})~', (string)$url, $m) ? $m[1] : '';
}

// Website Texts page: editable site texts per tab (defaults in includes/site-lang.php,
// edits saved in web_settings as text_bn:<key> / text_en:<key>)
const WEB_TEXT_TABS = [
    'menu' => ['title' => 'Menu & Name', 'keys' => [
        'nav.home', 'nav.programs', 'nav.about', 'nav.team',
        'nav.contact', 'nav.videos', 'nav.callNow', 'nav.langBtn',
        'nav.langBtnMobile', 'brand.full', 'nav.certificate', 'nav.oldCertificate',
        'nav.newCertificate',
    ]],
    'hero' => ['title' => 'Hero', 'keys' => [
        'hero.banglaTtitle', 'hero.title1', 'hero.title2', 'hero.description',
        'hero.startJourney', 'hero.quote',
    ]],
    'about' => ['title' => 'About', 'keys' => [
        'about.badge', 'about.title', 'about.titleHighlight', 'about.subtitle',
        'about.content.heading', 'about.content.paragraph1', 'about.content.paragraph2', 'about.content.tag1',
        'about.content.tag2', 'about.journey.heading', 'about.journey.headingHighlight', 'about.quote.badge',
        'about.quote.title', 'about.quote.text', 'about.quote.designation', 'about.quote.company',
        'about.milestones.founded.title', 'about.milestones.founded.description', 'about.milestones.expansion.title', 'about.milestones.expansion.description',
        'about.milestones.recognition.title', 'about.milestones.recognition.description', 'about.milestones.innovation.title', 'about.milestones.innovation.description',
    ]],
    'programs' => ['title' => 'Programs', 'keys' => [
        'programs.badge', 'programs.title', 'programs.titleHighlight', 'programs.subtitle',
        'programs.duration', 'programs.price', 'programs.viewDetails', 'program.callNow',
        'program.whatsapp', 'program.others', 'program.back', 'program.enroll',
        'program.enrollText',
    ]],
    'videos' => ['title' => 'Videos', 'keys' => [
        'videos.badge', 'videos.title', 'videos.titleHighlight', 'videos.subtitle',
        'videos.loadMore',
    ]],
    'reviews' => ['title' => 'Reviews', 'keys' => [
        'testimonials2.badge', 'testimonials2.title', 'testimonials2.titleHighlight', 'testimonials2.subtitle',
    ]],
    'team' => ['title' => 'Team', 'keys' => [
        'teamSection.badge', 'teamSection.title', 'teamSection.subtitle', 'teamSection.leadership',
        'teamSection.leadershipSub', 'teamSection.management', 'teamSection.managementSub', 'teamSection.marketing',
        'teamSection.marketingSub', 'teamSection.instructors', 'teamSection.instructorsSub', 'teamSection.technical',
        'teamSection.technicalSub',
    ]],
    'facilities' => ['title' => 'Facilities', 'keys' => [
        'facilities.badge', 'facilities.title', 'facilities.titleHighlight', 'facilities.subtitle',
    ]],
    'faq' => ['title' => 'FAQ', 'keys' => [
        'faq.badge', 'faq.title', 'faq.titleHighlight', 'faq.subtitle',
        'faq.q1', 'faq.a1', 'faq.q2', 'faq.a2',
        'faq.q3', 'faq.a3', 'faq.q4', 'faq.a4',
        'faq.a4link', 'faq.q5', 'faq.a5',
    ]],
    'contact' => ['title' => 'Contact', 'keys' => [
        'contact.badge', 'contact.title', 'contact.titleHighlight', 'contact.description',
        'contact.name', 'contact.email', 'contact.phone', 'contact.message',
        'contact.send', 'contact.address', 'contact.callUs', 'contact.emailUs',
        'contact.success', 'contact.error',
    ]],
    'footer' => ['title' => 'Footer', 'keys' => [
        'footer.programs', 'footer.company', 'footer.aboutUs', 'footer.ourTeam',
        'footer.rights', 'footer.brandSub', 'footer.address', 'footer.phone',
        'footer.email', 'footer.contact', 'footer.getInTouch', 'footer.poweredBy',
    ]],
    'certificate' => ['title' => 'Certificate Pages', 'keys' => [
        'cert.oldTitle', 'cert.oldSubtitle', 'cert.newTitle', 'cert.newSubtitle',
        'cert.backToOld', 'cert.rollNumber', 'cert.regNumber', 'cert.dob',
        'cert.search', 'cert.searching', 'cert.studentDetails', 'cert.studentPhoto',
        'cert.studentInfo', 'cert.studentName', 'cert.fatherName', 'cert.motherName',
        'cert.address', 'cert.certificate',
    ]],
];

// Default site texts ['bn' => [...], 'en' => [...]] from the website language file
function webTextDefaults() {
    if (!defined('SITE_ROOT')) define('SITE_ROOT', dirname(__DIR__));
    return require dirname(__DIR__) . '/includes/site-lang.php';
}

// Facility icons the website can draw (includes/site-icons.php)
const WEB_FACILITY_ICONS = ['hammer', 'building2', 'shield', 'zap', 'users', 'star', 'award', 'flame', 'briefcase', 'book-open', 'lightbulb', 'globe'];
