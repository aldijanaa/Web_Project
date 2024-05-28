<?php
require_once __DIR__ . '/../dao/UserDao.class.php';

use Firebase\JWT\JWT;

class UserService {

    private $user_dao;

    public function __construct() {
        $this->user_dao = new UserDao();
    }

    public function get_user_by_id($user_id) {
        return $this->user_dao->get_user_by_id($user_id);
    }

    public function get_all_users($offset = 0, $limit = 25, $order = "id") {
        return $this->user_dao->get_all($offset, $limit, $order);
    }



    //Function for registering a new user
    public function registerUser($data){
        $response = ['success' => true, 'status' => 200, 'message' => 'Validation successful'];


        // Perform validations
        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email']) || empty($data['password']) || empty($data['confirm_password'])) {
            return ['success' => false,'message' => 'Missing fields'];
        }

        // Check if passwords match
        if ($data['password'] !== $data['confirm_password']) {
            return ['success' => false, 'message' => 'Passwords do not match'];
        }

        
        // Check format of email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }

        // Check if user already exists
        $existingUser = $this->user_dao->get_user_by_email($data['email']); //functions in user dao
        if ($existingUser) {
            return ['success' => false, 'message' => 'User with those credentials already exists, either login or choose other credentials'];
        }

        // Check if TLD is valid (top level domain)
        if (!$this->validateEmailTLD($data['email'])) {
           return ['success' => false, 'message' => 'Invalid TLD in email address'];
        }

            // Validate MX (Mail Exchange) records  -- dio poslije @ se gleda kao mx record npr. @stu.ibu.edu.ba
        $domain = substr(strrchr($data['email'], "@"), 1);
        if (!$this->validateMXRecords($domain)) {
            Flight::json(['success' => false,"message" => "No MX records found for domain: $domain"]);
            return;
        }
            
        // Hash password and prepare user data
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        //Add user
        $user = $this->user_dao->add_user([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'USER'  // Default role
        ]);

        //Check if user was successfully registered
        if ($user) {
            return ['success' => true, 'message' => 'User registered successfully'];
        } else {
            return ['success' => false, 'message' => 'An unexpected error occurred'];
        }

    }


  // Function to validate email TLD
    function validateEmailTLD($email) {
        $url = 'https://data.iana.org/TLD/tlds-alpha-by-domain.txt'; // Fetching from remote url, can change later
        $tlds = file($url, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        array_shift($tlds); // Remove the first line
    
        $validTLDs = array_map('strtolower', $tlds);
        $partition = explode('@', $email);
        $domainPart = explode('.', $partition[1]);
        $tld = strtolower(end($domainPart));
    
        return in_array($tld, $validTLDs);
    }

    //Validate MX records
    public function validateMXRecords($domain) {
        if (getmxrr($domain, $mx_records)) {
            return count($mx_records) > 0;
        }
        return false;
    }


 


    //Login function  (bilo prije authenticate_user)
    public function login($email, $password) {

         // Check if username or password field is empty
        if (empty($email) || empty($password)) {
            Flight::json(['success' => false, 'message' => 'Email and password are required']);
            return;
        }


        //Check does that email exist in db
        $user = $this->user_dao->get_user_by_email($email);
        if (!$user) {
            return ['success' => false,'message' => 'No user found with this email'];
        }
    
        //Check does the password match
        if (!password_verify($password, $user['password'])) {
            return ['success' => false,'message' => 'Password verification failed'];
        }

        // Check if username is reserved, npr'admin'
        /*if (strtolower($username) === 'admin') {
            Flight::json(['success' => false, 'message' => 'The username is reserved']);
            return;
        }*/
        
        $issuedAt = time();
        $expirationTime = $issuedAt + 3600 * 24;  // jwt validity: 24 hours
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'userId' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role']
        ];
    
        $jwt = JWT::encode($payload, JWT_SECRET_KEY, 'HS256');
        return ['message'=> 'Successfuly logged in', 'jwt-token' => $jwt];
    }



       /*public function update_user($user_id, $user) {
        // If the password is being updated, hash it
        if (isset($user['password'])) {
            $user['password'] = password_hash($user['password'], PASSWORD_DEFAULT);
        }
        $this->user_dao->update_user($user_id, $user);
    }*/

    public function delete_user_by_id($user_id) {
        return $this->user_dao->delete_user_by_id($user_id);
    }

    public function get_user_by_email($email){
        return $this->user_dao->get_user_by_email($email);
    }

    


}