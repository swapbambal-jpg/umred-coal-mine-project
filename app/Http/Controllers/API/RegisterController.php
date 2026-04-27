<?php
     
namespace App\Http\Controllers\API;
     
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Validator;
use Illuminate\Http\JsonResponse;
     
class RegisterController extends BaseController
{
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request): JsonResponse
    {

      try {
           $validator = Validator::make($request->all(), [
                'name' => 'required',
                'email' => 'required|email|unique:users,email',
                'password' => 'required',
                'c_password' => 'required|same:password',
            ]);
            if($validator->fails()){
                return $this->sendError('Validation Error.', $validator->errors());       
            }
         
            $input = $request->all();
            $input['password'] = bcrypt($input['password']);
            $user = User::create($input);
            $success['token'] =  $user->createToken('MyApp')->accessToken;
            $success['name'] =  $user->name;
       
            return $this->sendResponse($success, 'User register successfully.');

        }catch (QueryException $e) {
    if ($e->getCode() == 23000) {
        return response()->json(['error' => 'Email already exists'], 409);
    }
}
    }
     
    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        
        // Validate request first to fail fast
        $validator = Validator::make($request->all(), [
            'email' => 'required', // Can be email or mobile
            'password' => 'required',
        ]);
        
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        
        // Additional validation for login field format
        $login = $request->email;
        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);
        $isMobile = preg_match('/^[0-9]{10}$/', $login); // Assuming 10-digit mobile numbers
        
        if (!$isEmail && !$isMobile) {
            return $this->sendError('Validation Error.', ['login' => 'Please provide a valid email address or 10-digit mobile number']);
        }

        try {
            // Determine if login field is email or mobile
            $loginField = filter_var($request->email, FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile';
            
            // Get fresh user record from database with role
            $user = User::where($loginField, $request->email)
                ->with('role:id,name')
                ->select([
                    'id', 
                    'name',
                    'image', 
                    'email', 
                    'mobile', 
                    'password', 
                    'role_id'
                ])
                ->where('status', 'active')
                ->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return $this->sendError('Unauthorised.', ['error' => 'Invalid credentials']);
            }

            // Generate token more efficiently with specific scopes
            $tokenResult = $user->createToken('MyApp', ['*']);
            $accessToken = $tokenResult->accessToken;

            // Cache user data for subsequent requests
            Cache::put('user_data_' . $user->id, [
                'id' => $user->id,
                'name' => $user->name,
                'image' => $user->image,
                'email' => $user->email,
                'mobile' => $user->mobile
            ], 3600); // 1 hour

            $success = [
                'token' => $accessToken,
                'name' => $user->name,
                'image' => $user->image ? url('public/' . $user->image) : null,
                'id' => $user->id,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'mobile' => $user->mobile,
                'role' => $user->role,
                "role_id"=>$user->role_id
            ];

            // Log performance metrics in development
            if (app()->environment('local')) {
                $executionTime = (microtime(true) - $startTime) * 1000;
                Log::info('Login performance', [
                    'execution_time_ms' => round($executionTime, 2),
                    'user_id' => $user->id
                ]);
            }

            return $this->sendResponse($success, 'User login successfully.');
            
        } catch (\Exception $e) {
            Log::error('Login error', [
                'error' => $e->getMessage(),
                'login' => $request->login
            ]);
            
            return $this->sendError('Login failed.', ['error' => 'Authentication error']);
        }
    }

    /**
     * Get server date and time
     *
     * @return \Illuminate\Http\Response
     */
    public function serverDateTime(): JsonResponse
    {
        $serverDateTime = now()->format('Y-m-d H:i:s');
        $timezone = config('app.timezone', 'UTC');
        
        $data = [
            'datetime' => $serverDateTime,
            'timezone' => $timezone,
            'timestamp' => now()->timestamp
        ];

        return $this->sendResponse($data, 'Server date and time retrieved successfully.');
    }
}

?>