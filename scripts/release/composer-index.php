<?php
/**
 * Adds one release to the Composer repository index (packages.json) served from gh-pages.
 * Package name and PHP requirement come from the plugin's composer.json.
 *
 *   php scripts/release/composer-index.php <packages.json> <lib> <x.y.z> <zip-url> <zip-file>
 *
 * @package MainWPAddons
 */

declare(strict_types=1);

if ( 6 !== $argc ) {
	fwrite( STDERR, "usage: composer-index.php <packages.json> <lib> <x.y.z> <zip-url> <zip-file>\n" );
	exit( 64 );
}

[ , $index_file, $lib, $version, $url, $zip_file ] = $argv;

$read_json = static function ( string $file ): array {
	$data = json_decode( (string) file_get_contents( $file ), true, 512, JSON_THROW_ON_ERROR );
	if ( ! is_array( $data ) ) {
		throw new UnexpectedValueException( "{$file} is not a JSON object" );
	}
	return $data;
};

$composer = $read_json( dirname( __DIR__, 2 ) . "/libs/{$lib}/composer.json" );
$index    = is_file( $index_file ) ? $read_json( $index_file ) : array( 'packages' => array() );
$name     = $composer['name'];

if ( isset( $index['packages'][ $name ][ $version ] ) ) {
	fwrite( STDERR, "composer-index.php: {$name} {$version} is already in {$index_file}\n" );
	exit( 1 );
}
if ( ! is_file( $zip_file ) ) {
	fwrite( STDERR, "composer-index.php: {$zip_file} does not exist\n" );
	exit( 66 );
}

$index['packages'][ $name ][ $version ] = array(
	'name'        => $name,
	'version'     => $version,
	'type'        => 'wordpress-plugin',
	'description' => $composer['description'] ?? '',
	'license'     => $composer['license'] ?? 'GPL-2.0-or-later',
	'require'     => array( 'php' => $composer['require']['php'] ),
	'dist'        => array(
		'type'   => 'zip',
		'url'    => $url,
		'shasum' => sha1_file( $zip_file ),
	),
);
uksort( $index['packages'][ $name ], static fn ( string $a, string $b ): int => version_compare( $a, $b ) );
ksort( $index['packages'] );

file_put_contents( $index_file, json_encode( $index, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR ) . "\n" );
echo "composer-index.php: added {$name} {$version}\n";
