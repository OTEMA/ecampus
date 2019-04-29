<?php
error_reporting(1);
session_start();

/*Getting root directory of the site*/
define('SCHOOL_ROOT', $_SERVER["DOCUMENT_ROOT"]."/ecampus/admin/");

/*
* Include the necessary configuration info
*/
include SCHOOL_ROOT .'sys/config/db-cred.inc.php';

/*
* Define constants for configuration info
*/
foreach ( $C as $name => $val )
{
define($name, $val);
}

/*
* Create a PDO object
*/
$dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;

$dbo = new PDO($dsn, DB_USER, DB_PASS);

/*
* Define the auto-load function for classes
*/
function __autoload($class)
{
$class=strtolower($class);
$filename = SCHOOL_ROOT ."sys/class/class." . $class . ".php";

if ( file_exists($filename) )
{
include_once $filename;
}
}

$common=new common();

$Remote = $common->CCStrip($_SERVER['REMOTE_ADDR']);   
$RemoteBrowser = $common->CCStrip($_SERVER['HTTP_USER_AGENT']);

//Initialize All Common Files
$SysConfig = $common->GetRows("SELECT * FROM tbl_sys_config");
foreach($SysConfig AS $SysConf)
	{
		$SystemURI = $SysConf['main_url'];
		$SystemName = $SysConf['system_name'];
		$SystemRegisteredTo = $SysConf['system_registered_to'];
		$SystemIP = $SysConf['sys_default_ip'];	
		$SystemEnabled = $SysConf['sys_status_enabled'];	
		$SystemEmailSupport = $SysConf['support_email'];	
		$SuportPhone = $SysConf['support_phone'];
		$SuportWebsite = $SysConf['support_website'];
		$DeploymentDate = $SysConf['deployment_date'];	
		$sys_version = $SysConf['sys_version'];
		$isssl = $SysConf['isssl'];

		//Company Details
		$coop_phone = $SysConf['coop_phone'];
		$coop_website = $SysConf['coop_website'];
		$coop_address = $SysConf['coop_address'];
		$coop_countyid = $SysConf['coop_countyid'];
		$coop_email = $SysConf['coop_email'];
		$coop_logo = $SysConf['coop_logo'];
		$coop_status = $SysConf['coop_status']; 
		if(empty($coop_logo))
		{
			$coop_logo = "logo.png";
		}

	}

define("SITE_ROOT", "/stars/");
define("ASSETS_URL", "../assets/");

/*Date Diff*/
function format_interval(DateInterval $interval) {
$result = "";
if ($interval->y) { $result .= $interval->format("%y years "); }
if ($interval->m) { $result .= $interval->format("%m months "); }
if ($interval->d) { $result .= $interval->format("%d days "); }
if ($interval->h) { $result .= $interval->format("%h hrs "); }
if ($interval->i) { $result .= $interval->format("%i mins "); }
if ($interval->s) { $result .= $interval->format("%s secs "); }
return $result;
}

function get_image_type ( $filename )
{
	$img = getimagesize( $filename );
	if ( !empty( $img[2] ) )
		return image_type_to_mime_type( $img[2] );
	return false;
}

//End Initialize All Common Files
function array_filter_by_value($my_array, $index, $value)
{ 
    if(is_array($my_array) && count($my_array)>0)  
    { 
        foreach(array_keys($my_array) as $key){ 
            $temp[$key] = $my_array[$key][$index]; 
             
            if ($temp[$key] == $value){ 
                $new_array[$key] = $my_array[$key]; 
            } 
        } 
      } 
  return $new_array; 
}

// Start DB Back Up Scripts
define("DB_USER", 'root');
define("DB_PASSWORD", '');
define("DB_NAME", 'ecampus_db');
define("DB_HOST", 'localhost');
define("BACKUP_DIR", 'db-backups'); // Comment this line to use same script's directory ('.')
define("TABLES", '*'); // Full backup
//define("TABLES", 'table1, table2, table3'); // Partial backup
define("CHARSET", 'utf8');
define("GZIP_BACKUP_FILE", true);  // Set to false if you want plain SQL backup files (not gzipped)

/**
 * The Backup_Database class
 */
class Backup_Database { 
    var $host; 
    var $username; 
    var $passwd; 
    var $dbName; 
    var $charset; 
    var $conn; 
    var $backupDir; 
    var $backupFile; 
    var $gzipBackupFile; 
    var $output;

    /**
     * Constructor initializes database
     */
    public function __construct($host, $username, $passwd, $dbName, $charset = 'utf8') {
        $this->host            = $host;
        $this->username        = $username;
        $this->passwd          = $passwd;
        $this->dbName          = $dbName;
        $this->charset         = $charset;
        $this->conn            = $this->initializeDatabase();
        $this->backupDir       = BACKUP_DIR ? BACKUP_DIR : '.';
        $this->backupFile      = 'database-backup-'.$this->dbName.'-'.date("Ymd_His", time()).'.sql';
        $this->gzipBackupFile  = defined('GZIP_BACKUP_FILE') ? GZIP_BACKUP_FILE : true;
        $this->output          = '';
    }

    protected function initializeDatabase() {
        try {
            $conn = mysqli_connect($this->host, $this->username, $this->passwd, $this->dbName);
            if (mysqli_connect_errno()) {
                throw new Exception('ERROR connecting database: ' . mysqli_connect_error());
                die();
            }
            if (!mysqli_set_charset($conn, $this->charset)) {
                mysqli_query($conn, 'SET NAMES '.$this->charset);
            }
        } catch (Exception $e) {
            print_r($e->getMessage());
            die();
        }

        return $conn;
    }

    /**
     * Backup the whole database or just some tables
     * Use '*' for whole database or 'table1 table2 table3...'
     * @param string $tables
     */
    public function backupTables($tables = '*') {
        try {
            /**
            * Tables to export
            */
            if($tables == '*') {
                $tables = array();
                $result = mysqli_query($this->conn, 'SHOW TABLES');
                while($row = mysqli_fetch_row($result)) {
                    $tables[] = $row[0];
                }
            } else {
                $tables = is_array($tables) ? $tables : explode(',', str_replace(' ', '', $tables));
            }

            $sql = 'CREATE DATABASE IF NOT EXISTS `'.$this->dbName."`;\n\n";
            $sql .= 'USE `'.$this->dbName."`;\n\n";

            /**
            * Iterate tables
            */
            foreach($tables as $table) {
                $this->obfPrint("Backing up `".$table."` table...".str_repeat('.', 50-strlen($table)), 0, 0);

                /**
                 * CREATE TABLE
                 */
                $sql .= 'DROP TABLE IF EXISTS `'.$table.'`;';
                $row = mysqli_fetch_row(mysqli_query($this->conn, 'SHOW CREATE TABLE `'.$table.'`'));
                $sql .= "\n\n".$row[1].";\n\n";

                /**
                 * INSERT INTO
                 */

                $row = mysqli_fetch_row(mysqli_query($this->conn, 'SELECT COUNT(*) FROM `'.$table.'`'));
                $numRows = $row[0];

                // Split table in batches in order to not exhaust system memory 
                $batchSize = 1000; // Number of rows per batch
                $numBatches = intval($numRows / $batchSize) + 1; // Number of while-loop calls to perform
                for ($b = 1; $b <= $numBatches; $b++) {
                    
                    $query = 'SELECT * FROM `'.$table.'` LIMIT '.($b*$batchSize-$batchSize).','.$batchSize;
                    $result = mysqli_query($this->conn, $query);
                    $numFields = mysqli_num_fields($result);

                    for ($i = 0; $i < $numFields; $i++) {
                        $rowCount = 0;
                        while($row = mysqli_fetch_row($result)) {
                            $sql .= 'INSERT INTO `'.$table.'` VALUES(';
                            for($j=0; $j<$numFields; $j++) {
                                if (isset($row[$j])) {
                                    $row[$j] = addslashes($row[$j]);
                                    $row[$j] = str_replace("\n","\\n",$row[$j]);
                                    $sql .= '"'.$row[$j].'"' ;
                                } else {
                                    $sql.= 'NULL';
                                }

                                if ($j < ($numFields-1)) {
                                    $sql .= ',';
                                }
                            }

                            $sql.= ");\n";
                        }
                    }

                    $this->saveFile($sql);
                    $sql = '';
                }

                $sql.="\n\n\n";

                $this->obfPrint(" OK");
            }

            if ($this->gzipBackupFile) {
                $this->gzipBackupFile();
            } else {
                $this->obfPrint('Backup file succesfully saved to ' . $this->backupDir.'/'.$this->backupFile, 1, 1); 
                
            }
        } catch (Exception $e) {
            print_r($e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Save SQL to file
     * @param string $sql
     */
    protected function saveFile(&$sql) {
        if (!$sql) return false;

        try {

            if (!file_exists($this->backupDir)) {
                mkdir($this->backupDir, 0777, true);
            }

            file_put_contents($this->backupDir.'/'.$this->backupFile, $sql, FILE_APPEND | LOCK_EX);

        } catch (Exception $e) {
            print_r($e->getMessage());
            return false;
        }

        return true;
    }

    /*
     * Gzip backup file
     *
     * @param integer $level GZIP compression level (default: 9)
     * @return string New filename (with .gz appended) if success, or false if operation fails
     */
    protected function gzipBackupFile($level = 9) {
        if (!$this->gzipBackupFile) {
            return true;
        }
        $RawFile = $this->backupFile;
        $source = $this->backupDir . '/' . $this->backupFile;
        $dest =  $source . '.gz';

        $this->obfPrint('Gzipping backup file to ' . $dest . '... ', 1, 0);

        // FTP Edited
        $ftp_server = "accusystems.co.ke";
        $ftp_user_name = "ftpfiles@accusystems.co.ke";
        $ftp_user_pass = "AGA-colo!qq";
        $ftp_directory = '';  
        $ftp_source_file_name = $RawFile;
        $ftp_dest_file_name = $ftp_source_file_name;
        
        function ftp_file( $ftpservername, $ftpusername, $ftppassword, $ftpsourcefile, $ftpdirectory, $ftpdestinationfile ){
            $conn_id = ftp_connect($ftpservername);
            if ( $conn_id == false ){
                var_dump("FTP open connection failed to $ftpservername \n");
                return false;
            }
            $login_result = ftp_login($conn_id, $ftpusername, $ftppassword);
            if ((!$conn_id) || (!$login_result)) {
                var_dump("FTP connection has failed!\n");
                var_dump("Attempted to connect to " . $ftpservername . " for user " . $ftpusername . "\n");
                return false;
            } else {
               var_dump("Connected to " . $ftpservername . ", for user " . $ftpusername . "\n");
            }
            if ( strlen( $ftpdirectory ) > 0 )
            {
                if (ftp_chdir($conn_id, $ftpdirectory )) {
                    var_dump("Current directory is now: " . ftp_pwd($conn_id) . "\n");
                } else {
                    var_dump("Couldn't change directory on $ftpservername\n");
                    return false;
                }
            }
            ftp_pasv ( $conn_id, true ) ;
            $upload = ftp_put( $conn_id, $ftpdestinationfile, $ftpsourcefile, FTP_ASCII );
            if (!$upload) {
                var_dump("$ftpservername: FTP upload has failed!\n");
                return false;
            } else {
                var_dump("Uploaded " . $ftpsourcefile . " to " . $ftpservername . " as " . $ftpdestinationfile . "\n");
            }
            ftp_close($conn_id);
            return true;
        }

        if(ftp_file( $ftp_server, $ftp_user_name, $ftp_user_pass, $ftp_source_file_name, $ftp_directory, $ftp_dest_file_name) ){
            var_dump("Success: FTP'd \n".$RawFile);
        } else {
            var_dump("Error: Could not FTP.\n".$RawFile);
        } 

        $mode = 'wb' . $level;
        if ($fpOut = gzopen($dest, $mode)) {
            if ($fpIn = fopen($source,'rb')) {
                while (!feof($fpIn)) {
                    gzwrite($fpOut, fread($fpIn, 1024 * 256));
                }
                fclose($fpIn);
            } else {
                return false;
            }
            gzclose($fpOut);
            if(!unlink($source)) {
                return false;
            }
        } else {
            return false;
        }
        
        $this->obfPrint('OK');
        return $dest;
 
    }

    /**
     * Prints message forcing output buffer flush
     *
     */
    public function obfPrint ($msg = '', $lineBreaksBefore = 0, $lineBreaksAfter = 1) {
        if (!$msg) {
            return false;
        }

        $output = '';

        if (php_sapi_name() != "cli") {
            $lineBreak = "<br />";
        } else {
            $lineBreak = "\n";
        }

        if ($lineBreaksBefore > 0) {
            for ($i = 1; $i <= $lineBreaksBefore; $i++) {
                $output .= $lineBreak;
            }                
        }

        $output .= $msg;

        if ($lineBreaksAfter > 0) {
            for ($i = 1; $i <= $lineBreaksAfter; $i++) {
                $output .= $lineBreak;
            }                
        }


        // Save output for later use
        $this->output .= str_replace('<br />', '\n', $output);

        echo $output;


        if (php_sapi_name() != "cli") {
            ob_flush();
        }

        $this->output .= " ";

        flush();
    } 
    public function getOutput() {
        return $this->output;
    }
}  
// End DB Back Up Scripts

?>