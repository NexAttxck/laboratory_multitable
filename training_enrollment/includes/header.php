<?php
// includes/header.php
// $base is '' for index.php and '../' for pages inside admin/
if (!isset($base))
{
    $base = '';
}

// Current page file name, used to mark the active nav link
$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');

// Nav items: file, folder, long label, short label (mobile), SVG icon paths
$navItems = [
    ['index.php',       '',       'Home',          'Home',     '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/>'],
    ['courses.php',     'admin/', 'Courses',       'Courses',  '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V3H6.5A2.5 2.5 0 0 0 4 5.5z"/>'],
    ['classes.php',     'admin/', 'Classes',       'Classes',  '<rect x="3" y="4" width="18" height="17" rx="3"/><path d="M8 2v4M16 2v4M3 10h18"/>'],
    ['students.php',    'admin/', 'Record Student','Record',   '<circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0"/><path d="M19 8v6M16 11h6"/>'],
    ['enroll.php',      'admin/', 'Enroll',        'Enroll',   '<circle cx="12" cy="12" r="9"/><path d="M8 12.5l3 3 5-6"/>'],
    ['enrollments.php', 'admin/', 'Enrollments',   'Enrolled', '<path d="M8 6h13M8 12h13M8 18h13"/><circle cx="3.5" cy="6" r="1"/><circle cx="3.5" cy="12" r="1"/><circle cx="3.5" cy="18" r="1"/>'],
    ['reports.php',     'admin/', 'Reports',       'Reports',  '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light dark">
    <title>Training Enrollment System</title>
    <link rel="stylesheet" href="<?php echo $base; ?>css/style.css">
    <script src="<?php echo $base; ?>js/app.js" defer></script>
</head>
<body>
<a class="skip" href="#content">Skip to content</a>
<nav aria-label="Main">
    <?php foreach ($navItems as $item) { ?>
    <a href="<?php echo $base . $item[1] . $item[0]; ?>"<?php echo ($currentPage === $item[0]) ? ' aria-current="page"' : ''; ?>>
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><?php echo $item[4]; ?></svg>
        <span class="lbl"><?php echo $item[2]; ?></span><span class="lbl-s" aria-hidden="true"><?php echo $item[3]; ?></span>
    </a>
    <?php } ?>
</nav>
<main id="content">
