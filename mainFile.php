<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

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

session_start();
$username = $_SESSION["username"];
$password = $_SESSION["password"];
$_SESSION["lists"] = getLists($connection, $username);
$_SESSION["list"] = 0;

if (!checkPassword($connection, $username, $password))
{
	header('Location: /Programming/MySQL/index.html');
}

if (isset($_POST['logout']))
{
	logout();
}

else if (isset($_POST['refresh']))
{
	header('Location: /Programming/MySQL/mainFile.php');
}

else if (isset($_POST['addList']))
{
	if ($_POST['name'] !== "")
	{
		addList($connection, $username, $_POST['name'], $_POST['colour'], $_POST['category']);
	}
}

else if (isset($_POST['addTask']))
{
	if ($_POST['content'] !== "")
	{
		$time = "";
		$deadline = "";
		$type = "";

		if (strpos($_POST['type'], "timer") !== false)
		{
			$time = $_POST['taskTime'];
			$type = "timer";
		}

		else if (strpos($_POST['type'], "deadline") !== false)
		{
			$deadline = $_POST['taskDate'];
			$type = "deadline";
		}

		addTask($connection, $_POST['listID'], $type, $deadline, $time, $_POST['content']);
	}
}

$countState = -1;

while (++$countState <= 20)
{
	if (isset($_POST['state'.$countState]))
	{
		changeTaskState($connection, $countState, $username);
	}
}

$countRemove = -1;

while (++$countRemove <= 20)
{
	if (isset($_POST['removeTask'.$countRemove]))
	{
		removeTask($connection, $countRemove);
	}
}

$countRemoveList = -1;

while (++$countRemoveList <= 20)
{
	if (isset($_POST['removeList'.$countRemoveList]))
	{
		removeList($connection, $username, $countRemoveList);
	}
}

$_POST = null;

// ------------------------ Databases ------------------------ 

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

function newID($connection, $table)
{
	orderTable($connection, $table);

	$currentID = 0;

	$sql = "SELECT id FROM $table";

	while(true)
	{
		$result = $connection->query($sql);

		if ($result->num_rows > 0)
		{
		    while($row = $result->fetch_assoc())
		    {
		        if ($row['id'] == $currentID)
		        {
		        	$currentID++;
		        }

		        else
		        {
		        	return $currentID;
		        }
		    }
		}

		else
		{
		    return 0;
		}
	}
}

function orderTable($connection, $table)
{
	$sql = "ALTER TABLE $table ORDER BY id ASC";

	$connection->query($sql);
}

function getPoints($connection, $username)
{
	$username = strtolower($username);

	$sql = "SELECT points FROM users WHERE username = '$username'";
	$result = $connection->query($sql);
	$result1 = $result->fetch_assoc();
	return $result1['points'];
}

function addPoints($connection, $username, $points)
{
	$username = strtolower($username);
	$points = getPoints($connection, $username) + $points;

	$sql = "UPDATE users SET points = '$points' WHERE username = '$username'";

    if ($connection->query($sql) === TRUE)
    {
	    echo "10 points added!\n";
	}
}

function removePoints($connection, $username, $points)
{
	$username = strtolower($username);
	$points = getPoints($connection, $username) - $points;

	$sql = "UPDATE users SET points = '$points' WHERE username = '$username'";

    if ($connection->query($sql) === TRUE)
    {
	    echo "10 points removed...\n";
	}
}

function logout()
{
	session_destroy();
	header('Location: /Programming/MySQL/index.html');
}

?>

<!DOCTYPE html>
<html>
	<head>
		<title>Your Tasks</title>

		<script type="text/javascript">

			window.onload = function()
			{
				hideElements();
			}

			function hideElements()
			{
				var count = 0;

				do 
				{
					document.getElementById('divDate' + count).style.display = 'none';
					document.getElementById('divTime' + count).style.display = 'none';
				} while (count++ <= 5);
			}

			function showHide(elem)
			{
				var count = 0;

				do
				{
					if (elem.value == ("timer" + count))
					{
						document.getElementById('divDate' + count).style.display = 'none';
						document.getElementById('divTime' + count).style.display = 'block';
					}

					else if (elem.value == ("deadline" + count))
					{
						document.getElementById('divTime' + count).style.display = 'none';
						document.getElementById('divDate' + count).style.display = 'block';
					}

					else
					{
						document.getElementById('divDate' + count).style.display = 'none';
						document.getElementById('divTime' + count).style.display = 'none';
					}
				} while(count++ <= 5);
			}
		</script>
	</head>

	<body>
		<form style="text-align: right" action="mainFile.php" method="post">
			<?php
				echo "Points: ".getPoints($connection, $username)."\n";
				echo "<input type='submit' name='refresh' value='\u{1F503}'>";
			?>
			<input type="submit" name="logout" value="Log Out">
		</form>
		<?php
			displayLists($connection, $username);
		?>
	</body>
</html>

<?php

// ------------------------ Lists ------------------------ 

function displayLists($connection, $username)
{
	$sql = "SELECT id, name, colour, category FROM lists WHERE username = '$username'";
	$result = $connection->query($sql);
	
	if ($result->num_rows > 0)
	{
		while ($result1 = $result->fetch_assoc())
		{
			echo "<form action='mainFile.php' method='post'>";
			echo "<fieldset style='border-color: ".$result1['colour']."'><legend>".$result1['name']." ".$result1['category']."</legend>";
		    displayTasks($connection, $result1['id']);
		    echo "</fieldset>";
		    echo "</form><br>";
		}
	}

	$sql = "SELECT lists FROM users WHERE username = '$username'";
	$result = $connection->query($sql);
	$result1 = $result->fetch_assoc();
	
	if ($result1['lists'] < 5)
	{
		echo "<form action='mainFile.php' method='post'>";
		echo "List Name:<br>";
		echo "<input type='text' name='name' required='true'><br>";
		echo "List Colour:<br>";
		echo "<select name='colour'>";
		echo "<option value='#FF0000'>Red</option>";
		echo "<option value='#FFFF00'>Yellow</option>";
		echo "<option value='#000000'>Black</option>";
		echo "<option value='#008000'>Green</option>";
		echo "<option value='#00FFFF'>Aqua</option>";
		echo "<option value='#0000FF'>Blue</option>";
		echo "<option value='#800080'>Purple</option>";
		echo "</select><br>";
		echo "List Category:<br>";
		echo "<select name='category'>";
		echo "<option value='\u{1F6B2}'>Sport</option>";
		echo "<option value='\u{26F5}'>Sea Side</option>";
		echo "<option value='\u{26FD}'>Car</option>";
		echo "<option value='\u{1F339}'>Garden</option>";
		echo "<option value='\u{1F355}'>Dinner</option>";
		echo "<option value='\u{1F382}'>Birthday</option>";
		echo "<option value='\u{1F393}'>Education</option>";
		echo "<option value='\u{1F4B3}'>Shopping</option>";
		echo "</select><br><br>";
		echo "<input type='submit' name='addList' value='Add List'>";
		echo "</form>";
	}
}

function getListID($connection, $username, $name)
{
	$username = strtolower($username);

	$sql = "SELECT id FROM lists WHERE username = '$username' AND name = '$name'";
	$result = $connection->query($sql);
	$result1 = $result->fetch_assoc();
	return $result1['id'];
}

function getLists($connection, $username)
{
	$username = strtolower($username);

	$sql = "SELECT lists FROM users WHERE username = '$username'";
	$result = $connection->query($sql);
	$result1 = $result->fetch_assoc();
	return $result1['lists'];
}

function addList($connection, $username, $name, $colour, $category)
{
	$username = strtolower($username);
	$lists = getLists($connection, $username) + 1;
	if ($lists >= 6)
	{
		echo "Maximum amount of lists available!\n"; 
	}

	else
	{	
		$sql = "INSERT INTO lists (id, username, name, colour, category) VALUES ('".newID($connection, "lists")."', '$username', '$name', '$colour', '$category')";

	    if ($connection->query($sql) === TRUE)
	    {
		    echo "List added!\n";
		}

		$sql = "UPDATE users SET lists = '$lists' WHERE username = '$username'";

	    $connection->query($sql);
	}
}

function removeList($connection, $username, $id)
{
	$username = strtolower($username);
	$lists = getLists($connection, $username) - 1;

	if ($lists >= 0)
	{
		$sql = "DELETE FROM tasks WHERE listID = '".$id."'";

	    $connection->query($sql);

		$sql = "DELETE FROM lists WHERE username = '$username' AND id = '$id'";

	    if ($connection->query($sql) === TRUE)
	    {
		    echo "List removed!\n";
		}

		$sql = "UPDATE users SET lists = '$lists' WHERE username = '$username'";

	    $connection->query($sql);
	}
}

// ------------------------ Tasks ------------------------ 

function displayTasks($connection, $id)
{
	$sql = "SELECT state, type, taskDate, taskTime, content FROM tasks WHERE listID = '$id'";
	$result = $connection->query($sql);
	
	if ($result->num_rows > 0)
	{
		echo "<table style='width:100%'";

		while ($result1 = $result->fetch_assoc())
		{
		    echo "<tr><td style='width:40%'>".$result1['content']."</td>";

		    if ($result1['type'] === "timer")
		    {
		    	echo "<td style='text-align:center'>".$result1['taskTime']."</td>";
		    }

		    else if ($result1['type'] === "deadline")
		    {
		    	echo "<td style='text-align:center'>".$result1['taskDate']."</td>";
		    }

		    else
		    {
		    	echo "<td style='text-align:center'></td>";
		    }

		    if ($result1['state'])
		    {
		    	echo "<td style='text-align:center'>Completed</td>";
		    	echo "<td style='text-align:right'><input type='submit' name='state".getTaskID($connection, $id, $result1['content'])."' value='\u{274C}'>";
		    }

		    else
		    {
		    	echo "<td style='text-align:center'>Incompleted</td>";
		    	echo "<td style='text-align:right'><input type='submit' name='state".getTaskID($connection, $id, $result1['content'])."' value='\u{2714}'>";
		    }

		    echo "<input type='submit' name='removeTask".getTaskID($connection, $id, $result1['content'])."' value='\u{1F5D1}'></td>";
		    	echo "</tr>";
		}

		echo "</table>";
	}

	echo "<br>Task:<br>";
	echo "<input type='text' name='content'>";
	echo "<button onclick='document.getElementById('content').value = ''>\u{274C}</button><br>";
	echo "<input type='hidden' name='listID' value='".$id."'>";

	echo "Type:<br>";
	echo "<select name='type' onchange='showHide(this)'>";
	echo "<option value='tick".$_SESSION["list"]."'>Tick</option>";
	echo "<option value='timer".$_SESSION["list"]."'>Timer</option>";
	echo "<option value='deadline".$_SESSION["list"]."'>Deadline</option>";
	echo "</select><br>";

	echo "<div id='divDate".$_SESSION["list"]."'>Date:<br>";
	echo "<input type='date' name='taskDate' min='".date("Y-m-d")."'><br></div>";

	echo "<div id='divTime".$_SESSION["list"]."'>Time:<br>";
	echo "<input type='time' name='taskTime'><br></div>";

	echo "<table style='width:31%'><tr>";
	echo "<td style='width:16%'><input type='submit' name='addTask' value='Add Task'></td>";
	echo "<td style='width:15%'><input type='submit' name='removeList".$id."' value='Remove List'></td>";
	echo "</tr></table>";

	$_SESSION["list"] = $_SESSION["list"] + 1;
}

function getTaskID($connection, $listID, $content)
{
	$sql = "SELECT id FROM tasks WHERE listID = '$listID' AND content = '$content'";
	$result = $connection->query($sql);
	$result1 = $result->fetch_assoc();
	
	return $result1['id'];
}

function addTask($connection, $listID, $type, $date, $time, $content)
{
	$sql = "INSERT INTO tasks (id, listID, state, type, taskDate, taskTime, content) VALUES ('".newID($connection, "tasks")."', '$listID', FALSE, '$type', '$date', '$time', '$content')";

	if ($connection->query($sql) === TRUE)
	{
		echo "Task added!\n";
	}

	else
	{
		echo "Error adding user: " . $connection->error."\n";
	}
}

function removeTask($connection, $id)
{
	$sql = "DELETE FROM tasks WHERE id = '$id'";

    if ($connection->query($sql) === TRUE)
    {
	    echo "Task removed!\n";
	}
}

function getTaskState($connection, $id)
{
	$sql = "SELECT state FROM tasks WHERE id = '$id'";
	$result = $connection->query($sql);
	$result1 = $result->fetch_assoc();
	
	if($result1['state'])
	{
		return 0;
	}

	return 1;
}

function changeTaskState($connection, $id, $username)
{
	$state = getTaskState($connection, $id);
	$sql = "UPDATE tasks SET state = '$state' WHERE id = '$id'";

    if ($connection->query($sql) === TRUE)
    {
	    echo "Task state changed!\n";

		if ($state)
		{
			addPoints($connection, $username, 10);
		}

		else
		{
			removePoints($connection, $username, 10);
		}
	}
}

?>