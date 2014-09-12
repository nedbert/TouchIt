<?php
namespace TouchIt\DataProvider;

use TouchIt\TouchIt;

class SQLDataProvider extends Provider{
    /** @var \SQLite3 */
    private $database;

    /** @var TouchIt */
    private $plugin;

    private $enable;
    private $write = [];

    public function onLoad(){}

    /**
     * Write an new log
     * @param $type
     * @param $data
     * @param $x
     * @param $y
     * @param $z
     * @param $level
     */
    public function create($type, $data, $x, $y, $z, $level){
        $this->write[] = "INSERT INTO sign VALUES ('".$this->position2string($x, $y, $z, $level)."', ".$type.", '".json_decode($data)."');";
        $this->notify();
    }

    /**
     * Check the log exists or not
     * @param $x
     * @param $y
     * @param $z
     * @param $level
     * @return bool
     */
    public function exists($x, $y, $z, $level){
        $query = $this->database->query("SELECT position FROM sign WHERE position = '".$this->position2string($x, $y, $z, $level)."';");
        if($query instanceof \SQLite3Result){
            while(($array = $query->fetchArray(SQLITE3_ASSOC))){
                if($array['position'] === $this->position2string($x, $y, $z, $level)){
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Delete log from database
     * @param $x
     * @param $y
     * @param $z
     * @param $level
     */
    public function remove($x, $y, $z, $level){
        $this->write[] = "DELETE FROM sign WHERE position = '".$this->position2string($x, $y, $z, $level)."';";
        $this->notify();
    }

    /**
     * return all the logs
     * @see Provider::getAll()
     * @return array
     */
    public function getAll(){
        $resule = [];
        $query = $this->database->query("SELECT * FROM sign;");
        if($query instanceof \SQLite3Result){
            while(($array = $query->fetchArray(SQLITE3_ASSOC))){
                $resule[] = ["position" => $this->string2position($array['position']), "type" => $array['type'], "data" => $array['data']];
            }
        }
        return $resule;
    }

    /**
     * Get log from database
     * @see Provider::get()
     * @param $x
     * @param $y
     * @param $z
     * @param $level
     * @return array|null
     */
    public function get($x, $y, $z, $level){
        $resule = null;
        $query = $this->database->query("SELECT * FROM sign WHERE position = '".$this->position2string($x, $y, $z, $level)."';");
        if($query instanceof \SQLite3Result){
            $array = $query->fetchArray(SQLITE3_ASSOC);
            $resule = ["position" => $this->string2position($array['position']), "type" => $array['type'], "data" => $array['data']];
        }
        return $resule;
    }

    /**
     * Call when need to load database
     */
    public function onEnable(){
    	$this->loadDataBase();
    }

    /**
     * Call when need to close database
     */
    public function onDisable(){
        if($this->database instanceof \SQLite3){
            $this->database->close();
        }
    }

    /**
     * Database writing thread
     * @throws \ErrorException
     */
    public function onLoop(){
        if(count($this->write) > 0){
            foreach($this->write as $action){
                if(!$this->database->exec((string) $action)){
                    throw new \ErrorException("Unable to write TouchIt database. Make sure you've got enough permissions.");
                }
            }
        }
        $this->wait();
    }

    /**
     * Internal use
     */
    private function loadDataBase(){
    	if(file_exists($this->plugin->getDataFolder()."data.db")){
    		$this->database = new \SQLite3($this->plugin->getDataFolder()."data.db", SQLITE3_OPEN_READWRITE|SQLITE3_OPEN_CREATE);
    		$this->database->exec(stream_get_contents($this->plugin->getResource("database/sqlite3.sql")));
    	}else{
    		$this->database = new \SQLite3($this->plugin->getDataFolder()."data.db", SQLITE3_OPEN_READWRITE);
    	}
    }

    /**
     * Get database string by position
     * @param $x
     * @param $y
     * @param $z
     * @param $level
     * @return string
     */
    private function position2string($x, $y, $z, $level){
        return $x."-".$y."-".$z."-".$level;
    }

    /**
     * Get position by database string
     * @param $string
     * @return array
     */
    private function string2position($string){
        $array = explode("-", $string);
        return ["x" => intval($array[0]), "y" => intval($array[1]), "z" => intval($array[2]), "level" => $array[3]];
    }
}
?>
