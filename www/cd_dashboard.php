<!DOCTYPE html>
<?php 

$bdd = new PDO('mysql:host=db;dbname=group10;charset=utf8mb4', 'group10', 'secret');

$sort_attr = $_POST['sort_attr'] ?? 'DATE';
$sort_order = $_POST['sort_order'] ?? 'DESC';

$allowed_sorts = ['event_date', 'cd_title', 'copies_used'];
if (!in_array($sort_attr, $allowed_sorts)) {
    $sort_attr = 'event_date'; 
}

$query_sql = "
    SELECT 
        E.DATE as event_date,
        C.TITLE as cd_title,
        C.COPIES as total_stock,
        COUNT(DISTINCT E.ID) as copies_used
    FROM EVENT E
    JOIN CONTAINS CO ON E.PLAYLIST = CO.PLAYLIST
    JOIN CD C ON CO.CD_NUMBER = C.CD_NUMBER
    GROUP BY E.DATE, C.CD_NUMBER
    ORDER BY $sort_attr $sort_order
";

try {
    $stmt = $bdd->query($query_sql);
    $results = $stmt->fetchAll();
} catch (PDOException $e) {
    die("<h3 style='color:red;'>SQL Error: " . $e->getMessage() . "</h3>");
}
?>
<html lang="en">
<head><meta charset="UTF-8"><title>CD Availability Dashboard</title></head>
<body>
    <a href="index.php">← Back to Home</a>
    <h1>CD Dashboard</h1>

    <form method="post">
        Sort by: 
        <select name="sort_attr">
            <option value="event_date" <?= $sort_attr == 'event_date' ? 'selected' : '' ?>>Date</option>
            <option value="cd_title" <?= $sort_attr == 'cd_title' ? 'selected' : '' ?>>CD Title</option>
            <option value="copies_used" <?= $sort_attr == 'copies_used' ? 'selected' : '' ?>>Usage</option>
        </select>
        
        Order: 
        <select name="sort_order">
            <option value="DESC" <?= $sort_order == 'DESC' ? 'selected' : '' ?>>Descending</option>
            <option value="ASC" <?= $sort_order == 'ASC' ? 'selected' : '' ?>>Ascending</option>
        </select>
        <input type="submit" value="Sort">
    </form>

    <br>
    <table border="1" cellpadding="5">
        <tr>
            <th>Date</th>
            <th>CD Title</th>
            <th>Total Stock</th>
            <th>Copies Used</th>
            <th>Status</th>
        </tr>
        <?php foreach ($results as $row): 
            $available = $row['total_stock'] - $row['copies_used'];
        ?>
        <tr>
            <td><?= htmlspecialchars($row['event_date']) ?></td>
            <td><?= htmlspecialchars($row['cd_title']) ?></td>
            <td><?= htmlspecialchars($row['total_stock']) ?></td>
            <td><?= htmlspecialchars($row['copies_used']) ?></td>
            <td style="background-color: <?= $available < 0 ? '#ffcccc' : '#ccffcc' ?>;">
                <?= $available >= 0 ? "OK ($available left)" : "OVERBOOKED!" ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <?php if (!$results) echo "<p>No data found.</p>"; ?>
</body>
</html>