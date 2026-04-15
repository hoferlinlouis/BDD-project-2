<!DOCTYPE html>
<?php $bdd = new PDO('mysql:host=db;dbname=group10;charset=utf8mb4', 'group10', 'secret'); ?>
<html lang="en">
<head><meta charset="UTF-8"><title>Client Search</title></head>
<body>
    <a href="index.php">← Back to Home</a>
    <h1>Search for a Client</h1>
    <form method="post" action="search_client.php">
        <p>Number: <input type="number" name="id" value="<?=htmlspecialchars($_POST['id']??'')?>"></p>
        <p>First Name: <input type="text" name="fn" value="<?=htmlspecialchars($_POST['fn']??'')?>"></p>
        <p>Last Name: <input type="text" name="ln" value="<?=htmlspecialchars($_POST['ln']??'')?>"></p>
        <p>Email: <input type="text" name="em" value="<?=htmlspecialchars($_POST['em']??'')?>"></p>
        <p>Phone: <input type="text" name="ph" value="<?=htmlspecialchars($_POST['ph']??'')?>"></p>
        <input type="submit" name="search" value="Filter">
    </form>

    <?php
    if (isset($_POST["search"])) {
        $sql = "SELECT * FROM CLIENT WHERE 1=1";
        $params = [];
        if (!empty($_POST["id"])) { $sql .= " AND CLIENT_NUMBER = ?"; $params[] = $_POST["id"]; }
        if (!empty($_POST["fn"])) { $sql .= " AND FIRST_NAME LIKE ?"; $params[] = "%".$_POST["fn"]."%"; }
        if (!empty($_POST["ln"])) { $sql .= " AND LAST_NAME LIKE ?"; $params[] = "%".$_POST["ln"]."%"; }
        if (!empty($_POST["em"])) { $sql .= " AND EMAIL_ADDRESS LIKE ?"; $params[] = "%".$_POST["em"]."%"; }
        if (!empty($_POST["ph"])) { $sql .= " AND PHONE_NUMBER LIKE ?"; $params[] = "%".$_POST["ph"]."%"; }

        $stmt = $bdd->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        if ($rows) {
            echo "<table border='1'><tr><th>ID</th><th>First Name</th><th>Last Name</th><th>Email</th><th>Phone</th></tr>";
            foreach ($rows as $r) {
                echo "<tr><td>".$r['CLIENT_NUMBER']."</td><td>".$r['FIRST_NAME']."</td><td>".$r['LAST_NAME']."</td><td>".$r['EMAIL_ADDRESS']."</td><td>".$r['PHONE_NUMBER']."</td></tr>";
            }
            echo "</table>";
        } else echo "No results found.";
    }
    ?>
</body>
</html>