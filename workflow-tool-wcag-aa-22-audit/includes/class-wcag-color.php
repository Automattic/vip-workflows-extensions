<?php
/** CSS color parsing and the WCAG 2.2 contrast ratio.
 * @package WorkflowToolWcagAa22Audit
 */
declare( strict_types=1 );
namespace WorkflowToolWcagAa22Audit;

/** Colors are arrays of [r, g, b, alpha]: channels 0-255 and alpha 0-1. */
final class WCAG_Color {
	private const NAMED = array(
		'black'         => '000000',
		'white'         => 'ffffff',
		'red'           => 'ff0000',
		'green'         => '008000',
		'blue'          => '0000ff',
		'yellow'        => 'ffff00',
		'orange'        => 'ffa500',
		'gray'          => '808080',
		'grey'          => '808080',
		'silver'        => 'c0c0c0',
		'maroon'        => '800000',
		'purple'        => '800080',
		'fuchsia'       => 'ff00ff',
		'magenta'       => 'ff00ff',
		'lime'          => '00ff00',
		'olive'         => '808000',
		'navy'          => '000080',
		'teal'          => '008080',
		'aqua'          => '00ffff',
		'cyan'          => '00ffff',
		'pink'          => 'ffc0cb',
		'gold'          => 'ffd700',
		'brown'         => 'a52a2a',
		'crimson'       => 'dc143c',
		'tomato'        => 'ff6347',
		'coral'         => 'ff7f50',
		'salmon'        => 'fa8072',
		'indigo'        => '4b0082',
		'violet'        => 'ee82ee',
		'beige'         => 'f5f5dc',
		'ivory'         => 'fffff0',
		'khaki'         => 'f0e68c',
		'lavender'      => 'e6e6fa',
		'darkgray'      => 'a9a9a9',
		'darkgrey'      => 'a9a9a9',
		'lightgray'     => 'd3d3d3',
		'lightgrey'     => 'd3d3d3',
		'dimgray'       => '696969',
		'dimgrey'       => '696969',
		'gainsboro'     => 'dcdcdc',
		'whitesmoke'    => 'f5f5f5',
		'darkred'       => '8b0000',
		'darkblue'      => '00008b',
		'darkgreen'     => '006400',
		'darkorange'    => 'ff8c00',
		'firebrick'     => 'b22222',
		'forestgreen'   => '228b22',
		'seagreen'      => '2e8b57',
		'skyblue'       => '87ceeb',
		'steelblue'     => '4682b4',
		'royalblue'     => '4169e1',
		'midnightblue'  => '191970',
		'slategray'     => '708090',
		'slategrey'     => '708090',
		'darkslategray' => '2f4f4f',
		'darkslategrey' => '2f4f4f',
		'lightblue'     => 'add8e6',
		'lightgreen'    => '90ee90',
		'lightyellow'   => 'ffffe0',
		'chocolate'     => 'd2691e',
		'tan'           => 'd2b48c',
		'wheat'         => 'f5deb3',
		'linen'         => 'faf0e6',
		'snow'          => 'fffafa',
		'aliceblue'     => 'f0f8ff',
		'azure'         => 'f0ffff',
		'honeydew'      => 'f0fff0',
		'mintcream'     => 'f5fffa',
		'ghostwhite'    => 'f8f8ff',
	);

	/**
	 * Parse a CSS color, resolving WordPress preset variables through the palette.
	 * Returns null for anything this audit cannot measure (currentColor, other custom properties, color-mix).
	 *
	 * @param string                $value   CSS value.
	 * @param array<string, string> $palette Preset slug => CSS color.
	 */
	public static function parse( string $value, array $palette = array(), int $depth = 0 ): ?array {
		$value = strtolower( trim( $value ) );
		if ( preg_match( '/^var\(\s*--wp--preset--color--([a-z0-9-]+)\s*(?:,\s*(.+))?\)$/', $value, $match ) ) {
			if ( $depth < 3 && isset( $palette[ $match[1] ] ) ) {
				return self::parse( $palette[ $match[1] ], $palette, $depth + 1 );
			}
			return isset( $match[2] ) && $depth < 3 ? self::parse( $match[2], $palette, $depth + 1 ) : null;
		}
		if ( preg_match( '/^var:preset\|color\|([a-z0-9-]+)$/', $value, $match ) ) {
			return $depth < 3 && isset( $palette[ $match[1] ] ) ? self::parse( $palette[ $match[1] ], $palette, $depth + 1 ) : null;
		}
		if ( 'transparent' === $value ) {
			return array( 0.0, 0.0, 0.0, 0.0 );
		}
		if ( isset( self::NAMED[ $value ] ) ) {
			$value = '#' . self::NAMED[ $value ];
		}
		if ( preg_match( '/^#([0-9a-f]{3,8})$/', $value, $match ) && in_array( strlen( $match[1] ), array( 3, 4, 6, 8 ), true ) ) {
			$hex = $match[1];
			if ( strlen( $hex ) <= 4 ) {
				$hex = implode( '', array_map( static fn( $c ) => $c . $c, str_split( $hex ) ) );
			}
			return array(
				(float) hexdec( substr( $hex, 0, 2 ) ),
				(float) hexdec( substr( $hex, 2, 2 ) ),
				(float) hexdec( substr( $hex, 4, 2 ) ),
				8 === strlen( $hex ) ? hexdec( substr( $hex, 6, 2 ) ) / 255 : 1.0,
			);
		}
		if ( preg_match( '/^(rgba?|hsla?)\(([^()]+)\)$/', $value, $match ) ) {
			$parts = preg_split( '/[\s,\/]+/', trim( $match[2] ) ) ?: array();
			if ( count( $parts ) < 3 || count( $parts ) > 4 ) {
				return null;
			}
			$alpha = isset( $parts[3] ) ? self::number( $parts[3], 1.0 ) : 1.0;
			if ( null === $alpha ) {
				return null;
			}
			if ( str_starts_with( $match[1], 'rgb' ) ) {
				$rgb = array();
				for ( $i = 0; $i < 3; $i++ ) {
					$channel = self::number( $parts[ $i ], 255.0 );
					if ( null === $channel ) {
						return null;
					}
					$rgb[] = $channel;
				}
				return array( $rgb[0], $rgb[1], $rgb[2], $alpha );
			}
			$hue        = is_numeric( preg_replace( '/deg$/', '', $parts[0] ) ) ? fmod( (float) preg_replace( '/deg$/', '', $parts[0] ), 360.0 ) : null;
			$saturation = self::number( str_ends_with( $parts[1], '%' ) ? $parts[1] : $parts[1] . '%', 1.0 );
			$lightness  = self::number( str_ends_with( $parts[2], '%' ) ? $parts[2] : $parts[2] . '%', 1.0 );
			if ( null === $hue || null === $saturation || null === $lightness ) {
				return null;
			}
			$hue = $hue < 0 ? $hue + 360 : $hue;
			$f   = static function ( float $n ) use ( $hue, $saturation, $lightness ): float {
				$k = fmod( $n + $hue / 30, 12 );
				return 255 * ( $lightness - $saturation * min( $lightness, 1 - $lightness ) * max( -1, min( $k - 3, 9 - $k, 1 ) ) );
			};
			return array( $f( 0 ), $f( 8 ), $f( 4 ), $alpha );
		}
		return null;
	}

	/** A channel or alpha: a bare number in [0, $scale] or a percentage of $scale. */
	private static function number( string $part, float $scale ): ?float {
		if ( preg_match( '/^(-?\d*\.?\d+)%$/', $part, $match ) ) {
			return max( 0.0, min( $scale, (float) $match[1] / 100 * $scale ) );
		}
		return is_numeric( $part ) ? max( 0.0, min( $scale, (float) $part ) ) : null;
	}

	/** Composite a translucent color over an opaque one. */
	public static function over( array $top, array $bottom ): array {
		$alpha = $top[3];
		return array(
			$top[0] * $alpha + $bottom[0] * ( 1 - $alpha ),
			$top[1] * $alpha + $bottom[1] * ( 1 - $alpha ),
			$top[2] * $alpha + $bottom[2] * ( 1 - $alpha ),
			1.0,
		);
	}

	/** WCAG 2.2 relative luminance. */
	public static function luminance( array $color ): float {
		$linear = array_map(
			static function ( float $channel ): float {
				$channel /= 255;
				return $channel <= 0.04045 ? $channel / 12.92 : ( ( $channel + 0.055 ) / 1.055 ) ** 2.4;
			},
			array_slice( $color, 0, 3 )
		);
		return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
	}

	/** WCAG 2.2 contrast ratio, 1 to 21. */
	public static function ratio( array $a, array $b ): float {
		$lighter = max( self::luminance( $a ), self::luminance( $b ) );
		$darker  = min( self::luminance( $a ), self::luminance( $b ) );
		return ( $lighter + 0.05 ) / ( $darker + 0.05 );
	}

	public static function hex( array $color ): string {
		return sprintf( '#%02x%02x%02x', (int) round( $color[0] ), (int) round( $color[1] ), (int) round( $color[2] ) );
	}
}
