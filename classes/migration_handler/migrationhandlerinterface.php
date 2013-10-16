<?php

interface MigrationHandlerInterface {
    static public function toArray( eZWorkflowEvent $event );
    static public function fromArray( eZWorkflowEvent $event, array $options  );
}
?>