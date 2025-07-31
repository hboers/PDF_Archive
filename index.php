<?php
$db = new PDO('sqlite:meta.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$db->exec("CREATE TABLE IF NOT EXISTS file_meta (
    path TEXT PRIMARY KEY,
    tags TEXT,
    notes TEXT
)");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare("REPLACE INTO file_meta (path, tags, notes) VALUES (?, ?, ?)");
    $stmt->execute([$_POST['path'], $_POST['tags'], $_POST['notes']]);
    header('Location: ?edit=' . urlencode($_POST['path']));
    exit;
}

$baseDir = realpath(__DIR__);
$dir = realpath($_GET['dir'] ?? $baseDir);
if (strpos($dir, $baseDir) !== 0) $dir = $baseDir;

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

function relpath($full, $base)
{
    return ltrim(str_replace($base, '', $full), DIRECTORY_SEPARATOR);
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
            max-height: 30vh;
            overflow-y: auto;
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
    <div class="card mb-4">
        <div class="card-header">
            Dateiliste
        </div>
        <div class="card-body">
            <input type="text" id="searchInput" class="form-control mb-3" placeholder="Dateien durchsuchen...">
            <div class="scrollable-list p-0">
                <div class="list-group list-group-flush" id="fileList">
                    <?php foreach ($files as $file):
                        if ($file->isDir()) continue;
                        $rel = relpath($file->getPathname(), $baseDir);
                        $stmt = $db->prepare("SELECT * FROM file_meta WHERE path = ?");
                        $stmt->execute([$rel]);
                        ?>
                        <a href="?edit=<?= urlencode($rel) ?>" class="list-group-item list-group-item-action">
                            <?= htmlspecialchars($rel) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <?php
    if (isset($_GET['edit'])):
        $rel = $_GET['edit'];
        $stmt = $db->prepare("SELECT * FROM file_meta WHERE path = ?");
        $stmt->execute([$rel]);
        $meta = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['path' => $rel, 'tags' => '', 'notes' => ''];

        // Securely call the show script with the file path
        $safePath = escapeshellarg($rel);
        $showScript = __DIR__ . '/show';
        if (is_executable($showScript)) {
            $output = shell_exec("$showScript $safePath 2>&1");
        } else {
            $output = "Fehler: 'show'-Skript nicht ausführbar oder nicht vorhanden.";
        }
        ?>
        <div class="card mb-4">
            <div class="card-header">
                Metadaten bearbeiten
            </div>
            <div class="card-body">
                <form method="post">
                <div class="row">
                    <div class="col-sm-12 col-lg-6"> <div class="mb-3">
                            <div class="form-group ">
                                <label class="form-label">Dateiname</label>
                                <input class="form-control" readonly type="text" name="path"
                                       value="<?= htmlspecialchars($meta['path']) ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-group">
                                <label class="form-label">Tags</label>
                                <input class="form-control" name="tags" value="<?= htmlspecialchars($meta['tags']) ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-group">
                                <label class="form-label">Notiz</label>
                                <textarea class="form-control" name="notes"
                                          rows="5"><?= htmlspecialchars($meta['notes']) ?></textarea>
                            </div>
                        </div></div>
                    <div class="col-sm-12 col-lg-6"><div class="mb-3">
                            <div class="form-group">
                                <label class="form-label">Inhalt</label>
                                <textarea readonly rows="12" class="form-control"><?= htmlspecialchars($output) ?></textarea>
                            </div>
                        </div></div>
                </div>




                    <button type="submit" class="btn btn-primary">Speichern</button>
                    <a href="?dir=<?= urlencode(dirname($rel)) ?>" class="btn btn-secondary">Zurück</a>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    document.getElementById('searchInput').addEventListener('input', function () {
        const search = this.value.toLowerCase();
        const items = document.querySelectorAll('#fileList a');
        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.display = text.includes(search) ? '' : 'none';
        });
    });
</script>

</body>
</html>