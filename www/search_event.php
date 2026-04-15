<!DOCTYPE html>
<?php $bdd = new PDO('mysql:host=db;dbname=group10;charset=utf8mb4', 'group10', 'secret'); ?>
<html lang="en">
<head><meta charset="UTF-8"><title>Event Search</title></head>
<body>
    <a href="index.php">← Back to Home</a>
    <h1>Search for an Event</h1>

    <form method="post" action="search_event.php">
        <p>Event ID: <br><input type="number" name="id" value="<?php if(isset($_POST['id'])) echo htmlspecialchars($_POST['id']); ?>"></p>
        <p>Name: <br><input type="text" name="name" value="<?php if(isset($_POST['name'])) echo htmlspecialchars($_POST['name']); ?>"></p>
        <p>Type: <br><input type="text" name="type" value="<?php if(isset($_POST['type'])) echo htmlspecialchars($_POST['type']); ?>"></p>
        <p>Theme: <br><input type="text" name="theme" value="<?php if(isset($_POST['theme'])) echo htmlspecialchars($_POST['theme']); ?>"></p>
        <p>Client ID: <br><input type="number" name="client" value="<?php if(isset($_POST['client'])) echo htmlspecialchars($_POST['client']); ?>"></p>
        <input type="submit" name="search" value="Search">
    </form>

    <?php
    if (isset($_POST["search"])) {
        $sql = "SELECT * FROM EVENT WHERE 1=1";
        $params = [];

        if (!empty($_POST["id"])) { $sql .= " AND ID = ?"; $params[] = $_POST["id"]; }
        if (!empty($_POST["name"])) { $sql .= " AND NAME LIKE ?"; $params[] = "%".$_POST["name"]."%"; }
        if (!empty($_POST["type"])) { $sql .= " AND TYPE LIKE ?"; $params[] = "%".$_POST["type"]."%"; }
        if (!empty($_POST["theme"])) { $sql .= " AND THEME LIKE ?"; $params[] = "%".$_POST["theme"]."%"; }
        if (!empty($_POST["client"])) { $sql .= " AND CLIENT = ?"; $params[] = $_POST["client"]; }

        $stmt = $bdd->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        if ($rows) {
            echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Type</th><th>Theme</th><th>Client</th></tr>";
            foreach ($rows as $row) {
                echo "<tr><td>".$row['ID']."</td><td>".$row['NAME']."</td><td>".$row['TYPE']."</td><td>".$row['THEME']."</td><td>".$row['CLIENT']."</td></tr>";
            }
            echo "</table>";
        } else { echo "No results found."; }
    }
    ?>
</body>
</html>