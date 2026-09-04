<?php
require __DIR__ . '/../includes/bootstrap.php';
if (!empty($_SESSION['admin_id'])) { header('Location: dashboard.php'); exit; }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 verify_csrf(); $email=trim($_POST['email']??''); $password=$_POST['password']??'';
 $stmt=db()->prepare('SELECT * FROM users WHERE email=? LIMIT 1'); $stmt->execute([$email]); $u=$stmt->fetch();
 if($u && password_verify($password,$u['password_hash'])) { session_regenerate_id(true); $_SESSION['admin_id']=$u['id']; $_SESSION['admin_name']=$u['name']; header('Location: dashboard.php'); exit; }
 $error='E-mail ou senha inválidos.';
}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login | PG Estética</title><link rel="stylesheet" href="../assets/css/style.css"></head><body class="soft"><section><div class="container" style="max-width:480px"><div class="form-card"><div style="text-align:center"><img src="../assets/img/logo.jpeg" alt="PG Estética" style="width:130px;border-radius:50%"><h1 style="font-family:Georgia,serif;color:var(--brown)">Painel administrativo</h1></div><?php if($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><label>E-mail</label><input type="email" name="email" required><label>Senha</label><input type="password" name="password" required><button class="btn btn-primary" style="width:100%;margin-top:1rem">Entrar</button></form><p class="small muted" style="text-align:center"><a href="../index.php">← Voltar ao site</a></p></div></div></section></body></html>
