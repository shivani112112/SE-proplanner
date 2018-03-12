<?php

$username = $_POST["username"];
$password = $_POST["password"];

$servername = "";
$serverUsername = "";
$serverPassword = "";

$connection = new mysqli($servername, $serverUsername, $serverPassword);
// Check connection
if ($connection->connect_error)
{
	die("Connection failed: " . $connection->connect_error);
}

useDatabase($connection);

if (checkPassword($connection, $username, $password))
{
	session_start();
	$_SESSION["username"] = $username;
	$_SESSION["password"] = $password;

	header('Location: /Programming/MySQL/mainFile.php');
}

else
{
	header('Location: /Programming/MySQL/index.html');
}

function useDatabase($connection)
{
	$sql = "USE SEP";

	$connection->query($sql);
}

function checkPassword($connection, $username, $password)
{
	$username = strtolower($username);

	$sql = "SELECT username, password FROM users WHERE username='$username' AND password='$password'";
	$result = $connection->query($sql);
	
	if ($result->num_rows > 0)
	{
		if ($result1 = $result->fetch_assoc())
		{
			return true;
		}
	}

	return false;
}

?>