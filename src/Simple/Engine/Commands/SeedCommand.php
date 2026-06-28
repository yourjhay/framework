<?php

namespace Simple\Engine\Commands;

use Simple\Engine\ConsoleOutput;
use Simple\Engine\Contracts\CommandInterface;
use Simple\Config;
use mysqli;
use PDO;

class SeedCommand implements CommandInterface
{
    private ConsoleOutput $output;

    public function __construct()
    {
        $this->output = new ConsoleOutput;
    }

    public function handle(array $args): ?array
    {
        Config::load('./app/Config');

        $dbname = Config::get('database.name', '');
        $dbuser = Config::get('database.user', '');
        $dbpass = Config::get('database.pass', '');
        $dbserver = Config::get('database.server', 'localhost');

        start:
        echo "seeding..." . PHP_EOL;
        echo $this->output->print_o(" Enter name: ", "white", "cyan");
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        $name = trim($line);
        fclose($handle);

        echo $this->output->print_o(" Enter Email: ", "white", "magenta");
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        $email = trim($line);
        fclose($handle);

        echo $this->output->print_o(" Enter password: ", "black", "light_gray");
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        $password = trim($line);
        fclose($handle);
        $password = password_hash($password, PASSWORD_BCRYPT);

        $dbEngine = Config::get('database.engine', 'mysql');
        if ($dbEngine == 'mysqli' || $dbEngine == 'mysql') {
            $db = new mysqli($dbserver, $dbuser, $dbpass, $dbname);
            $stmt = $db->prepare("INSERT INTO users(name,email,password_hash) VALUES(?,?,?)") or die($db->error);
            $stmt->bind_param("sss", $name, $email, $password);
            if ($stmt->execute()) {
                echo $this->output->print_o(PHP_EOL . " Seeding successfull ", "black", "green");
            } else {
                echo $this->output->print_o(" Seeding failed: $stmt->error", "white", "red");
            }
        } elseif ($dbEngine == 'sqlite') {
            try {
                $table = 'users';
                $db = new PDO("sqlite:" . "./database/database.db");
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $sql = "INSERT INTO users(name, email, password_hash) VALUES (?,?,?)";
                $stmt = $db->prepare($sql);
                $data = array($name, $email, $password);
                echo $sql;
                echo PHP_EOL;
                $stmt->execute($data);
                echo $this->output->print_o(PHP_EOL . " Seeding successfull ", "black", "green");
                unset($stmt);
                $db = null;
            } catch (\Exception $e) {
                echo "Unable to connect" . PHP_EOL;
                echo $e->getMessage();
                exit;
            }
        }

        echo PHP_EOL . "Do you want to seed another entry? (yes|no): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        $ans = trim($line);
        fclose($handle);
        if ($ans == 'yes') {
            goto start;
        }
        return null;
    }
}
