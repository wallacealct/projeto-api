<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Log; // Import Log facade
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Autenticação",
 *     description="Endpoints para autenticação e gerenciamento de usuários"
 * )
 */
class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware("auth:api", ["except" => ["login", "register"]]);
    }

    /**
     * @OA\Post(
     *      path="/api/auth/login",
     *      operationId="loginUser",
     *      tags={"Autenticação"},
     *      summary="Autentica um usuário e retorna um token JWT",
     *      description="Recebe email e senha e retorna um token JWT se as credenciais forem válidas.",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Credenciais do usuário",
     *          @OA\JsonContent(
     *              required={"email","password"},
     *              @OA\Property(property="email", type="string", format="email", example="usuario@exemplo.com"),
     *              @OA\Property(property="password", type="string", format="password", example="senha123")
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Login bem-sucedido",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
     *              @OA\Property(property="token_type", type="string", example="bearer"),
     *              @OA\Property(property="expires_in", type="integer", example=3600)
     *          )
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Credenciais inválidas",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Credenciais inválidas.")
     *          )
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Erro de validação",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Validation errors"),
     *              @OA\Property(property="data", type="object")
     *          )
     *      )
     * )
     */
    public function login(Request $request): JsonResponse
    {
        // ... (implementation as before)
        $validator = Validator::make($request->all(), [
            "email" => "required|email",
            "password" => "required|string|min:6",
        ]);

        if ($validator->fails()) {
            Log::warning("Login validation failed", ["errors" => $validator->errors()->toJson()]);
            return response()->json(["success" => false, "message" => "Validation errors", "data" => $validator->errors()], 422);
        }

        $credentials = $request->only("email", "password");

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                Log::warning("Login failed: Invalid credentials", ["email" => $request->email]);
                return response()->json(["success" => false, "message" => "Credenciais inválidas."], 401);
            }
        } catch (JWTException $e) {
            Log::error("Login error: Could not create token", ["error" => $e->getMessage()]);
            return response()->json(["success" => false, "message" => "Não foi possível gerar o token de acesso."], 500);
        }

        $user = Auth::user();
        Log::info("User logged in successfully", ["user_id" => $user->id, "email" => $user->email]);
        return $this->respondWithToken($token);
    }

    /**
     * @OA\Post(
     *      path="/api/auth/register",
     *      operationId="registerUser",
     *      tags={"Autenticação"},
     *      summary="Registra um novo usuário",
     *      description="Cria um novo usuário e retorna os dados do usuário e um token JWT.",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Dados do novo usuário",
     *          @OA\JsonContent(
     *              required={"name","email","password","password_confirmation"},
     *              @OA\Property(property="name", type="string", example="Novo Usuário"),
     *              @OA\Property(property="email", type="string", format="email", example="novo@exemplo.com"),
     *              @OA\Property(property="password", type="string", format="password", example="senha123"),
     *              @OA\Property(property="password_confirmation", type="string", format="password", example="senha123")
     *          ),
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Registro bem-sucedido",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Usuário registrado com sucesso!"),
     *              @OA\Property(property="user", ref="#/components/schemas/User"),
     *              @OA\Property(property="access_token", type="string"),
     *              @OA\Property(property="token_type", type="string", example="bearer"),
     *              @OA\Property(property="expires_in", type="integer")
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Erro de validação",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Validation errors"),
     *              @OA\Property(property="data", type="object")
     *          )
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Erro interno no servidor",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Falha ao registrar usuário.")
     *          )
     *      )
     * )
     */
    public function register(Request $request): JsonResponse
    {
        // ... (implementation as before)
        $validator = Validator::make($request->all(), [
            "name" => "required|string|between:2,100",
            "email" => "required|string|email|max:100|unique:users",
            "password" => "required|string|confirmed|min:6",
        ]);

        if($validator->fails()){
            Log::warning("Registration validation failed", ["errors" => $validator->errors()->toJson()]);
            return response()->json(["success" => false, "message" => "Validation errors", "data" => $validator->errors()], 400);
        }

        try {
            $user = User::create(array_merge(
                        $validator->validated(),
                        ["password" => Hash::make($request->password)]
                    ));

            Log::info("User registered successfully", ["user_id" => $user->id, "email" => $user->email]);

            // Automatically log in the user after registration and return token
            $token = JWTAuth::fromUser($user);

            return response()->json([
                "success" => true,
                "message" => "Usuário registrado com sucesso!",
                "user" => $user,
                "access_token" => $token,
                "token_type" => "bearer",
                "expires_in" => JWTAuth::factory()->getTTL() * 60 // expires_in in seconds
            ], 201);

        } catch (\Exception $e) {
            Log::error("User registration failed", ["error" => $e->getMessage(), "data" => $request->except("password", "password_confirmation")]);
            return response()->json(["success" => false, "message" => "Falha ao registrar usuário."], 500);
        }
    }


    /**
     * @OA\Post(
     *      path="/api/auth/logout",
     *      operationId="logoutUser",
     *      tags={"Autenticação"},
     *      summary="Desconecta o usuário (invalida o token)",
     *      description="Invalida o token JWT atual do usuário.",
     *      security={ {"bearerAuth": {}} },
     *      @OA\Response(
     *          response=200,
     *          description="Logout bem-sucedido",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Logout realizado com sucesso.")
     *          )
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Não autorizado (token inválido ou expirado)"
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Erro interno ao invalidar token",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Falha ao fazer logout, por favor tente novamente.")
     *          )
     *      )
     * )
     */
    public function logout(): JsonResponse
    {
        // ... (implementation as before)
        try {
            $user = JWTAuth::parseToken()->authenticate();
            JWTAuth::invalidate(JWTAuth::getToken());
            Log::info("User logged out successfully", ["user_id" => $user->id, "email" => $user->email]);
            return response()->json(["success" => true, "message" => "Logout realizado com sucesso."]);
        } catch (JWTException $e) {
            Log::error("Logout failed", ["error" => $e->getMessage()]);
            // Attempt to get user ID if possible, might fail if token is already invalid
            try { $userId = JWTAuth::parseToken()->getPayload()->get("sub"); } catch (\Exception $ex) { $userId = "unknown"; }
            Log::error("Logout failed for user", ["user_id" => $userId, "error" => $e->getMessage()]);
            return response()->json(["success" => false, "message" => "Falha ao fazer logout, por favor tente novamente."], 500);
        }
    }

    /**
     * @OA\Post(
     *      path="/api/auth/refresh",
     *      operationId="refreshToken",
     *      tags={"Autenticação"},
     *      summary="Atualiza o token JWT",
     *      description="Invalida o token atual e retorna um novo token JWT.",
     *      security={ {"bearerAuth": {}} },
     *      @OA\Response(
     *          response=200,
     *          description="Token atualizado com sucesso",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="access_token", type="string"),
     *              @OA\Property(property="token_type", type="string", example="bearer"),
     *              @OA\Property(property="expires_in", type="integer")
     *          )
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Não autorizado (token inválido, expirado ou blacklisted)",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Não foi possível atualizar o token, por favor faça login novamente.")
     *          )
     *      )
     * )
     */
    public function refresh(): JsonResponse
    {
        // ... (implementation as before)
        try {
            $newToken = JWTAuth::refresh();
            $user = JWTAuth::setToken($newToken)->authenticate(); // Get user from new token
            Log::info("Token refreshed successfully", ["user_id" => $user->id, "email" => $user->email]);
            return $this->respondWithToken($newToken);
        } catch (JWTException $e) {
             // If refresh fails, the original token might be blacklisted or invalid
             Log::error("Failed to refresh token", ["error" => $e->getMessage()]);
             return response()->json(["success" => false, "message" => "Não foi possível atualizar o token, por favor faça login novamente."], 401);
        }
    }

    /**
     * @OA\Get(
     *      path="/api/auth/me",
     *      operationId="getUserProfile",
     *      tags={"Autenticação"},
     *      summary="Retorna os dados do usuário autenticado",
     *      description="Retorna as informações do usuário associado ao token JWT fornecido.",
     *      security={ {"bearerAuth": {}} },
     *      @OA\Response(
     *          response=200,
     *          description="Perfil do usuário retornado com sucesso",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", ref="#/components/schemas/User")
     *          )
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Não autorizado (token inválido ou expirado)",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Usuário não autenticado ou token inválido.")
     *          )
     *      )
     * )
     */
    public function me(): JsonResponse
    {
        // ... (implementation as before)
        try {
            $user = JWTAuth::parseToken()->authenticate();
            Log::debug("Fetched authenticated user profile", ["user_id" => $user->id]);
            return response()->json(["success" => true, "data" => $user]);
        } catch (JWTException $e) {
            Log::error("Failed to get authenticated user", ["error" => $e->getMessage()]);
            return response()->json(["success" => false, "message" => "Usuário não autenticado ou token inválido."], 401);
        }
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token): JsonResponse
    {
        return response()->json([
            "success" => true,
            "access_token" => $token,
            "token_type" => "bearer",
            "expires_in" => JWTAuth::factory()->getTTL() * 60 // expires_in in seconds
        ]);
    }
}

