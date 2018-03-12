<?php

$username = $_POST["username"];
$firstname = $_POST["firstname"];
$lastname = $_POST["lastname"];
$email = $_POST["email"];
$password = $_POST["password"];
$_POST = null;

session_start();
$_SESSION["username"] = $username;
$_SESSION["password"] = $password;

$servername = "";
$serverUsername = "";
$serverPassword = "";

$connection = new mysqli($servername, $serverUsername, $serverPassword);
// Check connection
if ($connection->connect_error)
{
	die("Connection failed: " . $connection->connect_error);
}

createDatabase($connection);
useDatabase($connection);
createTables($connection);

if (register($connection, $username, $firstname, $lastname, $email, $password))
{
	header('Location: /Programming/MySQL/mainFile.php');
}

else
{
	header('Location: /Programming/MySQL/index.html');
}

function createDatabase($connection)
{
	$sql = "CREATE DATABASE SEP";

	$connection->query($sql);
}

function useDatabase($connection)
{
	$sql = "USE SEP";

	$connection->query($sql);
}

function createTables($connection)
{
	//Users
	$sql = "CREATE TABLE users (username VARCHAR(30), firstname VARCHAR(30), lastname VARCHAR(30), email VARCHAR(50), password VARCHAR(30), lists INT(6), points INT(6))";

	$connection->query($sql);

	//Lists
	$sql = "CREATE TABLE lists (id INT(6), username VARCHAR(30), name VARCHAR(100), colour VARCHAR(100), category VARCHAR(100))";

	$connection->query($sql);

	//Tasks
	$sql = "CREATE TABLE tasks (id INT(6), listID INT(6), state BOOLEAN, type VARCHAR(15), taskDate VARCHAR(25), taskTime VARCHAR(25), content VARCHAR(100))";

	$connection->query($sql);
}

function register($connection, $username, $firstname, $lastname, $email, $password)
{
	$username = strtolower($username);
	$firstname = strtolower($firstname);
	$lastname = strtolower($lastname);
	$email = strtolower($email);

	if (checkUsername($connection, $username))
	{
		echo "Username already taken: $username\n";
	}

	else
	{
		if (checkEmail($connection, $email))
		{
			echo "Email already in use: $email\n";
		}

		else
		{
			$sql = "INSERT INTO users (username, firstname, lastname, email, password, lists, points) VALUES ('$username', '$firstname', '$lastname', '$email', '$password', '0', '0')";

			if ($connection->query($sql) === TRUE)
			{
				echo "User added successfully.\n";
				return true;
			}

			else
			{
				echo "Error adding user: " . $connection->error."\n";
			}
		}
	}

	return false;
}

function checkUsername($connection, $username)
{
	$sql = "SELECT username FROM users";
	$result = $connection->query($sql);
	
	if ($result->num_rows > 0)
	{
		while ($result1 = $result->fetch_assoc())
		{
		    if($result1['username'] == $username)
		    {
		    	return true;
		    }
		}
	}

	return false;
}

function checkEmail($connection, $email)
{
	$sql = "SELECT email FROM users";
	$result = $connection->query($sql);
	
	if ($result->num_rows > 0)
	{
		while ($result1 = $result->fetch_assoc())
		{
		    if($result1['email'] == $email)
		    {
		    	return true;
		    }
		}
	}

	return false;
}

?>