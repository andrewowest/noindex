<?php
define('APP_ROOT', __DIR__);
define('DATA', APP_ROOT.'/data');
function write_json($p,$a){ if(!is_dir(dirname($p))) mkdir(dirname($p),0775,true); file_put_contents($p,json_encode($a, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)); }
function read_json($p){ return file_exists($p) ? (json_decode(file_get_contents($p), true) ?: []) : []; }
function now(){ return time(); }

@mkdir(DATA.'/users',0775,true);
@mkdir(DATA.'/categories',0775,true);
@mkdir(DATA.'/threads',0775,true);
@mkdir(APP_ROOT.'/uploads/avatars',0775,true);

$settings = read_json(DATA.'/settings.json');
if(empty($settings)){
  $settings = ['site_name'=>'longreply.club','items_per_page'=>25,'posts_per_page'=>50];
  write_json(DATA.'/settings.json',$settings);
}

$users = read_json(DATA.'/users/users.json');
if(!isset($users['andrew'])){
  $users['andrew'] = [
    'username'=>'andrew','pass'=>password_hash('d33ts', PASSWORD_DEFAULT),
    'role'=>'admin','joined'=>now(),'invite_code'=>strtoupper(bin2hex(random_bytes(5))),
    'invited_by'=>null,'bio'=>'','avatar'=>'','last_seen'=>now()
  ];
  write_json(DATA.'/users/users.json',$users);
}

$cats = read_json(DATA.'/categories/index.json');
if(!isset($cats['general'])){
  $cats['general'] = ['id'=>'general','name'=>'General Discussion','desc'=>'Talk about anything'];
  write_json(DATA.'/categories/index.json',$cats);
}

$threadPath = DATA.'/threads/general__welcome.json';
if(!file_exists($threadPath)){
  $t = [
    'id'=>'welcome','cat'=>'general','title'=>'Welcome to longreply','author'=>'andrew','created'=>now(),
    'posts'=>[['id'=>1,'author'=>'andrew','time'=>now(),'body'=>'This is your first post. Go nuts!']]
  ];
  write_json($threadPath,$t);
}
echo "OK — bootstrap complete. Delete /bootstrap.php now.";
