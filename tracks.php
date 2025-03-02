<?php
require_once 'config.php'; // Подключаем API

$cache_dir = __DIR__ . "/cache"; // Папка кеша
$cache_file = "$cache_dir/track_data.json"; // Файл кеша с треками
$cache_lifetime = 86400; // 24 часа (86400 секунд)

// 🔥 Создаём папку кеша, если её нет
if (!file_exists($cache_dir)) {
    if (!mkdir($cache_dir, 0777, true)) {
        die(json_encode(["error" => "❌ Ошибка: не удалось создать папку кеша ($cache_dir). Проверьте `chmod 777 cache`."]));
    }
}

// 🔥 Проверяем права на запись
if (!is_writable($cache_dir)) {
    die(json_encode(["error" => "❌ Ошибка: у PHP нет прав на запись в кеш ($cache_dir). Проверьте `chmod 777 cache`."]));
}

// 🔥 Если кеш есть и он актуален, загружаем его
if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_lifetime) {
    header('Content-Type: application/json');
    echo file_get_contents($cache_file);
    exit;
}

// 🔥 Запрашиваем список треков у API RadioBOSS
$rb_api_url = "$rb_api&action=library&filename=$rb_library";
$library_raw = file_get_contents($rb_api_url);

if (!$library_raw) {
    die(json_encode(["error" => "❌ Ошибка: Не удалось загрузить библиотеку треков. Проверьте API."]));
}

// 🔥 Разбираем XML
$xml = simplexml_load_string($library_raw);
if ($xml === false) {
    die(json_encode(["error" => "❌ Ошибка: Не удалось обработать XML."]));
}

// 🔥 Получаем список треков
$tracks = [];
foreach ($xml->Track as $track) {
    $artist = trim((string)$track['artist']);
    $title = trim((string)$track['title']);
    if ($artist && $title) {
        $tracks[] = "$artist - $title";
    }
}

// 🔥 Если треков нет, выдаём ошибку
$track_count = count($tracks);
if ($track_count == 0) {
    die(json_encode(["error" => "❌ Ошибка: Библиотека пуста. Проверьте API или название библиотеки."]));
}

// 🔥 Сохраняем кеш с количеством и треками
$cache_data = [
    "count" => $track_count,
    "tracks" => $tracks
];

if (!file_put_contents($cache_file, json_encode($cache_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX)) {
    die(json_encode(["error" => "❌ Ошибка: Не удалось записать кеш ($cache_file). Проверьте `chmod 777 cache`."]));
}

chmod($cache_file, 0666);

// ✅ Отправляем JSON с количеством треков и их названиями
header('Content-Type: application/json');
echo json_encode($cache_data, JSON_PRETTY_PRINT);
?>
