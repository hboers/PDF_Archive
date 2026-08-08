<?php
$baseDir = realpath(__DIR__);
$years = [];

foreach (new DirectoryIterator($baseDir) as $entry) {
    if ($entry->isDir() && !$entry->isDot() && preg_match('/^\d{4}$/', $entry->getFilename())) {
        $years[] = $entry->getFilename();
    }
}

rsort($years, SORT_NUMERIC);

$currentYear = date('Y');
$selectedYear = isset($_GET['year']) && in_array($_GET['year'], $years, true)
    ? $_GET['year']
    : $currentYear;

if (!in_array($selectedYear, $years, true)) {
    $selectedYear = $years[0] ?? null;
}

$files = [];

if ($selectedYear !== null) {
    $yearDir = $baseDir . DIRECTORY_SEPARATOR . $selectedYear;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($yearDir, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $entry) {
        if ($entry->isFile() && strtolower($entry->getExtension()) === 'pdf') {
            $relativePath = substr($entry->getPathname(), strlen($baseDir) + 1);
            $files[] = './' . str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
        }
    }
}

sort($files, SORT_NATURAL | SORT_FLAG_CASE);

$pdfSearch = isset($_GET['pdf_search']) && is_string($_GET['pdf_search'])
    ? trim($_GET['pdf_search'])
    : '';

if ($pdfSearch !== '') {
    set_time_limit(0);

    $files = array_values(array_filter($files, function ($file) use ($baseDir, $pdfSearch) {
        $absolutePath = $baseDir . DIRECTORY_SEPARATOR . ltrim($file, './');
        $command = 'pdftotext ' . escapeshellarg($absolutePath)
            . ' - 2>/dev/null | grep -Fq -- ' . escapeshellarg($pdfSearch);
        $output = [];
        $exitCode = 1;
        exec($command, $output, $exitCode);

        return $exitCode === 0;
    }));
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
            height: calc(100vh - 85px);
            overflow-y: auto;
        }

        .year-select {
            width: 130px;
        }

        .pdf-viewer {
            width: 100%;
            flex: 1 1 auto;
            min-height: 0;
            border: 1px solid #dee2e6;
            border-radius: .375rem;
            background-color: #fff;
        }

        .pdf-viewer-column {
            height: calc(100vh - 85px);
        }

        .pdf-viewer-filename {
            overflow-wrap: anywhere;
        }

        #fileList .file-list-item {
            padding: 0;
        }

        #fileList .file-list-item a {
            color: inherit;
            text-decoration: none;
            background-color: transparent;
            padding: .5rem 1rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: background-color .15s ease-in-out;
        }

        #fileList .file-list-item a:hover {
            color: inherit;
            background-color: #e9ecef;
            text-decoration: none;
        }

        #fileList .file-list-item.active a,
        #fileList .file-list-item.active a:hover {
            color: #fff;
            background-color: var(--bs-list-group-active-bg);
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
<div class="container-fluid py-2">
    <div class="row g-2 mb-2">
        <div class="col-auto">
            <form method="get">
                <?php if ($pdfSearch !== ''): ?>
                    <input type="hidden" name="pdf_search" value="<?= htmlspecialchars($pdfSearch) ?>">
                <?php endif; ?>
                <select id="year" name="year" class="form-select year-select" aria-label="Jahr"
                        onchange="this.form.submit()">
                    <?php foreach ($years as $year): ?>
                        <option value="<?= htmlspecialchars($year) ?>"
                            <?= $year === $selectedYear ? 'selected' : '' ?>>
                            <?= htmlspecialchars($year) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <div class="col">
            <input type="text" id="searchFiles" class="form-control" placeholder="Dateiname suchen ...">
        </div>
        <div class="col">
            <form method="get" class="input-group">
                <input type="hidden" name="year" value="<?= htmlspecialchars($selectedYear ?? '') ?>">
                <input type="search" id="pdfSearch" name="pdf_search" class="form-control"
                       value="<?= htmlspecialchars($pdfSearch) ?>" placeholder="PDF-Inhalt durchsuchen ...">
                <button type="submit" class="btn btn-primary">Suchen</button>
                <?php if ($pdfSearch !== ''): ?>
                    <a class="btn btn-outline-secondary" href="?year=<?= urlencode($selectedYear ?? '') ?>"
                       title="Volltextsuche zurücksetzen">×</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="row g-2">
        <div class="col-4">
            <div class="scrollable-list p-0">
                <div class="list-group list-group-flush" id="fileList">
                    <?php foreach ($files as $file): ?>
                        <div class="list-group-item file-list-item"
                             data-filename="<?= htmlspecialchars(basename($file)) ?>"
                             data-filepath="<?= htmlspecialchars(substr($file, 2)) ?>">
                            <a class="d-block"
                               href="<?= implode('/', array_map('rawurlencode', explode('/', substr($file, 2)))) ?>"
                               title="<?= htmlspecialchars(basename($file)) ?>"
                               target="pdfViewer">
                                <?= htmlspecialchars(basename($file)) ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($files === []): ?>
                        <div class="list-group-item text-muted">Keine PDF-Dateien gefunden.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-8 d-flex flex-column pdf-viewer-column">
            <div class="pdf-viewer-filename bg-white border rounded px-2 py-1 mb-2"
                 id="pdfViewerFilename">Kein PDF ausgewählt</div>
            <iframe class="pdf-viewer" id="pdfViewer" name="pdfViewer" title="PDF-Vorschau"></iframe>
        </div>
    </div>
</div>

<script>

    function filterItems(searchFiles) {
        const items = document.querySelectorAll('#fileList div.list-group-item[data-filename]');
        items.forEach(item => {
            const filename = item.dataset.filename;
            item.style.display = filename.includes(searchFiles) ? '' : 'none'
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

    document.querySelectorAll('#fileList a').forEach(link => {
        link.addEventListener('click', function () {
            document.querySelectorAll('#fileList .list-group-item').forEach(item => {
                item.classList.remove('active');
            });
            this.closest('.list-group-item').classList.add('active');
            document.getElementById('pdfViewerFilename').textContent =
                this.closest('.list-group-item').dataset.filepath;
        });
    });

</script>

</body>
</html>
