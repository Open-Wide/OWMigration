<?php

class DefaultEventTypeMigrationHandler implements MigrationHandlerInterface {

    static public function toArray( eZWorkflowEvent $event ) {
        $attributesArray = array( );
        foreach( $event->attributes() as $attributeIdentifier ) {
            if( strpos( $attributeIdentifier, "data_" ) === 0 ) {
                $attributesArray[$attributeIdentifier] = $event->attribute( $attributeIdentifier );
            }
        }
        return $attributesArray;
    }

    static public function fromArray( eZWorkflowEvent $event, array $options ) {
        foreach( $options as $optionsIdentifier => $optionsValue ) {
            if( $event->hasAttribute( $optionsIdentifier ) ) {
                $event->setAttribute( $optionsIdentifier, $optionsValue );
            }
        }
    }

}
?>