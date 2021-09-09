<?php

namespace Wpify\PluginComposerScripts;

use Composer\Command\BaseCommand;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Transliterator;

class ReplaceNameCommand extends BaseCommand {
	protected function configure() {
		$this->setName( 'replace-name' );
	}

	protected function execute( InputInterface $input, OutputInterface $output ) {
		$slug = basename( getcwd() );

		if ( ! empty( $slug ) ) {
			$this->replace_in_files(
				getcwd(),
				'/.*\.(php|json|js|jsx|css|scss|md)$/',
				array(
					'/' . preg_quote( 'wpify-plugin-skeleton', '/' ) . '/' => $this->slug( $slug ),
					'/' . preg_quote( 'WPIFY_PLUGIN_SKELETON', '/' ) . '/' => $this->underscored_uppercase( $slug ),
					'/' . preg_quote( 'WPify Plugin', '/' ) . '/'          => $this->spaced_camelcase( $slug ),
					'/' . preg_quote( 'Wpify Plugin', '/' ) . '/'          => $this->spaced_camelcase( $slug ),
					'/' . preg_quote( 'WPifyPluginSkeleton', '/' ) . '/'   => $this->camelcase( $slug ),
					'/' . preg_quote( 'WpifyPluginSkeleton', '/' ) . '/'   => $this->camelcase( $slug ),
					'/' . preg_quote( 'WPify_Plugin_Skeleton', '/' ) . '/' => $this->underscored_camelcase( $slug ),
					'/' . preg_quote( 'Wpify_Plugin_Skeleton', '/' ) . '/' => $this->underscored_camelcase( $slug ),
					'/' . preg_quote( 'wpify_plugin_skeleton', '/' ) . '/' => $this->underscored_lowercase( $slug ),
				),
				array(
					'/\deps\//',
					'/\vendor\//',
					'/\/node\_modules\//',
				)
			);

			rename( getcwd() . '/wpify-plugin-skeleton.php', getcwd() . '/' . $slug . '.php' );

			echo shell_exec( 'composer update' );
			echo shell_exec( 'npm install' );
			echo shell_exec( 'npm run build' );
			echo shell_exec( 'git init --initial-branch=master' );
			echo shell_exec( 'git add .' );
		}
	}

	/**
	 * @param string $path
	 * @param string $regex
	 * @param array $search_replace
	 * @param array $excludes
	 */
	private function replace_in_files(
		string $path,
		string $regex,
		array $search_replace = array(),
		array $excludes = array()
	) {
		$paths = $this->find_paths( $path, $regex, $excludes );

		foreach ( $paths as $path ) {
			$content = file_get_contents( $path );

			foreach ( $search_replace as $search => $replace ) {
				if ( preg_match( $search, $content ) ) {
					$content = preg_replace( $search, $replace, $content );
					file_put_contents( $path, $content );
				}
			}
		}
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
}
