<?php
return ['name'=>env('APP_NAME','MaeYangHa Shop'),'env'=>env('APP_ENV','production'),'debug'=>(bool)env('APP_DEBUG',false),'url'=>env('APP_URL','http://localhost'),'timezone'=>'Asia/Bangkok','locale'=>'th','fallback_locale'=>'en','key'=>env('APP_KEY'),'cipher'=>'AES-256-CBC','allow_demo_seed'=>filter_var(env('ALLOW_DEMO_SEED',false), FILTER_VALIDATE_BOOLEAN)];
