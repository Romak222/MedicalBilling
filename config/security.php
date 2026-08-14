<?php

return [
    'login_max_attempts' => (int) env('SECURITY_LOGIN_MAX_ATTEMPTS', 5),
    'login_decay_seconds' => (int) env('SECURITY_LOGIN_DECAY_SECONDS', 900),
    'hsts' => (bool) env('SECURITY_HSTS', false),
];
