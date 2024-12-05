<?php
require_once("../pdo_connect.php");
setcookie("user", "", 0, "/", "ada.cis.uncw.edu", true, true); // Log user out upon navigating to login page
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];
    if (!(isset($username) && isset($password))) {
        echo("Username or password not provided. Please try again.");
        return;
    }

    try {
        $sql = "
            SELECT UserID
            FROM Account
            WHERE Username = :username
            AND UserPassword = :password
        ";
        $stmt = $dbc->prepare($sql);

        $stmt->bindParam(":username", $username, PDO::PARAM_STR);
        $stmt->bindParam(":password", $password, PDO::PARAM_STR);

        if ($stmt->execute()) { // Execute statement and check for success
            if ($stmt->rowcount() === 1) {
                $userID = $stmt->fetchColumn();
                setcookie("user", $userID, time() + 86400, "/", "ada.cis.uncw.edu", true, true);
                $sql = "
                    UPDATE Account
                    SET UserLastUsedDate = CURDATE()
                    WHERE UserID = :userid
                ";
                $stmt = $dbc->prepare($sql);

                $stmt->bindParam(":userid", $userID, PDO::PARAM_STR);
                $stmt->execute();
                header("Location: home.php");
            }
            else {
                echo("Invalid username or passwoord. Please try again.");
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
    <meta charset="utf-8">
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico" link rel="stylesheet" href="style.css">
    <style>
        input, select {display: block; width: 20%; margin: 1rem 0;}
        #submit {width: 10%; margin-top: 2rem; margin-left: 2%;}
    </style>
</head>
<body>
    <h2>Log In</h2>
    <form action="login.php" method="POST">
        <input type="text" name="username" placeholder="Username" id="username">
        <input type="password" name="password" placeholder="Password" id="password">
        <input type="submit" name="submit" value="Login" id="submit">
    </form>
</body>
</html>