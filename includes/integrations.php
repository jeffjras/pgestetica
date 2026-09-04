<?php
function http_json(string $method,string $url,array $headers=[],?array $body=null): array {
    $ch=curl_init($url); $hdr=['Content-Type: application/json']; foreach($headers as $k=>$v)$hdr[]="$k: $v";
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_HTTPHEADER=>$hdr,CURLOPT_TIMEOUT=>25]);
    if($body!==null) curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($body,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    $raw=curl_exec($ch); $code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE); $err=curl_error($ch); curl_close($ch);
    $data=json_decode((string)$raw,true); return ['ok'=>$code>=200&&$code<300,'status'=>$code,'data'=>is_array($data)?$data:[],'raw'=>$raw,'error'=>$err];
}
function mp_create_preference(array $payment): array {
    $cfg=integrations_config()['mercado_pago']; if(!$cfg['access_token']) return ['ok'=>false,'error'=>'MP_ACCESS_TOKEN não configurado.'];
    $payload=['items'=>[['id'=>(string)$payment['id'],'title'=>$payment['description'],'quantity'=>1,'currency_id'=>'BRL','unit_price'=>(float)$payment['amount']]],'external_reference'=>(string)$payment['id'],'back_urls'=>['success'=>app_url('payment_return.php?result=success'),'pending'=>app_url('payment_return.php?result=pending'),'failure'=>app_url('payment_return.php?result=failure')],'auto_return'=>'approved','notification_url'=>app_url('webhooks/mercadopago.php'),'metadata'=>['payment_id'=>(int)$payment['id']]];
    $r=http_json('POST','https://api.mercadopago.com/checkout/preferences',['Authorization'=>'Bearer '.$cfg['access_token']],$payload);
    return $r['ok'] ? ['ok'=>true,'id'=>$r['data']['id']??null,'init_point'=>$cfg['sandbox']?($r['data']['sandbox_init_point']??$r['data']['init_point']??null):($r['data']['init_point']??null),'response'=>$r['data']] : ['ok'=>false,'error'=>$r['data']['message']??$r['error']?:'Falha no Mercado Pago','response'=>$r['data']];
}
function mp_get_payment(string $remoteId): array {
    $cfg=integrations_config()['mercado_pago']; if(!$cfg['access_token']) return ['ok'=>false];
    return http_json('GET','https://api.mercadopago.com/v1/payments/'.rawurlencode($remoteId),['Authorization'=>'Bearer '.$cfg['access_token']]);
}
function mp_validate_webhook(string $signature,string $requestId,string $dataId): bool {
    $secret=integrations_config()['mercado_pago']['webhook_secret']; if(!$secret) return false;
    $parts=[]; foreach(explode(',',$signature) as $p){$kv=array_map('trim',explode('=',$p,2)); if(count($kv)===2)$parts[$kv[0]]=$kv[1];}
    $ts=$parts['ts']??''; $v1=$parts['v1']??''; if(!$ts||!$v1)return false;
    $dataId=strtolower($dataId); $manifest=''; if($dataId!=='')$manifest.="id:$dataId;"; if($requestId!=='')$manifest.="request-id:$requestId;"; $manifest.="ts:$ts;";
    return hash_equals(hash_hmac('sha256',$manifest,$secret),$v1);
}
function whatsapp_send_text(string $phone,string $text): array {
    $cfg=integrations_config()['whatsapp']; $phone=normalize_phone($phone);
    if(!$cfg['phone_number_id']||!$cfg['access_token']) return ['ok'=>false,'mode'=>'link','url'=>'https://wa.me/'.$phone.'?text='.rawurlencode($text)];
    $url="https://graph.facebook.com/{$cfg['graph_version']}/{$cfg['phone_number_id']}/messages";
    return http_json('POST',$url,['Authorization'=>'Bearer '.$cfg['access_token']],['messaging_product'=>'whatsapp','to'=>$phone,'type'=>'text','text'=>['body'=>$text]]);
}
function whatsapp_send_template(string $phone,string $template,array $params=[]): array {
    $cfg=integrations_config()['whatsapp']; if(!$cfg['phone_number_id']||!$cfg['access_token']||!$template)return ['ok'=>false];
    $body=['messaging_product'=>'whatsapp','to'=>normalize_phone($phone),'type'=>'template','template'=>['name'=>$template,'language'=>['code'=>$cfg['language']]]];
    if($params)$body['template']['components']=[['type'=>'body','parameters'=>array_map(fn($v)=>['type'=>'text','text'=>(string)$v],$params)]];
    return http_json('POST',"https://graph.facebook.com/{$cfg['graph_version']}/{$cfg['phone_number_id']}/messages",['Authorization'=>'Bearer '.$cfg['access_token']],$body);
}
