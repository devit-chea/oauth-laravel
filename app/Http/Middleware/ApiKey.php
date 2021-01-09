<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\OauthCode;
use App\Models\OauthClient;

class ApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $header_key = $request->header('x-api-key');
        $check_api_key = OauthCode::where('scopes', $header_key)->first();
        if($check_api_key == null){
            return response()->json([
                'response' => false,
                'message' => 'X-API-KEY Access denied.'
            ], 404);
        }

        if($check_api_key != null){
            $auth_client = OauthClient::where('user_id', $check_api_key->user_id)
            ->where('personal_access_client', 1)->first();
            if($auth_client == null){
                return response()->json([
                    'response' => false,
                    'message' => 'Grant Credentail not yet Activated.'
                ], 404);
            }
            
        }
        return $next($request);
    }
}
