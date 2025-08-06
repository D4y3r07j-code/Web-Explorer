<?php
return [
    'username' => 'admin',
    'password' => '$argon2id$v=19$m=65536,t=4,p=3$YWRtaW5wYXNzd29yZA$8K9Z8K9Z8K9Z8K9Z8K9Z8K9Z8K9Z8K9Z8K9Z8K9Z', // Cambia esto
    'session_timeout' => 3600, // 1 hora
    'max_login_attempts' => 5,
    'lockout_duration' => 900 // 15 minutos
];
?>

