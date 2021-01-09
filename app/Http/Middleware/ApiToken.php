<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\OauthClient;
use App\Models\OauthCode;

class ApiToken
{
    private $AUTH_USER;
    private $AUTH_PASS;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // header('Cache-Control: no-cache, must-revalidate, max-age=0');
        if(!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])){
            return response()->json([
                'response' => false,
                'message' => 'Granted Not Found.'
            ], 404);
        }
        $has_supplied_credentials = !(empty($_SERVER['PHP_AUTH_USER']) && empty($_SERVER['PHP_AUTH_PW']));
        $credentials = OauthClient::where('name', $_SERVER['PHP_AUTH_USER'])
            ->where('secret', $_SERVER['PHP_AUTH_PW'])->first();
        if($credentials != null){
            $this->AUTH_USER = $credentials->name;
            $this->AUTH_PASS = $credentials->secret;
        }
        $is_not_authenticated = (
            !$has_supplied_credentials ||
            $_SERVER['PHP_AUTH_USER'] != $this->AUTH_USER ||
            $_SERVER['PHP_AUTH_PW']   != $this->AUTH_PASS
        );
        if ($is_not_authenticated) {
            // header('HTTP/1.1 401 Authorization Required');
            // header('WWW-Authenticate: Basic realm="Access denied"');
            return response()->json([
                'response' => false,
                'message' => 'Unauthorized! Granted Access denied'
            ], 401);
        }
        return $next($request);
    }
}
