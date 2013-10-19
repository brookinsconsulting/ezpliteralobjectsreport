<?php
/**
 * File containing the literalreport/report module view.
 *
 * @copyright Copyright (C) 1999 - 2014 Brookins Consulting. All rights reserved.
 * @copyright Copyright (C) 2013 - 2014 Think Creative. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2 (or later)
 * @version 0.1.2
 * @package ezpliteralobjectsreport
 */

/**
 * Default module parameters
 */
$module = $Params["Module"];

/**
* Default class instances
*/

// Parse HTTP POST variables
$http = eZHTTPTool::instance();

// Access system variables
$sys = eZSys::instance();

// Init template behaviors
$tpl = eZTemplate::factory();

// Access ini variables
$ini = eZINI::instance();
$iniLiteralReport = eZINI::instance( 'ezpliteralobjectsreport.ini' );

// Report file variables
$dir = $iniLiteralReport->variable( 'SiteSettings', 'ReportStoragePath' );
$file = $dir . '/ezpezpliteralobjectsreport.csv';

/**
 * Handle download action
 */
if ( $http->hasPostVariable( 'Download' ) )
{
    if ( !eZFile::download( $file, true, 'ezpezpliteralobjectsreport.csv' ) )
       $module->redirectTo( 'literalreport/report' );
}

/**
 * Handle generate actions
 */
if ( $http->hasPostVariable( 'Generate' ) )
{
    $siteHostname = $iniLiteralReport->variable( 'SiteSettings', 'SiteHostname' );
    $reportStoragePath = $iniLiteralReport->variable( 'SiteSettings', 'ReportStoragePath' );

    // General script options
    $phpBin = '/usr/bin/php';
    $generatorWorkerScript = 'extension/ezpliteralobjectsreport/bin/php/ezpezpliteralobjectsreport.php';
    $options = '--storage-dir=' . $reportStoragePath . ' --hostname=' . $siteHostname;
    $result = false;
    $output = false;

    exec( "$phpBin ./$generatorWorkerScript $options;", $output, $result );
}

/**
 * Test for generated report
 */
if ( file_exists( $file ) )
{
    $tpl->setVariable( 'fileModificationTimestamp', date("F d Y H:i:s", filemtime( $file ) ) );
    $tpl->setVariable( 'status', true );
}
else
{
    $tpl->setVariable( 'status', false );
}


/**
 * Default template include
 */
$Result = array();
$Result['content'] = $tpl->fetch( "design:literalreport/report.tpl" );
$Result['path'] = array( array( 'url' => false,
                                'text' => ezpI18n::tr('design/standard/literalreport', 'Literal Report') ),
                         array( 'url' => false,
                                'text' => ezpI18n::tr('design/standard/literalreport', 'Report') )
                        );

$Result['left_menu'] = 'design:literalreport/menu.tpl';

?>