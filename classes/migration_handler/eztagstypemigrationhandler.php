<?php

class eZTagsTypeMigrationHandler extends DefaultDatatypeMigrationHandler
{

    static public function toArray( eZContentClassAttribute $attribute )
    {
        $toArray = array();
        if( $attribute->attribute( eZTagsType::SUBTREE_LIMIT_FIELD ) != 0 )
        {
            $toArray['subtree_limit'] = $attribute->attribute( eZTagsType::SUBTREE_LIMIT_FIELD );
        }
        if( $attribute->attribute( eZTagsType::SHOW_DROPDOWN_FIELD ) )
        {
            $toArray['show_dropdown'] = (bool) $attribute->attribute( eZTagsType::SHOW_DROPDOWN_FIELD );
        }
        if( $attribute->attribute( eZTagsType::HIDE_ROOT_TAG_FIELD ) != 0 )
        {
            $toArray['hide_root_tag'] = (bool) $attribute->attribute( eZTagsType::HIDE_ROOT_TAG_FIELD );
        }
        if( $attribute->attribute( eZTagsType::MAX_TAGS_FIELD ) != 0 )
        {
            $toArray['max_tags'] = $attribute->attribute( eZTagsType::MAX_TAGS_FIELD );
        }
        return $toArray;
    }

    static public function fromArray( eZContentClassAttribute $attribute, array $options )
    {
        if( array_key_exists( 'subtree_limit', $options ) )
        {
            $attribute->setAttribute( eZTagsType::SUBTREE_LIMIT_FIELD, $options['subtree_limit'] );
        }
        if( array_key_exists( 'show_dropdown', $options ) )
        {
            $attribute->setAttribute( eZTagsType::SHOW_DROPDOWN_FIELD, $options['show_dropdown'] );
        }
        if( array_key_exists( 'hide_root_tag', $options ) )
        {
            $attribute->setAttribute( eZTagsType::HIDE_ROOT_TAG_FIELD, $options['hide_root_tag'] );
        }
        if( array_key_exists( 'max_tags', $options ) )
        {
            $attribute->setAttribute( eZTagsType::MAX_TAGS_FIELD, $options['max_tags'] );
        }
    }

}
