<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload ZIP</title>
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
                        <!-- Add more sidebar items as needed -->
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Upload ZIP</h1>
                </div>

                <form action="upload.php" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="zipFile" class="form-label">Select ZIP file:</label>
                        <input type="file" class="form-control" name="zipFile" id="zipFile" accept=".zip">
                    </div>
                    <button type="submit" class="btn btn-primary" name="submit">Upload</button>
                </form>
            </main>
        </div>
    </div>


</body>
</html>

<?php

// Подключение к базе данных
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "task";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$import = false;
if (isset($_POST["submit"])) {
    $zipFile = $_FILES["zipFile"]["tmp_name"];

    // Распаковка ZIP-файла
    $zip = new ZipArchive;
    if ($zip->open($zipFile) === TRUE) {
        $conn->autocommit(FALSE); // Отключаем автокоммит для использования транзакций

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                preg_match("/IMPORT_(\d+)\.XML/", $filename, $matches);
                $pharmacy_id = $matches[1];

                $xmlContent = file_get_contents("zip://".$zipFile."#".$filename);
                // Convert to UTF-8 from Windows-1251
                $xmlContent = iconv("Windows-1251", "UTF-8//IGNORE", $xmlContent);
                $xmlData = simplexml_load_string($xmlContent);

                if ($xmlData) {
                // Проверка существования аптеки в таблице pharmacies
                $sql_check_pharmacy = "SELECT pharmacy_id FROM pharmacies WHERE pharmacy_id = '$pharmacy_id'";
                $result_check_pharmacy = $conn->query($sql_check_pharmacy);

                if ($result_check_pharmacy->num_rows == 0) {
                    $sql_insert_pharmacy = "INSERT INTO pharmacies (pharmacy_id, pharmacy_code) VALUES ('$pharmacy_id', 'Аптека - $pharmacy_id')";
                    $conn->query($sql_insert_pharmacy);
                }
                    // Аптека существует, можно продолжить обработку
                    foreach ($xmlData->B as $item) {
                        $name = $conn->real_escape_string($item["N"]);
                        $manufacturer = $conn->real_escape_string($item["P"]);
                        $barcode = $conn->real_escape_string($item["B"]);
                        $quantity = intval($item["K"]);
                        $remaining_quantity = intval($item["K"]);
                        $purchase_price = floatval($item["S"]);
                        $selling_price = floatval($item["G"]);
                        $expiration_date = date("Y-m-d", strtotime($item["E"]));


                        // Проверка существования медикамента в таблице medicines
                        $sql_check_medicine = "SELECT medicine_id FROM medicines WHERE barcode = '$barcode'";
                        $result_check_medicine = $conn->query($sql_check_medicine);

                        if ($result_check_medicine->num_rows == 0) {
                            // Медикамента не существует в таблице medicines, добавляем его
                            $sql_insert_medicine = "INSERT INTO medicines (name, manufacturer, barcode) VALUES ('$name', '$manufacturer', '$barcode')";
                            $conn->query($sql_insert_medicine);
                        }

                        // Проверка существования записи в инвентаре
                        $sql_check = "SELECT inventory_id FROM inventory 
                                      WHERE pharmacy_id = '$pharmacy_id' 
                                      AND medicine_id = (SELECT medicine_id FROM medicines WHERE barcode = '$barcode') 
                                      AND expiration_date = '$expiration_date'";
                        $result_check = $conn->query($sql_check);

                        if ($result_check !== FALSE) {
                            if ($result_check->num_rows == 0) {
                                // Если запись не существует, то проверяем существование записи в таблице medicines
                                $sql_check_medicine = "SELECT medicine_id FROM medicines WHERE barcode = '$barcode'";
                                $result_check_medicine = $conn->query($sql_check_medicine);

                                if ($result_check_medicine->num_rows == 0) {
                                    // Если записи в medicines нет, то добавляем ее
                                    $sql_insert_medicine = "INSERT INTO medicines (name, barcode) VALUES ('$name', '$barcode')";
                                    $conn->query($sql_insert_medicine);

                                    $sql_insert_update_medicine = "INSERT INTO medicines (name, manufacturer, barcode) 
                                           VALUES ('$name', '$manufacturer', '$barcode') 
                                           ON DUPLICATE KEY UPDATE 
                                           name = VALUES(name), manufacturer = VALUES(manufacturer)";
                                    $conn->query($sql_insert_update_medicine);
                                }

                                // После проверки добавляем в инвентарь
                                $sql_insert = "INSERT INTO inventory (pharmacy_id, medicine_id, quantity, remaining_quantity, purchase_price, selling_price, expiration_date, import_date)
                                               VALUES ('$pharmacy_id', (SELECT medicine_id FROM medicines WHERE barcode = '$barcode'), '$quantity', '$remaining_quantity', '$purchase_price', '$selling_price', '$expiration_date', CURRENT_DATE)";
                                $conn->query($sql_insert);

                                // Проверка условия: отпускная цена превышает приходную на 20%
                                if ($selling_price > 1.2 * $purchase_price) {
                                    // Добавление пометки в базу данных или другие необходимые действия
                                    $mark_query = "UPDATE inventory 
                                                   SET is_price_high = 1 
                                                   WHERE pharmacy_id = '$pharmacy_id' 
                                                   AND medicine_id = (SELECT medicine_id FROM medicines WHERE barcode = '$barcode') 
                                                   AND expiration_date = '$expiration_date'";
                                    $conn->query($mark_query);
                                }
                            } else {
                                // Если запись существует, обновляем данные
                                $sql_update = "UPDATE inventory 
                                               SET quantity = '$quantity', remaining_quantity = '$remaining_quantity', 
                                                   purchase_price = '$purchase_price', selling_price = '$selling_price', 
                                                   import_date = CURRENT_DATE 
                                               WHERE pharmacy_id = '$pharmacy_id' 
                                               AND medicine_id = (SELECT medicine_id FROM medicines WHERE barcode = '$barcode') 
                                               AND expiration_date = '$expiration_date'";
                                $conn->query($sql_update);
                            }

                            // 1. Обновление или вставка в таблицу medicines
                            $sql_insert_update_medicine = "INSERT INTO medicines (name, manufacturer, barcode) 
                                           VALUES ('$name', '$manufacturer', '$barcode') 
                                           ON DUPLICATE KEY UPDATE 
                                           name = VALUES(name), manufacturer = VALUES(manufacturer)";
                            $conn->query($sql_insert_update_medicine);

                            // 2. Обновление или вставка в таблицу inventory
                            $sql_insert_update_inventory = "INSERT INTO inventory (pharmacy_id, medicine_id, quantity, remaining_quantity, purchase_price, selling_price, expiration_date, import_date) 
                                            VALUES ('$pharmacy_id', (SELECT medicine_id FROM medicines WHERE barcode = '$barcode'), '$quantity', '$remaining_quantity', '$purchase_price', '$selling_price', '$expiration_date', CURRENT_DATE) 
                                            ON DUPLICATE KEY UPDATE 
                                            quantity = VALUES(quantity), remaining_quantity = VALUES(remaining_quantity), 
                                            purchase_price = VALUES(purchase_price), selling_price = VALUES(selling_price), 
                                            import_date = VALUES(import_date)";
                            $conn->query($sql_insert_update_inventory);

                            // Проверка условия: отпускная цена превышает приходную на 20%
                            if ($selling_price > 1.2 * $purchase_price) {
                                // Добавление пометки в базу данных или другие необходимые действия
                                $mark_query = "UPDATE inventory 
                               SET is_price_high = 1 
                               WHERE pharmacy_id = '$pharmacy_id' 
                               AND medicine_id = (SELECT medicine_id FROM medicines WHERE barcode = '$barcode') 
                               AND expiration_date = '$expiration_date'";
                                $conn->query($mark_query);
                            }


                            $conn->commit(); // Фиксируем изменения при успешной обработке
                         //   echo "Data imported successfully.";
                            $import = true;
                        } else {
                            // Возникла ошибка при выполнении запроса
                            echo "Error checking record: " . $conn->error;
                            $import = false;
                        }
                    }

                } else {
                    echo "Failed to load XML file. Error message: " . libxml_get_last_error()->message;
                    $import = false;
                }
            }
        } catch (Exception $e) {
            $conn->rollback(); // Откатываем изменения в случае ошибки
            echo "Error processing data: " . $e->getMessage();
            $import = false;
        }

        $zip->close();
    } else {
        echo "Failed to open the ZIP file.";
    }
    if($import == true) {
        echo "Data imported successfully.";
    }
}

$conn->close();

