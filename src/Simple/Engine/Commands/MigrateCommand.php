<?php

namespace Simple\Engine\Commands;

use Simple\Engine\ConsoleOutput;
use Simple\Engine\Contracts\CommandInterface;
use Simple\Config;
use PDO;

class MigrateCommand implements CommandInterface
{
    public function handle(array $args): ?array
    {
        Config::load('./app/Config');
        $directory = './database';
        $imports = scandir($directory);
        $dbEngine = Config::get('database.engine', 'mysql');
        $file = $args[0] ?? null;
        $com = $args[1] ?? null;

        if ($dbEngine == 'mysql' || $dbEngine == 'mysqli') {
            $mysqlDatabaseName = Config::get('database.name', '');
            $mysqlUserName = Config::get('database.user', '');
            $mysqlPassword = Config::get('database.pass', '');
            $mysqlHostName = Config::get('database.server', 'localhost');

            if ($file == null) {
                foreach ($imports as $file) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }
                    $filePath = $directory . '/' . $file;
                    if (!is_dir($filePath)) {
                        echo "Importing => $filePath" . PHP_EOL;
                        $command = 'mysql -h' . $mysqlHostName . ' -u' . $mysqlUserName . ' --password="' . $mysqlPassword . '" ' . $mysqlDatabaseName . ' < ' . $filePath . ' 2>&1 | grep -v "Warning: Using a password"';
                        $output = [];
                        exec($command, $output, $worked);
                        switch ($worked) {
                            case 0:
                                echo 'success: file ' . $filePath . ' successfully imported ' . PHP_EOL;
                                break;
                            case 1:
                                echo 'error: There was an error during the import ' . PHP_EOL;
                                break;
                        }
                    }
                }
                return null;
            } else {
                $mysqlImportFilename = "./database/$file.sql";
                $command = 'mysql -h' . $mysqlHostName . ' -u' . $mysqlUserName . ' -p' . $mysqlPassword . ' ' . $mysqlDatabaseName . ' < ' . $mysqlImportFilename;
                $output = [];
                exec($command, $output, $worked);
                switch ($worked) {
                    case 0:
                        return ['type' => 'success', 'message' => 'Import file ' . $mysqlImportFilename . ' successfully imported to database ' . $mysqlDatabaseName];
                    case 1:
                        return ['type' => 'error', 'message' => 'There was an error during the import'];
                }
            }
        } elseif ($dbEngine == 'sqlite') {
            try {
                $db = new PDO("sqlite:" . "./database/database.db");
                $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                if ($file == '-c') {
                    $sql = $com;
                } elseif ($file == 'users') {
                    $sql = "CREATE TABLE IF NOT EXISTS users(
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        name TEXT NOT NULL,
                        email TEXT NOT NULL UNIQUE,
                        password_hash TEXT NOT NULL,
                        reset_token TEXT NULL,
                        email_verified_at TEXT NULL,
                        created_at TEXT NULL,
                        updated_at TEXT NULL)";
                } else {
                    return ['type' => 'error', 'message' => 'Unknown migrate option for sqlite'];
                }

                echo PHP_EOL;
                $command_ = explode(' ', $sql);
                if (strtoupper($command_[0]) === "SELECT") {
                    $res = $db->query($sql);
                    $i = 0;
                    $col = [];
                    $rows = [];
                    $v = [];
                    $table = new \LucidFrame\Console\ConsoleTable();
                    foreach ($res as $row) {
                        $i++;
                        foreach ($row as $key => $val) {
                            if ($i == 1) {
                                $col[] .= $key;
                            }
                            $v[] = $val;
                        }
                        $rows[$i] = $v;
                        unset($v);
                    }
                    $table->setHeaders($col);
                    foreach ($rows as $r) {
                        $table->addRow($r);
                    }
                    $table->display();
                } else {
                    $c = $db->exec($sql);
                    print("Command successfull. $c affected rows.\n");
                }
            } catch (\Exception $e) {
                echo "Unable to connect" . PHP_EOL;
                echo $e->getMessage();
                exit;
            }
        }
        return null;
    }
}
