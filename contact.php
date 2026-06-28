<?php
require_once __DIR__ . '/include/data.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$page_title = 'Contact — Akira Zakamoto';
$active = 'contact';

// Se le chiavi hCaptcha sono configurate uso hCaptcha, altrimenti captcha aritmetica
$use_hcaptcha = (HCAPTCHA_SITE !== '' && HCAPTCHA_SECRET !== '');

$sent = false;
$error = '';
$name = $email = $message = '';

// Verifica hCaptcha lato server
function hcaptcha_verify(string $resp): bool {
    if ($resp === '') return false;
    $data = http_build_query([
        'secret'   => HCAPTCHA_SECRET,
        'response' => $resp,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);
    $out = false;
    if (function_exists('curl_init')) {
        $ch = curl_init('https://api.hcaptcha.com/siteverify');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>$data, CURLOPT_TIMEOUT=>10]);
        $out = curl_exec($ch);
        curl_close($ch);
    }
    if ($out === false || $out === null || $out === '') {
        $ctx = stream_context_create(['http'=>['method'=>'POST','header'=>'Content-Type: application/x-www-form-urlencoded','content'=>$data,'timeout'=>10]]);
        $out = @file_get_contents('https://api.hcaptcha.com/siteverify', false, $ctx);
    }
    if (!$out) return false;
    $j = json_decode($out, true);
    return !empty($j['success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = strip_tags(trim($_POST['name'] ?? ''));
    $name    = str_replace(["\r", "\n"], ' ', $name);
    $email   = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $message = trim($_POST['message'] ?? '');

    // Anti-spam: honeypot (deve restare vuoto) + hCaptcha oppure captcha aritmetica
    $honeypot   = trim($_POST['website'] ?? '');
    $cap_answer = trim($_POST['captcha'] ?? '');
    $cap_expect = $_SESSION['cap_sum'] ?? null;
    $captcha_ok = $use_hcaptcha
        ? hcaptcha_verify(trim($_POST['h-captcha-response'] ?? ''))
        : ($cap_expect !== null && ctype_digit($cap_answer) && (int)$cap_answer === (int)$cap_expect);

    if ($honeypot !== '') {
        // probabile bot: fingiamo successo senza inviare
        $sent = true;
        $name = $email = $message = '';
    } elseif (!$captcha_ok) {
        $error = $use_hcaptcha
            ? 'Please complete the anti-spam check and try again.'
            : 'Incorrect answer to the anti-spam question. Please try again.';
    } elseif ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please complete all fields with a valid email and try again.';
    } else {
        require_once __DIR__ . '/include/PHPMailer/Exception.php';
        require_once __DIR__ . '/include/PHPMailer/PHPMailer.php';
        require_once __DIR__ . '/include/PHPMailer/SMTP.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
            $mail->addAddress(CONTACT_TO);
            $mail->addReplyTo($email, $name);

            $mail->Subject = "New contact from zakamoto.com";
            $mail->Body    = "Name: $name\nEmail: $email\n\nMessage:\n$message\n";

            $mail->send();
            $sent = true;
            $name = $email = $message = '';
        } catch (Throwable $e) {
            $error = "Oops! Something went wrong and we couldn't send your message.";
            error_log('Contact SMTP error: ' . $mail->ErrorInfo);
        }
    }
}

// Captcha aritmetica di fallback (usata solo se hCaptcha non è configurato)
$cap_a = random_int(1, 9);
$cap_b = random_int(1, 9);
$_SESSION['cap_sum'] = $cap_a + $cap_b;

include __DIR__ . '/include/head.php';
include __DIR__ . '/include/nav.php';
?>
<main class="page contact-page">
  <h1>Contact</h1>
  <p class="contact-intro">Use this form to get in touch with the artist — for enquiries about works, exhibitions, collaborations or to purchase a piece. Leave your name and email and we'll get back to you.</p>

  <?php if ($sent): ?>
    <p class="form-ok">Thank you! Your message has been sent.</p>
  <?php else: ?>
    <?php if ($error): ?><p class="form-err"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
    <form class="contact-form" method="post" action="contact.php">
      <label>Name <span>*</span>
        <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
      </label>
      <label>Email <span>*</span>
        <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
      </label>
      <label>Message <span>*</span>
        <textarea name="message" rows="7" required><?php echo htmlspecialchars($message); ?></textarea>
      </label>

      <?php if (!$use_hcaptcha): ?>
      <label>Anti-spam: what is <?php echo $cap_a; ?> + <?php echo $cap_b; ?>? <span>*</span>
        <input type="text" name="captcha" inputmode="numeric" autocomplete="off" required>
      </label>
      <?php endif; ?>

      <div class="hp-field" aria-hidden="true" style="position:absolute;left:-5000px;top:auto;width:1px;height:1px;overflow:hidden">
        <label>Leave this field empty
          <input type="text" name="website" tabindex="-1" autocomplete="off">
        </label>
      </div>

      <?php if ($use_hcaptcha): ?>
      <div class="h-captcha" data-sitekey="<?php echo htmlspecialchars(HCAPTCHA_SITE); ?>" style="margin:6px 0 4px"></div>
      <?php endif; ?>

      <button type="submit">Submit</button>
    </form>
    <?php if ($use_hcaptcha): ?>
    <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
    <?php endif; ?>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/include/footer.php'; ?>
