<?php
require_once("../pdo_connect.php");
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];
    if (!(isset($username) && isset($password))) {
        echo("Username or password not provided. Please try again.");
        return;
    }

    try {
        $sql = "
        SELECT COUNT(*)
        FROM Account
        WHERE Username = :username
        AND UserPassword = :password
        ";
        $stmt = $dbc->prepare($sql);

        $stmt->bindParam(":username", $username, PDO::PARAM_STR);
        $stmt->bindParam(":password", $password, PDO::PARAM_STR);

        if ($stmt->execute()) { // Execute statement and check for success
            if ($stmt->rowcount() === 1) {
                
            }
        }
        else {
            echo("Failed to validate login. Please try again.");
        }
    }
    catch (PDOException $e) {
        echo("Error: " . $e->getMessage());
        echo("Trace: " . print_r($e->getTrace(), true));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login</title>
    <style>
        input, select {display: block; width: 20%;}
        #submit {width: 10%; margin-top: 2rem; margin-left: 2%;}
    </style>
</head>
<body>
    <h2>Log In</h2>
    <form action="login.php" method="POST">
        <input type="text" name="username" placeholder="Username" id="username">
        <input type="text" name="password" placeholder="Password" id="password">
        <input type="submit" name="submit" value="Login" id="submit">
    </form>
</body>
</html>