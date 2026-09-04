<?php
function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function verify_csrf(): void { if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); exit('Sessão expirada. Atualize a página e tente novamente.'); } }
function admin_required(): void { if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; } }
function money(float $value): string { return 'R$ '.number_format($value,2,',','.'); }
function normalize_phone(string $phone): string { return preg_replace('/\D+/', '', $phone); }
function random_token(int $bytes = 24): string { return bin2hex(random_bytes($bytes)); }
function app_config(): array { static $c; return $c ??= require __DIR__.'/../config/app.php'; }
function integrations_config(): array { static $c; return $c ??= require __DIR__.'/../config/integrations.php'; }
function app_url(string $path=''): string { return rtrim(app_config()['url'],'/').'/'.ltrim($path,'/'); }
function flash(string $key, ?string $value=null): ?string { if ($value !== null) { $_SESSION['_flash'][$key]=$value; return null; } $v=$_SESSION['_flash'][$key]??null; unset($_SESSION['_flash'][$key]); return $v; }
function is_valid_status(string $status): bool { return in_array($status,['pendente','confirmado','concluido','cancelado','nao_compareceu'],true); }
function client_id_by_contact(string $name,string $phone,?string $email): int {
    $pdo=db(); $phone=normalize_phone($phone);
    $st=$pdo->prepare('SELECT id FROM clients WHERE phone=? OR (email IS NOT NULL AND email=?) LIMIT 1'); $st->execute([$phone,$email?:null]);
    $id=$st->fetchColumn();
    if($id){ $pdo->prepare('UPDATE clients SET name=?, phone=?, email=COALESCE(?,email), updated_at=NOW() WHERE id=?')->execute([$name,$phone,$email?:null,$id]); return (int)$id; }
    $pdo->prepare('INSERT INTO clients(name,phone,email) VALUES (?,?,?)')->execute([$name,$phone,$email?:null]); return (int)$pdo->lastInsertId();
}
function get_available_slots(int $serviceId,string $date): array {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date) || $date < date('Y-m-d')) return [];
    $pdo=db();
    $s=$pdo->prepare('SELECT duration_minutes FROM services WHERE id=? AND active=1'); $s->execute([$serviceId]); $duration=(int)$s->fetchColumn(); if(!$duration) return [];
    $weekday=(int)date('w',strtotime($date));
    $wh=$pdo->prepare('SELECT start_time,end_time FROM business_hours WHERE weekday=? AND active=1 ORDER BY start_time'); $wh->execute([$weekday]); $hours=$wh->fetchAll(); if(!$hours) return [];
    $st=$pdo->prepare("SELECT appointment_time, s.duration_minutes FROM appointments a JOIN services s ON s.id=a.service_id WHERE appointment_date=? AND a.status NOT IN ('cancelado')"); $st->execute([$date]); $booked=$st->fetchAll();
    $bl=$pdo->prepare('SELECT start_time,end_time FROM schedule_blocks WHERE block_date=?'); $bl->execute([$date]); $blocks=$bl->fetchAll();
    $interval=max(5,(int)app_config()['slot_interval']); $slots=[];
    foreach($hours as $h){ $start=strtotime("$date {$h['start_time']}"); $end=strtotime("$date {$h['end_time']}");
        for($t=$start;$t+$duration*60<=$end;$t+=$interval*60){ if($date===date('Y-m-d') && $t<time()) continue; $tEnd=$t+$duration*60; $ok=true;
            foreach($booked as $b){$bs=strtotime("$date {$b['appointment_time']}");$be=$bs+(int)$b['duration_minutes']*60;if($t<$be&&$tEnd>$bs){$ok=false;break;}}
            if($ok) foreach($blocks as $b){$bs=strtotime("$date {$b['start_time']}");$be=strtotime("$date {$b['end_time']}");if($t<$be&&$tEnd>$bs){$ok=false;break;}}
            if($ok)$slots[]=date('H:i',$t);
        }
    } return array_values(array_unique($slots));
}
