<?php $active = $active ?? ''; ?>
<header class="site-header">
  <div class="bar">
    <?php if (!empty($hide_wordmark)): ?>
      <a class="wordmark wordmark-star" href="index.php" aria-label="Home">
        <svg viewBox="0 0 24 24" width="16" height="16" xmlns="http://www.w3.org/2000/svg"><path d="M12 2l2.9 6.2 6.8.8-5 4.6 1.3 6.7L12 17.8 5.9 21l1.3-6.7-5-4.6 6.8-.8z"/></svg>
      </a>
    <?php else: ?>
      <a class="wordmark" href="index.php">AKIRA ZAKAMOTO</a>
    <?php endif; ?>
    <div class="header-right">
      <nav class="site-nav-inline">
        <a href="art.php"       class="<?php echo $active==='art'       ?'is-active':''; ?>">Art</a>
        <a href="about.php"     class="<?php echo $active==='about'     ?'is-active':''; ?>">About</a>
        <a href="files.php"     class="<?php echo $active==='files'     ?'is-active':''; ?>">Files</a>
        <a href="atelier.php"   class="<?php echo $active==='atelier'   ?'is-active':''; ?>">Atelier</a>
        <a href="contact.php"   class="<?php echo $active==='contact'   ?'is-active':''; ?>">Contact</a>
        <a href="galleries.php" class="<?php echo $active==='galleries' ?'is-active':''; ?>">Galleries &amp; Collectors</a>
      </nav>
      <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode" title="Light / dark">◐</button>
      <button class="nav-toggle" id="navToggle" aria-label="Menu" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</header>

<nav class="nav-overlay" id="navOverlay" aria-hidden="true">
  <a href="art.php"       class="<?php echo $active==='art'       ?'is-active':''; ?>">Art</a>
  <a href="about.php"     class="<?php echo $active==='about'     ?'is-active':''; ?>">About</a>
  <a href="files.php"     class="<?php echo $active==='files'     ?'is-active':''; ?>">Files</a>
  <a href="atelier.php"   class="<?php echo $active==='atelier'   ?'is-active':''; ?>">Atelier</a>
  <a href="contact.php"   class="<?php echo $active==='contact'   ?'is-active':''; ?>">Contact</a>
  <a href="galleries.php" class="<?php echo $active==='galleries' ?'is-active':''; ?>">Galleries &amp; Collectors</a>
</nav>
