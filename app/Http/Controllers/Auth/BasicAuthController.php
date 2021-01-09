<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OauthClient;
use App\Models\OauthCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class BasicAuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function request_api_key()
    {
        $credentials = OauthClient::where('name', $_SERVER['PHP_AUTH_USER'])
                                    ->where('secret', $_SERVER['PHP_AUTH_PW'])->first();
        if ($credentials == null) {
            $response = [
                'response' => false,
                'message' => 'Invalid username and password!',
            ];
            return $response;
        }
        $key_md5 = Str::random(60);
        $data = OauthCode::updateOrCreate([
            'user_id' => $credentials->user_id
        ],[
            'id' => Str::random(60),
            'user_id' => $credentials->user_id,
            'client_id' =>  $credentials->id,
            'scopes' =>   md5($key_md5),
            'revoked' => 1,
        ]);
        $response = [
            'response' => true,
            'x-api-key' => $data->scopes,
        ];
        return $response;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function register_grant_client(Request $request)
    {
        $validator = $this->validator($request->all());
        if ($validator->fails()) {
            $response = [
                'response' => false,
                'message' => $validator->errors()->first(),
            ];
            return $response;
        }

        $user = User::create([
            'name' => $request['name'],
            'email' => $request['email'],
            'password' => Hash::make($request['password']),
            'last_login' => date('Y-m-d H:m:s'),
        ]);
        $user->createToken($request['name'])->accessToken;

        $key_md5 = Str::random(60);
        $data = OauthClient::updateOrCreate([
            'user_id' => $user->id
        ],[
            'user_id' => $user->id,
            'name' => $request['name'],
            'secret' => md5($key_md5),
            'redirect' => \Request::ip(),
            'personal_access_client' => 1,
            'password_client' => 1,
            'revoked' => 1,
        ]);
        $data->fresh();
        $response = [
            'response' => true,
            'data' => $data,
        ];
        return $response;
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ]);
    }
}
