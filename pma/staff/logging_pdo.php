<?php

class LoggingPDOStatement
{
    private $stmt;
    private $pdo;
    private $query;

    public function __construct(PDOStatement $stmt, $query, LoggingPDO $pdo)
    {
        $this->stmt  = $stmt;
        $this->query = $query;
        $this->pdo   = $pdo;
    }

    public function execute($params = null)
    {
        $result = $this->stmt->execute($params);
        if ($result) {
            $this->pdo->writeLog($this->query);
        }
        return $result;
    }

    public function fetch(...$args)       { return $this->stmt->fetch(...$args); }
    public function fetchAll(...$args)    { return $this->stmt->fetchAll(...$args); }
    public function fetchColumn(...$args) { return $this->stmt->fetchColumn(...$args); }
    public function rowCount()            { return $this->stmt->rowCount(); }
    public function closeCursor()         { return $this->stmt->closeCursor(); }
    public function errorInfo()           { return $this->stmt->errorInfo(); }

    public function bindParam($parameter, &$variable, $data_type = PDO::PARAM_STR, $length = null, $driver_options = null)
    {
        return $this->stmt->bindParam($parameter, $variable, $data_type, $length, $driver_options);
    }

    public function bindValue($parameter, $value, $data_type = PDO::PARAM_STR)
    {
        return $this->stmt->bindValue($parameter, $value, $data_type);
    }

    // Catch-all for any other PDOStatement methods
    public function __call($name, $args)
    {
        return call_user_func_array([$this->stmt, $name], $args);
    }
}

class LoggingPDO extends PDO
{
    // Default: commands that change data. Override with setLoggedCommands() before connecting.
    private static $logged_commands = ['INSERT', 'UPDATE', 'DELETE', 'ALTER', 'TRUNCATE', 'CEATE', 'DROP'];

    // All supported patterns — extended set covers SELECT/CREATE/DROP if you add them.
    private static $table_patterns = [
        'INSERT'   => '/INSERT\s+(?:INTO\s+)?`?(\w+)`?/i',
        'UPDATE'   => '/UPDATE\s+`?(\w+)`?/i',
        'DELETE'   => '/DELETE\s+FROM\s+`?(\w+)`?/i',
        'ALTER'    => '/ALTER\s+TABLE\s+`?(\w+)`?/i',
        'TRUNCATE' => '/TRUNCATE\s+(?:TABLE\s+)?`?(\w+)`?/i',
        'SELECT'   => '/FROM\s+`?(\w+)`?/i',
        'CREATE'   => '/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?/i',
        'DROP'     => '/DROP\s+TABLE\s+(?:IF\s+EXISTS\s+)?`?(\w+)`?/i',
    ];

    // Replace the entire list: LoggingPDO::setLoggedCommands(['INSERT','UPDATE','DELETE'])
    public static function setLoggedCommands(array $commands)
    {
        self::$logged_commands = array_map('strtoupper', $commands);
    }

    // Add a single command: LoggingPDO::addLoggedCommand('SELECT')
    public static function addLoggedCommand($command)
    {
        $command = strtoupper($command);
        if (!in_array($command, self::$logged_commands, true)) {
            self::$logged_commands[] = $command;
        }
    }

    // Remove a single command: LoggingPDO::removeLoggedCommand('ALTER')
    public static function removeLoggedCommand($command)
    {
        $command = strtoupper($command);
        self::$logged_commands = array_values(array_filter(
            self::$logged_commands,
            fn($c) => $c !== $command
        ));
    }

    #[\ReturnTypeWillChange]
    public function prepare($query, $options = [])
    {
        $stmt = parent::prepare($query, $options);
        return new LoggingPDOStatement($stmt, $query, $this);
    }

    // Log file location — one level above the web root's kai/ directory
    private static $log_file = __DIR__ . '/../../logs/db_changes.log';

    public function writeLog($query)
    {
        $query_trimmed = ltrim($query);
        $first_word    = strtoupper(strtok($query_trimmed, " \t\n\r"));

        if (!in_array($first_word, self::$logged_commands, true)) {
            return;
        }

        $table    = $this->extractTableName($query_trimmed, $first_word);
        $username = $this->resolveUsername();
        $now      = date('Y-m-d H:i:s');

        try {
            $log_sql = "INSERT INTO db_change_log (username, sql_command, table_name, query_text)
                        VALUES (:username, :sql_command, :table_name, :query_text)";
            $stmt = parent::prepare($log_sql);
            $stmt->execute([
                ':username'    => $username,
                ':sql_command' => $first_word,
                ':table_name'  => $table,
                ':query_text'  => $query_trimmed,
            ]);
        } catch (PDOException $ex) {
            error_log("db_change_log write failed: " . $ex->getMessage());
        }

        $log_line = "[$now] [$username] $first_word $table | " . preg_replace('/\s+/', ' ', $query_trimmed) . PHP_EOL;
        $log_path = realpath(dirname(self::$log_file)) ?: dirname(self::$log_file);
        if (!is_dir($log_path)) {
            mkdir($log_path, 0750, true);
        }
        file_put_contents(self::$log_file, $log_line, FILE_APPEND | LOCK_EX);
    }

    private function extractTableName($query, $command)
    {
        if (isset(self::$table_patterns[$command]) &&
            preg_match(self::$table_patterns[$command], $query, $m)) {
            return $m[1];
        }
        return 'unknown';
    }

    private function resolveUsername()
    {
        if (!empty($_SERVER['REMOTE_USER'])) {
            return $_SERVER['REMOTE_USER'];
        }
        if (!empty($_SERVER['LOCAL_USER'])) {
            return $_SERVER['LOCAL_USER'];
        }
        return 'unknown';
    }
}
