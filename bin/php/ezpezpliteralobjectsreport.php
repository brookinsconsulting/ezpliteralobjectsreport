#!/usr/bin/env php
<?php
/**
 * File containing the ezpezpliteralobjectsreport.php bin script
 *
 * @copyright Copyright (C) 1999 - 2014 Brookins Consulting. All rights reserved.
 * @copyright Copyright (C) 2013 - 2014 Think Creative. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2 (or later)
 * @version 0.1.1
 * @package ezpezpliteralobjectsreport
 */

require 'autoload.php';

/** Script startup and initialization **/

$cli = eZCLI::instance();
$script = eZScript::instance( array( 'description' => ( "eZ Publish Literal Object CSV Report Script\n" .
                                                        "\n" .
                                                        "ezpezpliteralobjectsreport.php --storage-dir=report --hostname=www.example.com" ),
                                     'use-session' => false,
                                     'use-modules' => true,
                                     'use-extensions' => true,
                                     'user' => true ) );

$script->startup();

$options = $script->getOptions( "[storage-dir:][hostname:]",
                                "[node]",
                                array( 'storage-dir' => 'Directory to place exported report file in',
                                       'hostname' => 'Website hostname to match url searches for' ),
                                false,
                                array( 'user' => true ) );
$script->initialize();

/** Script default values **/

$openedFPs = array();

$orphanedCsvReportFileName = 'ezpezpliteralobjectsreport';

$csvHeader = array( 'ContentObjectID', 'NodeID', 'AttributeID', 'Version', 'Contains Literal', 'Name', 'Url' );

$siteNodeUrlPrefix = "http://";

/** Test for required script arguments **/

if ( $options['storage-dir'] )
{
    $storageDir = $options['storage-dir'];
}
else
{
    $storageDir = '';
}

if ( $options['hostname'] )
{
    $siteNodeUrlHostname = $options['hostname'];
}
else
{
    $cli->error( 'Hostname is required. Specify a website hostname for the site report url matching' );
    $script->shutdown( 2 );
}

/** Alert user of report generation process starting **/

$cli->output( "Searching through content for literal usage ...\n" );

/** Fetch objects in content tree with attribute containing literal usage **/

$db = eZDB::instance();
$query = 'SELECT DISTINCT ezcontentobject_attribute.contentobject_id, ezcontentobject_attribute.contentclassattribute_id, ezcontentobject_attribute.id, MAX( ezcontentobject_attribute.version ) as version FROM ezcontentobject_attribute WHERE data_text like "%<literal class=\"html\">%" GROUP BY ezcontentobject_attribute.contentobject_id ORDER BY ezcontentobject_attribute.contentobject_id DESC, ezcontentobject_attribute.id DESC, version DESC;';

// $results = $db->arrayQuery( $sql, array( 'limit' => 1 ) );
$results = $db->arrayQuery( $query );
$resultsCount = count( $results );

/*
if( is_array( $results ) and count( $results ) >= 1 )
{
print_r( $resultsCount ); echo "\n\n";
print_r( $results );
}
*/


/** Setup script iteration details **/

$script->setIterationData( '.', '.' );
$script->resetIteration( $resultsCount );

/** Open report file for writting **/

if ( !isset( $openedFPs[$orphanedCsvReportFileName] ) )
{
    $tempFP = @fopen( $storageDir . '/' . $orphanedCsvReportFileName . '.csv', "w" );

    if ( $tempFP )
    {
        $openedFPs[$orphanedCsvReportFileName] = $tempFP;
    }
    else
    {
        $cli->error( "Can not open output file for $storageDir/$orphanedCsvReportFileName file" );
        $script->shutdown( 4 );
    }
}
else
{
   if ( !$openedFPs[$orphanedCsvReportFileName] )
   {
        $cli->error( "Can not open output file for $storageDir/$orphanedCsvReportFileName file" );
        $script->shutdown( 4 );
   }
}

/** Define report file pointer **/

$fp = $openedFPs[$orphanedCsvReportFileName];

/** Write report csv header **/

if ( !fputcsv( $fp, $csvHeader, ';' ) )
{
    $cli->error( "Can not write to report file" );
    $script->shutdown( 6 );
}

/** Iterate over nodes **/

while ( list( $key, $contentObject ) = each( $results ) )
{
    $objectData = array();
    $estimateObjectOrphaned = 0;
    $status = true;

    /** Fetch object details **/
    $objectContainsLiteral = 1;
    $contentObjectID = $contentObject['contentobject_id'];
    $contentClassAttributeID = $contentObject['contentclassattribute_id'];
    $contentObjectAttributeID = $contentObject['id'];
    $contentObjectVersionID = $contentObject['version'];

    $object = eZContentObject::fetch( $contentObjectID );
    $objectName = $object->name();
    $objectMainNode = $object->mainNode();
    if ( is_object( $objectMainNode ) )
    {
        $objectMainNodeID = $objectMainNode->attribute( 'node_id' );
        $objectMainNodePath = $siteNodeUrlPrefix . $siteNodeUrlHostname . '/' . $objectMainNode->attribute( 'url' );

        /** Build report for objects **/

        $objectData[] = $contentObjectID;

        $objectData[] = $objectMainNodeID;

        $objectData[] = $contentObjectAttributeID;

        $objectData[] = $contentObjectVersionID;

        $objectData[] = $objectContainsLiteral;

        $objectData[] = $objectName;

        $objectData[] = $objectMainNodePath;

        /** Test if report file is opened **/

        if ( !$fp )
        {
            $cli->error( "Can not open output file" );
            $script->shutdown( 5 );
        }

        /** Write report datat to file **/

        if ( !fputcsv( $fp, $objectData, ';' ) )
        {
            $cli->error( "Can not write to file" );
            $script->shutdown( 6 );
        }
    }

    $script->iterate( $cli, $status );
}

/** Close report file **/

while ( $fp = each( $openedFPs ) )
{
    fclose( $fp['value'] );
}

/** Shutdown script **/

$script->shutdown();

?>