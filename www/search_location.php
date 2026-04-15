<!DOCTYPE html>
<?php $bdd = new PDO('mysql:host=db;dbname=group10;charset=utf8mb4', 'group10', 'secret'); ?>
<html lang="en">
<head><meta charset="UTF-8"><title>Location Search</title></head>
<body>
    <a href="index.php">← Back to Home</a>
    <h1>Search for a Location</h1>

    <form method="post" action="search_location.php">
        <p>Location ID: <br><input type="number" name="id" value="<?php if(isset($_POST['id'])) echo htmlspecialchars($_POST['id']); ?>"></p>
        <p>City: <br><input type="text" name="city" value="<?php if(isset($_POST['city'])) echo htmlspecialchars($_POST['city']); ?>"></p>
        <p>Postal Code: <br><input type="number" name="pc" value="<?php if(isset($_POST['pc'])) echo htmlspecialchars($_POST['pc']); ?>"></p>
        <p>Country: <br><input type="text" name="country" value="<?php if(isset($_POST['country'])) echo htmlspecialchars($_POST['country']); ?>"></p>
        <input type="submit" name="search" value="Search">
    </form>

    <?php
    if (isset($_POST["search"])) {
        $sql = "SELECT * FROM LOCATION WHERE 1=1";
        $params = [];

        if (!empty($_POST["id"])) { $sql .= " AND ID = ?"; $params[] = $_POST["id"]; }
        if (!empty($_POST["city"])) { $sql .= " AND CITY LIKE ?"; $params[] = "%".$_POST["city"]."%"; }
        if (!empty($_POST["pc"])) { $sql .= " AND POSTAL_CODE = ?"; $params[] = $_POST["pc"]; }
        if (!empty($_POST["country"])) { $sql .= " AND COUNTRY LIKE ?"; $params[] = "%".$_POST["country"]."%"; }

        $stmt = $bdd->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        if ($rows) {
            echo "<table border='1'><tr><th>ID</th><th>Street</th><th>City</th><th>PC</th><th>Country</th></tr>";
            foreach ($rows as $row) {
                echo "<tr><td>".$row['ID']."</td><td>".$row['STREET']."</td><td>".$row['CITY']."</td><td>".$row['POSTAL_CODE']."</td><td>".$row['COUNTRY']."</td></tr>";
            }
            echo "</table>";
        } else { echo "No results found."; }
    }
    ?>
</body>
</html>