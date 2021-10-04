<?php

namespace Wpify\PluginComposerScripts;

use Composer\Command\BaseCommand;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Transliterator;

class RenameWpifyPlugin extends BaseCommand {
	protected function configure() {
		$slug = basename( getcwd() );
		$this->addArgument( 'search', InputArgument::OPTIONAL, 'Plugin slug to search for', 'wpify-plugin-skeleton' );
		$this->addArgument( 'replace', InputArgument::OPTIONAL, 'New plugin slug', $slug );
	}

	protected function execute( InputInterface $input, OutputInterface $output ) {
		$arguments = $input->getArguments();
		$search    = $arguments['search'];
		$replace   = $arguments['replace'];

		$search_replace = array(
			'/' . preg_quote( $this->slug( $search ), '/' ) . '/'                  => $this->slug( $replace ),
			'/' . preg_quote( $this->underscored_uppercase( $search ), '/' ) . '/' => $this->underscored_uppercase( $replace ),
			'/' . preg_quote( $this->spaced_camelcase( $search ), '/' ) . '/'      => $this->spaced_camelcase( $replace ),
			'/' . preg_quote( $this->camelcase( $search ), '/' ) . '/'             => $this->camelcase( $replace ),
			'/' . preg_quote( $this->underscored_camelcase( $search ), '/' ) . '/' => $this->underscored_camelcase( $replace ),
			'/' . preg_quote( $this->underscored_lowercase( $search ), '/' ) . '/' => $this->underscored_lowercase( $replace ),
		);

		$this->replace_in_files(
			getcwd(),
			'/.*/',
			$search_replace,
			array(
				'/\/deps\//',
				'/\/vendor\//',
				'/\/node\_modules\//',
			)
		);
	}

	private function slug( string $str ) {
		return $this->replace_space_with_dash(
			$this->lowercase(
				$this->remove_special_chars(
					$this->remove_accent( $str )
				)
			)
		);
	}

	private function replace_space_with_dash( string $str ) {
		return preg_replace( '/\s+/', '-', trim( $str ) );
	}

	private function lowercase( string $str ) {
		return strtolower( $str );
	}

	private function remove_special_chars( string $str ) {
		return trim( preg_replace( '/[^A-Za-z0-9]+/', ' ', $str ) );
	}

	private function remove_accent( string $str ) {
		$transliterator = Transliterator::createFromRules( ':: Any-Latin; :: Latin-ASCII; :: NFD; :: [:Nonspacing Mark:] Remove; :: Lower(); :: NFC;', Transliterator::FORWARD );

		return $transliterator->transliterate( $str );
	}

	private function underscored_uppercase( string $str ) {
		$parts = explode( ' ', $this->remove_special_chars(
			$this->remove_accent( $str )
		) );

		return implode( '_', array_map( 'strtoupper', $parts ) );
	}

	private function spaced_camelcase( string $str ) {
		$parts = explode( ' ', $this->remove_special_chars(
			$this->remove_accent( $str )
		) );

		return implode( ' ', array_map( 'ucfirst', $parts ) );
	}

	private function camelcase( string $str ) {
		$parts = explode( ' ', $this->remove_special_chars(
			$this->remove_accent( $str )
		) );

		return implode( '', array_map( 'ucfirst', $parts ) );
	}

	private function underscored_camelcase( string $str ) {
		$parts = explode( ' ', $this->remove_special_chars(
			$this->remove_accent( $str )
		) );

		return implode( '_', array_map( 'ucfirst', $parts ) );
	}

	private function underscored_lowercase( string $str ) {
		$parts = explode( ' ', $this->remove_special_chars(
			$this->remove_accent( $str )
		) );

		return implode( '_', array_map( 'strtolower', $parts ) );
	}

	/**
	 * @param string $folder
	 * @param string $regex
	 * @param array $search_replace
	 * @param array $excludes
	 */
	private function replace_in_files(
		string $folder,
		string $regex,
		array $search_replace = array(),
		array $excludes = array()
	) {
		$paths   = $this->find_paths( $folder, $regex, $excludes );

		foreach ( $paths as $path ) {
			$content = file_get_contents( $path );
			$file    = substr( $path, strlen( $folder ) );

			foreach ( $search_replace as $search => $replace ) {
				if ( preg_match( $search, $content ) ) {
					$content = preg_replace( $search, $replace, $content );
				}

				if ( preg_match( $search, $file ) ) {
					$file = preg_replace( $search, $replace, $file );
				}
			}

			file_put_contents( $path, $content );

			$new_path = $folder . $file;

			if ( $new_path !== $path && file_exists( $path ) ) {
				if ( ! file_exists( dirname( $new_path ) ) ) {
					mkdir( dirname( $new_path ), fileperms( dirname( $path ) ), true );
				}

				rename( $path, $new_path );
			}
		}

		$this->remove_empty_directories( $folder );
	}

	private function find_paths( string $folder, string $regex, array $excludes ) {
		$dir       = new RecursiveDirectoryIterator( $folder );
		$ite       = new RecursiveIteratorIterator( $dir );
		$files     = new RegexIterator( $ite, $regex, RecursiveRegexIterator::GET_MATCH );
		$file_list = array();

		foreach ( $files as $file ) {
			$file = current( $file );

			foreach ( $excludes as $exclude ) {
				$local = str_replace( $folder, '', $file );


				if ( preg_match( $exclude, $local ) ) {
					continue 2;
				}
			}

			$file_list[] = $file;
		}

		return $file_list;
	}

	private function remove_empty_directories( $path ) {
		$empty = true;

		foreach ( glob( $path . DIRECTORY_SEPARATOR . "*" ) as $file ) {
			$empty &= is_dir( $file ) && $this->remove_empty_directories( $file );
		}

		return $empty && rmdir( $path );
	}
}
