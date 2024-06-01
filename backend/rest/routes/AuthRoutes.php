
<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
require_once __DIR__ . '/../services/UserService.class.php';




Flight::group('/auth', function () {   //grouping routes so that we can use the same prefix for all routes - auth


    Flight::route('GET /all', function () {
        $offset = Flight::request()->query['offset'] ?? 0;
        $limit = Flight::request()->query['limit'] ?? 25;
        $order = Flight::request()->query['order'] ?? 'id';
        $user_service = new UserService();
        $users = $user_service->get_all_users($offset, $limit, $order);
        Flight::json($users);
    });



    /**
     * @OA\Post(
     *     path="/auth/register",
     *     tags={"Authentication"},
     *     summary="Register a new user",
     *     description="Registers a new user",
     *     @OA\RequestBody(
     *         required=true,
     *         description="User registration data",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"first_name", "last_name", "email", "password", "confirm_password"},
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="last_name", type="string", example="Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="johndoe123@gmail.com"),
     *                 @OA\Property(property="password", type="string", format="password", example="password123"),
     *                 @OA\Property(property="confirm_password", type="string", format="password", example="password123")
     *             ) 
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input, missing fields, or password mismatch"
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="User with those credentials already exists"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error or unexpected error occurred during the registration process"
     *     )
     * )
     */
    Flight::route('POST /register', function() {
        $data = Flight::request()->data->getData();
        $service = new UserService(); //CREATING OBJECT OF USER SERVICE
        $result = $service->registerUser($data);
        
        Flight::json($result, $result['status'] ?? 200);
    });
    
  

    
   /**
     * @OA\Post(
     *     path="/auth/login",
     *     summary="Log in the user and return a JWT",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         description="Credentials needed to login",
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="aldijana@gmail.com"),
     *             @OA\Property(property="password", type="string", format="password", example="aldijana123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Authentication successful",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="token", type="string", description="JWT for authenticated user")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Email and password are required"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid email or password"
     *     )
     * )
     */
    Flight::route('POST /login', function() {
        $data = Flight::request()->data->getData();  //getting payload data that has been sent through the request
        $service = new UserService();

        $result = $service->login($data['email'], $data['password']);
        Flight::json($result, $result['status'] ?? 200);
    });



   

     //We want user to be authenticated in order to trigger this logout route   --- ovo ne radi još
    /*Flight::route('POST /logout', function() {
        $data = Flight::request()->data->getData();  //getting payload data that has been sent through the request
        $service = new UserService();

        try{
            $token = Flight::request()->getHeader('Authentication');
            if(!$token){
                throw new Exception("Token is missing");
            }

            $decoded_token = JWT::decode($token, new Key(JWT_SECRET_KEY . 'ss', 'HS256'));
            Flight::json([
                'jwt_decoded'=> $decoded_token,
                'user'=>$decoded_token->user
            ]);

        }catch(\Exception $e){
            Flight::halt(401, 'Invalid token');
        }
    });*/

    //Setting up a route to check if user is logged in
    /*Flight::route('GET /auth/status', function() {
        $service = new UserService();

        $result = $service->checkLoginStatus();
        Flight::json($result);
    });*/


});
