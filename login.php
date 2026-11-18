<?php
require __DIR__.'/bootstrap.php';
require_once __DIR__.'/smtp.php';
if(!empty($_SESSION['uid']))go();
if(isset($_GET['restart'])){unset($_SESSION['pending_user'],$_SESSION['pending_purpose'],$_SESSION['setup_verified_user']);go('login.php');}
$message='';$error='';$mode=isset($_SESSION['pending_user'])?'verify':'login';
function mn_send_verification(array $user,string $purpose):bool {
    $id=(int)$user['id'];$old=q('SELECT * FROM mn_codes WHERE user_id=?',[$id])->fetch();
    $recent=$old&&strtotime($old['window_at'])>time()-3600;
    $count=$recent?(int)$old['sends']:0;
    if($old&&strtotime($old['sent_at'])>time()-60)return false;
    if($count>=5)return false;
    $code=(string)random_int(100000,999999);
    $minutes=max(2,min(30,(int)setting('code_minutes','10')));
    if(!mynotes_send_code($user['email'],$code,$minutes)) {error_log('MyNotes verification email failed');return false;}
    q('INSERT INTO mn_codes(user_id,code_hash,expires_at,attempts,sent_at,window_at,sends) VALUES(?,?,DATE_ADD(NOW(),INTERVAL '.$minutes.' MINUTE),0,NOW(),NOW(),1) ON DUPLICATE KEY UPDATE code_hash=VALUES(code_hash),expires_at=VALUES(expires_at),attempts=0,sent_at=NOW(),window_at=IF(window_at<DATE_SUB(NOW(),INTERVAL 1 HOUR),NOW(),window_at),sends=IF(window_at<DATE_SUB(NOW(),INTERVAL 1 HOUR),1,sends+1)',[$id,password_hash($code,PASSWORD_DEFAULT)]);
    $_SESSION['pending_user']=$id;$_SESSION['pending_purpose']=$purpose;
    return true;
}
if($_SERVER['REQUEST_METHOD']==='POST') {
    check_csrf();$action=(string)($_POST['action']??'');
    if($action==='login') {
        $email=strtolower(trim((string)($_POST['email']??'')));
        $password=(string)($_POST['password']??'');
        $u=q('SELECT * FROM mn_users WHERE email=? AND enabled=1',[$email])->fetch();
        if((int)($_SESSION['login_failures']??0)>=10){$error='Too many attempts. Close this browser session and try again later.';}
        elseif($u&&!empty($u['password_hash'])&&password_verify($password,$u['password_hash'])) {
            $_SESSION['login_failures']=0;
            if(setting('require_2fa','1')==='1'&&!mn_trusted_browser((int)$u['id'])) {
                if(mn_send_verification($u,'login')){$mode='verify';$message='A six-digit verification code was sent to your email.';}
                else $error='Unable to send a code yet. Please wait a minute and try again.';
            } else {mn_finish_login($u);go();}
        } else {$_SESSION['login_failures']=(int)($_SESSION['login_failures']??0)+1;$error='Invalid email or password.';}
    }
    elseif($action==='setup') {
        $email=strtolower(trim((string)($_POST['email']??'')));
        $u=q('SELECT * FROM mn_users WHERE email=? AND enabled=1 AND password_hash IS NULL',[$email])->fetch();
        $message='If this approved account needs a password, a verification code has been sent.';
        if($u&&mn_send_verification($u,'setup'))$mode='verify';
    }
    elseif($action==='verify') {
        $id=(int)($_SESSION['pending_user']??0);$code=trim((string)($_POST['code']??''));
        $r=$id?q('SELECT c.*,u.email,u.password_hash,u.enabled,u.is_admin,(c.expires_at<=NOW()) AS is_expired FROM mn_codes c JOIN mn_users u ON u.id=c.user_id WHERE c.user_id=?',[$id])->fetch():false;
        if($r&&(int)$r['enabled']&&preg_match('/^[0-9]{6}$/D',$code)&&!(int)$r['is_expired']&&(int)$r['attempts']<5) {
            q('UPDATE mn_codes SET attempts=attempts+1 WHERE user_id=?',[$id]);
            if(password_verify($code,$r['code_hash'])) {
                q('DELETE FROM mn_codes WHERE user_id=?',[$id]);
                if(($_SESSION['pending_purpose']??'')==='setup' && $r['password_hash']===null) {
                    $_SESSION['setup_verified_user']=$id;unset($_SESSION['pending_user'],$_SESSION['pending_purpose']);$mode='password';
                } elseif(($_SESSION['pending_purpose']??'')==='login' && $r['password_hash']!==null) {
                    mn_remember_browser($id);mn_finish_login($r);go();
                }
            }
        }
        if($mode!=='password'){$error='Invalid or expired code.';$mode='verify';}
    }
    elseif($action==='set_password') {
        $id=(int)($_SESSION['setup_verified_user']??0);
        $password=(string)($_POST['password']??'');$confirm=(string)($_POST['confirm']??'');
        if(!$id){$error='Start password setup again.';}
        elseif(strlen($password)<12||strlen($password)>128){$error='Use a password between 12 and 128 characters.';$mode='password';}
        elseif(!hash_equals($password,$confirm)){$error='Passwords do not match.';$mode='password';}
        else {
            q('UPDATE mn_users SET password_hash=? WHERE id=? AND enabled=1 AND password_hash IS NULL',[password_hash($password,PASSWORD_DEFAULT),$id]);
            unset($_SESSION['setup_verified_user']);$message='Password saved. Sign in with your email and password.';$mode='login';
        }
    }
}
if(!empty($_SESSION['setup_verified_user']))$mode='password';
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MyNotes · Sign in</title><link rel="stylesheet" href="style.css"></head><body><main class="login"><h1>My<span>Notes</span></h1><p>Private notes. Approved email accounts only.</p>
<?php if($message):?><p class="notice"><?=h($message)?></p><?php endif;?>
<?php if($error):?><p class="error"><?=h($error)?></p><?php endif;?>
<?php if($mode==='login'):?>
<form method="post"><input type="hidden" name="csrf" value="<?=h(csrf())?>"><input type="hidden" name="action" value="login"><label>Email</label><input type="email" name="email" autocomplete="username" required><label>Password</label><input type="password" name="password" autocomplete="current-password" required><button>Sign in</button></form>
<details><summary>First time here? Set up your password</summary><form method="post"><input type="hidden" name="csrf" value="<?=h(csrf())?>"><input type="hidden" name="action" value="setup"><label>Approved email address</label><input type="email" name="email" required><button>Send setup code</button></form></details>
<?php elseif($mode==='verify'):?>
<form method="post"><input type="hidden" name="csrf" value="<?=h(csrf())?>"><input type="hidden" name="action" value="verify"><label>Six-digit email code</label><input name="code" pattern="[0-9]{6}" maxlength="6" inputmode="numeric" autocomplete="one-time-code" required><button>Verify</button></form><p><a href="login.php?restart=1">Start again</a></p>
<?php elseif($mode==='password'):?>
<form method="post"><input type="hidden" name="csrf" value="<?=h(csrf())?>"><input type="hidden" name="action" value="set_password"><label>Create password (12 characters minimum)</label><input type="password" name="password" minlength="12" maxlength="128" autocomplete="new-password" required><label>Confirm password</label><input type="password" name="confirm" minlength="12" maxlength="128" autocomplete="new-password" required><button>Save password</button></form>
<?php endif;?></main></body></html>
