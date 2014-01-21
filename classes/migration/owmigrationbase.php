<?php

abstract class OWMigrationBase {

	protected $output;
	protected $db;

	public function __construct() {
		$this->db = eZDB::instance();
	}

	abstract function startMigrationOn( $param );

	abstract function end();

	public function manualStep( $action, $description ) {
		OWScriptLogger::writeNotice( "=== $action ===" . PHP_EOL . $description . PHP_EOL . "-> You can add a comment and press enter to continue. If you want to report a warning or an error, start you comment by 'warn:' or 'error:'.", 'manual_step' );
		$fp = fopen( "php://stdin", "r" );
		$result = trim( fgets( $fp ) );
		if ( empty( $result ) ) {
			OWScriptLogger::logNotice( "'$action' done", 'manual_step' );
		} elseif ( strpos( strtolower( $result ), 'warn:' ) === 0 ) {
			$result = trim( substr( $result, 5 ) );
			OWScriptLogger::logWarning( "'$action' done" . PHP_EOL . "Warning: $result", 'manual_step' );
		} elseif ( strpos( strtolower( $result ), 'error:' ) === 0 ) {
			$result = trim( substr( $result, 6 ) );
			OWScriptLogger::logError( "'$action' done" . PHP_EOL . "Error: $result", 'manual_step' );
			return false;
		} else {
			OWScriptLogger::logNotice( "'$action' done" . PHP_EOL . "Comment: $result", 'manual_step' );
		}
		return true;
	}

}
