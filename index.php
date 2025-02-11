<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#FFD700">
    <title>Song Request | Radio Samui</title>
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background: url('https://radiosamui.online/img/tempbg.jpg') no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            overflow: hidden;
        }
        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
        }
        .container {
            position: relative;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            width: 100%;
            max-width: 350px;
        }
        .logo {
            max-width: 120px;
            margin-bottom: 20px;
        }
        input, button {
            width: 100%;
            max-width: 300px;
            padding: 12px;
            margin: 10px 0;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            text-align: center;
        }
        input {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
        button {
            background: #FFD700;
            color: black;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover {
            background: #FFC107;
        }
        .track-count {
            color: white;
            font-size: 14px;
            margin-top: 10px;
        }
        /* Стили для автоподсказок */
        .autocomplete-list {
            position: absolute;
            background: white;
            max-height: 200px;
            overflow-y: auto;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            z-index: 1000;
        }
        .autocomplete-item {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #ddd;
        }
        .autocomplete-item:hover {
            background: #f0f0f0;
        }
    </style>
    <script>
        let trackList = [];

        document.addEventListener("DOMContentLoaded", function () {
            // Загружаем список треков
            fetch("tracks.php")
                .then(response => response.json())
                .then(data => {
                    document.getElementById("trackCount").innerText = data.count;
                    trackList = data.tracks;
                })
                .catch(error => {
                    console.error("Error loading track count:", error);
                    document.getElementById("trackCount").innerText = "❌ Error";
                });

            // Функция автоподсказок
            function setupAutocomplete(inputElement) {
                inputElement.addEventListener("input", function () {
                    let value = this.value.toLowerCase();
                    let listContainer = document.getElementById(this.getAttribute("data-list"));

                    if (!value) {
                        listContainer.innerHTML = "";
                        return;
                    }

                    let filteredTracks = trackList.filter(track => track.toLowerCase().includes(value)).slice(0, 5);

                    listContainer.innerHTML = "";
                    filteredTracks.forEach(track => {
                        let item = document.createElement("div");
                        item.classList.add("autocomplete-item");
                        item.innerText = track;
                        item.addEventListener("click", function () {
                            let [artist, title] = track.split(" - ");
                            document.getElementById("artist").value = artist || "";
                            document.getElementById("title").value = title || "";
                            listContainer.innerHTML = "";
                        });
                        listContainer.appendChild(item);
                    });
                });

                inputElement.addEventListener("blur", function () {
                    setTimeout(() => document.getElementById(this.getAttribute("data-list")).innerHTML = "", 200);
                });
            }

            // Подключаем автоподсказки
            setupAutocomplete(document.getElementById("artist"));
            setupAutocomplete(document.getElementById("title"));

            // 📌 Фиксим отправку формы
            document.getElementById("requestForm").addEventListener("submit", function (event) {
                event.preventDefault(); // Останавливаем перезагрузку страницы

                let formData = new FormData(this);

                fetch("request.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("✅ Запрос отправлен: " + data.success);
                        document.getElementById("requestForm").reset();
                    } else {
                        alert("❌ Ошибка: " + data.error);
                    }
                })
                .catch(error => {
                    console.error("Request error:", error);
                    alert("❌ Ошибка запроса. Проверьте соединение.");
                });
            });
        });
    </script>
</head>
<body>
    <div class="overlay"></div>
    <div class="container">
        <img src="https://radiosamui.online/img/logo.png" alt="Radio Samui Online" class="logo">
        <h2 style="color: white;">Request a Song</h2>
        <form id="requestForm">
            <div style="position: relative;">
                <input type="text" id="artist" name="artist" placeholder="Artist" required data-list="artist-list">
                <div id="artist-list" class="autocomplete-list"></div>
            </div>
            <div style="position: relative;">
                <input type="text" id="title" name="title" placeholder="Song Title" required data-list="title-list">
                <div id="title-list" class="autocomplete-list"></div>
            </div>
            <button type="submit">Request Song</button>
        </form>
        <div class="track-count">Total tracks in the database: <span id="trackCount">Loading...</span></div>
    </div>
		
		<script>
if ("serviceWorker" in navigator) {
    navigator.serviceWorker.register("sw.js")
    .then(() => console.log("Service Worker зарегистрирован!"))
    .catch(err => console.error("Ошибка регистрации Service Worker:", err));
}
</script>
</body>
</html>
