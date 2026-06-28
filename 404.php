<?php
// Qualsiasi URL inesistente porta alla home.
http_response_code(302);
header('Location: /');
exit;
