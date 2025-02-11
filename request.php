<?php
require_once 'config.php'; // Подключаем API

$log_file = __DIR__ . "/request.log"; // Файл лога
$max_log_size = 5 * 1024 * 1024; // 5MB (максимальный размер лога)

// Если лог больше 5MB – очищаем
if (file_exists($log_file) && filesize($log_file) > $max_log_size) {
    file_put_contents($log_file, ""); // Очищаем лог
}

// Функция для записи в лог
function logRequest($message) {
    global $log_file;
    file_put_contents($log_file, "[" . date("Y-m-d H:i:s") . "] " . $message . PHP_EOL, FILE_APPEND);
}

// Функция запроса к API
function HTTPGet($url) {
    logRequest("Запрос к API: $url");
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    $res = curl_exec($curl);

    if (curl_errno($curl)) {
        logRequest("Ошибка API: " . curl_error($curl));
        die(json_encode(["error" => "API Error: " . curl_error($curl)]));
    }

    curl_close($curl);
    return $res;
}

// Получаем данные из формы
$artist = isset($_POST['artist']) ? trim($_POST['artist']) : '';
$title = isset($_POST['title']) ? trim($_POST['title']) : '';

if ($artist === '' && $title === '') {
    die(json_encode(["error" => "Please enter an artist or song title."]));
}

// Запрашиваем библиотеку
$library_raw = HTTPGet("$rb_api&action=library&filename=$rb_library");

if (!$library_raw) {
    logRequest("Ошибка загрузки библиотеки");
    die(json_encode(["error" => "Failed to load music library."]));
}

// Разбираем XML и ищем песню
$xml = simplexml_load_string($library_raw);
if ($xml === false) {
    logRequest("Ошибка обработки XML");
    die(json_encode(["error" => "Library processing error."]));
}

$found_track = false;
foreach ($xml->Track as $track) {
    $track_artist = mb_strtolower(trim((string) $track['artist']));
    $track_title = mb_strtolower(trim((string) $track['title']));
    $track_filename = trim((string) $track['filename']);

    if (
        (stripos($track_artist, $artist) !== false || empty($artist)) &&
        (stripos($track_title, $title) !== false || empty($title))
    ) {
        $found_track = $track_filename;
        break;
    }
}

if ($found_track) {
    logRequest("✅ Найдена песня: $found_track");

    $request_url = "$rb_api&action=songrequest&filename=" . urlencode($found_track);
    $res = HTTPGet($request_url);

    if ($res !== 'OK') {
        $request_url = "$rb_api&action=schedule&type=add&time=now&command=play \"" . urlencode($found_track) . "\"";
        $res = HTTPGet($request_url);
    }

    if ($res === 'OK') {
        echo json_encode(["success" => "Song successfully requested!"]);
    } else {
        logRequest("Ошибка запроса песни: $res");
        echo json_encode(["error" => "Error requesting the song. API Response: $res"]);
    }
} else {
    logRequest("Песня не найдена: Artist: $artist, Title: $title");
    echo json_encode(["error" => "Song not found in the music library."]);
}
?>
