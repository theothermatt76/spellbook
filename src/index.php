<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spells Database</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body { padding-top: 20px; }
        .cli-code {
            font-family: monospace;
            background-color: #f8f9fa;
            padding: 8px 12px;
            border-radius: 4px;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .table td {
            vertical-align: middle;
        }
        .success-alert {
            animation: fadeIn 0.5s;
        }
    </style>
</head>
<body>
    <div class="container">

        <?php
        // ==================== CONFIGURATION ====================
        $dbHost = 'localhost';
        $dbName = 'spell';
        $dbUser = 'appuser';
        $dbPass = 'appPass456!';

        try {
            $pdo = new PDO(
                "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
                $dbUser,
                $dbPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            die('<div class="alert alert-danger">Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</div>');
        }

        // ==================== HANDLE ADD NEW ENTRY (POST) ====================
        $message = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_spell'])) {
            $tool        = trim($_POST['tool'] ?? '');
            $tag         = trim($_POST['tag'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $cli         = trim($_POST['cli'] ?? '');

            if ($tool !== '' && $tag !== '') {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO spells (tool, tag, description, cli)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$tool, $tag, $description, $cli]);
                    // Redirect to prevent form resubmission on refresh
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?added=1');
                    exit;
                } catch (PDOException $e) {
                    $message = '<div class="alert alert-danger">Error adding spell: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
            } else {
                $message = '<div class="alert alert-warning">Tool and Tag are required.</div>';
            }
        }

        // Success message after redirect
        if (isset($_GET['added'])) {
            $message = '<div class="alert alert-success success-alert">✅ Spell added successfully!</div>';
        }

        // ==================== HANDLE SEARCH (GET) ====================
        $results = [];
        $isSearch = false;
        $searchTermDisplay = '';

        if (isset($_GET['search']) && isset($_GET['term']) && trim($_GET['term']) !== '') {
            $isSearch = true;
            $searchType = $_GET['type'] ?? 'both';
            $term = trim($_GET['term']);
            $searchTermDisplay = htmlspecialchars($term);
            $like = "%$term%";

            try {
                if ($searchType === 'tool') {
                    $stmt = $pdo->prepare("SELECT * FROM spells WHERE tool LIKE ? ORDER BY id DESC");
                    $stmt->execute([$like]);
                } elseif ($searchType === 'tag') {
                    $stmt = $pdo->prepare("SELECT * FROM spells WHERE tag LIKE ? ORDER BY id DESC");
                    $stmt->execute([$like]);
                } else {
                    // both (default)
                    $stmt = $pdo->prepare("SELECT * FROM spells WHERE tool LIKE ? OR tag LIKE ? ORDER BY id DESC");
                    $stmt->execute([$like, $like]);
                }
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $message .= '<div class="alert alert-danger">Search error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        } else {
            // Show ALL spells by default
            try {
                $stmt = $pdo->query("SELECT * FROM spells ORDER BY id DESC");
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $message .= '<div class="alert alert-danger">Error loading spells: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
        ?>

        <h1 class="mb-4 text-center">🪄 Spells Database</h1>

        <!-- Success / Error Message -->
        <?= $message ?>

        <div class="row">
            <!-- ADD NEW SPELL FORM -->
            <div class="col-lg-5 mb-5">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">➕ Add New Spell</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="addForm">
                            <div class="mb-3">
                                <label for="tool" class="form-label">Tool <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="tool" name="tool" required maxlength="100" placeholder="e.g. docker">
                            </div>
                            <div class="mb-3">
                                <label for="tag" class="form-label">Tag <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="tag" name="tag" required maxlength="100" placeholder="e.g. container">
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" placeholder="What does this spell do?"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="cli" class="form-label">CLI Command</label>
                                <textarea class="form-control font-monospace" id="cli" name="cli" rows="3" placeholder="docker run -it --rm ubuntu bash"></textarea>
                            </div>
                            <button type="submit" name="add_spell" class="btn btn-success w-100">Save Spell</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- SEARCH & RESULTS -->
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">🔎 Search Spells</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-auto">
                                <select name="type" class="form-select">
                                    <option value="both" <?= !isset($_GET['type']) || $_GET['type']==='both' ? 'selected' : '' ?>>Tool OR Tag</option>
                                    <option value="tool" <?= isset($_GET['type']) && $_GET['type']==='tool' ? 'selected' : '' ?>>Tool only</option>
                                    <option value="tag" <?= isset($_GET['type']) && $_GET['type']==='tag' ? 'selected' : '' ?>>Tag only</option>
                                </select>
                            </div>
                            <div class="col">
                                <input type="text" name="term" class="form-control" placeholder="Search term..." value="<?= htmlspecialchars($_GET['term'] ?? '') ?>">
                            </div>
                            <div class="col-auto">
                                <button type="submit" name="search" class="btn btn-primary">Search</button>
                            </div>
                            <div class="col-auto">
                                <a href="index.php" class="btn btn-outline-secondary">View All</a>
                            </div>
                        </form>

                        <hr>

                        <?php if ($isSearch): ?>
                            <h6 class="mb-3">
                                Results for <strong>"<?= $searchTermDisplay ?>"</strong> 
                                <span class="badge bg-info"><?= count($results) ?> found</span>
                            </h6>
                        <?php else: ?>
                            <h6 class="mb-3">
                                All Spells 
                                <span class="badge bg-secondary"><?= count($results) ?> total</span>
                            </h6>
                        <?php endif; ?>

                        <?php if (empty($results)): ?>
                            <div class="alert alert-info py-4 text-center">
                                No spells found.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 60px;">ID</th>
                                            <th>Tool</th>
                                            <th>Tag</th>
                                            <th>Description</th>
                                            <th>CLI Command</th>
                                            <th style="width: 110px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($results as $row): ?>
                                            <tr>
                                                <td><strong><?= $row['id'] ?></strong></td>
                                                <td><?= htmlspecialchars($row['tool']) ?></td>
                                                <td><span class="badge bg-secondary"><?= htmlspecialchars($row['tag']) ?></span></td>
                                                <td><?= nl2br(htmlspecialchars($row['description'])) ?></td>
                                                <td>
                                                    <div class="cli-code"><?= htmlspecialchars($row['cli']) ?></div>
                                                </td>
                                                <td>
                                                    <button onclick="copyCLI(<?= $row['id'] ?>)" class="btn btn-sm btn-outline-primary w-100">
                                                        📋 Copy
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <footer class="text-center text-muted mt-5 small">
       Sellbook: a PHP/Mariadb cluster for querying...anything.
	</footer>
    </div>

    <script>
        // Copy CLI to clipboard
        function copyCLI(id) {
            const row = document.querySelector(`tr td:first-child strong:contains('${id}')`)?.closest('tr');
            if (!row) return;
            
            const cliText = row.querySelector('.cli-code').textContent.trim();
            
            navigator.clipboard.writeText(cliText).then(() => {
                const btn = row.querySelector('button');
                const originalText = btn.innerHTML;
                btn.innerHTML = '✅ Copied!';
                btn.classList.add('btn-success');
                btn.classList.remove('btn-outline-primary');
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-outline-primary');
                }, 2000);
            }).catch(() => {
                alert('Failed to copy (clipboard not supported)');
            });
        }

        // make "contains" work for the ID lookup in the copy function (small helper)
        Element.prototype.contains = function(text) {
            return this.textContent.includes(text);
        };

        // Bootstrap JS (for any future components)
        // No need to load full bundle unless using modals/tooltips
    </script>
</body>
</html>
