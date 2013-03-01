<?php

/**
 * This is a test of internationalization.lib.php which is
 * a common purpose library for supporting internationalization
 * related functions. Only the public API is tested here.
 * @author Ricardo Rodriguez Salazar, 2009.
 * @author Ivan Tcholakov, September 2009.
 * For licensing terms, see /dokeos_license.txt
 *
 * Notes:
 * 1. While saving this file, please, preserve its UTF-8 encoding.
 * Othewise this test would be broken.
 * 2. While running this test, send a header declaring UTF-8 encoding.
 * Then you would see variable dumps correctly.
 * 3. Tests about string comparison and sorting might give false results
 * if the intl extension has not been installed.
 */


class TestInternationalization extends UnitTestCase {

	function TestInternationalization() {
        $this->UnitTestCase('Internationalization Tests');
	}


/**
 * ----------------------------------------------------------------------------
 * A safe way to calculate binary lenght of a string (as number of bytes)
 * ----------------------------------------------------------------------------
 */

	public function test_api_byte_count() {
		$string = 'xxxáéíóú?'; // UTF-8
		$res = api_byte_count($string);
		$this->assertTrue($res == 14);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}


/**
 * ----------------------------------------------------------------------------
 * Multibyte string conversion functions
 * ----------------------------------------------------------------------------
 */

	public function test_api_convert_encoding() {
		$string = 'xxxáéíóú?€'; // UTF-8
		$from_encoding = 'UTF-8';
		$to_encoding = 'ISO-8859-15';
		$res = api_convert_encoding($string, $to_encoding, $from_encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue(api_convert_encoding($res, $from_encoding, $to_encoding) == $string);
		//var_dump($res);
		//var_dump(api_convert_encoding($res, $from_encoding, $to_encoding));
	}

	public function test_api_utf8_encode() {
		$string = 'xxxáéíóú?€'; // UTF-8
		$from_encoding = 'ISO-8859-15';
		$string1 = api_utf8_decode($string, $from_encoding);
		$res = api_utf8_encode($string1, $from_encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == $string);
		//var_dump($res);
	}

	public function test_api_utf8_decode() {
		$string = 'xxxx1ws?!áéíóú@€'; // UTF-8
		$to_encoding = 'ISO-8859-15';
		$res = api_utf8_decode($string, $to_encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue(api_utf8_encode($res, $to_encoding) == $string);
		//var_dump($res);
	}

	public function test_api_to_system_encoding() {
		$string = api_utf8_encode(get_lang('Title'), api_get_system_encoding());
		$from_encoding = 'UTF-8';
		$check_utf8_validity = false;
		$res = api_to_system_encoding($string, $from_encoding, $check_utf8_validity);
		$this->assertTrue(is_string($res));
		$this->assertTrue(api_convert_encoding($res, $from_encoding, api_get_system_encoding()) == $string);
		//var_dump(api_utf8_encode($res, api_get_system_encoding()));
	}

	public function test_api_htmlentities() {
		$string = 'áéíóú@!?/\-_`*ç´`'; // UTF-8
		$quote_style = ENT_QUOTES;
		$encoding = 'UTF-8';
		$res = api_htmlentities($string, $quote_style, $encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue(api_convert_encoding($res, $encoding, 'HTML-ENTITIES') == $string);
		//var_dump($res);
	}

	public function test_api_html_entity_decode() {
		$string = 'áéíóú@/\!?Ç´`+*?-_ '; // UTF-8
		$quote_style = ENT_QUOTES;
		$encoding = 'UTF-8';
		$res = api_html_entity_decode(api_convert_encoding($string, 'HTML-ENTITIES', $encoding), $quote_style, $encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == $string);
		//var_dump($res);
	}

	public function test_api_xml_http_response_encode() {
		$string='áéíóú@/\!?Ç´`+*?-_'; // UTF-8
		$from_encoding = 'UTF-8';
		$res = api_xml_http_response_encode($string, $from_encoding);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function test_api_file_system_encode() {
		$string = 'áéíóú@/\!?Ç´`+*?-_'; // UTF-8
		$from_encoding = 'UTF-8';
		$res = api_file_system_encode($string, $from_encoding);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function test_api_file_system_decode() {
		$string='áéíóú@/\!?Ç´`+*?-_'; // UTF-8
		$to_encoding = 'UTF-8';
		$res = api_file_system_decode($string, $to_encoding);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function test_api_transliterate() {
		$string = 'Фёдор Михайлович Достоевкий'; // UTF-8
		/*
		// If you have broken by mistake UTF-8 encoding of this source, try the following equivalent:
		$string = api_html_entity_decode(
			'&#1060;&#1105;&#1076;&#1086;&#1088; '.
			'&#1052;&#1080;&#1093;&#1072;&#1081;&#1083;&#1086;&#1074;&#1080;&#1095; '.
			'&#1044;&#1086;&#1089;&#1090;&#1086;&#1077;&#1074;&#1082;&#1080;&#1081;',
			ENT_QUOTES, 'UTF-8');
		*/
		$unknown = 'X';
		$from_encoding = 'UTF-8';
		$res = api_transliterate($string, $unknown, $from_encoding);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == 'Fyodor Mihaylovich Dostoevkiy');
		//var_dump($string);
		//var_dump($res);
	}


/**
 * ----------------------------------------------------------------------------
 * Common multibyte string functions
 * ----------------------------------------------------------------------------
 */

	public function test_api_ord() {
		$encoding = 'UTF-8';
		$characters = array('И', 'в', 'а', 'н', ' ', 'I', 'v', 'a', 'n'); // UTF-8
		$codepoints = array(1048, 1074, 1072, 1085, 32, 73, 118, 97, 110);
		$res = array();
		foreach ($characters as $character) {
			$res[] = api_ord($character, $encoding);
		}
		$this->assertTrue($res == $codepoints);
		//var_dump($res);
	}

	public function test_api_chr() {
		$encoding = 'UTF-8';
		$codepoints = array(1048, 1074, 1072, 1085, 32, 73, 118, 97, 110);
		$characters = array('И', 'в', 'а', 'н', ' ', 'I', 'v', 'a', 'n'); // UTF-8
		$res = array();
		foreach ($codepoints as $codepoint) {
			$res[] = api_chr($codepoint, $encoding);
		}
		$this->assertTrue($res == $characters);
		//var_dump($res);
	}

	public function test_api_str_ireplace() {
		$search = 'Á'; // UTF-8
		$replace = 'a';
		$subject = 'bájando'; // UTF-8
		$count = null;
		$encoding = 'UTF-8';
		$res = api_str_ireplace($search, $replace, $subject, & $count, $encoding);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == 'bajando');
		//var_dump($res);
	}

	public function test_api_str_split() {
		$string = 'áéíóúº|\/?Ç][ç]'; // UTF-8
		$split_length = 1;
		$encoding = 'UTF-8';
		$res = api_str_split($string, $split_length, $encoding);
		$this->assertTrue(is_array($res));
		$this->assertTrue(count($res) == 15);
		//var_dump($res);
	}

	public function test_api_stripos() {
		$haystack = 'bájando'; // UTF-8
		$needle = 'Á';
		$offset = 0;
		$encoding = 'UTF-8';
		$res = api_stripos($haystack, $needle, $offset, $encoding);
		$this->assertTrue(is_numeric($res)|| is_bool($res));
		$this->assertTrue($res == 1);
		//var_dump($res);
	}

	public function test_api_stristr() {
		$haystack = 'bájando'; // UTF-8
		$needle = 'Á';
		$part = false;
		$encoding = 'UTF-8';
		$res = api_stristr($haystack, $needle, $part, $encoding);
		$this->assertTrue(is_bool($res) || is_string($res));
		$this->assertTrue($res == 'ájando');
		//var_dump($res);
	}

	public function test_api_strlen() {
		$string='áéíóúº|\/?Ç][ç]'; // UTF-8
		$encoding = 'UTF-8';
		$res = api_strlen($string, $encoding);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue($res == 15);
		//var_dump($res);
	}

	public function test_api_strpos() {
		$haystack = 'bájando'; // UTF-8
		$needle = 'á';
		$offset = 0;
		$encoding = 'UTF-8';
		$res = api_strpos($haystack, $needle, $offset, $encoding);
		$this->assertTrue(is_numeric($res)|| is_bool($res));
		$this->assertTrue($res == 1);
		//var_dump($res);
	}

	public function test_api_strrchr() {
		$haystack = 'aviación aviación'; // UTF-8
		$needle = 'ó';
		$part = false;
		$encoding = 'UTF-8';
		$res = api_strrchr($haystack, $needle, $part, $encoding);
		$this->assertTrue(is_string($res)|| is_bool($res));
		$this->assertTrue($res == 'ón');
		//var_dump($res);
	}

	public function test_api_strrev() {
		$string = 'áéíóúº|\/?Ç][ç]'; // UTF-8
		$encoding = 'UTF-8';
		$res = api_strrev($string, $encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == ']ç[]Ç?/\|ºúóíéá');
		//var_dump($res);
	}

	public function test_api_strripos() {
		$haystack = 'aviación aviación'; // UTF-8
		$needle = 'Ó';
		$offset = 0;
		$encoding = 'UTF-8';
		$res = api_strripos($haystack, $needle, $offset, $encoding);
		$this->assertTrue(is_numeric($res) || is_bool($res));
		$this->assertTrue($res == 15);
		//var_dump($res);
	}

	public function test_api_strrpos() {
		$haystack = 'aviación aviación'; // UTF-8
		$needle = 'ó';
		$offset = 0;
		$encoding = 'UTF-8';
		$res = api_strrpos($haystack, $needle, $offset, $encoding);
		$this->assertTrue(is_numeric($res) || is_bool($res));
		$this->assertTrue($res == 15);
		//var_dump($res);
	}

	public function test_api_strstr() {
		$haystack = 'aviación'; // UTF-8
		$needle = 'ó';
		$part = false;
		$encoding = 'UTF-8';
		$res = api_strstr($haystack, $needle, $part, $encoding);
		$this->assertTrue(is_bool($res)|| is_string($res));
		$this->assertTrue($res == 'ón');
		//var_dump($res);
	}

	public function test_api_strtolower() {
		$string = 'áéíóúº|\/?Ç][ç]'; // UTF-8
		$encoding = 'UTF-8';
		$res = api_strtolower($string, $encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == 'áéíóúº|\/?ç][ç]');
		//var_dump($res);
	}

	public function test_api_strtoupper() {
		$string='áéíóúº|\/?Ç][ç]'; // UTF-8
		$encoding = 'UTF-8';
		$res = api_strtoupper($string, $encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res =='ÁÉÍÓÚº|\/?Ç][Ç]');
		//var_dump($res);
	}

	public function test_api_substr() {
		$string = 'áéíóúº|\/?Ç][ç]'; // UTF-8
		$start = 10;
		$length = 4;
		$encoding = 'UTF-8';
		$res = api_substr($string, $start, $length, $encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == 'Ç][ç');
		//var_dump($res);
	}

	public function test_api_substr_replace() {
		$string = 'áéíóúº|\/?Ç][ç]'; // UTF-8
		$replacement = 'eiou';
		$start= 1;
		$length = 4;
		$encoding = 'UTF-8';
		$res = api_substr_replace($string, $replacement, $start, $length, $encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == 'áeiouº|\/?Ç][ç]');
		//var_dump($res);
	}

	public function test_api_ucfirst() {
		$string = 'áéíóúº|\/? xx ][ xx ]'; // UTF-8
		$encoding = 'UTF-8';
		$res = api_ucfirst($string, $encoding);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == 'Áéíóúº|\/? xx ][ xx ]');
		//var_dump($res);
	}

	public function test_api_ucwords() {
		$string = 'áéíóúº|\/? xx ][ xx ]'; // UTF-8
		$encoding = 'UTF-8';
		$res = api_ucwords($string, $encoding);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == 'Áéíóúº|\/? Xx ][ Xx ]');
		//var_dump($res);
	}


/**
 * ----------------------------------------------------------------------------
 * String operations using regular expressions
 * ----------------------------------------------------------------------------
 */

	public function test_api_preg_match() {
		$pattern = '/иван/i'; // UTF-8
		$subject = '-- Ivan (en) -- Иван (bg) --'; // UTF-8
		$matches = null;
		$flags = 0;
		$offset = 0;
		$encoding = 'UTF-8';
		$res = api_preg_match($pattern, $subject, $matches, $flags, $offset, $encoding);
		$this->assertTrue($res == 1);
		//var_dump($res);
		//var_dump($matches);
	}

	public function test_api_preg_match_all() {
		$pattern = '/иван/i'; // UTF-8
		$subject = '-- Ivan (en) -- Иван (bg) -- иван --'; // UTF-8
		$matches = null;
		$flags = PREG_PATTERN_ORDER;
		$offset = 0;
		$encoding = 'UTF-8';
		$res = api_preg_match_all($pattern, $subject, $matches, $flags, $offset, $encoding);
		$this->assertTrue($res == 2);
		//var_dump($res);
		//var_dump($matches);
	}

	public function test_api_preg_replace() {
		$pattern = '/иван/i'; // UTF-8
		$replacement = 'ИВАН'; // UTF-8
		$subject = '-- Ivan (en) -- Иван (bg) -- иван --'; // UTF-8
		$limit = -1;
		$count = null;
		$encoding = 'UTF-8';
		$res = api_preg_replace($pattern, $replacement, $subject, $limit, $count, $encoding);
		$this->assertTrue($res == '-- Ivan (en) -- ИВАН (bg) -- ИВАН --'); // UTF-8
		//var_dump($res);
	}

	public function test_api_preg_replace_callback() {
		$pattern = '/иван/i'; // UTF-8
		$subject = '-- Ivan (en) -- Иван (bg) -- иван --'; // UTF-8
		$limit = -1;
		$count = null;
		$encoding = 'UTF-8';
		$res = api_preg_replace_callback($pattern, create_function('$matches', 'return api_ucfirst($matches[0], \'UTF-8\');'), $subject, $limit, $count, $encoding);
		$this->assertTrue($res == '-- Ivan (en) -- Иван (bg) -- Иван --'); // UTF-8
		//var_dump($res);
	}

	public function test_api_preg_split() {
		$pattern = '/иван/i'; // UTF-8
		$subject = '-- Ivan (en) -- Иван (bg) -- иван --'; // UTF-8
		$limit = -1;
		$count = null;
		$encoding = 'UTF-8';
		$res = api_preg_split($pattern, $subject, $limit, $count, $encoding);
		$this->assertTrue($res[0] == '-- Ivan (en) -- '); // UTF-8
		//var_dump($res);
	}


/**
 * ----------------------------------------------------------------------------
 * Obsolete string operations using regular expressions, to be deprecated
 * ----------------------------------------------------------------------------
 */

	public function test_api_ereg() {
		$pattern = 'scorm/showinframes.php([^"\'&]*)(&|&amp;)file=([^"\'&]*)$';
		$string = 'http://localhost/dokeos/main/scorm/showinframes.php?id=5&amp;file=test.php';
		$res = api_ereg($pattern, $string, $regs);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue($res == 45);
		//var_dump($res);
	}

	public function test_api_ereg_replace() {
		$pattern = 'file=([^"\'&]*)$';
		$string = 'http://localhost/dokeos/main/scorm/showinframes.php?id=5&amp;file=test.php';
		$replacement = 'file=my_test.php';
		$option = null;
		$res = api_ereg_replace($pattern, $replacement, $string, $option);
		$this->assertTrue(is_string($res));
		$this->assertTrue(strlen($res) == 77);
		//var_dump($res);
	}

	public function testapi_eregi() {
		$pattern = 'scorm/showinframes.php([^"\'&]*)(&|&amp;)file=([^"\'&]*)$';
		$string = 'http://localhost/dokeos/main/scorm/showinframes.php?id=5&amp;file=test.php';
		$res = api_eregi($pattern, $string, $regs);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue($res == 45);
		//var_dump($res);
	}

	public function test_api_eregi_replace() {
		$pattern = 'file=([^"\'&]*)$';
		$string = 'http://localhost/dokeos/main/scorm/showinframes.php?id=5&amp;file=test.php';
		$replacement = 'file=my_test.php';
		$option = null;
		$res = api_eregi_replace($pattern, $replacement, $string, $option);
		$this->assertTrue(is_string($res));
		$this->assertTrue(strlen($res) == 77);
		//var_dump($res);
	}

	public function test_api_split() {
		$pattern = '[/.-]';
		$string = '08/22/2009';
		$limit = null;
		$res = api_split($pattern, $string, $limit);
		$this->assertTrue(is_array($res));
		$this->assertTrue(count($res) == 3);
		//var_dump($res);
	}


/**
 * ----------------------------------------------------------------------------
 * String comparison
 * ----------------------------------------------------------------------------
 */

	public function test_api_strcasecmp() {
		$string1 = 'áéíóu'; // UTF-8
		$string2 = 'Áéíóu'; // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_strcasecmp($string1, $string2, $language, $encoding);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue($res == 0);
		//var_dump($res);
	}

	public function test_api_strcmp() {
		$string1 = 'áéíóu'; // UTF-8
		$string2 = 'Áéíóu'; // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_strcmp($string1, $string2, $language, $encoding);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue($res == 1);
		//var_dump($res);
	}

	public function test_api_strnatcasecmp() {
		$string1 = '201áéíóu.txt'; // UTF-8
		$string2 = '30Áéíóu.TXT'; // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_strnatcasecmp($string1, $string2, $language, $encoding);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue($res == 1);
		//var_dump($res);
	}

	public function  test_api_strnatcmp() {
		$string1 = '201áéíóu.txt'; // UTF-8
		$string2 = '30áéíóu.TXT'; // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_strnatcmp($string1, $string2, $language, $encoding);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue($res == 1);
		//var_dump($res);
	}


/**
 * ----------------------------------------------------------------------------
 * Sorting arrays
 * ----------------------------------------------------------------------------
 */

	public function test_api_asort() {
		$array = array('úéo', 'aíó', 'áed'); // UTF-8
		$sort_flag = SORT_REGULAR;
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_asort($array, $sort_flag, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'aíó' || $array[$keys[0]] == 'áed'); // The second result is given when intl php-extension is active.
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_arsort() {
		$array = array('aíó', 'úéo', 'áed'); // UTF-8
		$sort_flag = SORT_REGULAR;
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_arsort($array, $sort_flag, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'úéo');
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_natsort() {
		$array = array('img12.png', 'img10.png', 'img2.png', 'img1.png'); // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_natsort($array, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'img1.png');
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_natrsort() {
		$array = array('img2.png', 'img10.png', 'img12.png', 'img1.png'); // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_natrsort($array, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'img12.png');
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_natcasesort() {
		$array = array('img2.png', 'img10.png', 'Img12.png', 'img1.png'); // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_natcasesort($array, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'img1.png');
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_natcasersort() {
		$array = array('img2.png', 'img10.png', 'Img12.png', 'img1.png'); // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_natcasersort($array, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'Img12.png');
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_ksort() {
		$array = array('aíó' => 'img2.png', 'úéo' => 'img10.png', 'áed' => 'img12.png', 'áedc' => 'img1.png'); // UTF-8
		$sort_flag = SORT_REGULAR;
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_ksort($array, $sort_flag, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'img2.png');
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_krsort() {
		$array = array('aíó' => 'img2.png', 'úéo' => 'img10.png', 'áed' => 'img12.png', 'áedc' => 'img1.png'); // UTF-8
		$sort_flag = SORT_REGULAR;
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_krsort($array, $sort_flag, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'img10.png');
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_knatsort() {
		$array = array('img2.png' => 'aíó', 'img10.png' => 'úéo', 'img12.png' => 'áed', 'img1.png' => 'áedc'); // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_knatsort($array, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'áedc');
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_knatrsort() {
		$array = array('img2.png' => 'aíó', 'img10.png' => 'úéo', 'IMG12.PNG' => 'áed', 'img1.png' => 'áedc'); // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_knatrsort($array, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'úéo' || $array[$keys[0]] == 'áed'); // The second result is given when intl php-extension is active.
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_knatcasesort() {
		$array = array('img2.png' => 'aíó', 'img10.png' => 'úéo', 'IMG12.PNG' => 'áed', 'img1.png' => 'áedc'); // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_knatcasesort($array, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'áedc');
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_knatcasersort() {
		$array = array('img2.png' => 'aíó', 'img10.png' => 'úéo', 'IMG12.PNG' => 'áed', 'IMG1.PNG' => 'áedc'); // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_knatcasersort($array, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'áed');
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_sort() {
		$array = array('úéo', 'aíó', 'áed', 'áedc'); // UTF-8
		$sort_flag = SORT_REGULAR;
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_sort($array, $sort_flag, $language, $encoding);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[0] == 'aíó' || $array[0] == 'áed');  // The second result is given when intl php-extension is active.
		//var_dump($array);
		//var_dump($res);
	}

	public function testapi_rsort() {
		$array = array('aíó', 'úéo', 'áed', 'áedc'); // UTF-8
		$sort_flag = SORT_REGULAR;
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_rsort($array, $sort_flag, $language, $encoding);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[0] == 'úéo');
		//var_dump($array);
		//var_dump($res);
	}


/**
 * ----------------------------------------------------------------------------
 * Common sting operations with arrays
 * ----------------------------------------------------------------------------
 */

	public function test_api_in_array_nocase() {
		$needle = 'áéíó'; // UTF-8
		$haystack = array('Áéíó', 'uáé', 'íóú'); // UTF-8
		$strict = false;
		$encoding = 'UTF-8';
		$res = api_in_array_nocase($needle, $haystack, $strict, $encoding);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true);
		//var_dump($res);
	}


/**
 * ----------------------------------------------------------------------------
 * Encoding management functions
 * ----------------------------------------------------------------------------
 */

	public function test_api_refine_encoding_id() {
		$encoding = 'koI8-r';
		$res = api_refine_encoding_id($encoding);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == 'KOI8-R');
		//var_dump($res);
	}

	public function test_api_equal_encodings() {
		$encoding1 = 'cp65001';
		$encoding2 = 'utf-8';
		$encoding3 = 'WINDOWS-1251';
		$encoding4 = 'WINDOWS-1252';
		$encoding5 = 'win-1250';
		$encoding6 = 'windows-1250';
		$res1 = api_equal_encodings($encoding1, $encoding2);
		$res2 = api_equal_encodings($encoding3, $encoding4);
		$res3 = api_equal_encodings($encoding5, $encoding6);
		$res4 = api_equal_encodings($encoding5, $encoding6, true);
		$this->assertTrue(is_bool($res1));
		$this->assertTrue(is_bool($res2));
		$this->assertTrue(is_bool($res3));
		$this->assertTrue(is_bool($res4));
		$this->assertTrue($res1);
		$this->assertTrue(!$res2);
		$this->assertTrue($res3);
		$this->assertTrue(!$res4);
		//var_dump($res1);
		//var_dump($res2);
		//var_dump($res3);
		//var_dump($res4);
	}

	public function test_api_is_utf8() {
		$encoding = 'cp65001'; // This an alias of UTF-8.
		$res = api_is_utf8($encoding);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function test_api_is_latin1() {
		$encoding = 'ISO-8859-15';
		$strict = false;
		$res = api_is_latin1($encoding, false);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function test_api_get_system_encoding() {
		$res = api_get_system_encoding();
		$this->assertTrue(is_string($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function test_api_get_file_system_encoding() {
		$res = api_get_file_system_encoding();
		$this->assertTrue(is_string($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function test_api_is_encoding_supported() {
		$encoding1 = 'UTF-8';
		$encoding2 = 'XXXX#%#%VR^%BBDNdjlrsg;d';
		$res1 = api_is_encoding_supported($encoding1);
		$res2 = api_is_encoding_supported($encoding2);
		$this->assertTrue(is_bool($res1) && is_bool($res2));
		$this->assertTrue($res1 && !$res2);
		//var_dump($res1);
		//var_dump($res2);
	}

	public function test_api_get_non_utf8_encoding() {
		$language = 'bulgarian';
		$res = api_get_non_utf8_encoding($language);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == 'WINDOWS-1251');
		//var_dump($res);
	}

	public function test_api_get_valid_encodings() {
		$res = api_get_valid_encodings();
		$ok = is_array($res) && !empty($res);
		$this->assertTrue($ok);
		if ($ok) {
			foreach ($res as $value) {
				$ok = $ok && is_string($value);
			}
			$this->assertTrue($ok);
		}
		//var_dump($res);
	}

	public function test_api_detect_encoding_xml() {
		$xml1 = '
			<Users>
				<User>
					<Username>username1</Username>
					<Lastname>xxx</Lastname>
					<Firstname>xxx</Firstname>
					<Password>xxx</Password>
					<Email>xxx@xx.xx</Email>
					<OfficialCode>xxx</OfficialCode>
					<Phone>xxx</Phone>
					<Status>student</Status>
				</User>
			</Users>'; // US-ASCII
		$xml2 = '<?xml version="1.0" encoding="ISO-8859-15"?>'.$xml1;
		$xml3 = '<?xml version="1.0" encoding="utf-8"?>'.$xml1;
		$xml4 = str_replace('<Lastname>xxx</Lastname>', '<Lastname>x'.chr(192).'x</Lastname>', $xml1); // A non-UTF-8 character has been inserted.
		$res1 = api_detect_encoding_xml($xml1);
		$res2 = api_detect_encoding_xml($xml2);
		$res3 = api_detect_encoding_xml($xml3);
		$res4 = api_detect_encoding_xml($xml4);
		$res5 = api_detect_encoding_xml($xml4, 'windows-1251');
		$this->assertTrue(
			$res1 === 'UTF-8'
			&& $res2 === 'ISO-8859-15'
			&& $res3 === 'UTF-8'
			&& api_equal_encodings($res4, api_get_system_encoding())
			&& $res5 === 'WINDOWS-1251'
		);
		//var_dump($res1);
		//var_dump($res2);
		//var_dump($res3);
		//var_dump($res4);
		//var_dump($res5);
	}

	public function test_api_convert_encoding_xml() {
		$xml = '
			<?xml version="1.0" encoding="UTF-8"?>
			<Users>
				<User>
					<Username>username1</Username>
					<Lastname>xxx</Lastname>
					<Firstname>Иван</Firstname>
					<Password>xxx</Password>
					<Email>xxx@xx.xx</Email>
					<OfficialCode>xxx</OfficialCode>
					<Phone>xxx</Phone>
					<Status>student</Status>
				</User>
			</Users>'; // UTF-8
		$res1 = api_convert_encoding_xml($xml, 'WINDOWS-1251', 'UTF-8');
		$res2 = api_convert_encoding_xml($xml, 'WINDOWS-1251');
		$res3 = api_convert_encoding_xml($res1, 'UTF-8', 'WINDOWS-1251');
		$res4 = api_convert_encoding_xml($res2, 'UTF-8');
		$this->assertTrue(
			$res3 === $xml
			&& $res4 === $xml
		);
		//var_dump(preg_replace(array('/\r?\n/m', '/\t/m'), array('<br />', '&nbsp;&nbsp;&nbsp;&nbsp;'), htmlspecialchars($res1)));
		//var_dump(preg_replace(array('/\r?\n/m', '/\t/m'), array('<br />', '&nbsp;&nbsp;&nbsp;&nbsp;'), htmlspecialchars($res2)));
		//var_dump(preg_replace(array('/\r?\n/m', '/\t/m'), array('<br />', '&nbsp;&nbsp;&nbsp;&nbsp;'), htmlspecialchars($res3)));
		//var_dump(preg_replace(array('/\r?\n/m', '/\t/m'), array('<br />', '&nbsp;&nbsp;&nbsp;&nbsp;'), htmlspecialchars($res4)));
	}

	public function test_api_utf8_encode_xml() {
		$xml1 = '
			<?xml version="1.0" encoding="UTF-8"?>
			<Users>
				<User>
					<Username>username1</Username>
					<Lastname>xxx</Lastname>
					<Firstname>Иван</Firstname>
					<Password>xxx</Password>
					<Email>xxx@xx.xx</Email>
					<OfficialCode>xxx</OfficialCode>
					<Phone>xxx</Phone>
					<Status>student</Status>
				</User>
			</Users>'; // UTF-8
		$xml2 = '
			<?xml version="1.0" encoding="WINDOWS-1251"?>
			<Users>
				<User>
					<Username>username1</Username>
					<Lastname>xxx</Lastname>
					<Firstname>'.chr(200).chr(226).chr(224).chr(237).'</Firstname>
					<Password>xxx</Password>
					<Email>xxx@xx.xx</Email>
					<OfficialCode>xxx</OfficialCode>
					<Phone>xxx</Phone>
					<Status>student</Status>
				</User>
			</Users>'; // WINDOWS-1251
		$res1 = api_utf8_encode_xml($xml2);
		$this->assertTrue($res1 === $xml1);
		//var_dump(preg_replace(array('/\r?\n/m', '/\t/m'), array('<br />', '&nbsp;&nbsp;&nbsp;&nbsp;'), htmlspecialchars($res1)));
	}

	public function test_api_utf8_decode_xml() {
		$xml1 = '
			<?xml version="1.0" encoding="UTF-8"?>
			<Users>
				<User>
					<Username>username1</Username>
					<Lastname>xxx</Lastname>
					<Firstname>Иван</Firstname>
					<Password>xxx</Password>
					<Email>xxx@xx.xx</Email>
					<OfficialCode>xxx</OfficialCode>
					<Phone>xxx</Phone>
					<Status>student</Status>
				</User>
			</Users>'; // UTF-8
		$xml2 = '
			<?xml version="1.0" encoding="WINDOWS-1251"?>
			<Users>
				<User>
					<Username>username1</Username>
					<Lastname>xxx</Lastname>
					<Firstname>'.chr(200).chr(226).chr(224).chr(237).'</Firstname>
					<Password>xxx</Password>
					<Email>xxx@xx.xx</Email>
					<OfficialCode>xxx</OfficialCode>
					<Phone>xxx</Phone>
					<Status>student</Status>
				</User>
			</Users>'; // WINDOWS-1251
		$res1 = api_utf8_decode_xml($xml1, 'WINDOWS-1251');
		$this->assertTrue($res1 === $xml2);
		//var_dump(preg_replace(array('/\r?\n/m', '/\t/m'), array('<br />', '&nbsp;&nbsp;&nbsp;&nbsp;'), htmlspecialchars($res1)));
	}

/**
 * ----------------------------------------------------------------------------
 * String validation functions concerning certain encodings
 * ----------------------------------------------------------------------------
 */

	public function test_api_is_valid_utf8() {
		$string = 'áéíóú1@\/-ḉ`´';
		$res = api_is_valid_utf8($string);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function test_api_is_valid_ascii() {
		$string = 'áéíóú'; // UTF-8
		$res = api_is_valid_ascii($string);
		$this->assertTrue(is_bool($res));
		$this->assertTrue(!$res);
		//var_dump($res);
	}


/**
 * ----------------------------------------------------------------------------
 * Language management functions
 * ----------------------------------------------------------------------------
 */

	public function test_api_is_language_supported() {
		$language1 = 'english';
		$language2 = 'english_org';
		$language3 = 'EnGlIsh';
		$language4 = 'EnGlIsh_oRg';
		$language5 = 'french';
		$language6 = 'french_corporate';
		$language7 = 'frEncH';
		$language8 = 'freNch_corPorAte';
		$language9 = 'xxxxxxxxxxxxxx';
		$res1 = api_is_language_supported($language1);
		$res2 = api_is_language_supported($language2);
		$res3 = api_is_language_supported($language3);
		$res4 = api_is_language_supported($language4);
		$res5 = api_is_language_supported($language5);
		$res6 = api_is_language_supported($language6);
		$res7 = api_is_language_supported($language7);
		$res8 = api_is_language_supported($language8);
		$res9 = api_is_language_supported($language9);
		$this->assertTrue(
			$res1 === true
			&& $res2 === true
			&& $res3 === true
			&& $res4 === true
			&& $res5 === true
			&& $res6 === true
			&& $res7 === true
			&& $res8 === true
			&& $res9 === false
		);
		//var_dump($res1);
		//var_dump($res2);
		//var_dump($res3);
		//var_dump($res4);
		//var_dump($res5);
		//var_dump($res6);
		//var_dump($res7);
		//var_dump($res8);
		//var_dump($res9);
	}

	public function test_api_get_valid_language() {
		$enabled_languages_info = api_get_languages();
		$enabled_languages = $enabled_languages_info['folder'];
		$language = array();
		$language[] = '   '.strtoupper(api_get_interface_language()).'    ';
		$language[] = " \t   ".strtoupper(api_get_setting('platformLanguage'))."   \t ";
		$language[] = 'xxxxxxxxxxxxxx';
		$language[] = '   \t'.strtoupper('bulgarian').'    ';
		$res = array();
		$res[] = api_get_valid_language($language[1]);
		$res[] = api_get_valid_language($language[2]);
		$res[] = api_get_valid_language($language[3]);
		$res[] = api_get_valid_language($language[4]);
		$expected = array();
		foreach ($language as $value) {
			$value = str_replace('_km', '_KM', strtolower(trim($value)));
			if (empty($value) || !in_array($value, $enabled_languages) || !api_is_language_supported($value)) {
				$value = api_get_setting('platformLanguage');
			}
			$expected = $value;
		}
		$is_ok = true;
		foreach ($language as $key => $value) {
			$is_ok = $is_ok && ($value === $res[$key]);
		}
		//var_dump($res);
		//var_dump($expected);
	}

	public function test_api_purify_language_id() {
		$language = 'english_org';
		$res = api_purify_language_id($language);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == 'english');
		//var_dump($res);
	}

	function test_api_get_language_isocode() {
		$test_language_table = array(
			'*** invalid entry ***' => null, // An invalid entry.
			'arabic' => 'ar',
			'arabic_unicode' => 'ar',
			'asturian' => 'ast',
			'bosnian' => 'bs',
			'brazilian' => 'pt-BR',
			'bulgarian' => 'bg',
			'catalan' => 'ca',
			'croatian' => 'hr',
			'czech' => 'cs',
			'danish' => 'da',
			'dari' => 'prs',
			'dutch' => 'nl',
			'dutch_corporate' => 'nl',
			'english' => 'en',
			'english_org' => 'en',
			'esperanto' => 'eo',
			'euskera' => 'eu',
			'finnish' => 'fi',
			'french' => 'fr',
			'french_corporate' => 'fr',
			'french_KM' => 'fr',
			'french_org' => 'fr',
			'french_unicode' => 'fr',
			'friulian' => 'fur',
			'galician' => 'gl',
			'georgian' => 'ka',
			'german' => 'de',
			'greek' => 'el',
			'hebrew' => 'he',
			'hungarian' => 'hu',
			'indonesian' => 'id',
			'italian' => 'it',
			'japanese' => 'ja',
			'japanese_unicode' => 'ja',
			'korean' => 'ko',
			'latvian' => 'lv',
			'lithuanian' => 'lt',
			'macedonian' => 'mk',
			'malay' => 'ms',
			'norwegian' => 'no',
			'occitan' => 'oc',
			'pashto' => 'ps',
			'persian' => 'fa',
			'polish' => 'pl',
			'portuguese' => 'pt',
			'quechua_cusco' => 'qu',
			'romanian' => 'ro',
			'russian' => 'ru',
			'russian_unicode' => 'ru',
			'serbian' => 'sr',
			'simpl_chinese' => 'zh',
			'simpl_chinese_unicode' => 'zh',
			'slovak' => 'sk',
			'slovenian' => 'sl',
			'slovenian_unicode' => 'sl',
			'spanish' => 'es',
			'spanish_latin' => 'es',
			'swahili' => 'sw',
			'swedish' => 'sv',
			'thai' => 'th',
			'trad_chinese' => 'zh-TW',
			'trad_chinese_unicode' => 'zh-TW',
			'turkce' => 'tr',
			'ukrainian' => 'uk',
			'vietnamese' => 'vi',
			'yoruba' => 'yo'
		);
		$res = array();
		foreach ($test_language_table as $language => $expected_result) {
			$test_result = api_get_language_isocode($language);
			$res[$language] = array(
				'expected_result' => $expected_result,
				'test_result' => $test_result,
				'is_ok' => $expected_result === $test_result
			);
		}
		$this->assertTrue(is_array($res));
		$is_ok = true;
		foreach ($res as $language => $test_case) {
			$is_ok = $is_ok && $test_case['is_ok'];
		}
		$this->assertTrue($is_ok);
		//var_dump($res);
		//foreach ($res as $language => $test_case) { echo ($test_case['is_ok'] ? '<span style="color: green; font-weight: bold;">Ok</span>' : '<span style="color: red; font-weight: bold;">Failed</span>').' '.$language.' => '.(is_null($test_case['test_result']) ? 'NULL' : $test_case['test_result']).'<br />'; }
	}

	public function test_api_is_latin1_compatible() {
		$language = 'portuguese';
		$res = api_is_latin1_compatible($language);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

}

?>