<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/share.php';

// Opening a share link: rebuild the sender's profile, then go through the loading screen to the day page.
$profile = share_profile((string) ($_GET['t'] ?? ''));

if ($profile === null) {
    set_flash('That link doesn\'t lead anywhere. It may have been cut short when it was copied. Fill in the form to begin.');
    redirect('/');
}

$_SESSION['profile'] = $profile;
redirect('/loading');
