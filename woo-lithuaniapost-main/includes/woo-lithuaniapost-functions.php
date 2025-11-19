<?php
/**
 * All plugin functions
 *
 * @link       https://post.lt
 * @since      1.0.0
 *
 * @package    Woo_Lithuaniapost
 * @subpackage Woo_Lithuaniapost/includes
 */

/**
 * Get file headers to force download attachment
 *
 * @param $filename
 * @param $type
 * @since 1.0.0
 */
function woo_lithuaniapost_file_headers ( $filename, $type ) {
    /**
     * Output header so that file is downloaded
     * instead of open for reading.
     */
    header ( "Pragma: public" );
    header ( "Expires: 0" );
    header ( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
    header ( "Cache-Control: private", false );
    header ( 'Content-Type: ' . $type . '; charset=utf-8' );
    header ( "Content-Disposition: attachment; filename=\"" . $filename . "\";" );
    header ( "Content-Transfer-Encoding: binary" );
}
