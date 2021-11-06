<?php

namespace Ephect\Core;

use Ephect\Cache\Cache;
use Ephect\Element;
use Ephect\Registry\Registry;

abstract class AbstractApplication extends Element
{
    use IniLoaderTrait;

    const DEBUG_MODE = 'DEBUG';
    const TEST_MODE = 'TEST';
    const PROD_MODE = 'PROD';

    private static $_executionMode = self::PROD_MODE;
    private static $_verboseMode = false;
    private static $_useTransactions = true;

    protected $commands = [];
    protected $callbacks = [];
    protected $appName = 'app';
    protected $appTitle = '';
    protected $scriptName = 'app.php';
    protected $appDirectory = '';
    protected $canStop = false;
    protected $dataConfName = '';
    private $_usage = '';
    private $_appini = [];

    public function __construct()
    {
        parent::__construct();
    }

    abstract public function run(?array ...$params) : void;

    protected function execute(): void
    {}

    protected function ignite(): void
    {
        $this->loadInFile();

       
    }

    abstract public function displayConstants(): array;

    public function clearLogs(): string
    {
        $result = '';
        try {
            self::getLogger()->clearAll();

            $result = 'All logs cleared';
        } catch (\Throwable $ex) {
            self::writeException($ex);

            $result = 'Impossible to clear logs';
        }
        return $result;
    }

    public function clearRuntime(): string
    {
        $result = '';
        try {
            Cache::clearRuntime();

            $result = 'All runtime files deleted';
        } catch (\Throwable $ex) {
            self::writeException($ex);

            $result = 'Impossible to delete runtime files';
        }
        return $result;
    }

    public function getDebugLog(): string
    {
        return self::getLogger()->getDebugLog();
    }

    public function getPhpErrorLog(): string
    {
        return self::getLogger()->getPhpErrorLog();
    }

    public function loadInFile(): void
    {
        try {
            $exist = $this->loadINI(SRC_ROOT);
            if (!$exist) {
                return;
            }

            $this->appName = Registry::read('application', 'name');
            $this->appTitle = Registry::read('application', 'title');

        } catch (\Throwable $ex) {
            $this->writeException($ex);
        }
    }

    public static function write($string, ...$params): void
    {}

    public static function writeLine($string, ...$params): void
    {}

    public static function writeException(\Throwable $ex, $file = null, $line = null): void
    {}

    public function help(): void
    {
        $this->writeLine($this->getName());
        $this->writeLine('Expected commands : ');
        $usage = Registry::read('commands', 'usage'); 
        $this->writeLine($usage);
    }

    public function getName(): string
    {
        if(empty($this->appName) || $this->appName == 'app') {
            $this->appName = Registry::ini('application', 'name');
        }

        return $this->appName;
    }

    public function getTitle(): string
    {
        return $this->appTitle;
    }

    public function getDirectory(): string
    {
        return $this->appDirectory;
    }

    public function setCommand(string $long, string $short = '', string $definition = '', $callback = null): void
    {
        $this->commands[$long] = [
            'short' => $short,
            'definition' => $definition,
            'callback' => $callback,
        ];

        if ($short !== '') {
            $this->_usage .= "\t--$long, -$short : $definition" . PHP_EOL;
        } else {
            $this->_usage .= "\t--$long : $definition" . PHP_EOL;
        }
    }

    // public function commandRunner(string $cmd, callable $callback, $arg = null) {

    //     if (isset($this->commands[$cmd])) {
    //         $cmd = $this->commands[$cmd];
    //         $statement = $cmd['callback'];

    //         if ($statement !== null && $arg === null) {
    //             call_user_func($statement, $callback);
    //         } elseif ($statement !== null && $arg !== null) {
    //             call_user_func($statement, $callback, $arg);
    //         }

    //         return Registry::read('console', 'buffer');
    //     }
    // }

    public function canStop()
    {
        return $this->canStop;
    }

    public static function getExecutionMode(): string
    {
        return self::$_executionMode;
    }

    public function getOS(): string
    {
        return PHP_OS;
    }

    public static function setExecutionMode($myExecutionMode): void
    {
        if (!$myExecutionMode) {
            $myExecutionMode = (IS_WEB_APP) ? 'debug' : 'prod';
        }

        $prod = ($myExecutionMode == 'prod');
        $test = ($myExecutionMode == 'test' || $myExecutionMode == 'devel' || $myExecutionMode == 'dev');
        $debug = ($myExecutionMode == 'debug');

        if ($prod) {
            self::$_executionMode = self::PROD_MODE;
        }
        if ($test) {
            self::$_executionMode = self::TEST_MODE;
        }
        if ($debug) {
            self::$_executionMode = self::DEBUG_MODE;
        }
    }

    public static function getVerboseMode(): bool
    {
        return self::$_verboseMode;
    }

    public static function setVerboseMode($set = false)
    {
        self::$_verboseMode = $set;
    }

    public static function getTransactionUse(): bool
    {
        return self::$_useTransactions;
    }

    public static function useTransactions($set = true): void
    {
        self::$_useTransactions = $set;
    }

    public static function isProd(): bool
    {
        return self::$_executionMode == self::PROD_MODE;
    }

    public static function isTest(): bool
    {
        return self::$_executionMode == self::TEST_MODE;
    }

    public static function isDebug(): bool
    {
        return self::$_executionMode == self::DEBUG_MODE;
    }

    public static function authenticateByToken($token): string
    {

        // On prend le token en cours
        if (is_string($token)) {
            // avec ce token on récupère l'utilisateur et un nouveau token
            $token = TAuthentication::getUserCredentialsByToken($token);
        }

        return $token;
    }

    protected static function _write($string, ...$params): string
    {
        if (is_array($string)) {
            $string = print_r($string, true);
        }
        $result = $string;
        if (count($params) > 0 && is_array($params[0])) {
            $result = vsprintf($string, $params[0]);
        }
        return $result;
    }
}
