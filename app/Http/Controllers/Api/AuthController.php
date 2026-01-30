<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;
use App\Notifications\NewUserRegisterNotification;
use Spatie\Permission\Models\Role;
use App\Events\UserlogProcessed;
use App\Models\UserCyberV6;
use OwenIt\Auditing\Facades\Auditor;


class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }


    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    // public function register() {
    //     $validator = Validator::make(request()->all(), [
    //         'name' => 'required',
    //         'email' => 'required|email|unique:users',
    //         'password' => 'required|confirmed|min:8',
    //     ]);

    //     if($validator->fails()){
    //         return response()->json($validator->errors()->toJson(), 400);
    //     }

    //     $user = new User;
    //     $user->name = request()->name;
    //     $user->email = request()->email;
    //     $user->password = bcrypt(request()->password);
    //     $user->save();

    //     return response()->json($user, 201);
    // }


    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    // public function login(Request $request)
    // {

    //     $is_field = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
    //     $is_email = $is_field == 'email' ? '|email' : '';
    //     $request->merge([
    //         $is_field => $request->username
    //     ]);

    //     $rules = [
    //         $is_field => 'required' . $is_email,
    //         'password' => 'required',
    //     ];

    //     $validator = Validator::make($request->all(), $rules);

    //     if ($validator->fails())
    //         return response()->json($validator->errors(), 422);

    //     $credentials = $request->only($is_field, 'password');

    //     if (!Auth::attempt($credentials))
    //         return response()->json([
    //             'message' => 'Las credenciales que estás usando no son validas.'
    //         ], 401);

    //     $user = Auth::user();


    //     $csrfToken = csrf_token();

    //     $token = $user->createToken('token')->plainTextToken;
    //     $cookie = cookie('cookie_token', $token, 60 * 24);

    //     $usersession = User::with('roles.permissions')->where('id', $user->id)->first();

    //     $response = [
    //         'status' => true,
    //         'data' => $user,
    //         'datasession' => $usersession,
    //         'access_token' => $token,
    //         'access_token_type' => 'Bearer',
    //         // 'csrf_token' => $csrfToken,
    //     ];

    //     return response()->json($response)->withCookie($cookie)
    //         ->header('Content-Type', 'application/json')
    //         ->header('Access-Control-Allow-Origin', '*');
    // }

    //     public function login(Request $request)
    // {
    //     $is_field = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
    //     $is_email = $is_field == 'email' ? '|email' : '';
    //     $request->merge([
    //         $is_field => $request->username,
    //     ]);

    //     // Reglas de validación
    //     $rules = [
    //         $is_field => 'required' . $is_email,
    //         'password' => 'required',
    //     ];

    //     $validator = Validator::make($request->all(), $rules);

    //     if ($validator->fails()) {
    //         return response()->json($validator->errors(), 422);
    //     }

    //     // Credenciales para el modelo principal
    //     $credentials = $request->only($is_field, 'password');

    //     // Intenta autenticar usando el modelo principal
    //     if (Auth::attempt($credentials)) {
    //         $user = Auth::user();

    //         return $this->generateLoginResponse($user);
    //     }

    //     // Si falla con el modelo principal, intenta con el modelo UserCyberV6
    //     $userCyber = UserCyberV6::where('usuario', $request->username)->first();

    //     if ($userCyber && Hash::check($request->password, $userCyber->password)) {
    //         return $this->generateLoginResponse($userCyber, true);
    //     }

    //     // Si las credenciales no son válidas en ninguno de los modelos
    //     return response()->json([
    //         'message' => 'Las credenciales que estás usando no son válidas.',
    //     ], 401);
    // }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $username = $request->username;
        $password = $request->password;

        // Buscar usuario legacy
        $user = UserCyberV6::where('usuario', $username)->first();

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        // Validar contraseña
        if (!Hash::check($password, $user->password)) {
            return response()->json(['message' => 'Contraseña incorrecta.'], 401);
        }

        // Autenticar en Laravel
        Auth::login($user);

        // Respuesta con token, cookie y roles/permissions
        return $this->generateLoginResponse($user);
    }

    /**
     * Generar la respuesta de inicio de sesión.
     *
     * @param  mixed  $user
     * @param  bool   $isUserCyber
     * @return \Illuminate\Http\JsonResponse
     */
    // private function generateLoginResponse($user, $isUserCyber = false)
    // {
    //     $token = $user->createToken('token')->plainTextToken;
    //     $cookie = cookie('cookie_token', $token, 60 * 24);

    //     // Datos adicionales solo para el modelo principal
    //     $usersession = !$isUserCyber
    //         ? User::with('roles.permissions')->where('id', $user->id)->first()
    //         : null;

    //     $response = [
    //         'status' => true,
    //         'data' => $user,
    //         'datasession' => $usersession,
    //         'access_token' => $token,
    //         'access_token_type' => 'Bearer',
    //     ];

    //     return response()->json($response)->withCookie($cookie)
    //         ->header('Content-Type', 'application/json')
    //         ->header('Access-Control-Allow-Origin', '*');
    // }

 private function generateLoginResponse($user)
{
    $token = $user->createToken('token')->plainTextToken;
    $cookie = cookie('cookie_token', $token, 60 * 24);

    // Cargar roles y permisos con Spatie
    $usersession = $user->load('roles.permissions');

    $response = [
        'status' => true,
        'data' => $user,
        'datasession' => $usersession,
        'access_token' => $token,
        'access_token_type' => 'Bearer',
    ];

    return response()->json($response)
        ->withCookie($cookie)
        ->header('Content-Type', 'application/json');
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        $user = auth('api')->user();

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,

            ],
        ]);
    }
}
