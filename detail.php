<?php
// detail.php

// Подключение к базе данных
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "task";

$conn = new mysqli($servername, $username, $password, $dbname);

// Проверка успешности подключения
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Получение параметра pharmacy_id из URL
$pharmacy_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Проверка, что pharmacy_id является целым числом и больше 0
if ($pharmacy_id > 0) {
    // Запрос для получения информации об инвентаре и лекарствах для конкретной аптеки
    $sql = "SELECT i.*, m.name as medicine_name, m.manufacturer as medicine_manufacturer,
            m.barcode as barcode
            FROM inventory i
            JOIN medicines m ON i.medicine_id = m.medicine_id
            WHERE i.pharmacy_id = $pharmacy_id";

    $result = $conn->query($sql);

    // Проверка наличия записей
    if ($result->num_rows > 0) {
        $inventoryDetails = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        $inventoryDetails = array();
    }

    // ... Остальной HTML-код для отображения информации
} else {
    // В случае недопустимого pharmacy_id
    echo "Invalid pharmacy ID";
}

// Закрытие соединения с базой данных
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Task</title>
    <!-- Add Bootstrap CDN links -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>


<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <div class="position-sticky">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            List of Pharmacies
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="upload.php">
                            Upload ZIP file
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <h3>Список препаратов</h3>
        <table class="table">
            <thead>
            <tr>
                <th>Medicine Name</th>
                <th>Manufacturer</th>
                <th>Barcode</th>
                <th>Quantity</th>
                <th>Purchase Price</th>
                <th>Selling Price</th>
                <th>Expiration Date</th>
                <th>Import Date</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($inventoryDetails as $item): ?>
                <tr>
                    <td><?= $item['medicine_name'] ?></td>
                    <td><?= $item['medicine_manufacturer'] ?></td>
                    <td><?= $item['barcode'] ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td><?= $item['purchase_price'] ?></td>
                    <td><?= $item['selling_price'] ?></td>
                    <td><?= $item['expiration_date'] ?></td>
                    <td><?= $item['import_date'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </main>
    </div>
</div>

</body>
</html>
