<?php
require_once __DIR__ . '/include/data.php';
$page_title = 'About — Akira Zakamoto';
$active = 'about';

$expo   = expo_load_all();
$prizes = prizes_load_all();

// Converte le parole tutte maiuscole in forma normale (prima lettera maiuscola).
function normalize_case(string $s): string
{
    $words = preg_split('/(\s+)/u', $s, -1, PREG_SPLIT_DELIM_CAPTURE);
    foreach ($words as &$w) {
        if (preg_match('/\s+/u', $w)) continue;            // separatori invariati
        $letters = preg_replace('/[^\p{L}]/u', '', $w);
        if ($letters !== '' && mb_strlen($letters, 'UTF-8') > 1
            && mb_strtoupper($w, 'UTF-8') === $w) {        // parola tutta maiuscola
            $low = mb_strtolower($w, 'UTF-8');
            $w = mb_strtoupper(mb_substr($low, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($low, 1, null, 'UTF-8');
        }
    }
    return implode('', $words);
}

// formatta una riga mostra/premio: "Title Subtitle, Where"
function cv_text(array $r): string
{
    $t = normalize_case(trim(w_clean((string)($r['titolo'] ?? ''))));
    $s = normalize_case(trim(w_clean((string)($r['sottotitolo'] ?? ''))));
    $d = normalize_case(trim(w_clean((string)($r['dove'] ?? ''))));
    $parts = [];
    $main = trim($t . ($s !== '' ? ' ' . $s : ''));
    if ($main !== '') $parts[] = $main;
    if ($d !== '')    $parts[] = $d;
    $out = implode(', ', $parts);
    // Rimette maiuscole le sigle di provincia tra parentesi: (to) -> (TO)
    $out = preg_replace_callback('/\(([A-Za-z]{2})\)/u',
        fn($m) => '(' . mb_strtoupper($m[1], 'UTF-8') . ')', $out);
    return $out;
}
function cv_year(array $r): string { return substr((string)($r['data'] ?? ''), 0, 4); }

// Raggruppa per anno (mantiene l'ordine decrescente) e stampa: anno, mostre a capo.
function render_cv(array $items): void
{
    $groups = [];
    foreach ($items as $r) {
        $y = cv_year($r);
        $groups[$y][] = cv_text($r);
    }
    foreach ($groups as $year => $rows) {
        echo '<div class="cv-group"><div class="cv-year">' . htmlspecialchars($year) . '</div><div class="cv-items">';
        foreach ($rows as $txt) {
            echo '<div class="cv-item"><span class="cv-star">★</span>' . htmlspecialchars($txt) . '</div>';
        }
        echo '</div></div>';
    }
}

include __DIR__ . '/include/head.php';
include __DIR__ . '/include/nav.php';
?>
<main class="page about">

  <h1>About</h1>

  <?php
  $mot = '<a href="https://motolese.com" target="_blank" rel="noopener">Luca Motolese</a>';
  $poems = [
    'en' => "Akira Zakamoto was born much loved.<br>He keeps a friend, imagined, unproved,<br>who carries out deeds of great renown,<br>called $mot, talk of the town.<br>He paints and invents frivolous stuff,<br>on the beach with wine and mussels enough.<br>He reads a great deal and writes very little,<br>he'll die before or after — life is brittle.",
    'it' => "Akira Zakamoto è nato amato.<br>Ha un amico immaginario<br>che compie grandi imprese,<br>detto $mot.<br>Dipinge e inventa cose futili,<br>sulla spiaggia vino e mitili.<br>Legge tanto e scrive poco,<br>morirà prima o dopo.",
    'fr' => "Akira Zakamoto est né comblé.<br>Il a un ami imaginaire, voilé,<br>qui accomplit de hauts faits sans pareil,<br>nommé $mot, le soleil.<br>Il peint et invente des choses futiles,<br>sur la plage, du vin et des moules dociles.<br>Il lit beaucoup et n'écrit qu'un peu,<br>il mourra tôt ou tard, si Dieu le veut.",
    'de' => "Akira Zakamoto kam geliebt zur Welt.<br>Er hat einen Freund, den niemand sieht,<br>der große Taten vollbringt als Held,<br>genannt $mot, in jedem Lied.<br>Er malt und erfindet nichtige Sachen,<br>am Strand bei Wein und Muschellachen.<br>Er liest sehr viel und schreibt nur wenig,<br>er stirbt wohl früher oder später, König.",
    'pt' => "Akira Zakamoto nasceu amado.<br>Tem um amigo imaginário,<br>que faz grandes feitos, dedicado,<br>chamado $mot, visionário.<br>Pinta e inventa coisas fúteis,<br>na praia, vinho e mexilhões úteis.<br>Lê bastante e escreve pouco,<br>mais cedo ou mais tarde morre, louco.",
  ];
  // bandierine SVG
  $flags = [
    'en' => "<svg viewBox='0 0 60 40'><rect width='60' height='40' fill='#012169'/><path d='M0,0 60,40 M60,0 0,40' stroke='#fff' stroke-width='8'/><path d='M0,0 60,40 M60,0 0,40' stroke='#C8102E' stroke-width='4'/><path d='M30,0 30,40 M0,20 60,20' stroke='#fff' stroke-width='12'/><path d='M30,0 30,40 M0,20 60,20' stroke='#C8102E' stroke-width='7'/></svg>",
    'it' => "<svg viewBox='0 0 3 2'><rect width='1' height='2' x='0' fill='#009246'/><rect width='1' height='2' x='1' fill='#fff'/><rect width='1' height='2' x='2' fill='#ce2b37'/></svg>",
    'fr' => "<svg viewBox='0 0 3 2'><rect width='1' height='2' x='0' fill='#0055A4'/><rect width='1' height='2' x='1' fill='#fff'/><rect width='1' height='2' x='2' fill='#EF4135'/></svg>",
    'de' => "<svg viewBox='0 0 5 3'><rect width='5' height='1' y='0' fill='#000'/><rect width='5' height='1' y='1' fill='#D00'/><rect width='5' height='1' y='2' fill='#FFCE00'/></svg>",
    'pt' => "<svg viewBox='0 0 5 3'><rect width='2' height='3' fill='#060'/><rect x='2' width='3' height='3' fill='#f00'/><circle cx='2' cy='1.5' r='0.55' fill='#ff0'/></svg>",
  ];
  $names = ['en'=>'English','it'=>'Italiano','fr'=>'Français','de'=>'Deutsch','pt'=>'Português'];
  ?>
  <div class="bio-block">
    <div id="poem" lang="it"><?php echo $poems['it']; ?></div>
    <div class="poem-flags">
      <?php foreach (['it','en','fr','de','pt'] as $lg): ?>
        <button type="button" class="poem-flag<?php echo $lg==='it'?' active':''; ?>" data-lang="<?php echo $lg; ?>" title="<?php echo $names[$lg]; ?>" aria-label="<?php echo $names[$lg]; ?>"><?php echo $flags[$lg]; ?></button>
      <?php endforeach; ?>
    </div>
    <div id="poem-src" hidden>
      <?php foreach ($poems as $lg => $html): ?>
        <div data-lang="<?php echo $lg; ?>"><?php echo $html; ?></div>
      <?php endforeach; ?>
    </div>
  </div>
  <script>
  (function(){
    var src=document.getElementById('poem-src'), poem=document.getElementById('poem');
    document.querySelectorAll('.poem-flag').forEach(function(b){
      b.addEventListener('click',function(){
        var l=b.getAttribute('data-lang'); var el=src.querySelector('[data-lang="'+l+'"]');
        if(!el) return;
        poem.innerHTML=el.innerHTML; poem.setAttribute('lang',l);
        document.querySelectorAll('.poem-flag').forEach(function(x){x.classList.toggle('active',x===b);});
      });
    });
  })();
  </script>

  <?php if ($expo): ?>
  <section class="cv-section">
    <h2>Exhibitions</h2>
    <div class="cv-list"><?php render_cv($expo); ?></div>
  </section>
  <?php endif; ?>

  <?php if ($prizes): ?>
  <section class="cv-section">
    <h2>Awards</h2>
    <div class="cv-list"><?php render_cv($prizes); ?></div>
  </section>
  <?php endif; ?>

</main>
<?php include __DIR__ . '/include/footer.php'; ?>
