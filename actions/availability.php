<?php
require __DIR__.'/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
$serviceId=(int)($_GET['service_id']??0); $date=$_GET['date']??'';
echo json_encode(['slots'=>get_available_slots($serviceId,$date)],JSON_UNESCAPED_UNICODE);
