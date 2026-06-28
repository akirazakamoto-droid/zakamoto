<?php
require_once __DIR__ . '/data.php';
$page_title = $page_title ?? 'Akira Zakamoto';
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($page_title); ?></title>
<meta name="description" content="Akira Zakamoto — pop-political figuration. Painting, sculpture, street art, NFT.">
<?php if (!empty($head_extra)) echo $head_extra; ?>
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M12 2l2.9 6.2 6.8.8-5 4.6 1.3 6.7L12 17.8 5.9 21l1.3-6.7-5-4.6 6.8-.8z' fill='%234B0082'/%3E%3C/svg%3E">
<script>(function(){try{if(localStorage.getItem('zk-theme')==='dark')document.documentElement.classList.add('dark');}catch(e){}})();</script>
<?php if (IS_LOCAL): ?>
<link rel="preconnect" href="https://zakamoto.com" crossorigin>
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css?v=<?php echo ASSET_VER; ?>">
</head>
<body>
