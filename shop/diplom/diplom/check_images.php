<?php
// test_upload.php
echo "<h2>Проверка загрузки фото</h2>";

if ($_FILES) {
    $target_dir = "images/";
    $target_file = $target_dir . basename($_FILES["photo"]["name"]);
    
    if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
        echo "✅ Файл загружен: " . $target_file;
        echo "<br><img src='$target_file' width='200'>";
    } else {
        echo "❌ Ошибка загрузки";
    }
}
?>

<form method="POST" enctype="multipart/form-data">
    <input type="file" name="photo" required>
    <button>Загрузить</button>
</form>

<hr>
<h3>Что сейчас в папке images:</h3>
<?php
if (is_dir('images/')) {
    $files = scandir('images/');
    echo "<ul>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>$file</li>";
        }
    }
    echo "</ul>";
} else {
    echo "Папки images/ нет!";
}
?>