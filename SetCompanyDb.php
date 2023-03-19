<?php

namespace App\Http\Middleware;

use Config;
use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetCompanyDb
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if($request->has('auth_token') || Session::has('auth_token')){
            if($request->has('auth_token')){
                $token=$request->get('auth_token');
                $result=DB::connection('mysql2')->table('mst_editor_database_info')->where("auth_token",$token)->first();
                DB::disconnect(env('DB_DATABASE'));
                if($result == null ) {
                    Auth::logout();
                    Session::flush();
                    return redirect('/');
                    //return redirect('http://www.webtart.com');
                }else{
                    if($result->is_first==1){
                        DB::connection('mysql2')->table('mst_editor_database_info')->where("id",$result->id)->update(array("is_first"=>0));
                        DB::disconnect(env('DB_DATABASE'));
                        $user=User::findorfail(1);
                        Auth::login($user);
                        Session::put('auth_token',$token);
                        Session::put('DB_DATABASE',$result->dbname);
                        Session::put('site',$result->paths);
                        return redirect('/info');
                    }
                    $user=User::findorfail(1);
                    Auth::login($user);
                    Session::put('auth_token',$token);
                    Session::put('DB_DATABASE',$result->dbname);
                    Session::put('site',$result->paths);
                    return redirect('/');
                }
            }
            if(Session::has('DB_DATABASE')) {

                Config::set('database.connections.mysql', array(
                    'driver' => 'mysql',
                    'host' => env('DB_HOST'),
                    'port' => env('DB_PORT'),
                    'database' =>Session::get('DB_DATABASE'),

                    'username' => 'username',

                    'password' => 'password',

                    'charset' => 'utf8',

                    'collation' => 'utf8_unicode_ci',

                    'prefix' => '',

                ));

                DB::reconnect('mysql');
            }else{
                Auth::logout();
                Session::flush();
                return redirect('/');
            }

        }
        return $next($request);
    }
}
