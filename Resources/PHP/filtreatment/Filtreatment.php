<?php
/**
 * FILTREATMENT CLASS FILE
 *
 *
 * @author Cristian Năvălici {@link http://www.lemonsoftware.eu} lemonsoftware [at] gmail [.] com
 * @version 1.32 06 January 2010
 */

//error_reporting(E_ALL);

/**
 * CLASS DEFINITION
 *
 * This class can be used to sanitize user inputs and prevent
 * most of known vulnerabilities
 * it requires at least PHP 5.0
 *
 */
class Filtreatment {

	//-----------------------------------------------------------------------------
	/**
	* CLEANS AGAINST XSS
	*
	* NOTE all credits goes to codeigniter.com
	* @param string $str - string to check
	* @param string $charset - character set (default ISO-8859-1)
	* @return string|bool $value sanitized string
	*/
	public function ft_xss($str, $charset = 'ISO-8859-1') {
		/*
		 * Remove Null Characters
		*
		* This prevents sandwiching null characters
		* between ascii characters, like Java\0script.
		*
		*/
		$str = preg_replace('/\0+/', '', $str);
		$str = preg_replace('/(\\\\0)+/', '', $str);

		/*
		 * Validate standard character entities
		*
		* Add a semicolon if missing.  We do this to enable
		* the conversion of entities to ASCII later.
		*
		*/
		$str = preg_replace('#(&\#*\w+)[\x00-\x20]+;#u',"\\1;",$str);

		/*
		 * Validate UTF16 two byte encoding (x00)
		*
		* Just as above, adds a semicolon if missing.
		*
		*/
		$str = preg_replace('#(&\#x*)([0-9A-F]+);*#iu',"\\1\\2;",$str);

		/*
		 * URL Decode
		*
		* Just in case stuff like this is submitted:
		*
		* <a href="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">Google</a>
		*
		* Note: Normally urldecode() would be easier but it removes plus signs
		*
		*/
		$str = preg_replace("/%u0([a-z0-9]{3})/i", "&#x\\1;", $str);
		$str = preg_replace("/%([a-z0-9]{2})/i", "&#x\\1;", $str);

		/*
		 * Convert character entities to ASCII
		*
		* This permits our tests below to work reliably.
		* We only convert entities that are within tags since
		* these are the ones that will pose security problems.
		*
		*/
		if (preg_match_all("/<(.+?)>/si", $str, $matches)) {
			for ($i = 0; $i < count($matches['0']); $i++) {
				$str = str_replace($matches['1'][$i],
						html_entity_decode($matches['1'][$i], ENT_COMPAT, $charset), $str);
			}
		}

		/*
		 * Convert all tabs to spaces
		*
		* This prevents strings like this: ja	vascript
		* Note: we deal with spaces between characters later.
		*
		*/
		$str = preg_replace("#\t+#", " ", $str);

		/*
		 * Makes PHP tags safe
		*
		*  Note: XML tags are inadvertently replaced too:
		*
		*	<?xml
		*
		* But it doesn't seem to pose a problem.
		*
		*/
		$str = str_replace(array('<?php', '<?PHP', '<?', '?>'),  array('&lt;?php', '&lt;?PHP', '&lt;?', '?&gt;'), $str);

		/*
		 * Compact any exploded words
		*
		* This corrects words like:  j a v a s c r i p t
		* These words are compacted back to their correct state.
		*
		*/
		$words = array('javascript', 'vbscript', 'script', 'applet', 'alert', 'document', 'write', 'cookie', 'window');
		foreach ($words as $word) {
			$temp = '';
			for ($i = 0; $i < strlen($word); $i++) {
				$temp .= substr($word, $i, 1)."\s*";
			}

			$temp = substr($temp, 0, -3);
			$str = preg_replace('#'.$temp.'#s', $word, $str);
			$str = preg_replace('#'.ucfirst($temp).'#s', ucfirst($word), $str);
		}

		/*
		 * Remove disallowed Javascript in links or img tags
		*/
		$str = preg_replace("#<a.+?href=.*?(alert\(|alert&\#40;|javascript\:|window\.|document\.|\.cookie|<script|<xss).*?\>.*?</a>#si", "", $str);
		$str = preg_replace("#<img.+?src=.*?(alert\(|alert&\#40;|javascript\:|window\.|document\.|\.cookie|<script|<xss).*?\>#si","", $str);
		$str = preg_replace("#<(script|xss).*?\>#si", "", $str);

		/*
		 * Remove JavaScript Event Handlers
		*
		* Note: This code is a little blunt.  It removes
		* the event handler and anything up to the closing >,
		* but it's unlikely to be a problem.
		*
		*/
		$str = preg_replace('#(<[^>]+.*?)(onabort|onactivate|onafterprint|onafterupdate|onbeforeactivate|onbeforecopy|onbeforecut|onbeforedeactivate|onbeforeeditfocus|onbeforepaste|onbeforeprint|
			onbeforeunload|onbeforeupdate|onblur|onbounce|oncanplay|oncanplaythrough|oncellchange|onchange|
			onclick|oncontextmenu|oncontrolselect|oncopy|oncuechange|oncut|ondataavailable|ondatasetchanged|
			ondatasetcomplete|ondblclick|ondeactivate|ondrag|ondragend|ondragenter|ondragleave|ondragover|
			ondragstart|ondrop|ondurationchange|onemptied|onended|onerror|onerrorupdate|onfilterchange|
			onfinish|onfocus|onfocusin|onfocusout|onformchange|onforminput|onhashchange|onhelp|oninput|oninvalid|onkeydown|
			onkeypress|onkeyup|onlayoutcomplete|onload|onloadeddata|onloadedmetadata|onloadstart|
			onlosecapture|onmessage|onmousedown|onmouseenter|onmouseleave|onmousemove|onmouseout|
			onmouseover|onmouseup|onmousewheel|onmove|onmoveend|onmovestart|onoffline|ononline|
			onpagehide|onpageshow|onpaste|onpause|onplay|onplaying|onpopstate|onprogress|
			onpropertychange|onratechange|onreadystatechange|onredo|onreset|onresize|onresizeend|
			onresizestart|onrowenter|onrowexit|onrowsdelete|onrowsinserted|onscroll|onseeked|onseeking|
			onselect|onselectionchange|onselectstart|onshow|onstalled|onstart|onstop|onstorage|onsubmit|
			onsuspend|ontimeupdate|onundo|onunload|onvolumechange|onwaiting)[^>]*>#iU',"\\1>",$str);

		/*
		 * Sanitize naughty HTML elements
		*
		* If a tag containing any of the words in the list
		* below is found, the tag gets converted to entities.
		*
		* So this: <blink>
		* Becomes: &lt;blink&gt;
		*
		*/
		$str = preg_replace('#<(/*\s*)(alert|applet|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|layer|link|meta|object|plaintext|style|script|textarea|title|xml|xss)([^>]*)>#is', "&lt;\\1\\2\\3&gt;", $str);

		/*
		 * Sanitize naughty scripting elements
		*
		* Similar to above, only instead of looking for
		* tags it looks for PHP and JavaScript commands
		* that are disallowed.  Rather than removing the
		* code, it simply converts the parenthesis to entities
		* rendering the code un-executable.
		*
		* For example:	eval('some code')
		* Becomes:		eval&#40;'some code'&#41;
		*
		*/
		$str = preg_replace('#(alert|cmd|passthru|eval|exec|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si', "\\1\\2&#40;\\3&#41;", $str);

		/*
		 * Final clean up
		*
		* This adds a bit of extra precaution in case
		* something got through the above filters
		*
		*/

		$bad = array(
				'document.cookie'	=> '',
				'document.write'	=> '',
				'window.location'	=> '',
				"javascript\s*:"	=> '',
				"Redirect\s+302"	=> '',
				'<!--'			=> '&lt;!--',
				'-->'			=> '--&gt;'
		);

		foreach ($bad as $key => $val)	{
			$str = preg_replace("#".$key."#i", $val, $str);
		}

		return $str;

	}

} // class

?>