<?php
namespace App\Http\Middleware;
use Closure;
class AdminAccess {public function handle($request,Closure $next){if(!$request->user())return redirect()->route('login');$module=$request->route('module')?:($request->routeIs('admin.reports')?'reports':'dashboard');if(!$request->user()->canManage($module))abort(403,'You do not have access to this admin module.');return $next($request);}}
