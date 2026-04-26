<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
if (is_logged_in()) audit_log('user_logout','user',current_user_id());
session_kill();
redirect('/pages/auth/login.php');
