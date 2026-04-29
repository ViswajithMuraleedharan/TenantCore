<?php
// database/Migrator.php
class Migrator
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = new PDO(
            "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
            $_ENV['DB_USERNAME'],
            $_ENV['DB_PASSWORD'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $this->ensureMigrationsTable();
    }

    private function ensureMigrationsTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                migration  VARCHAR(255) NOT NULL,
                ran_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    public function run(): void
    {
        $files = glob(__DIR__ . '/migrations/*.sql');
        sort($files);

        foreach ($files as $file) {
            $name = basename($file);
            $ran  = $this->pdo->query("SELECT id FROM migrations WHERE migration = '$name'")->fetch();

            if ($ran) {
                echo "  [skip] $name\n";
                continue;
            }

            $sql = file_get_contents($file);
            $this->pdo->exec($sql);

            $stmt = $this->pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
            $stmt->execute([$name]);

            echo "  [done] $name\n";
        }
    }
}