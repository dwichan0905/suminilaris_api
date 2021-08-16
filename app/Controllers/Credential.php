<?php namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Model\UsersModel;

class Credential extends ResourceController 
{
    use ResponseTrait;
    private $db;
    private $builder;
    private $roles = ['Admin', 'Reseller'];

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
    public function register()
    {
        helper(['pass']);
        
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
        if (!in_array($data['role'], $this->roles)) {
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

    // Modify a user data.
    // parameter: username (GET), name, phone, role
    // method: PUT
    public function identityModify()
    {
        $username = $this->request->getGet("username");
        if ($username == null) {
            return $this->failForbidden("Username must be defined!");
        }

        $fields = ['id', 'nama', 'telepon', 'role'];

        $json = $this->request->getJSON();
        if ($json) {
            $data = [
                $fields[0] => $json->username,
                $fields[1] => $json->name,
                $fields[2] => $json->phone,
                $fields[3] => $json->role
            ];
        } else {
            $input = $this->request->getRawInput();
            $data = [
                $fields[0] => $input['username'],
                $fields[1] => $input['name'],
                $fields[2] => $input['phone'],
                $fields[3] => $input['role']
            ];
        }

        // empty parameters check
        if (strlen($data['id']) < 0 || $data['id'] == null) {
            return $this->failValidationErrors("Username must be defined!");
        }
        if (strlen($data['nama']) < 0 || $data['nama'] == null) {
            return $this->failValidationErrors("Name must be defined!");
        }
        // invalid form input checks
        if (!in_array($data['role'], $this->roles)) {
            return $this->failValidationErrors("Invalid role: " . $data['role']);
        }
        if (!filter_var($data['id'], FILTER_VALIDATE_EMAIL)) {
            return $this->failValidationErrors("Username must be formatted as email like: john.doe@example.com!");
        }
        // too long input checks
        if (strlen($data['id']) > 50) {
            return $this->failValidationErrors("Username must be no longer than 50 characters!");
        }
        // get the data in database
        $result = $this->builder->getWhere([
            'id' => $username
        ])->getResultArray();

        if (count($result) == 0) {
            // user ID invalid or not found
            return $this->failNotFound("Username doesn't exist: " . $username);
        } else {
            // user already registered
            if ($data['id'] !== $username) {
                // get the data in database
                $resultNew = $this->builder->getWhere([
                    'id' => $data['id']
                ])->getResultArray();

                if (count($resultNew) > 0) {
                    return $this->failResourceExists("Username " . $data['id'] . " already taken!");
                } else {
                    $this->builder->update($data, ['id' => $username]);
                    return $this->respondUpdated("User data " . $username . " has been successfully changed, also the user ID changed to " . $data['id'] . "!");
                }
            } else {
                $this->builder->update($data, ['id' => $username]);
                return $this->respondUpdated("User data " . $username . " has been successfully changed!");
            }
        }
    }

    // change password
    // parameter: username, old_pass, new_pass
    // method: PUT
    public function modifyPassword()
    {
        helper(['pass']);

        $fields = ['id', 'old_pass', 'new_pass'];

        $json = $this->request->getJSON();
        if ($json) {
            $data = [
                $fields[0] => $json->username,
                $fields[1] => $json->old_pass,
                $fields[2] => $json->new_pass
            ];
        } else {
            $input = $this->request->getRawInput();
            $data = [
                $fields[0] => $input['username'],
                $fields[1] => $input['old_pass'],
                $fields[2] => $input['new_pass']
            ];
        }
        // empty parameters check
        if (strlen($data['id']) < 0 || $data['id'] == null) {
            return $this->failValidationErrors("Username must be defined!");
        }
        if (strlen($data['old_pass']) < 0 || $data['old_pass'] == null) {
            return $this->failValidationErrors("Old Password must be defined!");
        }
        if (strlen($data['new_pass']) < 0 || $data['new_pass'] == null) {
            return $this->failValidationErrors("New Password must be defined!");
        }

        // verify that the data exists
        $result = $this->builder->getWhere([
            'id' => $data['id']
        ])->getRow();
        
        if (!$result) {
            return $this->failNotFound("Username is not recognized: " . $data['id']);
        } else {
            if (!pass_check($data['old_pass'], $result->password)) {
                return $this->failUnauthorized("Wrong password!");
            } else {
                if (strlen($data['new_pass']) > 72) {
                    return $this->failValidationErrors("Username must be no longer than 72 characters!");
                } else {
                    $new_pass = pass_create($data['new_pass']);
                    $this->builder->update(
                        ['password' => $new_pass],
                        ['id' => $data['id']]
                    );
                    return $this->respondUpdated("Password has been modified successfully!");
                }
            }
        }
    }

    public function deleteAccount($username = '')
    {
        helper(['pass']);

        $usernameLogin = $this->request->getGet("username");
        $passwordLogin = $this->request->getGet("password");

        if (strlen($username) < 0 || $username == null) {
            return $this->failValidationErrors("Deleted Username must be defined!");
        }
        if (strlen($usernameLogin) < 0 || $usernameLogin == null) {
            return $this->failValidationErrors("Username must be defined!");
        }
        if (strlen($passwordLogin) < 0 || $passwordLogin == null) {
            return $this->failValidationErrors("Password must be defined!");
        }
        if (!filter_var($usernameLogin, FILTER_VALIDATE_EMAIL)) {
            return $this->failValidationErrors("Username must be formatted as email like: john.doe@example.com!");
        }
        if (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
            return $this->failValidationErrors("Deleted Username must be formatted as email like: john.doe@example.com!");
        }

        // login first!
        $result = $this->builder->getWhere([
            'id' => $usernameLogin
        ])->getRow();

        if (!$result) {
            return $this->failNotFound("Username is not recognized: " . $usernameLogin);
        } else {
            if (!pass_check($passwordLogin, $result->password)) {
                return $this->failUnauthorized("Wrong password!");
            } else {
                $getDeletedUser = $this->builder->getWhere([
                    'id' => $username
                ])->getResultArray();

                if (count($getDeletedUser) == 0) {
                    return $this->failNotFound("Username " . $username . " doesn't exist!");
                } else {
                    $this->builder->delete(['id' => $username]);
                    return $this->respondDeleted("User " . $username . " has been deleted successfully!");
                }
            }
        }
    }
}
