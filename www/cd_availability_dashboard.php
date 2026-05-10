<!DOCTYPE html>
<?php
$bdd = new PDO('mysql:host=db;dbname=group10;charset=utf8mb4', 'group10', 'secret', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$sort_attr = $_POST['sort_attr'] ?? 'event_date';
$sort_order = $_POST['sort_order'] ?? 'DESC';

$allowed_sorts = ['event_date', 'cd_title', 'total_copies', 'copies_used'];
if (!in_array($sort_attr, $allowed_sorts, true)) {
    $sort_attr = 'event_date';
}
if ($sort_order !== 'ASC' && $sort_order !== 'DESC') {
    $sort_order = 'DESC';
}

$query_sql = "
    WITH RECURSIVE dates AS (
        SELECT MIN(DATE) AS event_date, MAX(DATE) AS last_date
        FROM EVENT
        UNION ALL
        SELECT DATE_ADD(event_date, INTERVAL 1 DAY), last_date
        FROM dates
        WHERE event_date < last_date
    ),
    cd_usage AS (
        SELECT
            E.DATE AS event_date,
            CO.CD_NUMBER,
            COUNT(DISTINCT E.ID) AS copies_used
        FROM EVENT E
        JOIN CONTAINS CO ON E.PLAYLIST = CO.PLAYLIST
        GROUP BY E.DATE, CO.CD_NUMBER
    )
    SELECT
        D.event_date,
        C.TITLE AS cd_title,
        C.COPIES AS total_copies,
        COALESCE(U.copies_used, 0) AS copies_used
    FROM dates D
    CROSS JOIN CD C
    LEFT JOIN cd_usage U
        ON U.event_date = D.event_date
       AND U.CD_NUMBER = C.CD_NUMBER
    WHERE D.event_date IS NOT NULL
    ORDER BY $sort_attr $sort_order, cd_title ASC
";

try {
    $stmt = $bdd->query($query_sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<h3>SQL Error: " . htmlspecialchars($e->getMessage()) . "</h3>");
}
?>
<html lang="en">
<head><meta charset="UTF-8"><title>CD Availability Dashboard</title></head>
<body>
    <a href="index.php">Back to Home</a>
    <h1>CD Availability Dashboard</h1>

    <form method="post">
        Sort by:
        <select name="sort_attr">
            <option value="event_date" <?= $sort_attr == 'event_date' ? 'selected' : '' ?>>Date</option>
            <option value="cd_title" <?= $sort_attr == 'cd_title' ? 'selected' : '' ?>>CD Title</option>
            <option value="total_copies" <?= $sort_attr == 'total_copies' ? 'selected' : '' ?>>Total Copies</option>
            <option value="copies_used" <?= $sort_attr == 'copies_used' ? 'selected' : '' ?>>Copies Used</option>
        </select>

        Order:
        <select name="sort_order">
            <option value="DESC" <?= $sort_order == 'DESC' ? 'selected' : '' ?>>Descending</option>
            <option value="ASC" <?= $sort_order == 'ASC' ? 'selected' : '' ?>>Ascending</option>
        </select>
        <input type="submit" value="Sort">
    </form>

    <table border="1" cellpadding="5" style="border-collapse: collapse; width: 100%;">
        <tr style="background: #eee;">
            <th>Date</th>
            <th>CD Title</th>
            <th>Total Copies</th>
            <th>Copies Used</th>
        </tr>
        <?php foreach ($results as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['event_date']) ?></td>
                <td><?= htmlspecialchars($row['cd_title']) ?></td>
                <td><?= htmlspecialchars($row['total_copies']) ?></td>
                <td><?= htmlspecialchars($row['copies_used']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <?php if (!$results) echo "<p>No data found.</p>"; ?>
</body>
</html>
