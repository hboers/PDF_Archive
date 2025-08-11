<?php
$baseDir = realpath(__DIR__);
$files = explode("\n", shell_exec("find  . -type f -name \"*.pdf\" | sed 's|^/app||'"));
$text_array = [];
foreach ($files as $file) {
    $directory = dirname($file);
    $baseName = basename($file, '.pdf'); // Dateiname ohne .pdf
    $textfile = "$directory/$baseName.txt";
    if (file_exists($textfile))
        $text_array[$file] = file_get_contents($textfile);
    else
        $text_array[$file] = '';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>PDF Archive Web Interface</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .scrollable-list {
            max-height: 80vh;
            overflow-y: auto;
        }

        a {
            color: #007bff;
            text-decoration: none;
            background-color: transparent;
        }

        a:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        pre.output {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 1rem;
            border-radius: .5rem;
            white-space: pre-wrap;
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-2">
    <input type="text" id="searchFiles" class="form-control mb-3" placeholder="Suchen ...">
    <div class="scrollable-list p-0">
        <div class="list-group list-group-flush" id="fileList">
            <?php foreach ($files as $file): ?>
                <div class="list-group-item ">
                    <a class="float-start" target="_blank"
                       href="<?= urlencode($file) ?>"><?= htmlspecialchars($file) ?>
                    <span style="display:none"><?= htmlspecialchars($text_array[$file]) ?></span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>

    function filterItems(searchFiles) {
        const searchLower = searchFiles.toLowerCase();
        const items = document.querySelectorAll('#fileList div.list-group-item');
        items.forEach(item => {
            item.style.display = 'none';
        })
        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.display = text.includes(searchLower) ? '' : 'none'
        });
    }


    window.addEventListener('DOMContentLoaded', () => {
        const searchFiles = sessionStorage.getItem('searchFiles');
        if (searchFiles !== null) {
            document.getElementById('searchFiles').value = searchFiles;
            filterItems(searchFiles);
        }
    });

    document.getElementById('searchFiles').addEventListener('input', function () {
        const searchFiles = this.value;
        sessionStorage.setItem('searchFiles', searchFiles);
        filterItems(searchFiles)
    });

</script>

</body>
</html>