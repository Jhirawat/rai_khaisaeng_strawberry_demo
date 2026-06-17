<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
class SocialLoginStatusController extends Controller
{
    public function index()
    {
        $providers = collect(['google'=>'Google','facebook'=>'Facebook','line'=>'LINE'])->map(function($label,$key){
            return [
                'key'=>$key,
                'label'=>$label,
                'client_id'=>filled(config("services.$key.client_id")),
                'client_secret'=>filled(config("services.$key.client_secret")),
                'redirect'=>config("services.$key.redirect"),
                'callback'=>route('social.callback',$key),
                'ready'=>filled(config("services.$key.client_id")) && filled(config("services.$key.client_secret")) && filled(config("services.$key.redirect")),
            ];
        });
        return view('admin.social-login.index', compact('providers'));
    }
}
