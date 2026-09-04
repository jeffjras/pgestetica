<?php
// Execute via cron a cada hora: php /caminho/cron/reminders.php
require __DIR__.'/../includes/bootstrap.php';
$cfg=integrations_config()['whatsapp'];
$sql="SELECT a.id,a.appointment_date,a.appointment_time,c.name,c.phone,s.name service_name FROM appointments a JOIN clients c ON c.id=a.client_id JOIN services s ON s.id=a.service_id WHERE a.status='confirmado' AND a.reminder_sent_at IS NULL AND TIMESTAMP(a.appointment_date,a.appointment_time) BETWEEN DATE_ADD(NOW(), INTERVAL 20 HOUR) AND DATE_ADD(NOW(), INTERVAL 28 HOUR)";
$items=db()->query($sql)->fetchAll();
foreach($items as $a){$when=date('d/m/Y',strtotime($a['appointment_date'])).' às '.substr($a['appointment_time'],0,5);$res=$cfg['template_reminder']?whatsapp_send_template($a['phone'],$cfg['template_reminder'],[$a['name'],$a['service_name'],$when]):whatsapp_send_text($a['phone'],"Olá, {$a['name']}! Lembrando seu atendimento de {$a['service_name']} amanhã, $when. PG Estética.");if(!empty($res['ok']))db()->prepare('UPDATE appointments SET reminder_sent_at=NOW() WHERE id=?')->execute([$a['id']]);}
echo count($items)." lembrete(s) processado(s).\n";
