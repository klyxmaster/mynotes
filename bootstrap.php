<?php
declare(strict_types=1);
require __DIR__.'/config.php';
ini_set('display_errors','0');
ini_set('log_errors','1');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS']==='off') { if (!in_array($_SERVER['HTTP_HOST']??'', ['localhost','127.0.0.1'],true)) {http_response_code(403);exit('HTTPS required');} }
$pdo=new PDO('mysql:host='.MN_DB_HOST.';dbname='.MN_DB_NAME.';charset=utf8mb4',MN_DB_USER,MN_DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
function db():PDO{global $pdo;return $pdo;}
function q(string $sql,array $params=[]):PDOStatement{$s=db()->prepare($sql);$s->execute($params);return $s;}
function setting(string $name,string $default=''):string{$v=q('SELECT value FROM mn_settings WHERE name=?',[$name])->fetchColumn();return $v===false?$default:(string)$v;}
$ttl=max(5,min(43200,(int)setting('session_minutes','10080')));
ini_set('session.use_strict_mode','1');ini_set('session.use_only_cookies','1');ini_set('session.cookie_httponly','1');ini_set('session.cookie_secure','1');ini_set('session.cookie_samesite','Lax');ini_set('session.gc_maxlifetime',(string)($ttl*60));
session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>true,'httponly'=>true,'samesite'=>'Lax']);session_start();
function h($v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf():string{return $_SESSION['csrf']??=bin2hex(random_bytes(32));}
function check_csrf():void{if(!hash_equals(csrf(),(string)($_POST['csrf']??''))){http_response_code(403);exit('Invalid form token');}}
function uid():int{if(empty($_SESSION['uid'])){header('Location: login.php');exit;}if(time()-(int)($_SESSION['last_active']??0)>max(5,(int)setting('session_minutes','10080'))*60){session_unset();session_destroy();header('Location: login.php');exit;}$_SESSION['last_active']=time();return (int)$_SESSION['uid'];}
function admin():void{if(empty($_SESSION['admin'])){http_response_code(403);exit('Administrator only');}}
function go(string $url='index.php'):never{header('Location: '.$url);exit;}
function safe_cat(int $id,int $user):?int{if(!$id)return null;$v=q('SELECT id FROM mn_categories WHERE id=? AND user_id=?',[$id,$user])->fetchColumn();return $v===false?null:(int)$v;}
function getcat(int $user,string $name,?int $parent=null):?int{$name=trim($name);if($name==='')return $parent;if(mb_strlen($name)>160)throw new RuntimeException('Category too long');$s=q('SELECT id FROM mn_categories WHERE user_id=? AND name=? AND parent_id <=> ? LIMIT 1',[$user,$name,$parent]);$id=$s->fetchColumn();if($id)return (int)$id;q('INSERT INTO mn_categories(user_id,parent_id,name) VALUES(?,?,?)',[$user,$parent,$name]);return (int)db()->lastInsertId();}
function set_tags(int $user,int $note,string $raw):void{q('DELETE FROM mn_note_tags WHERE note_id=?',[$note]);foreach(array_unique(array_filter(array_map('trim',explode(',',$raw)))) as $tag){if(mb_strlen($tag)>160)continue;q('INSERT IGNORE INTO mn_tags(user_id,name) VALUES(?,?)',[$user,$tag]);$id=q('SELECT id FROM mn_tags WHERE user_id=? AND name=?',[$user,$tag])->fetchColumn();q('INSERT IGNORE INTO mn_note_tags(note_id,tag_id) VALUES(?,?)',[$note,$id]);}}
function tags_for(int $note):string{return implode(', ',q('SELECT t.name FROM mn_tags t JOIN mn_note_tags nt ON nt.tag_id=t.id WHERE nt.note_id=? ORDER BY t.name',[$note])->fetchAll(PDO::FETCH_COLUMN));}

function mn_finish_login(array $user):void {
    session_regenerate_id(true);
    $_SESSION['uid']=(int)$user['id'];
    $_SESSION['admin']=(bool)$user['is_admin'];
    $_SESSION['last_active']=time();
    unset($_SESSION['pending_user'],$_SESSION['pending_purpose'],$_SESSION['login_failures']);
}
function mn_trusted_browser(int $user):bool {
    $cookie=(string)($_COOKIE['mn_trusted']??'');
    if(!preg_match('/^[a-f0-9]{32}:[a-f0-9]{64}$/D',$cookie))return false;
    [$selector,$token]=explode(':',$cookie,2);
    $r=q('SELECT token_hash FROM mn_trusted_browsers WHERE selector=? AND user_id=? AND expires_at>NOW()',[$selector,$user])->fetch();
    return $r && hash_equals($r['token_hash'],hash('sha256',$token));
}
function mn_remember_browser(int $user):void {
    $selector=bin2hex(random_bytes(16));$token=bin2hex(random_bytes(32));
    q('DELETE FROM mn_trusted_browsers WHERE user_id=? AND expires_at<=NOW()',[$user]);
    q('INSERT INTO mn_trusted_browsers(user_id,selector,token_hash,expires_at) VALUES(?,?,?,DATE_ADD(NOW(),INTERVAL 30 DAY))',[$user,$selector,hash('sha256',$token)]);
    setcookie('mn_trusted',$selector.':'.$token,['expires'=>time()+30*86400,'path'=>'/MyNotes/','secure'=>true,'httponly'=>true,'samesite'=>'Lax']);
}
