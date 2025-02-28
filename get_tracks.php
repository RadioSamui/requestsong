<?php
require_once 'config.php'; // Подключаем API

$cache_dir = __DIR__ . "/cache";
$log_dir = __DIR__ . "/logs";
$cache_file = "$cache_dir/cache_autocomplete.json";
$log_file = "$log_dir/get_tracks.log";
$cache_lifetime = 86400; // 24 часа

// 🔥 Функция логирования
function logMessage($message) {
    global $log_file;
    $timestamp = date("Y-m-d H:i:s");

    if (!file_exists($log_file)) {
        file_put_contents($log_file, "[$timestamp] Лог создан\n", LOCK_EX);
        chmod($log_file, 0666);
    }

    file_put_contents($log_file, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

// 🔥 Создаём папки кеша и логов, если их нет
foreach ([$cache_dir, $log_dir] as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// 🔥 Проверяем кеш
if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_lifetime) {
    logMessage("✅ Загружаем кеш ($cache_file)");
    $cache_data = json_decode(file_get_contents($cache_file), true);
    if (isset($cache_data['tracks'])) {
        header('Content-Type: application/json');
        echo json_encode($cache_data);
        exit;
    }
}

// 🔥 Проверяем, работает ли API (ping)
$ping_url = "$rb_api&cmd=ping";
$ping_response = file_get_contents($ping_url);
if (trim($ping_response) !== "OK") {
    logMessage("❌ Ошибка: API RadioBOSS не отвечает.");
    die(json_encode(["error" => "Ошибка: API RadioBOSS не отвечает."]));
}

// 🔥 Получаем список плейлистов
$playlists_url = "$rb_api&cmd=getplaylists";
$playlists_raw = file_get_contents($playlists_url);
if (!$playlists_raw || str_contains($playlists_raw, "E005")) {
    logMessage("❌ Ошибка: не удалось получить список плейлистов.");
    die(json_encode(["error" => "Ошибка: не удалось получить список плейлистов."]));
}

// 🔥 Проверяем, есть ли плейлист requestmusic
$playlists = explode("\n", trim($playlists_raw));
if (!in_array($rb_library, $playlists)) {
    logMessage("❌ Ошибка: плейлист `$rb_library` не найден в RadioBOSS.");
    die(json_encode(["error" => "Ошибка: плейлист `$rb_library` не найден в RadioBOSS."]));
}

// 🔥 Получаем список треков
$tracks_url = "$rb_api&cmd=getfiles&filename=$rb_library";
$tracks_raw = file_get_contents($tracks_url);
if (!$tracks_raw || str_contains($tracks_raw, "E005")) {
    logMessage("❌ Ошибка: не удалось получить список треков.");
    die(json_encode(["error" => "Ошибка: не удалось получить список треков."]));
}

// 🔥 Обрабатываем список треков
$tracks = explode("\n", trim($tracks_raw));
if (empty($tracks)) {
    logMessage("❌ Ошибка: плейлист `$rb_library` пуст.");
    die(json_encode(["error" => "Ошибка: плейлист `$rb_library` пуст."]));
}

// 🔥 Сохраняем кеш
file_put_contents($cache_file, json_encode(["tracks" => $tracks]), LOCK_EX);
chmod($cache_file, 0666);
logMessage("✅ Успешно загружено " . count($tracks) . " треков в кеш.");

// ✅ Отправляем JSON с треками
header('Content-Type: application/json');
echo json_encode(["tracks" => $tracks]);
?>