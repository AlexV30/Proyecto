<?php
require_once 'config.php';
session_destroy();
responder(true, null, 'Sesión cerrada correctamente.');
