<?php namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Model\UsersModel;

class Credential extends ResourceController 
{
    use ResponseTrait;
    private $db;
    private $builder;

    // database connection
    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->builder = $this->db->table("user");
    }
    
    // Checks the registered users.
    // parameter: username, password
    // method: POST
    public function login()
    {
        helper(['pass']);

        // returieve datas
        $data = [
            'username' => $this->request->getPost("username"),
            'password' => $this->request->getPost("password")
        ];

        // get the data in database
        $result = $this->builder->getWhere([
            'id' => $data['username']
        ])->getRowArray();

        // buat bikin password: echo pass_create($data['password']);
        // processing the login data
        if (count($result) == 0) {
            return $this->failNotFound("Username is not recognized: " . $data['username']);
        } else {
            if (pass_check($data['password'], $result['password'])) {
                return $this->respond([
                    'status' => 201,
                    'error' => 0,
                    'messages' => "Username " . $data['username'] . " and the password is valid!"
                ], 201);
            } else {
                return $this->failUnauthorized("Wrong password! User: " . $data['password'] . " in db: " . $result['password']);
            }
        }
    }

    // Create a new user. Role just 2 items: Admin and Reseller.
    // parameter: username, name, password, phone, role
    // method: POST
    public function create()
    {
        helper(['pass']);
        $roles = ['Admin', 'Reseller'];
        
        $data = [
            'id' => $this->request->getPost("username"),
            'nama' => $this->request->getPost("name"),
            'password' => $this->request->getPost("password"),
            'telepon' => $this->request->getPost("phone"),
            'role' => $this->request->getPost("role")
        ];

        // validation progress
        // empty parameters check
        if (strlen($data['id']) < 0 || $data['id'] == null) {
            return $this->failValidationErrors("Username must be defined!");
        }
        if (strlen($data['nama']) < 0 || $data['nama'] == null) {
            return $this->failValidationErrors("Name must be defined!");
        }
        if (strlen($data['password']) < 0 || $data['password'] == null) {
            return $this->failValidationErrors("Password must be defined!");
        }
        // too long input checks
        if (strlen($data['id']) > 50) {
            return $this->failValidationErrors("Username must be no longer than 50 characters!");
        }
        if (strlen($data['password']) > 72) {
            return $this->failValidationErrors("Username must be no longer than 72 characters!");
        }
        // invalid form input checks
        if (!filter_var($data['id'], FILTER_VALIDATE_EMAIL)) {
            return $this->failValidationErrors("Username must be formatted as email like: john.doe@example.com!");
        }
        if (!in_array($data['role'], $roles)) {
            return $this->failValidationErrors("Invalid role: " . $data['role']);
        }

        // get the data in database
        $result = $this->builder->getWhere([
            'id' => $data['id']
        ])->getResultArray();

        if (count($result) == 0) {
            // create a new user
            $this->builder->insert([
                'id' => $data['id'],
                'nama' => $data['nama'],
                'password' => pass_create($data['password']),
                'telepon' => $data['telepon'],
                'role' => $data['role']
            ]);
            
            return $this->respondCreated("User " . $data['id'] . " has been successfully created!");
        } else {
            // user already registered
            return $this->failResourceExists("User " . $data['id'] . " already registered!");
        }
    }
}
