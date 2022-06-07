<?php

	if($argc > 1) {
		$path = $argv[1];
	} else {
		die(PHP_EOL."usage: ".basename(__FILE__)." [path]".PHP_EOL.PHP_EOL);
	}

	if(!is_readable($path)) {
		die(PHP_EOL."Access denied reading file.".PHP_EOL.PHP_EOL);
	}

	$image = new ImageToHTML($path);
	$image->save();

	
	/**
	 * Image to HTML
	 *
	 * @property {string}      $path          The path to the image.
	 * @property {resource}    $image         The image resource.
	 *
	 * @property {integer}     $width         The width of the image.
	 * @property {integer}     $height        The height of the image.
	 * @property {integer}     $pixelSize     The number of pixels used for each, uhh... pixel.
	 *
	 * @property {ImageRow[]}  $rows          The rows in this image.
	 *
	 * @property {float}       $parseTime     The amount of time it took to parse this image.
	 * @property {float}       $compileTime   The amount of time it took to compile the HTML.
	 */
	class ImageToHTML {
		protected $path;
		protected $image;

		protected $width;
		protected $height;
		protected $pixelSize = 1;

		protected $rows = array();

		protected $parseTime = 0;
		protected $compileTime = 0;

		/**
		 * Creates a new ``ImageToHTML`` object.
		 * @param {string}  $path   The path to the image.
		 * @return {object}  ``$this``
		 */
		public function __construct($path) {
			$this->path = $path;
			$this->image = imagecreatefromjpeg($path);

			$this->width = imagesx($this->image);
			$this->height = imagesy($this->image);

			$this->parse();
		}

		/**
		 * Returns this object as an HTML string.
		 * @return {string}
		 */
		public function __toString() {
			$start = microtime(true);

			$image = '';
			foreach($this->rows as $row) {
				$image .= $row;
			}

			$this->compileTime = (microtime(true) - $start);

			$total_time = ($this->parseTime + $this->compileTime);

			$header  = '<!DOCTYPE html>';
			$header .= '<html>';
			$header .=     '<head>';
			$header .=         '<title>'.htmlentities(basename($this->path)).'</title>';
			$header .=         '<style>';
			$header .=             'div.image {';
			$header .=                 'display: inline-block;';
			$header .=                 'width: '.$this->getWidth().'px;';
			$header .=                 'height: '.$this->getHeight().'px;';
			$header .=             '}';
			$header .=             'div.image div.imageRow {';
			$header .=                 'display: block;';
			$header .=                 'width: '.$this->getWidth().'px;';
			$header .=                 'margin: 0;';
			$header .=                 'padding: 0;';
			$header .=                 'height: '.$this->pixelSize.'px;';
			$header .=             '}';
			$header .=             'div.image div.imagePixel {';
			$header .=                 'display: inline-block;';
			$header .=                 'margin: 0;';
			$header .=                 'padding: 0;';
			$header .=                 'width: '.$this->pixelSize.'px;';
			$header .=                 'height: '.$this->pixelSize.'px;';
			$header .=             '}';
			$header .=         '</style>';
			$header .=     '</head>';
			$header .=     '<body>';
			$header .=         '<h1>'.htmlentities(basename($this->path)).'</h1>';
			$header .=         '<p>';
			$header .=             '<strong>Compile time:</strong> ';
			$header .=             number_format($total_time, 3).' sec';
			$header .=         '</p>';
			$header .=         '<div class="image">';

			$footer  =         '</div>';
			$footer .=         '<p>';
			$footer .=             'Written by <a href="https://www.quora.com/profile/Rich-Kosiba">Rich Kosiba</a> ';
			$footer .=             'in response to Quora question ';
			$footer .=             '<a href="http://qr.ae/TUIjLv">&quot;How do you convert images from JPEG to HTML?&quot;</a>.';
			$footer .=         '</p>';
			$footer .=     '</body>';
			$footer .= '</html>';

			return $header.$image.$footer;
		}

		/**
		 * Returns the finished height of this image.
		 * @return {integer}
		 */
		public function getHeight() {
			return ($this->height * $this->pixelSize);
		}

		/**
		 * Returns the finished width of this image.
		 * @return {integer}
		 */
		public function getWidth() {
			return ($this->width * $this->pixelSize);
		}

		/**
		 * Parse the image.
		 * @return {this}  ``$this``
		 */
		public function parse() {
			$this->rows = array();

			$start = microtime(true);

			for($y = 0; $y < $this->height; $y++) {
				$this->rows[] = $row = new ImageRow();
				for($x = 0; $x < $this->width; $x++) {
					$row->addPixel(imagecolorat($this->image, $x, $y));
				}
			}

			$this->parseTime = (microtime(true) - $start);

			return $this;
		}

		/**
		 * Save this as an HTML file.
		 * @return {this}  ``$this``
		 */
		public function save() {
			$output = $this->path.".html";

			$counter = 0;
			while(file_exists($output)) {
				$counter++;
				$output = $this->path."-$counter.html";
			}

			file_put_contents($output, strval($this));

			return $this;
		}
	}

	/**
	 * Image Row
	 * @property {Color[]}  $pixels   An array of colors representing the pixels.
	 */
	class ImageRow {
		protected $pixels = array();

		/**
		 * Add a pixel to this row.
		 * @param {integer}  $idx   The color index.
		 * @return {this}  ``$this``
		 */
		public function addPixel($idx) {
			$this->pixels[] = Color::getByIdx($idx);
			return $this;
		}

		/**
		 * Returns this object as a string.
		 * @return {string}
		 */
		public function __toString() {
			$result = '<div class="imageRow">';

			foreach($this->pixels as $pixel) {
				$result .= $pixel->toPixel();
			}

			$result .= '</div>';

			return $result;
		}
	}

	/**
	 * Color
	 *
	 * @property {Color[]}  $_instances   An array of instances of this class keyed by color index.
	 *
	 * @property {integer}  $idx          The color index.
	 * @property {string}   $rgb          The color in RGB format.
	 * @property {string}   $pixel        The color as a pixel.
	 */
	class Color {
		private static $_instances = array();

		protected $idx;
		protected $rgb = null;
		protected $pixel = null;

		/**
		 * Creates a new ``Color`` object.
		 * @param {integer}  $idx   The color index.
		 * @return {object}  ``$this``
		 */
		public function __construct($idx) {
			$this->idx = $idx;
		}
		
		/**
		 * Returns this object as a string.
		 * @return {string}
		 */
		public function __toString() {
			if($this->rgb === null) {
				$r = ($this->idx >> 16) & 0xFF;
				$g = ($this->idx >> 8) & 0xFF;
				$b = $this->idx & 0xFF;
				$this->rgb = "rgb($r, $g, $b)";
			}

			return $this->rgb;
		}

		/**
		 * Get the color for the given index.
		 * @param {integer}  $idx
		 * @return {Color}
		 */
		public static function getByIdx($idx) {
			if(!isset(self::$_instances[$idx])) {
				$c = get_called_class();
				self::$_instances[$idx] = new $c($idx);
			}

			return self::$_instances[$idx];
		}

		/**
		 * Converts this color to a pixel.
		 * @return {string}
		 */
		public function toPixel() {
			if($this->pixel === null) {
				$this->pixel  = '<div';
				$this->pixel .= ' class="imagePixel"';
				$this->pixel .= ' style="background-color: '.strval($this).';"';
				$this->pixel .= '></div>';
			}
			return $this->pixel;
		}
	}