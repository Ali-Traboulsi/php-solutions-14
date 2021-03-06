<?php
use PhpSolutions\Authenticate\CheckPassword;

require_once __DIR__ . '/../PhpSolutions/Authenticate/CheckPassword.php';
$usernameMinChars = 6;
$errors = [];
if (strlen($username) < $usernameMinChars) {
    $errors[] = "Username must be at least $usernameMinChars characters.";
}
if (preg_match('/\s/', $username)) {
    $errors[] = 'Username should not contain spaces.';
}
$checkPwd = new CheckPassword($password, 10);
$checkPwd->requireMixedCase();
$checkPwd->requireNumbers(2);
$checkPwd->requireSymbols();
$passwordOK = $checkPwd->check();
if (!$passwordOK) {
    $errors = array_merge($errors, $checkPwd->getErrors());
}
if ($password != $retyped) {
    $errors[] = "Your passwords don't match.";
}
if (!$errors) {
    // encrypt password using default encryption
    $password = password_hash($password, PASSWORD_DEFAULT);
    // include the connection file
    require_once 'connection.php';
    $conn = dbConnect('write', 'pdo');
    // prepare SQL statement
    $sql = 'INSERT INTO users (username, pwd) VALUES (:username, :pwd)';
    $stmt = $conn->prepare($sql);
    // bind parameters and insert the details into the database
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->bindParam(':pwd', $password, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() == 1) {
        $success = "$username has been registered. You may now log in.";
    } elseif ($stmt->errorCode() == 23000) {
        $errors[] = "$username is already in use. Please choose another username.";
    } else {
        $errorInfo = $stmt->errorInfo();
        if (isset($errorInfo[2])) {
            $errors[] = $errorInfo[2];
        }
    }
}
