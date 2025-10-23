<?php
require __DIR__.'/inc/common.php';
session_destroy();
if (!headers_sent()) header('Location: /index.php');
