<?php
// Fixed-host YouTube image proxy. No arbitrary remote URLs.
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
uid();
$id=(string)($_GET['v']??'');
if(!preg_match('/^[A-Za-z0-9_-]{11}$/D',$id)){http_response_code(400);exit;}
$hosts=['i.ytimg.com','img.youtube.com'];
$errors=[];
foreach($hosts as $host){
    $url='https://'.$host.'/vi/'.$id.'/hqdefault.jpg';
    $data=false;$status=0;$reason='';
    if(function_exists('curl_init')){
        $ch=curl_init($url);
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>12,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_USERAGENT=>'Mozilla/5.0 (compatible; MyNotesThumbnail/1.0)',CURLOPT_MAXFILESIZE=>1000000]);
        $data=curl_exec($ch);
        $status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
        if($data===false)$reason=curl_error($ch);
        curl_close($ch);
    }else{
        $ctx=stream_context_create(['http'=>['timeout'=>10,'follow_location'=>0,'user_agent'=>'Mozilla/5.0'],'ssl'=>['verify_peer'=>true,'verify_peer_name'=>true]]);
        $data=@file_get_contents($url,false,$ctx,0,1000001);
        if(isset($http_response_header[0])&&preg_match('/\s(\d{3})\s/',$http_response_header[0],$m))$status=(int)$m[1];
        if($data===false)$reason='Remote fetch failed';
    }
    if($status===200&&is_string($data)&&strlen($data)>=100&&strlen($data)<=1000000){
        $info=@getimagesizefromstring($data);
        if($info!==false&&($info['mime']??'')==='image/jpeg'){
            header('Content-Type: image/jpeg');
            header('Cache-Control: private, max-age=86400');
            header('X-Content-Type-Options: nosniff');
            echo $data;exit;
        }
    }
    $errors[]=$host.': HTTP '.$status.($reason?' ('.$reason.')':'');
}
// Diagnostic details are only shown to an authenticated user requesting debug=1.
if(($_GET['debug']??'')==='1'){
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(502);
    echo "Thumbnail fetch failed\n".implode("\n",$errors);exit;
}
http_response_code(404);
