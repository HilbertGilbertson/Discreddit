<?php
/**
 * Discreddit DB Class
 * @file Discreddit.DB.php
 * @version 1.0
 * @author HilbertGilbertson
 * @url https://github.com/HilbertGilbertson/Discreddit
 */

class DiscredditDB
{
    private $con;
    public $table;

    public function __construct($mode, $fp)
    {
        $this->con = null;
        $this->table = "discreddit";

        if ($mode == "sqlite")
            $this->sqlite_init($fp);
        elseif ($mode == "mysql")
            $this->mysql_init($fp);
    }

    private function sqlite_init($fp)
    {
        if (!file_exists($fp)) {
            die("SQLite mode is enabled but the SQLite DB file could not be located.");
        }
        if (!is_writable($fp)) {
            die("SQLite mode is enabled but the SQLite DB file is not writable.");
        }

        $this->con = new PDO("sqlite:$fp");
        $tryQuery = @$this->con->query("SELECT * FROM {$this->table} LIMIT 1");
        if (!$tryQuery) {
            die("SQLite mode is enabled but the SQLite connection could not be established. Please check the file and the configured table name.");
        }
    }

    private function mysql_init($conf)
    {
        try {
            $this->con = new PDO("mysql:host=" . $conf->host . ";dbname=" . $conf->dbname . ";charset=utf8mb4", $conf->user, $conf->password);
        } catch (Exception $e) {
            die("DB Mode is enabled but the mysql connection failed, returning the following error: " . $e->getMessage());
        }
    }

    public function from_discord($discord_id)
    {
        $q = $this->con->prepare("SELECT * FROM `{$this->table}` WHERE `discord_id` = ? LIMIT 1");
        $q->bindParam(1, $discord_id, PDO::PARAM_INT);
        $q->execute();
        return $q->fetch(PDO::FETCH_ASSOC);
    }

    public function from_reddit($reddit_username)
    {
        $q = $this->con->prepare("SELECT * FROM `{$this->table}` WHERE `reddit_username` = ?");
        $q->bindParam(1, $reddit_username);
        $q->execute();
        return $q->fetch(PDO::FETCH_ASSOC);
    }

    public function create($discord_id, $reddit_username)
    {
        $q = $this->con->prepare("INSERT INTO `{$this->table}` (`discord_id`, `reddit_username`) VALUES(?, ?)");
        $q->bindParam(1, $discord_id, PDO::PARAM_INT);
        $q->bindParam(2, $reddit_username, PDO::PARAM_STR);
        return $q->execute();
    }
}