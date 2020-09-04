<?php
/*
	Responsive Pics is a Wordpress tool for resizing images on the fly.
	It uses a concise syntax for determining the image sizes you need in your template.
	You can define number of columns, aspect ratios and crop settings.
	It handles @2x images and missing breakpoints automatically.

	syntax    : ResponsivePics::get[_background](id, 'breakpoint:width [/factor|height]|crop_x crop_y, …', 'class-name', lazyload, intrinsic);

	breakpoint: a number or a key in $breakpoints (e.g. "xs")
				if not defined, and width is a number, breakpoint will be the same as the width
				if not defined, and width is a column definition, breakpoint will be the corresponding breakpoint
				(e.g. if width is "xs-8", breakpoint will be "xs")
	width     : a number or a column definition
				a column definition is a key in $grid_widths plus a dash and a column span number (e.g. "xs-8")
				if column span number is "full", the full width of the next matching $breakpoint is used (e.g. "xs-full")
	height    : a number in pixels
	factor    : a factor of width
	crop_x    : t(op), r(ight), b(ottom), l(eft) or c(enter),
	crop_y    : t(op), r(ight), b(ottom), l(eft) or c(enter)
				if crop_y is not defined, crop_x will be treated as a shortcut:
				"c" = "center center", "t" = "top center", r = "right center", "b" = "center bottom", "l" = "left center"
	class-name: a class name to add to the html element
	lazyload  : (boolean, default: false) if true:
				- adds a 'lazyload' class to the picture img element
				- swaps the 'src' with 'data-src' attributes on the picture source elements
				- this will enable you to use a lazy loading plugin such as Lazysizes: https://github.com/aFarkas/lazysizes
	intrinsic : (boolean, default: false) if true:
				- adds an 'intrinsic' class to the picture element and a 'intrinsic__item' class to the picture img element
				- adds 'data-aspectratio' attributes on the picture source and img elements
				- this will enable you to pre-occupy the space needed for an image by calculating the height from the image width or the width from the height
				  with an intrinsic plugin such as the lazysizes aspectratio extension

	API

	ResponsivePics::setColumns(number):    set number of grid columns
	ResponsivePics::setGutter(pixels):     set grid gutter width
	ResponsivePics::setGridWidths(array):  set grid widths for various breakpoints, example:
		[
			'xs' => 540,
			'sm' => 720,
			'md' => 960,
			'lg' => 1140,
			'xl' => 1140
		]
	ResponsivePics::setBreakpoints(array): set breakpoints, example:
		[
			'xs' => 0,
			'sm' => 576,
			'md' => 768,
			'lg' => 992,
			'xl' => 1200
		]

	ResponsivePics::setLazyLoadClass(string): set lazyload classname

	examples  : ResponsivePics::get(1, 'xs-12, sm-6, md-4');
				ResponsivePics::get(1, 'xs-12 300, sm-6 400, md-4 500');
				ResponsivePics::get(1, '400:200 300, 800:400 600', 'my-picture');
				ResponsivePics::get(1, '400:200 200|c, 800:400 400|l t');
				ResponsivePics::get(1, 'xs-full|c, sm-12/0.5|c, md-12/0.25|c');

				ResponsivePics::get_background(1, 'xs:200 200|c, lg:400 400');


	Javascript dependencies:

				A responsive image polyfill such as Picturefill:
				http://scottjehl.github.io/picturefill/

				A lazy loader for images such as Lazysizes:
				https://github.com/aFarkas/lazysizes

				import 'picturefill';
				import 'lazysizes';
				import 'lazysizes/plugins/aspectratio/ls.aspectratio.js';

	TO DO'S:
	* Support for multiple background images
*/


// exit if accessed directly
if (!defined('ABSPATH') ) exit;

// check if class already exists
if (!class_exists('ResponsivePics')) {

	class ResponsivePics {
		private static $columns = null;
		private static $gutter = null;
		private static $grid_widths = null;
		private static $breakpoints = null;
		private static $lazyload_class = null;
		private static $image_quality = null;

		// map short letters to valid crop values
		private static $crop_map = [
			'c' => 'center',
			't' => 'top',
			'r' => 'right',
			'b' => 'bottom',
			'l' => 'left'
		];

		// some handy shortcuts
		private static $crop_shortcuts = [
			'c' => 'c c',
			't' => 'c t',
			'r' => 'r c',
			'b' => 'c b',
			'l' => 'l c'
		];

		// only resizes images with the following mime types
		private static $supported_mime_types = [
			'image/jpeg',
			'image/png',
			'image/gif'
		];

		// keeps track of added image IDs
		private static $id_map = [];

		// print error message
		private static function show_error($message) {
			$error = sprintf('<pre>%s error: %s</pre>', get_class(), $message);

			echo $error;
		}

		// calculates column shortcut (e.g. "xs-5") to actual pixels
		private static function columns_to_pixels($input) {
			$components = explode('-', $input);

			$key = $components[0];
			$col = $components[1];

			if (self::contains($col, '/')) {
				$col = explode('/', $col)[0];
			}

			if ($col === 'full') {
				$next_breakpoint = self::get_next_breakpoint($key);

				// use max breakpoint if there's no bigger one left
				if (!isset($next_breakpoint)) {
					$next_breakpoint = $key;
				}

				$next_width = self::$breakpoints[$next_breakpoint];

				if (!isset($next_width)) {
					self::show_error(sprintf('no breakpoint set for "%s"', $key));
				}

				return $next_width;
			} else if (self::match($col, '/(\d+)/')) {
				if ($col < 1 || $col > self::$columns) {
					self::show_error(sprintf('number of columns should be between 1 and %s', self::$columns));
				}
			} else {
				self::show_error(sprintf('invalid columns: %s', $col));
			}

			$grid_width = self::$grid_widths[$key];

			if (!isset($grid_width)) {
				self::show_error(sprintf('no width found for breakpoint "%s"', $key));
			}

			$column_pixels = ($grid_width - (self::$columns) * self::$gutter) / self::$columns;

			$pixels = floor($column_pixels * $col + self::$gutter * ($col - 1));

			return $pixels;
		}

		private static function get_next_breakpoint($key) {
			$previous_breakpoint = null;

			foreach (self::$breakpoints as $breakpoint_key => $breakpoint_value) {
				if ($previous_breakpoint === $key) {
					return $breakpoint_key;
				}

				$previous_breakpoint = $breakpoint_key;
			}

			return null;
		}

		// returns true if a string contains a substring
		private static function contains($haystack, $needle) {
			return strpos($haystack, $needle) !== false;
		}

		// returns regex match of string
		private static function match($string, $regex) {
			$found = preg_match($regex, $string, $matches);

			return $found ? $matches[0] : null;
		}

		// breakpoint can be shortcut (e.g. "xs") or number
		private static function process_breakpoint($input) {
			$input = trim($input);

			if (isset(self::$breakpoints[$input])) {
				return self::$breakpoints[$input];
			} else {
				return $input;
			}

			return false;
		}

		// dimensions can be shortcut (e.g. "xs-5"), width (e.g. "400") or width and height (e.g. "400 300");
		private static function process_dimensions($input) {
			$dimensions = trim($input);
			$width      = -1;
			$height     = -1;
			$crop       = false;
			$crop_ratio = null;

			if (self::contains($dimensions, '-')) {
				if (self::contains($dimensions, ' ')) {
					// width and height supplied
					$wh        = explode(' ', $dimensions);
					$dimension = trim($wh[0]);
					$height    = trim($wh[1]);
					$width     = self::columns_to_pixels($dimension);
				} else {
					$width = self::columns_to_pixels($dimensions);
				}
			} else {
				if (self::contains($dimensions, ' ')) {
					// width and height supplied
					$wh     = explode(' ', $dimensions);
					$width  = trim($wh[0]);
					$height = trim($wh[1]);
				} else {
					// height will be calculated based on width
					$width = self::match($dimensions, '/(\d+)/');

					if (!isset($width)) {
						self::show_error('width undefined: ' . $dimensions);
					}
				}
			}

			if (self::contains($dimensions, '/')) {
				// height is a specified factor of weight
				$wh         = explode('/', $dimensions);
				$crop_ratio = (float) trim($wh[1]);

				if (self::process_ratio($crop_ratio)) {
					$height = $width * $crop_ratio;
				}
			}

			return [
				'input'      => $input,
				'width'      => (int) $width,
				'height'     => (int) $height,
				'crop_ratio' => $crop_ratio
			];
		}

		// check if ratio is a number and between reasonable values 0-2
		private static function process_ratio($ratio) {
			if (is_numeric($ratio) && (0 < $ratio) && ($ratio <= 2)) {
				return true;
			} else {
				self::show_error(sprintf('the crop ratio %d needs to be higher then 0 and equal or lower then 2', $ratio));
				return false;
			}
		}

		// crop values can be single shortcut values (e.g. "c") or two dimensional values (e.g. "l t");
		private static function process_crop($input) {
			if ($input === false) {
				return false;
			}

			$shortcuts = explode(' ', trim($input));

			if (sizeof($shortcuts) === 1) {
				$shortcuts = self::$crop_shortcuts[$shortcuts[0]];
				$shortcuts = explode(' ', trim($shortcuts));
			}

			$result = [];

			foreach($shortcuts as $key => $value) {
				$result[] = self::$crop_map[$value];
			}

			return $result;
		}

		// get breakpoint name and number of columns
		private static function get_breakpoint_from_rule($rule) {
			if (!self::contains($rule, ':')) {
				if (self::contains($rule, '-')) {
					$components = explode('-', $rule);

					return [
						'breakpoint' => trim($components[0]),
						'columns'    => trim($components[1]) // can be 'full'
					];
				}
			}

			return null;
		}

		// sort defined breakpoints from small to wide
		private static function sort_by_breakpoint_index($a, $b) {
			$index_a = array_search($a['breakpoint'], array_keys(self::$breakpoints));
			$index_b = array_search($b['breakpoint'], array_keys(self::$breakpoints));

			return $index_a > $index_b;
		}

		// add missing breakpoints
		private static function add_missing_breakpoints($variants) {
			$result = [];
			$defined_breakpoints = [];
			$found_custom_breakpoints = false;

			// collect defined breakpoints into array
			foreach ($variants as $variant) {
				$breakpoint = self::get_breakpoint_from_rule($variant);
				$breakpoint_name = null;

				if (isset($breakpoint)) {
					$breakpoint_name = $breakpoint['breakpoint'];
				}

				if (isset($breakpoint) && isset(self::$breakpoints[$breakpoint_name])) {
					$defined_breakpoints[$breakpoint_name] = $breakpoint;
				} else {
					$found_custom_breakpoints = true;
				}
			}

			// we've also found custom breakpoints, so cancel adding missing breakpoints
			if ($found_custom_breakpoints) {
				return $variants;
			}

			uasort($defined_breakpoints, ['self', 'sort_by_breakpoint_index']);

			// add missing smaller breakpoints
			foreach (self::$breakpoints as $breakpoint_key => $breakpoint_value) {
				if (!isset($defined_breakpoints[$breakpoint_key])) {
					$result[] = $breakpoint_key . '-12';
				} else {
					break;
				}
			}

			// now loop again through original rules
			foreach ($variants as $variant) {
				$breakpoint = self::get_breakpoint_from_rule($variant);

				if (isset($breakpoint)) {
					$next_breakpoint = self::get_next_breakpoint($breakpoint['breakpoint']);
					$columns = $breakpoint['columns'];

					if (self::contains($columns, '/')) {
						$columns = explode('/', $columns)[0];
					}

					$columns = trim($columns);

					// add in-between breakpoints if we haven't defined them explicitly yet
					while ($next_breakpoint && !isset($defined_breakpoints[$next_breakpoint])) {
						if ($columns === 'full' || ($columns !== 'full' && isset(self::$grid_widths[$next_breakpoint]))) {
							$result[] = sprintf('%s-%s', $next_breakpoint, $breakpoint['columns']);
						}

						$next_breakpoint = self::get_next_breakpoint($next_breakpoint);
					}
				}

				$result[] = $variant;
			}

			return $result;
		}

		// this processes our resizing syntax with art direction support and returns a normalized array with resizing rules
		private static function get_art_image_rules($input, $reverse = false) {
			$variants = self::add_missing_breakpoints(explode(',', $input));
			$result   = [];

			foreach ($variants as $variant) {
				$crop       = false;
				$breakpoint = -1;
				$dimensions = [
					'width'  => -1,
					'height' => -1,
					'crop'   => false
				];

				// get crop positions
				if (self::contains($variant, '|')) {
					$components = explode('|', $variant);
					$variant    = trim($components[0]);
					$crop       = self::process_crop($components[1]);
				}

				// get dimensions
				if (self::contains($variant, ':')) {
					$components = explode(':', $variant);
					$breakpoint = self::process_breakpoint($components[0]);
					$dimensions = self::process_dimensions($components[1]);
				} else {
					$dimensions = self::process_dimensions($variant);
				}

				$width      = $dimensions['width'];
				$height     = $dimensions['height'];
				$crop_ratio = $dimensions['crop_ratio'];

				if ($breakpoint === -1) {
					if (self::contains($dimensions['input'], '-')) {
						// use breakpoint based on defined column size
						$components = explode('-', $dimensions['input']);
						$bp         = trim($components[0]);
						$breakpoint = self::$breakpoints[$bp];
					} else {
						// use breakpoint based on width
						$breakpoint = $width;
					}
				}

				$result[] = [
					'breakpoint' => $breakpoint,
					'width'      => $width,
					'height'     => $height,
					'crop_ratio' => $crop_ratio,
					'crop'       => $crop
				];
			}

			usort($result, ['self', $reverse ? 'sort_by_breakpoint_reverse' : 'sort_by_breakpoint']);

			return $result;
		}

		// this processes our resizing syntax and returns a normalized array with resizing rules
		private static function get_image_rules($input, $reverse = false, $img_crop = null) {
			$variants = self::add_missing_breakpoints(explode(',', $input));
			$result   = [];

			foreach ($variants as $variant) {
				$crop       = false;
				$crop_ratio = null;
				$breakpoint = -1;
				$dimensions = [
					'width'  => -1,
					'height' => -1,
					'crop'   => false
				];

				// check for height and/or crops syntax
				$variant = trim($variant);
				if (self::contains($variant, ' ') || self::contains($variant, '|') || self::contains($variant, '/')) {
					self::show_error(sprintf('art directed parameters (height, factor, crop_x, crop_y) are not supported on image sizes: %s', $variant));
				}

				// get global img crop positions
				if ($img_crop) {
					// check if crop position is set
					if (self::contains($img_crop, '|')) {
						$components = explode('|', $img_crop);
						$ratio      = (float) trim($components[0]);

						// check if ratio is within range
						if (self::process_ratio($ratio)) {
							$crop     = self::process_crop($components[1]);
							$variant .= '/'. $ratio;
						}
					// add default crop position
					} else {
						$ratio = (float) $img_crop;

						// check if ratio is within range
						if (self::process_ratio($ratio)) {
							$crop     = self::process_crop($components[1]);
							$variant .= '/'. $ratio . '|c';
						}
					}
				}

				// get dimensions
				if (self::contains($variant, ':')) {
					$components = explode(':', $variant);
					$breakpoint = self::process_breakpoint($components[0]);
					$dimensions = self::process_dimensions($components[1]);
				} else {
					$dimensions = self::process_dimensions($variant);
				}

				$width      = $dimensions['width'];
				$height     = $dimensions['height'];
				$crop_ratio = $dimensions['crop_ratio'];

				if ($breakpoint === -1) {
					if (self::contains($dimensions['input'], '-')) {
						// use breakpoint based on defined column size
						$components = explode('-', $dimensions['input']);
						$bp         = trim($components[0]);
						$breakpoint = self::$breakpoints[$bp];
					} else {
						// use breakpoint based on width
						$breakpoint = $width;
					}
				}

				$result[] = [
					'breakpoint' => $breakpoint,
					'width'      => $width,
					'height'     => $height,
					'crop_ratio' => $crop_ratio,
					'crop'       => $crop
				];
			}

			usort($result, ['self', $reverse ? 'sort_by_breakpoint_reverse' : 'sort_by_breakpoint']);

			return $result;
		}

		// sort breakpoints from small to wide
		private static function sort_by_breakpoint($a, $b) {
			return $a['breakpoint'] < $b['breakpoint'];
		}

		// sort breakpoints from wide to small
		private static function sort_by_breakpoint_reverse($a, $b) {
			return $b['breakpoint'] < $a['breakpoint'];
		}

		private static function get_resized_suffix($width, $height, $ratio, $crop) {
			if ($ratio === 1) {
				$ratio_indicator = '';
			} else {
				$ratio_indicator = '@' . $ratio . 'x';
			}

			if ($crop === false) {
				$crop_indicator = '';
			} else {
				$crop_indicator = '-' . implode('-', $crop);
			}

			// note: actual dimensions can be different from the dimensions appended to the filename, but we don't know those before we actually resize
			$suffix = sprintf('%sx%s%s%s', (int)$width, (int)$height, $crop_indicator, $ratio_indicator);

			return $suffix;
		}

		// creates a resized file if it doesn't exist and returns the final image url
		private static function get_resized_url($id, $file_path, $original_url, $width, $height, $crop, $ratio = 1) {
			$suffix            = self::get_resized_suffix($width, $height, $ratio, $crop);
			$path_parts        = pathinfo($file_path);
			$resized_file_path = join(DIRECTORY_SEPARATOR, [$path_parts['dirname'], $path_parts['filename'] . '-' . $suffix . '.' . $path_parts['extension']]);
			$resized_url       = join(DIRECTORY_SEPARATOR, [dirname($original_url), basename($resized_file_path)]);
			$resize_request    = [
				'id'          => (int) $id,
				'quality'     => (int) self::$image_quality,
				'width'       => (int) $width,
				'height'      => (int) $height,
				'crop'        => $crop,
				'ratio'       => (int) $ratio
			];

			// if image size does not exist yet as filename
			if (!file_exists($resized_file_path)) {
				$is_pending = self::is_scheduled_action($resize_request, $id);

				// if image size is not a pending request
				if (!$is_pending) {
					as_schedule_single_action(time(), 'process_resize_request', $resize_request, 'process_resize_request_' . $id);
				}

				return;
			} else {
				return $resized_url;
			}
		}

		// check if resize requerst is not a pending scheduled action
		private static function is_scheduled_action($request, $id) {
			$scheduled = as_get_scheduled_actions([
				'group'    => 'process_resize_request_' . $id,
				'status'   => ActionScheduler_Store::STATUS_PENDING,
				'per_page' => -1
			]);

			foreach ($scheduled as $action) {
				// Get protected value from object
				$reflection = new ReflectionClass($action);
				$property   = $reflection->getProperty('args');
				$property->setAccessible(true);
				$action_args = $property->getValue($action);

				if ($action_args === $request) {
					return true;
				}
			}

			return false;
		}

		// check if png file has transparent background
		private static function is_alpha_png($fn) {
			return (ord(@file_get_contents($fn, NULL, NULL, 25, 1)) === 6);
		}

		// check if gif file is animated
		private static function is_gif_ani($fn) {
			if (!($fh = @fopen($fn, 'rb')))
				return false;

			$count = 0;
			while (!feof($fh) && $count < 2) {
				$chunk = fread($fh, 1024 * 100);
				$count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $chunk, $matches);
			}

			fclose($fh);
			return $count > 1;
		}

		// returns a normalized definition of breakpoints
		private static function get_definition($id, $sizes, $reverse = false, $art_direction = true, $img_crop = null) {
			$url       = wp_get_attachment_url($id);
			$file_path = get_attached_file($id);

			if (!$file_path) {
				self::show_error(sprintf('file does not exist for id %s', $id));
			}

			if (!$url) {
				self::show_error(sprintf('url does not exist for id %s', $id));
			}

			$mime_type       = get_post_mime_type($id);
			$alt             = get_post_meta($id, '_wp_attachment_image_alt', true);
			$alpha           = false;
			$animated        = false;

			// check if png has alpha channel
			if ($mime_type === 'image/png') {
				$alpha = self::is_alpha_png($file_path);
			}

			// check if gif is animated
			if ($mime_type === 'image/gif') {
				$animated = self::is_gif_ani($file_path);
			}

			// unsupported mime-type, return original source without breakpoints
			if (!in_array($mime_type, self::$supported_mime_types) || $animated) {
				return [
					'sources' => [[
						'source1x' => $url,
						'ratio'    => 1
					]],
					'mimetype' => $mime_type,
					'alt'      => $alt,
					'alpha'    => $alpha
				];
			}

			$meta_data       = wp_get_attachment_metadata($id);
			$original_width  = $meta_data['width'];
			$original_height = $meta_data['height'];
			$rules           = $art_direction ? self::get_art_image_rules($sizes, $reverse) : self::get_image_rules($sizes, $reverse, $img_crop);
			$sources         = [];

			$addedSource     = false;
			$min_breakpoint  = null;

			if (!$original_width || !$original_height) {
				self::show_error(sprintf('no dimensions for file id %s', $id));
			}

			foreach ($rules as $rule) {
				$width      = $rule['width'];
				$height     = $rule['height'];
				$crop       = $rule['crop'];
				$crop_ratio = $rule['crop_ratio'];

				if ($height === -1) {
					// calculate height based on original aspect ratio
					$height = floor($original_height / $original_width * $width);
				}

				if ($width < $original_width && $height < $original_height) {
					// we can safely resize
					$resized_url = self::get_resized_url($id, $file_path, $url, $width, $height, $crop);

					if ($resized_url) {
						$source1x    = $resized_url;
						$source2x    = null;

						if ($width * 2 < $original_width && $height * 2 < $original_height) {
							// we can also resize for @2x
							$resized_2x_url = self::get_resized_url($id, $file_path, $url, $width, $height, $crop, 2);
							$source2x       = $resized_2x_url ? $resized_2x_url : null;
						}

						$breakpoint = $rule['breakpoint'];

						if ($breakpoint < $min_breakpoint || !isset($min_breakpoint)) {
							$min_breakpoint = $breakpoint;
						}

						$sources[] = [
							'breakpoint' => $breakpoint,
							'source1x'   => $source1x,
							'source2x'   => $source2x,
							'width'      => $width,
							'height'     => $height,
							'ratio'      => $width / $height
						];

						$addedSource = true;
					}

				} else {
					// Use original image to resize and crop
					$ratio = $original_width / $original_height;

					if ($crop_ratio) {
						$cropped_height  = $original_width * $crop_ratio;
						$cropped_width   = $original_width;
						$ratio           = $cropped_width / $cropped_height;

						// check if new height will be enough to get the right aspect ratio
						if ($cropped_height > $original_height) {
							$cropped_height = $original_height;
							$cropped_width  = $original_height * $ratio;
						}

						$resized_url = self::get_resized_url($id, $file_path, $url, $cropped_width, $cropped_height, $crop);
					}

					$source1x   = isset($resized_url) ? $resized_url : $url;
					$source2x   = null;
					$breakpoint = $rule['breakpoint'];

					if ($breakpoint < $min_breakpoint || !isset($min_breakpoint)) {
						$min_breakpoint = $breakpoint;
					}

					$sources[] = [
						'breakpoint' => $breakpoint,
						'source1x'   => $source1x,
						'source2x'   => $source2x,
						'width'      => $crop_ratio ? $cropped_width : $original_height,
						'height'     => $crop_ratio ? $cropped_height : $original_width,
						'ratio'      => $ratio
					];

					$addedSource = true;
				}
			}

			if (!$addedSource) {
				// add original source if no sources have been found so far
				$sources[] = [
					'source1x' => $url,
					'width'    => $original_width,
					'height'   => $original_height,
					'ratio'    => $original_width / $original_height
				];
			} else if ($min_breakpoint != 0) {
				// add minimum breakpoint if it doesn't exist (otherwise there will be no image)
				$minimum_breakpoint = [
					'breakpoint' => 0,
					'source1x'   => $url,
					'width'      => $original_width,
					'height'     => $original_height,
					'ratio'      => $original_width / $original_height
				];

				if ($reverse) {
					array_unshift($sources, $minimum_breakpoint);
				} else {
					array_push($sources, $minimum_breakpoint);
				}
			}

			return [
				'sources'  => $sources,
				'alt'      => $alt,
				'mimetype' => $mime_type,
				'alpha'    => $alpha
			];
		}

		// get a css rule for targeting high dpi screens
		private static function get_media_query_2x($breakpoint) {
			// apparently this targets high dpi screens cross-browser
			return sprintf('@media only screen and (-webkit-min-device-pixel-ratio: 2) and (min-width: %spx), only screen and (min-resolution: 192dpi) and (min-width: %spx)', $breakpoint, $breakpoint);
		}



		/**************
		 *            *
		 *   PUBLIC   *
		 *            *
		 **************/

		public function __construct() {
			self::init();
		}

		public static function init() {
			self::setColumns();
			self::setGutter();
			self::setGridWidths();
			self::setBreakpoints();
			self::setLazyLoadClass();
			self::setImageQuality();

			add_action('process_resize_request', array('ResponsivePics', 'process_resize_request'), 10, 6);
		}

		// set number of grid columns
		public static function setColumns($value = 12) {
			self::$columns = $value;
		}

		// set grid gutter width, in pixels
		public static function setGutter($value = 20) {
			self::$gutter = $value;
		}

		// breakpoints used for "media(min-width: x)" in picture element, in pixels
		public static function setBreakpoints($value = [
			'xs'    => 0,
			'sm'    => 576,
			'md'    => 768,
			'lg'    => 992,
			'xl'    => 1200,
			'xxl'   => 1400,
			'xxxl'  => 1600
		]) {
			self::$breakpoints = $value;
		}

		// grid system should match the container widths in css
		public static function setGridWidths($value = [
			'xs'    => 576, // self::$breakpoints['sm']
			'sm'    => 540,
			'md'    => 720,
			'lg'    => 960,
			'xl'    => 1140,
			'xxl'   => 1140,
			'xxxl'  => 1140
		]) {
			self::$grid_widths = $value;
		}

		// set lazyload classname
		public static function setLazyLoadClass($value = 'lazyload') {
			self::$lazyload_class = $value;
		}

		// set lazyload classname
		public static function setImageQuality($value = 90) {
			self::$image_quality = $value;
		}

		// get breakpoints used for "media(min-width: x)" in picture element, in pixels
		public static function getBreakpoints() {
			return self::$breakpoints;
		}

		// get the current set of container widths
		public static function getGridWidths() {
			return self::$grid_widths;
		}

		// get number of grid columns
		public static function getColumns() {
			return self::$columns;
		}

		// get grid gutter width, in pixels
		public static function getGutter() {
			return self::$gutter;
		}

		// get lazyload classname
		public static function getLazyLoadClass() {
			return self::$lazyload_class;
		}

		// get image quality
		public static function getImageQuality() {
			return self::$image_quality;
		}

		// process the scheduled resize action
		public static function process_resize_request($id, $quality, $width, $height, $crop, $ratio) {
			$file_path   = get_attached_file($id);
			$path_parts  = pathinfo($file_path);
			$suffix      = self::get_resized_suffix($width, $height, $ratio, $crop);
			$resize_path = join(DIRECTORY_SEPARATOR, [$path_parts['dirname'], $path_parts['filename'] . '-' . $suffix . '.' . $path_parts['extension']]);
			$wp_editor   = wp_get_image_editor($file_path);

			// Check if image exists
			if (!file_exists($resize_path)) {
				if (!is_wp_error($wp_editor)) {
					$wp_editor->set_quality($quality);
					$wp_editor->resize($width * $ratio, $height * $ratio, $crop);
					$wp_editor->save($resize_path);

				} else {
					syslog(LOG_ERR, sprintf('error resizing image "%s"', $resize_path));
				}
			}
		}

		/*
		 * Alias old `get` function to `get_picture`
		 */
		public static function get($id, $sizes, $picture_classes = null, $lazyload = false, $intrinsic = false) {
			return self::get_picture($id, $sizes, $picture_classes, $lazyload, $intrinsic);
		}

		/*
		 * Construct a responsive picture element
		 * returns <picture> element as html markup
		 */
		public static function get_picture($id, $sizes, $picture_classes = null, $lazyload = false, $intrinsic = false) {
			if (!isset($id)) {
				return 'image id undefined';
			}

			$definition  = self::get_definition($id, $sizes);

			if (!$definition) {
				return 'no image found with id ' . $id;
			}

			$sources = $definition['sources'];
			$picture = [];

			// convert $picture_classes to array if it is a string
			if (!is_array($picture_classes)) {
				if (!empty($picture_classes)) {
					$picture_classes = preg_split('/[\s,]+/', $picture_classes);
				} else {
					$picture_classes = [];
				}
			}

			$img_classes   = [];

			// lazyload option
			if ($lazyload) {
				$img_classes[] = self::$lazyload_class;
			}

			// exclude unsupported mime types from intrinsic
			if ($intrinsic && !in_array($definition['mimetype'], self::$supported_mime_types)) {
				$intrinsic = false;
			}

			// set intrinsic classes
			if ($intrinsic) {
				$picture_classes[] = 'intrinsic';
				$img_classes[]     = 'intrinsic__item';

				if ($definition['alpha']) {
					$img_classes[] = 'has-alpha';
				}
			}

			$picture[] = sprintf('<picture%s>', $picture_classes ? ' class="' . implode(' ', $picture_classes) . '"' : '');

			$src_attribute = $lazyload ? 'data-srcset' : 'srcset';
			$classes = $img_classes ? ' class="' . implode(' ', $img_classes) . '"' : '';

			// add all sources
			foreach ($sources as $source) {
				$data_aspectratio = $intrinsic ? ' data-aspectratio="' . $source['ratio'] . '"' : '';

				if (isset($source['breakpoint'])) {
					$urls = $source['source1x'];

					if (isset($source['source2x'])) {
						$urls .= ' 1x, ' . $source['source2x'] . ' 2x';
					}

					$picture[] = sprintf('  <source media="(min-width: %spx)" %s="%s"%s />', $source['breakpoint'], $src_attribute, $urls, $data_aspectratio);
				} else {
					$picture[] = sprintf('  <source %s="%s"%s />', $src_attribute, $source['source1x'], $data_aspectratio);
				}
			}

			// transparent gif
			$style     = $intrinsic ? ' style="width:100%;"' : '';
			$ratio     = $intrinsic ? ' data-aspectratio=""' : '';
			$picture[] = sprintf('  <img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw=="%s alt="%s"%s%s />', $ratio, $definition['alt'], $classes, $style);
			$picture[] = '</picture>';

			return implode("\n", $picture) . "\n";
		}


		/*
		 * Construct a responsive image element
		 * returns <img> element as html markup
		 */
		public static function get_image($id, $sizes, $crop = false, $img_classes = null, $lazyload = false) {
			if (!isset($id)) {
				return 'image id undefined';
			}

			$definition  = self::get_definition($id, $sizes, false, false, $crop);

			if (!$definition) {
				return 'no image found with id ' . $id;
			}

			$sources = $definition['sources'];

			// convert $picture_classes to array if it is a string
			if (!is_array($img_classes)) {
				if (!empty($img_classes)) {
					$img_classes = preg_split('/[\s,]+/', $img_classes);
				} else {
					$img_classes = [];
				}
			}

			// lazyload option
			if ($lazyload) {
				$img_classes[] = self::$lazyload_class;
			}

			$src_attribute = $lazyload ? 'data-srcset' : 'srcset';
			$classes = $img_classes ? ' class="' . implode(' ', $img_classes) . '"' : '';

			// add all sources & sizes
			$srcsets  = [];
			$sizes    = [];
			$full_img = wp_get_attachment_image_src($id, 'full', false);
			$fallback = ' src="'. $full_img[0] . '"';

			foreach ($sources as $source) {
				$srcsets[] = $source['source1x'] . ' ' . $source['width'] . 'w';
				if (isset($source['source2x'])) {
					$srcsets[] = $source['source2x'] . ' ' . ($source['width'] * 2) . 'w';
				}

				if (isset($source['breakpoint'])) {
					$sizes[] = '(min-width: '. $source['breakpoint'] .'px) '. $source['width'] . 'px';
				} else {
					$sizes[] = $source['width'] . 'px';
				}
			}

			// add fallback size
			$sizes[] = '100vw';

			// construct image
			$image = sprintf('<img%s %s="%s" sizes="%s"%s alt="%s"/>', $classes, $src_attribute, implode(', ', $srcsets), implode(', ', $sizes), $fallback, $definition['alt']);
			return $image;
		}

		/*
		 * Construct a background image element and a matching responsive inline style element
		 *
		 * Returns an inline <style> element with a dedicated image class with media-queries for all the different image sizes
		 * and an div with the same dedicated image class
		 */
		public static function get_background($id, $sizes, $classes = null) {
			if (!isset($id)) {
				return 'image id undefined';
			}

			// Check for multiple background images
			if (is_array($id)) {
				// temp solution
				$definition = self::get_definition($id[0], $sizes, true);
			} else {
				$definition = self::get_definition($id, $sizes, true);
			}

			if (!$definition) {
				return 'no image found with id ' . $id;
			}

			$sources = $definition['sources'];

			$copy = $id;

			// convert $classes to array if it is a string
			if (!is_array($classes)) {
				if (!empty($classes)) {
					$classes = preg_split('/[\s,]+/', $classes);
				} else {
					$classes = [];
				}
			}

			// prevent same id, append copy number to existing
			if (isset(self::$id_map[$id])) {
				self::$id_map[$id]++;
				$copy .= '-' . self::$id_map[$id];
			} else {
				self::$id_map[$id] = 0;
			}

			$id = sprintf('responsive-pics-background-%s', $copy);

			$background = [];
			$background[] = '<style scoped="scoped" type="text/css">';

			// add all sources as background-images
			foreach ($sources as $source) {
				if (isset($source['breakpoint'])) {
					$sources = $source['source1x'];

					$background[] = sprintf('  @media (min-width: %spx) {', $source['breakpoint']);
					$background[] = sprintf('  #%s {background-image: url("%s");}', $id, $source['source1x']);
					$background[] = '  }';

					if (isset($source['source2x'])) {
						$background[] = sprintf('  %s {', self::get_media_query_2x($source['breakpoint']));
						$background[] = sprintf('  #%s {background-image: url("%s");}', $id, $source['source2x']);
						$background[] = '  }';
					}
				} else {
					$background[] = sprintf('  #%s {background-image: url("%s");}', $id, $source['source1x']);
				}
			}

			$background[] = '  }';
			$background[] = '</style>';
			$background[] = sprintf('<div%s id="%s"></div>', $classes ? ' class="' . implode(' ', $classes) . '"' : '', $id);

			return implode("\n", $background) . "\n";
		}

		/*
		 * Construct a sources array
		 *
		 * Returns an array with all the available image sources and classes for the request
		 */
		public static function get_sources($id, $sizes, $classes = null, $lazyload = false) {
			if (!isset($id)) {
				return 'image id undefined';
			}

			$definition  = self::get_definition($id, $sizes);
			if (!$definition) {
				return 'no image found with id ' . $id;
			}

			$sources = $definition['sources'];

			// convert $classes to array if it is a string
			if (!is_array($classes)) {
				if (!empty($classes)) {
					$classes = preg_split('/[\s,]+/', $classes);
				} else {
					$classes = [];
				}
			}

			// lazyload option
			if ($lazyload) {
				$classes[] = self::$lazyload_class;
			}

			$properties = [
				'alt'     => $definition['alt'],
				'classes' => $classes
			];

			// add all sources
			foreach ($sources as $source) {
				if (isset($source['breakpoint'])) {
					$src = [
						'breakpoint'  => $source['breakpoint'],
						'srcset'      => [
							'1x'      => $source['source1x']
						],
						'width'       => $source['width'],
						'height'      => $source['height'],
						'aspectratio' => $source['ratio']
					];

					if (isset($source['source2x'])) {
						$src['srcset']['2x'] = $source['source2x'];
					}

					$properties['sources'][] = $src;
				} else {
					$properties['sources'][] = [
						'srcset'      => [
							'1x'      => $source['source1x']
						],
						'width'       => $source['width'],
						'height'      => $source['height'],
						'aspectratio' => $source['ratio']
					];
				}
			}

			return $properties;
		}
	}

	new ResponsivePics();

	// Support older versions > 0.7
	class_alias('ResponsivePics', 'ResponsivePicture');
}