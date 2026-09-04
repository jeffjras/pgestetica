<?php
require __DIR__.'/../includes/bootstrap.php';
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Location: ../packages.php');exit;} verify_csrf();
$packageId=(int)($_POST['package_id']??0);$name=trim($_POST['name']??'');$phone=normalize_phone($_POST['phone']??'');$email=trim($_POST['email']??'');
$st=db()->prepare('SELECT * FROM packages WHERE id=? AND active=1');$st->execute([$packageId]);$p=$st->fetch(); if(!$p||!$name||strlen($phone)<10){flash('error','Dados inválidos.');header('Location: ../packages.php');exit;}
$clientId=client_id_by_contact($name,$phone,$email?:null);$expires=date('Y-m-d',strtotime('+'.(int)$p['validity_days'].' days'));
db()->prepare('INSERT INTO client_packages(client_id,package_id,expires_at,status) VALUES (?,?,?,?)')->execute([$clientId,$packageId,$expires,'pendente']);$cpId=(int)db()->lastInsertId();
db()->prepare('INSERT INTO payments(client_id,client_package_id,description,amount,status) VALUES (?,?,?,?,?)')->execute([$clientId,$cpId,'Pacote '.$p['name'],$p['price'],'criado']);$payId=(int)db()->lastInsertId();
$pay=['id'=>$payId,'description'=>'Pacote '.$p['name'],'amount'=>(float)$p['price']];$mp=mp_create_preference($pay);
if(!$mp['ok']||empty($mp['init_point'])){flash('error','Mercado Pago ainda não está configurado. Defina MP_ACCESS_TOKEN no servidor.');header('Location: ../packages.php');exit;}
db()->prepare('UPDATE payments SET preference_id=?,checkout_url=?,external_reference=?,status=? WHERE id=?')->execute([$mp['id'],$mp['init_point'],(string)$payId,'pendente',$payId]);
header('Location: '.$mp['init_point']);exit;
