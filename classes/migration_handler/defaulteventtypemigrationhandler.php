<?php

class DefaultEventTypeMigrationHandler implements MigrationHandlerInterface {

    static public function toArray( eZWorkflowEvent $event ) {
        $attributesArray = array( );
        foreach( $event->attributes() as $attributeIdentifier ) {
            if( (strpos( $attributeIdentifier, "data_int" ) === 0 && $event->attribute( $attributeIdentifier ) !== '0') || (strpos( $attributeIdentifier, "data_text" ) === 0 && $event->attribute( $attributeIdentifier ) !== '') ) {
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