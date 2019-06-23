<?php
/*----------------------------------------------------------------
|
| The Simple PHP Framework
| @reyjhonbaquirin
| *** ERROR Handling Class ***
------------------------------------------------------------------*/
namespace Simple;

Use View;

class Error
{
    /**
     * Error handler: Convert any errors to exeptions by throwing an ErrorException
     * @param int $level - Error Level
     * @param string $message - Error Message
     * @param string $file - File where's the error raised
     * @param int $line - The line number of error in the $file
     * 
     * @return void
     */
    public static function errorHandler($level, $message, $file, $line) 
    {
        if(error_reporting() !== 0) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
    }

    public static function exceptionHandler($exception) 
    {
        $code = $exception->getCode();
        if($code != 404) {
            $code = 500;
        }
        http_response_code($code);
        $errorTitle = array(
            'Please fixed me up :(',
            'I think there\'s a problem dear :/',
            'My system is rusty -_-',
            'Did I tell you this already?',
            'My system is broken :{',
            'Oops! You need to fixed this... :]',
            'My Author told me that. if you see this dont give up!',
            'A fatal error is here...',
            'I hate errors! :[',
            'Errors are like rain, so many...',
            'Did you miss me?',
            'I\'m here if you have problem.',
            'Sometimes a semi-colon is enough.',
            'I don\'t understand your command.',
            'Coding is fun!.... without errors.',
            'Don\'t talk to me.'
        );
        $random_title = array_rand($errorTitle);

        if(SHOW_ERRORS == true) {
            die( '
            <style>
                pre{
                    background: black;
                    color: #80cdd6;
                    padding: 20px;
                    overflow-y: auto;
                    max-height:300px;
                }
                .error-container{
                    background-color:#661328;
                    color:white;
                    padding:20px;
                    font-family: Calibri;
                }
                .ex-title{
                    padding:10px;
                    background-color:#afa7a9;
                    font-size: 20px;
                }
                .exp, .exp a {
                    color: #4beaed;
                    font-weight: bold;
                    text-decoration: none;
                }
                .message {
                    font-weight: bold;
                }
            </style>
            <div class="error-container">
            <h1>'.$errorTitle[$random_title].'</h1>
            <div class="ex-title">
            <p>Uncaught exception: "<span class="exp"><a target="_blank" title="click here to search" href="https://www.google.com/search?q=Uncaught exception: '.get_class($exception).'">'.get_class($exception).'</a></span>"</p>
            <p class="message">Message: <br>"'.$exception->getMessage().'."</p>
            </div>
            <p><h3>Stack trace: </h3><pre>'.$exception->getTraceAsString().'</pre></p>
            <p><h3>Thrown in:</h3> "'.$exception->getFile().'" on line: '.$exception->getLine().'</p>
            <br>
            <p>© The Simple PHP Framework </p>
            <small>Creator: @reyjhonbaquirin - University of Caloocan City</small>
            </div>
            ');
        } else {
            if (!file_exists('../Simply/Logs')) {
                mkdir('../Simply/Logs/', 0777, true);
            }
            $log = '../Simply/Logs/' . date('Y-m-d') . '.txt';
            ini_set('error_log', $log);
            $m = 'Uncaught exception: [' .get_class($exception).']';
            $m .= ' with message ['.$exception->getMessage().']'.PHP_EOL;
            $m .= 'Stack trace: ['.$exception->getTraceAsString().']'.PHP_EOL;
            $m .= 'Thrown in ['.$exception->getFile().'] on line:'. $exception->getLine();
            error_log($m);
            if($code == 404) {
                View::render('error.404',[
                    'name' => APP_NAME
                ]);
            } else {
                View::render('error.500',[
                    'name' => APP_NAME
                ]);
            }
        }

    }

}

