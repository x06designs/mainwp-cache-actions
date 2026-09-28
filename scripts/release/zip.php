<?php
/**
 * Zips a staged plugin directory under a root folder and fails when a required entry is missing.
 *
 *   php scripts/release/zip.php <stage-dir> <root-folder> <zip-file> <required-entry...>
 *
 * @package MainWPAddons
 */

declare(strict_types=1);

if ( $argc < 4 ) {
	fwrite( STDERR, "usage: zip.php <stage-dir> <root-folder> <zip-file> <required-entry...>\n" );
	exit( 64 );
}

[ , $stage, $root, $zip_file ] = $argv;
$required                      = array_slice( $argv, 4 );

$stage = realpath( $stage );
if ( false === $stage || ! is_dir( $stage ) ) {
	fwrite( STDERR, "zip.php: stage dir {$argv[1]} does not exist\n" );
	exit( 66 );
}

$files    = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $stage, FilesystemIterator::SKIP_DOTS ) );
$relative = array();
foreach ( $files as $file ) {
	if ( $file->isFile() ) {
		$relative[] = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $stage ) + 1 ) );
	}
}
sort( $relative, SORT_STRING );

$zip = new ZipArchive();
if ( true !== $zip->open( $zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
	fwrite( STDERR, "zip.php: cannot create {$zip_file}\n" );
	exit( 73 );
}
foreach ( $relative as $path ) {
	if ( ! $zip->addFile( $stage . '/' . $path, $root . '/' . $path ) ) {
		fwrite( STDERR, "zip.php: cannot add {$path}\n" );
		exit( 74 );
	}
}
if ( ! $zip->close() ) {
	fwrite( STDERR, "zip.php: cannot write {$zip_file}\n" );
	exit( 74 );
}

$missing = array_diff( $required, $relative );
if ( array() !== $missing ) {
	fwrite( STDERR, 'zip.php: missing in ' . $zip_file . ': ' . implode( ', ', $missing ) . "\n" );
	exit( 1 );
}

printf( "zip.php: %s, %d files, sha1 %s\n", $zip_file, count( $relative ), sha1_file( $zip_file ) );
