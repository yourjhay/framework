<?php
namespace Simple\Engine;

use Simple\Engine\ConsoleOutput as co; 
use mysqli;
use Simple\Session;

class Console 
{
    private $argv;
    private $status;
    private $controllerPath = './app/Controllers/';
    private $modelPath  = 'app/Models/';
    private $output;
    public function __construct($argc, $argv)
    {
        $this->status = null;
        $this->argv = null;
        if($argc) {
            $this->argv = $argv;
        }
        $this->output = new co;
    }

    public function consoleRun()
    {
        switch($this->argv[1]) {
            case "make:controller":
            $this->createController($this->argv[2] ?? null, $this->argv[3] ?? null);
            break;
            case "make:model":
            $this->createModel($this->argv[2] ?? null);
            break;
            case "migrate":
            $this->migrate($this->argv[2] ?? null);
            break;
            case "make:auth":
            $this->makeAuth();
            break;
            case "user:seed":
            $this->seed();
            break;
            case "session:destroy":
            Session::destroy();
            $this->status = 'success: All sessions is destroyed.'.PHP_EOL;
            break;
            case "serve":
            $this->serve($this->argv[2] ?? null, $this->argv[3] ?? null);
            break;
            case "key:generate":
            $this->keyGenerate();
            break;
            default:
            $this->status = 'error: ===== Command not found. ====='.PHP_EOL;
        }
    }

    public function print_status()
    {
        $status = explode(':',$this->status);
        if ($status[0] == 'error') {
        $status = $this->output->print_o($status[1], "white", "red");
            echo $status;
        } else if($status[0] == 'success') {
            $status = $this->output->print_o($status[1], "black", "green");
            echo $status;
        }
    }

    private function createController($name, $option)
    {

        $model = $name;
        if($name) {
            if(!preg_match("/controller$/i", $name))
            {
                $name = $name.'Controller';
            }
            $name = self::convertToStudlyCaps($name);       
if($option== trim("-r") || $option== trim("-rm")){
    $contentController = '<?php
namespace App\Controllers;
    
Use Simple\Request;
    
class '.$name.' extends Controller 
{
    
    /**
     * the index action can be use to show all the records
     *
     * @return void
     */
    public function index() 
    {

    }

    /**
     * Shows the from for creating '.$name.'
     * 
     * @return void
     */
    public function create()
    {
        
    }

    /**
     * Store the data from '.$name.' POST form
     *
     * @param Request $request
     * @return void
     */
    public function store(Request $request)
    {
        
    }

    /**
     * Show the edit form for '.$name.'
     *
     * @param Request $request
     * @return void
     */
    public function edit(Request $request)
    {
        $id = $request->route(\'id\');
    
    }

    /**
     * Update the existing record
     *
     * @param Request $request
     * @return void
     */
    public function update(Request $request)
    {
        $id = $request->route(\'id\');
    
    }

    /**
    * Delete the record 
    *
    * @param Request $request
    * @return void
    */
    public function destroy(Request $request)
    {
        $id = $request->route(\'id\');
    
    }

}';
    if($option == trim("-rm")){
        self::createModel($model);
    }
} else if($option==null) {
    $contentController = '<?php
namespace App\Controllers;
    
Use Simple\Request;
    
class '.$name.' extends Controller 
{
    
    public function index() 
    {
    
    }
    
}';

} else if($option == trim("-m")) {
    $contentController = '<?php
namespace App\Controllers;
    
Use Simple\Request;
    
class '.$name.' extends Controller 
{
    
    public function index() 
    {
    
    }
    
}';
    self::createModel($model);
}
            if(file_exists("$this->controllerPath$name.php")) {
                $this->status = 'error: '.$name.' Controller is already exist!'.PHP_EOL;
            } else {
                $file = fopen("$this->controllerPath$name.php", 'w');
                if (fwrite($file, $contentController)) {
                    $this->status = 'success: Controller '.$name.' created successfuly '.PHP_EOL;
                } else {
                    $this->status = 'error: failed to create controller '.PHP_EOL;
                }
                fclose($file);
            }
        } else {
            $this->status = 'error: Controller name must be defined '.PHP_EOL;
        }
    }

    private function createModel($model)
    {
        if($model) {
            $model = self::convertToStudlyCaps($model);  
$content = '<?php
namespace App\Models;

Use Simple\Model;
Use function Simple\QueryBuilder\field;

class '.$model.' extends Model
{
    /**
     * $table - table name using by this model
     *
     * @var string
     */
    protected $table = \''.\strtolower($model) .'s\';

    /**
     * Fillables - the columns in you $table 
     *
     * @var array
     */
    protected $fillable = [];

    /**
     *  This is generated '.$model.' model.
     *  It is recommended that you put all queries here. 
     *  Create Something great!
     */
}
';
            if(file_exists("$this->modelPath$model.php")) {
                $this->status = 'error: '.$model.' Model is already exist!'.PHP_EOL;
            } else {
                $file = fopen("$this->modelPath$model.php", 'w');
                if(fwrite($file,$content)) {
                    $this->status = 'success: Model '.$model.' created successfuly '.PHP_EOL;
                } else {
                    $this->status = 'error: failed to create model '.PHP_EOL;
                }
                fclose($file);
            }
    
        } else {
            $this->status = 'error: Model name must be defined '.PHP_EOL;
        }
    }

    /**
     * convert string into Studly Case format 
     * @var string
     * @return string
     */
    private static function convertToStudlyCaps($string) 
    {
        return str_replace(' ','',ucwords(str_replace('-',' ', $string)));
    }

    private function migrate($file)
    {
        if($file == null)
        {
            $this->status = 'error: Please specify the filename '.PHP_EOL;
            return false;
        }
    require './app/Config/global.php';
    $mysqlDatabaseName = DBNAME;
    $mysqlUserName =DBUSER;
    $mysqlPassword =DBPASS;
    $mysqlHostName =DBSERVER;
    $mysqlImportFilename ="./database/$file.sql";

    $command='mysql -h' .$mysqlHostName .' -u' .$mysqlUserName .' -p' .$mysqlPassword .' ' .$mysqlDatabaseName .' < ' .$mysqlImportFilename;

    //var_dump( file_exists('postcode_withLatlang.sql') );

    $output = array();

    exec($command, $output, $worked);

    // test whether they are imported successfully or not
    switch ($worked) {
        case 0:
            $this->status = 'success: Import file ' .$mysqlImportFilename .' successfully imported to database ' .$mysqlDatabaseName.PHP_EOL;
            break;
        case 1:
            $this->status = 'error: There was an error during the import '.PHP_EOL;
            break;
        }
    }

    private function makeAuth()
    {
        $success = true;
        foreach (glob('./vendor/simplyphp/framework/src/AuthScaffolding/controller/*.php') as $filename)
        {
            $dest = "app/Controllers/Auth/".basename($filename);
            if (!file_exists('app/Controllers/Auth')) {
                mkdir('app/Controllers/Auth', 0777, true);
            }
            $file = fopen($dest, "w");
            copy($filename, $dest);
            fclose($file);
        }

        foreach (glob('./vendor/simplyphp/framework/src/AuthScaffolding/helper/*.php') as $filename)
        {
            $dest = "app/Helper/Auth/".basename($filename);
            if (!file_exists('app/Helper/Auth')) {
                mkdir('app/Helper/Auth', 0777, true);
            }
            $file = fopen($dest, "w");
            copy($filename, $dest);
            fclose($file);
        }
        foreach (glob('./vendor/simplyphp/framework/src/AuthScaffolding/model/*.php') as $filename)
        {
            $dest = "app/Models/".basename($filename);
            $file = fopen($dest, "w");
            copy($filename, $dest);
            fclose($file);
        } 
        foreach (glob('./vendor/simplyphp/framework/src/AuthScaffolding/Views/Auth/*.html') as $filename)
        {
            $dest = "app/Views/auth/".basename($filename);
            if (!file_exists('app/Views/auth')) {
                mkdir('app/Views/auth', 0777, true);
            }
            $file = fopen($dest, "w");
            copy($filename, $dest);
            fclose($file);
        }
        foreach (glob('./vendor/simplyphp/framework/src/AuthScaffolding/Views/layouts/*.html') as $filename)
        {
            $dest = "app/Views/layouts/".basename($filename);
            $file = fopen($dest, "w");
            copy($filename, $dest);
            fclose($file);
        }
        
        $routeFile = './vendor/simplyphp/framework/src/AuthScaffolding/routes.simply';
        $file = file_get_contents($routeFile, FILE_USE_INCLUDE_PATH);
        $mainRoute = "./app/Routes.php";
        file_put_contents($mainRoute, PHP_EOL.$file, FILE_APPEND | LOCK_EX);

        if($success!=false){
            $this->status = 'success: Auth scaffolding created successfully  '.PHP_EOL;
        }
    }

    private function seed() 
    {
    require './app/Config/global.php';
    $dbname = DBNAME;
    $dbuser = DBUSER;
    $dbpass = DBPASS;
    $dbserver = DBSERVER;
        start:
        echo "seeding...".PHP_EOL;
        echo $this->output->print_o(" Enter name: ","white","cyan");
        $handle = fopen ("php://stdin","r");
        $line = fgets($handle);
        $name = trim($line);
        fclose($handle);
        echo $this->output->print_o(" Enter Email: ", "white", "magenta");
        $handle = fopen ("php://stdin","r");
        $line = fgets($handle);
        $email = trim($line);
        fclose($handle);
        echo $this->output->print_o(" Enter password: ", "black","light_gray");
        $handle = fopen ("php://stdin","r");
        $line = fgets($handle);
        $password = trim($line);
        fclose($handle);
        $db = new mysqli ($dbserver,$dbuser,$dbpass,$dbname);
        $stmt = $db->prepare("INSERT INTO users(name,email,password_hash) VALUES(?,?,?)") or die($db->error);
        $password = password_hash($password, PASSWORD_BCRYPT);
        $stmt->bind_param("sss",$name,$email,$password);
        if($stmt->execute()){
            echo $this->output->print_o(PHP_EOL." Seeding successfull " , "black", "green");
        } else {
            echo $this->output->print_o(" Seeding failed: $stmt->error", "white", "red");
        }
        echo PHP_EOL."Do you want to seed another entry? (yes|no): ";
        $handle = fopen ("php://stdin","r");
        $line = fgets($handle);
        $ans = trim($line);
        fclose($handle);
        if($ans == 'yes') {
            goto start;
        } else {
            return false;
        }
    }

    /**
     * @param $host - address to be serve
     * @param $port - port
     */
    public function serve($host, $port)
    {
            $host = $host == null ? 'localhost':$host;
            $port = $port == null ? '8000':explode('=',$port);
            $port = is_array($port) ? $port[1] : $port;  
        $command = "php -S $host:$port -t public/";
        echo $this->output->print_o("Simply Development Server started at: http://$host:$port".PHP_EOL, 'green', 'white');
        echo $this->output->print_o("Press CTRL+C to cancel".PHP_EOL, 'green', 'black');
        exec($command,$worked,$output);
    }

    /**
     * Generate Application Key
     */
    public function keyGenerate()
    {
            $key = $key = \Simple\Security\Encryption::generateKey();
            $id = "define('APP_KEY'";
            $new_line = "define('APP_KEY', '$key');"; 
            $dir = './app/Config/global.php';
            $contents = file_get_contents($dir);
            $new_contents= "";
            if( strpos($contents, $id) !== false) { 
                $contents_array = preg_split("/\\r\\n|\\r|\\n/", $contents);
                foreach ($contents_array as &$record) { 
                    if (strpos($record, $id) !== false) { 
                        $new_contents .= $new_line.PHP_EOL; 
                    }else{
                        $new_contents .= $record . "\r";
                    }
                }
                file_put_contents($dir, $new_contents); 
                echo "Application Key Generated Successfully!".PHP_EOL;
            }
            else{
                echo "Failed to generate application key".PHP_EOL;
            }
    }
}