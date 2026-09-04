<?php
require __DIR__.'/../includes/bootstrap.php';
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Location: ../index.php');exit;}
verify_csrf();
$name=trim($_POST['name']??''); $phone=normalize_phone($_POST['phone']??''); $email=trim($_POST['email']??'');
$serviceId=(int)($_POST['service_id']??0); $date=$_POST['appointment_date']??''; $time=$_POST['appointment_time']??''; $notes=trim($_POST['notes']??'');
if(!$name||strlen($phone)<10||!$serviceId||!$date||!$time){flash('error','Preencha os campos obrigatórios.');header('Location: ../index.php#agendamento');exit;}
$slots=get_available_slots($serviceId,$date); if(!in_array(substr($time,0,5),$slots,true)){flash('error','Esse horário não está mais disponível. Escolha outro.');header('Location: ../index.php#agendamento');exit;}
$clientId=client_id_by_contact($name,$phone,$email?:null); $token=random_token();
$st=db()->prepare('INSERT INTO appointments(client_id,service_id,appointment_date,appointment_time,notes,status,confirmation_token) VALUES (?,?,?,?,?,?,?)');
$st->execute([$clientId,$serviceId,$date,$time,$notes?:null,'pendente',$token]); $appointmentId=(int)db()->lastInsertId();
$s=db()->prepare('SELECT name,price FROM services WHERE id=?');$s->execute([$serviceId]);$service=$s->fetch();
$confirm=app_url('confirm.php?token='.$token); $text="Olá, $name! Recebemos seu agendamento de {$service['name']} para ".date('d/m/Y',strtotime($date))." às ".substr($time,0,5).". Confirme aqui: $confirm";
whatsapp_send_text($phone,$text);
flash('success','Agendamento registrado. Enviamos a confirmação pelo WhatsApp quando a integração estiver configurada.');
header('Location: ../index.php?agendado=1#agendamento');exit;
