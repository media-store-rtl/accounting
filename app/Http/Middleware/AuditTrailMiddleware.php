<?php
namespace App\Http\Middleware;
use App\Services\AuditTrailService; use Closure; use Illuminate\Http\Request;
class AuditTrailMiddleware { public function handle(Request $request,Closure $next){$response=$next($request); if($request->user() && $request->session()->get('company_id')){ $method=$request->method(); if(in_array($method,['POST','PUT','PATCH','DELETE'],true)) app(AuditTrailService::class)->record($request ,'http','request',null,null,null,['status'=>$response->getStatusCode()],$response->getStatusCode()); } return $response; } }