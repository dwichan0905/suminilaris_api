<?php 

function pass_create(String $password = '')
{
    $options = [
        'cost' => 12,
    ];
    return password_hash($password, PASSWORD_BCRYPT, $options);
}

function pass_check(String $pass_input, String $password_hash)
{
    return password_verify($pass_input, $password_hash);
}