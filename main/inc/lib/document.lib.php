<?php

/* For licensing terms, see /dokeos_license.txt */

/**
==============================================================================
*	This is the document library for Dokeos.
*	It is / will be used to provide a service layer to all document-using tools.
*	and eliminate code duplication fro group documents, scorm documents, main documents.
*	Include/require it in your code to use its functionality.
*
*	@version 1.1, January 2005
*	@package dokeos.library
==============================================================================
*/

/*
==============================================================================
	DOCUMENTATION
	use the functions like this: DocumentManager::get_course_quota()
==============================================================================
*/

/*
==============================================================================
		CONSTANTS
==============================================================================
*/

define("DISK_QUOTA_FIELD", "disk_quota"); //name of the database field
/** default quota for the course documents folder */
define("DEFAULT_DOCUMENT_QUOTA", api_get_setting('default_document_quotum'));

/*
==============================================================================
		VARIABLES
==============================================================================
*/

$sys_course_path = api_get_path(SYS_COURSE_PATH);
$baseServDir = api_get_path(SYS_PATH);
$baseServUrl = $_configuration['url_append']."/";
$baseWorkDir = $sys_course_path.(!empty($courseDir)?$courseDir:'');

/*
==============================================================================
		DocumentManager CLASS
		the class and its functions
==============================================================================
*/

/**
 *	@package dokeos.library
 */
class DocumentManager {
	private function __construct() {

	}
	/**
	* @return the document folder quuta of the current course, in bytes
	* @todo eliminate globals
	*/
	public static function get_course_quota () {
		global $_course, $maxFilledSpace;
		$course_code = Database::escape_string($_course['sysCode']);
		$course_table = Database::get_main_table(TABLE_MAIN_COURSE);

		$sql_query = "SELECT ".DISK_QUOTA_FIELD." FROM $course_table WHERE code = '$course_code'";
		$sql_result = Database::query($sql_query, __FILE__, __LINE__);
		$result = Database::fetch_array($sql_result);
		$course_quota = $result[DISK_QUOTA_FIELD];

		if ($course_quota == NULL)
		{
			//course table entry for quota was null
			//use default value
			$course_quota = DEFAULT_DOCUMENT_QUOTA;
		}

		return $course_quota;
	}

	/**
	*	Get the content type of a file by checking the extension
	*	We could use mime_content_type() with php-versions > 4.3,
	*	but this doesn't work as it should on Windows installations
	*
	*	@param string $filename or boolean TRUE to return complete array
	*	@author ? first version
	*	@author Bert Vanderkimpen
	*
	*/
	public static function file_get_mime_type ($filename) {
		//all mime types in an array (from 1.6, this is the authorative source)
		//please keep this alphabetical if you add something to this list!!!
	    $mime_types=array(
			"ai" => "application/postscript",
			"aif" => "audio/x-aiff",
    		"aifc" => "audio/x-aiff",
   			"aiff" => "audio/x-aiff",
		    "asf" => "video/x-ms-asf",
		    "asc" => "text/plain",
		    "au" => "audio/basic",
		    "avi" => "video/x-msvideo",
		    "bcpio" => "application/x-bcpio",
		    "bin" => "application/octet-stream",
		    "bmp" => "image/bmp",
		    "cdf" => "application/x-netcdf",
		    "class" => "application/octet-stream",
		    "cpio" => "application/x-cpio",
		    "cpt" => "application/mac-compactpro",
		    "csh" => "application/x-csh",
		    "css" => "text/css",
		    "dcr" => "application/x-director",
		    "dir" => "application/x-director",
		    "djv" => "image/vnd.djvu",
		    "djvu" => "image/vnd.djvu",
		    "dll" => "application/octet-stream",
		    "dmg" => "application/x-diskcopy",
		    "dms" => "application/octet-stream",
		    "doc" => "application/msword",
		    "docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
		    "dvi" => "application/x-dvi",
		    "dwg" => "application/vnd.dwg",
		    "dxf" => "application/vnd.dxf",
		    "dxr" => "application/x-director",
		    "eps" => "application/postscript",
		    "etx" => "text/x-setext",
		    "exe" => "application/octet-stream",
		    "ez" => "application/andrew-inset",
		    "gif" => "image/gif",
		    "gtar" => "application/x-gtar",
		    "gz" => "application/x-gzip",
		    "hdf" => "application/x-hdf",
		    "hqx" => "application/mac-binhex40",
		    "htm" => "text/html",
		    "html" => "text/html",
		    "ice" => "x-conference-xcooltalk",
		    "ief" => "image/ief",
		    "iges" => "model/iges",
		    "igs" => "model/iges",
		    "jar" => "application/java-archiver",
		    "jpe" => "image/jpeg",
		    "jpeg" => "image/jpeg",
		    "jpg" => "image/jpeg",
		    "js" => "application/x-javascript",
		    "kar" => "audio/midi",
		    "latex" => "application/x-latex",
		    "lha" => "application/octet-stream",
		    "lzh" => "application/octet-stream",
		    "m1a" => "audio/mpeg",
		    "m2a" => "audio/mpeg",
		    "m3u" => "audio/x-mpegurl",
		    "man" => "application/x-troff-man",
		    "me" => "application/x-troff-me",
		    "mesh" => "model/mesh",
		    "mid" => "audio/midi",
		    "midi" => "audio/midi",
		    "mov" => "video/quicktime",
		    "movie" => "video/x-sgi-movie",
		    "mp2" => "audio/mpeg",
		    "mp3" => "audio/mpeg",
		    "mp4" => "video/mpeg4-generic",
		    "mpa" => "audio/mpeg",
		    "mpe" => "video/mpeg",
		    "mpeg" => "video/mpeg",
		    "mpg" => "video/mpeg",
		    "mpga" => "audio/mpeg",
		    "ms" => "application/x-troff-ms",
		    "msh" => "model/mesh",
		    "mxu" => "video/vnd.mpegurl",
		    "nc" => "application/x-netcdf",
		    "oda" => "application/oda",
		    "pbm" => "image/x-portable-bitmap",
		    "pct" => "image/pict",
		    "pdb" => "chemical/x-pdb",
		    "pdf" => "application/pdf",
		    "pgm" => "image/x-portable-graymap",
		    "pgn" => "application/x-chess-pgn",
		    "pict" => "image/pict",
		    "png" => "image/png",
		    "pnm" => "image/x-portable-anymap",
		    "ppm" => "image/x-portable-pixmap",
		    "ppt" => "application/vnd.ms-powerpoint",
		    "pps" => "application/vnd.ms-powerpoint",
		    "pptx" => "application/vnd.openxmlformats-officedocument.presentationml.presentation ",
		    "ps" => "application/postscript",
		    "qt" => "video/quicktime",
		    "ra" => "audio/x-realaudio",
		    "ram" => "audio/x-pn-realaudio",
		    "rar" => "image/x-rar-compressed",
		    "ras" => "image/x-cmu-raster",
		    "rgb" => "image/x-rgb",
		    "rm" => "audio/x-pn-realaudio",
		    "roff" => "application/x-troff",
		    "rpm" => "audio/x-pn-realaudio-plugin",
		    "rtf" => "text/rtf",
		    "rtx" => "text/richtext",
		    "sgm" => "text/sgml",
		    "sgml" => "text/sgml",
		    "sh" => "application/x-sh",
		    "shar" => "application/x-shar",
		    "silo" => "model/mesh",
		    "sib" => "application/X-Sibelius-Score",
		    "sit" => "application/x-stuffit",
		    "skd" => "application/x-koan",
		    "skm" => "application/x-koan",
		    "skp" => "application/x-koan",
		    "skt" => "application/x-koan",
		    "smi" => "application/smil",
		    "smil" => "application/smil",
		    "snd" => "audio/basic",
		    "so" => "application/octet-stream",
		    "spl" => "application/x-futuresplash",
		    "src" => "application/x-wais-source",
		    "sv4cpio" => "application/x-sv4cpio",
		    "sv4crc" => "application/x-sv4crc",
		    "svf" => "application/vnd.svf",
		    "swf" => "application/x-shockwave-flash",
		    "sxc" => "application/vnd.sun.xml.calc",
		    "sxi" => "application/vnd.sun.xml.impress",
		    "sxw" => "application/vnd.sun.xml.writer",
		    "t" => "application/x-troff",
		    "tar" => "application/x-tar",
		    "tcl" => "application/x-tcl",
		    "tex" => "application/x-tex",
		    "texi" => "application/x-texinfo",
		    "texinfo" => "application/x-texinfo",
		    "tga" => "image/x-targa",
		    "tif" => "image/tif",
		    "tiff" => "image/tiff",
		    "tr" => "application/x-troff",
		    "tsv" => "text/tab-seperated-values",
		    "txt" => "text/plain",
		    "ustar" => "application/x-ustar",
		    "vcd" => "application/x-cdlink",
		    "vrml" => "model/vrml",
		    "wav" => "audio/x-wav",
		    "wbmp" => "image/vnd.wap.wbmp",
		    "wbxml" => "application/vnd.wap.wbxml",
		    "wml" => "text/vnd.wap.wml",
		    "wmlc" => "application/vnd.wap.wmlc",
		    "wmls" => "text/vnd.wap.wmlscript",
		    "wmlsc" => "application/vnd.wap.wmlscriptc",
		    "wma" => "video/x-ms-wma",
		    "wmv" => "audio/x-ms-wmv",
		    "wrl" => "model/vrml",
		    "xbm" => "image/x-xbitmap",
		    "xht" => "application/xhtml+xml",
		    "xhtml" => "application/xhtml+xml",
		    "xls" => "application/vnd.ms-excel",
		    "xlsx" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
		    "xml" => "text/xml",
		    "xpm" => "image/x-xpixmap",
		    "xsl" => "text/xml",
		    "xwd" => "image/x-windowdump",
		    "xyz" => "chemical/x-xyz",
		    "zip" => "application/zip"
		);

		if ($filename === TRUE)
		{
			return $mime_types;
		}

		//get the extension of the file
		$extension = explode('.', $filename);

		//$filename will be an array if a . was found
		if (is_array($extension))
		{
			$extension = (strtolower($extension[sizeof($extension) - 1]));
		}
		//file without extension
		else
		{
			$extension = 'empty';
		}

		//if the extension is found, return the content type
		if (isset ($mime_types[$extension]))
			return $mime_types[$extension];
		//else return octet-stream
		return "application/octet-stream";
	}

	/**
	*	@return true if the user is allowed to see the document, false otherwise
	*	@author Sergio A Kessler, first version
	*	@author Roan Embrechts, bugfix
	*   @todo ??not only check if a file is visible, but also check if the user is allowed to see the file??
	*/
	public static function file_visible_to_user ($this_course, $doc_url) {
		$current_session_id = api_get_session_id();
		
		$is_allowed_to_edit = api_is_allowed_to_edit(null,true);
		
		if ($is_allowed_to_edit)
		{
			return true;
		}
		else
		{
			$tbl_document = Database::get_course_table(TABLE_DOCUMENT);
			$tbl_item_property = $this_course.'item_property';
			$doc_url = Database::escape_string($doc_url);
			//$doc_url = addslashes($doc_url);
			$query = "SELECT 1 FROM $tbl_document AS docs,$tbl_item_property AS props
					  WHERE props.tool = 'document' AND docs.id=props.ref AND props.visibility <> '1' AND docs.path = '$doc_url'";
			//echo $query;
			$result = Database::query($query, __FILE__, __LINE__);

			return (Database::num_rows($result) == 0);
		}
	}

	/**
	* This function streams a file to the client
	*
	* @param string $full_file_name
	* @param boolean $forced
	* @param string $name
	* @return false if file doesn't exist, true if stream succeeded
	*/
	public static function file_send_for_download ($full_file_name, $forced = false, $name = '') {
		if (!is_file($full_file_name))
		{
			return false;
		}
		$filename = ($name == '') ? basename($full_file_name) : $name;
		$len = filesize($full_file_name);

		if ($forced)
		{
			//force the browser to save the file instead of opening it

			header('Content-type: application/octet-stream');
			//header('Content-Type: application/force-download');
			header('Content-length: '.$len);
			if (preg_match("/MSIE 5.5/", $_SERVER['HTTP_USER_AGENT']))
			{
				header('Content-Disposition: filename= "'.$filename.'"');
			}
			else
			{
				header('Content-Disposition: attachment; filename= "'.$filename.'"');
			}
			if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE'))
			{
				header('Pragma: ');
				header('Cache-Control: ');
				header('Cache-Control: public'); // IE cannot download from sessions without a cache
			}
			header('Content-Description: "'.$filename.'"');
			header('Content-transfer-encoding: binary');

			$fp = fopen($full_file_name, 'r');
			fpassthru($fp);
			return true;
		}
		else
		{
			//no forced download, just let the browser decide what to do according to the mimetype

			$content_type = self::file_get_mime_type($filename);
			header('Expires: Wed, 01 Jan 1990 00:00:00 GMT');
			header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
            // Commented to avoid double caching declaration when playing with IE and HTTPS
			//header('Cache-Control: no-cache, must-revalidate');
			//header('Pragma: no-cache');
			header('Content-type: '.$content_type);
			header('Content-Length: '.$len);
	
			$user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
			if (strpos($user_agent, 'msie'))
			{
				header('Content-Disposition: ; filename= "'.$filename.'"');
			}
			else
			{
				header('Content-Disposition: inline; filename= "'.$filename.'"');
			}
			readfile($full_file_name);
			return true;
		}
	}

	/**
	* This function streams a string to the client for download.
	* You have to ensure that the calling script then stops processing (exit();)
	* otherwise it may cause subsequent use of the page to want to download
	* other pages in php rather than interpreting them.
	*
	* @param string The string contents
	* @param boolean Whether "save" mode is forced (or opening directly authorized)
	* @param string The name of the file in the end (including extension)
	* @return false if file doesn't exist, true if stream succeeded
	*/
	public static function string_send_for_download ($full_string, $forced = false, $name = '') {
		$filename = $name;
		$len = strlen($full_string);

		if ($forced)
		{
			//force the browser to save the file instead of opening it

			header('Content-type: application/octet-stream');
			//header('Content-Type: application/force-download');
			header('Content-length: '.$len);
			if (preg_match("/MSIE 5.5/", $_SERVER['HTTP_USER_AGENT']))
			{
				header('Content-Disposition: filename= '.$filename);
			}
			else
			{
				header('Content-Disposition: attachment; filename= '.$filename);
			}
			if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE'))
			{
				header('Pragma: ');
				header('Cache-Control: ');
				header('Cache-Control: public'); // IE cannot download from sessions without a cache
			}
			header('Content-Description: '.$filename);
			header('Content-transfer-encoding: binary');

			//$fp = fopen($full_string, 'r');
			//fpassthru($fp);
			echo $full_string;
			return true;
			//You have to ensure that the calling script then stops processing (exit();)
			//otherwise it may cause subsequent use of the page to want to download
			//other pages in php rather than interpreting them.
		}
		else
		{
			//no forced download, just let the browser decide what to do according to the mimetype

			$content_type = self::file_get_mime_type($filename);
			header('Expires: Wed, 01 Jan 1990 00:00:00 GMT');
			header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
			header('Cache-Control: no-cache, must-revalidate');
			header('Pragma: no-cache');
			header('Content-type: '.$content_type);
			header('Content-Length: '.$len);
			$user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
			if (strpos($user_agent, 'msie'))
			{
				header('Content-Disposition: ; filename= '.$filename);
			}
			else
			{
				header('Content-Disposition: inline; filename= '.$filename);
			}
			echo($full_string);
			//You have to ensure that the calling script then stops processing (exit();)
			//otherwise it may cause subsequent use of the page to want to download
			//other pages in php rather than interpreting them.
			return true;
		}
	}

	/**
	* Fetches all document data for the given user/group
	*
	* @param array $_course
	* @param string $path
	* @param int $to_group_id
	* @param int $to_user_id
	* @param boolean $can_see_invisible
	* @return array with all document data
	*/
	public static function get_all_document_data ($_course, $path = '/', $to_group_id = 0, $to_user_id = NULL, $can_see_invisible = false) {
		$TABLE_ITEMPROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY, $_course['dbName']);
		$TABLE_DOCUMENT = Database::get_course_table(TABLE_DOCUMENT, $_course['dbName']);
		$TABLE_COURSE = Database::get_main_table(TABLE_MAIN_COURSE);

		//if to_user_id = NULL -> change query (IS NULL)
		//$to_user_id = (is_null($to_user_id))?'IS NULL':'= '.$to_user_id;
		$to_group_is_null = '';
		if (!is_null($to_user_id)) {
			$to_field = 'last.to_user_id';
			$to_value = $to_user_id;
		} else {
			$to_field = 'last.to_group_id';
			$to_value = $to_group_id;
			// Olds training has NULL value in the to_group_id field
			if ($to_group_id == 0) {
			  $to_group_is_null = " OR $to_field IS NULL";
			}
		}

		//escape underscores in the path so they don't act as a wildcard
		$path 		= Database::escape_string(str_replace('_', '\_', $path));
		$to_user_id = Database::escape_string($to_user_id);
		$to_value	= Database::escape_string($to_value);

		//if they can't see invisible files, they can only see files with visibility 1
		$visibility_bit = ' = 1';
		//if they can see invisible files, only deleted files (visibility 2) are filtered out
		if ($can_see_invisible) {
			$visibility_bit = ' <> 2';
		}

		//the given path will not end with a slash, unless it's the root '/'
		//so no root -> add slash
		$added_slash = ($path == '/') ? '' : '/';
		
		//condition for the session		
		$current_session_id = api_get_session_id();
                $condition_session = " AND (id_session = '$current_session_id' OR id_session = '0')";
                //$condition_session = " AND (id_session = '$current_session_id' OR (id_session = '0' AND insert_date <= (SELECT creation_date FROM $TABLE_COURSE WHERE code = '{$_course[id]}')))";
        
		$sql = "SELECT *
						FROM  ".$TABLE_ITEMPROPERTY."  AS last, ".$TABLE_DOCUMENT."  AS docs
						WHERE docs.id = last.ref
						AND docs.path LIKE '".$path.$added_slash."%'
						AND docs.path NOT LIKE '".$path.$added_slash."%/%'
						AND last.tool = '".TOOL_DOCUMENT."'
						AND (".$to_field." = ".$to_value." $to_group_is_null)
						AND last.visibility".$visibility_bit . $condition_session;								
		$result = Database::query($sql,__FILE__,__LINE__);
		if ($result && Database::num_rows($result) != 0) {
			while ($row = Database::fetch_array($result,'ASSOC')) {
				if($row['filetype']=='file' && pathinfo($row['path'],PATHINFO_EXTENSION) == 'html') {
					//Templates management
					$table_template = Database::get_main_table(TABLE_MAIN_TEMPLATES);
					$sql_is_template = "SELECT id FROM $table_template
										WHERE course_code='".$_course['id']."'
										AND user_id='".api_get_user_id()."'
										AND ref_doc='".$row['id']."'";
					$template_result = Database::query($sql_is_template);
					if (Database::num_rows($template_result)>0) {
						$row['is_template'] = 1;
					} else {
						$row['is_template'] = 0;
					}
				}

				$document_data[$row['id']] = $row;
				//$document_data[] = $row;
			}
			return $document_data;
		}
		else
		{
			//display_error("Error getting document info from database (".mysql_error().")!");
			return false;
		}
	}

	/**
	 * Gets the paths of all folders in a course
	 * can show all folders (exept for the deleted ones) or only visible ones
	 * @param array $_course
	 * @param boolean $can_see_invisible
	 * @param int $to_group_id
	 * @return array with paths
	 */
	public static function get_all_document_folders ($_course, $to_group_id = '0', $can_see_invisible = false) {
		$TABLE_ITEMPROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY, $_course['dbName']);
		$TABLE_DOCUMENT = Database::get_course_table(TABLE_DOCUMENT, $_course['dbName']);
		/*if(empty($doc_url)){
			$to_group_id = '0';
		} else {
			$to_group_id = Database::escape_string($to_group_id);
		}*/
		if (!empty($to_group_id)) {
		   $to_group_id = intval($to_group_id);
		}

		if ($can_see_invisible)
		{
			//condition for the session
			$session_id = api_get_session_id();
			$condition_session = api_get_session_condition($session_id);
			$sql = "SELECT path
								FROM  ".$TABLE_ITEMPROPERTY."  AS last, ".$TABLE_DOCUMENT."  AS docs
								WHERE docs.id = last.ref
								AND docs.filetype = 'folder' 
								AND last.tool = '".TOOL_DOCUMENT."' 
								AND last.to_group_id = ".$to_group_id." 
								AND last.visibility <> 2 $condition_session";

			$result = Database::query($sql, __FILE__, __LINE__);
			if ($result && Database::num_rows($result) != 0)
			{
				while ($row = Database::fetch_array($result,'ASSOC'))
				{
					$document_folders[] = $row['path'];
				}

				//sort($document_folders);
				natsort($document_folders);

				//return results
				return $document_folders;
			}
			else
			{
				return false;
			}
		}
		//no invisible folders
		else
		{
			//condition for the session
			$session_id = api_get_session_id();
			$condition_session = api_get_session_condition($session_id);
			//get visible folders
			$visible_sql = "SELECT path
						FROM  ".$TABLE_ITEMPROPERTY."  AS last, ".$TABLE_DOCUMENT."  AS docs
						WHERE docs.id = last.ref
						AND docs.filetype = 'folder' 
						AND last.tool = '".TOOL_DOCUMENT."' 
						AND last.to_group_id = ".$to_group_id." 
						AND last.visibility = 1 $condition_session";
			$visibleresult = Database::query($visible_sql, __FILE__, __LINE__);
			while ($all_visible_folders = Database::fetch_array($visibleresult,'ASSOC'))
			{
				$visiblefolders[] = $all_visible_folders['path'];
				//echo "visible folders: ".$all_visible_folders['path']."<br>";
			}
			//condition for the session
			$session_id = api_get_session_id();
			$condition_session = api_get_session_condition($session_id);
			//get invisible folders
			$invisible_sql = "SELECT path
						FROM  ".$TABLE_ITEMPROPERTY."  AS last, ".$TABLE_DOCUMENT."  AS docs
						WHERE docs.id = last.ref
						AND docs.filetype = 'folder' 
						AND last.tool = '".TOOL_DOCUMENT."' 
						AND last.to_group_id = ".$to_group_id." 
						AND last.visibility = 0 $condition_session";
			$invisibleresult = Database::query($invisible_sql, __FILE__, __LINE__);
			while ($invisible_folders = Database::fetch_array($invisibleresult,'ASSOC'))
			{
				//condition for the session
				$session_id = api_get_session_id();
				$condition_session = api_get_session_condition($session_id);
				//get visible folders in the invisible ones -> they are invisible too
				//echo "invisible folders: ".$invisible_folders['path']."<br>";
				$folder_in_invisible_sql = "SELECT path
								FROM  ".$TABLE_ITEMPROPERTY."  AS last, ".$TABLE_DOCUMENT."  AS docs
								WHERE docs.id = last.ref
								AND docs.path LIKE '".Database::escape_string($invisible_folders['path'])."/%' 
								AND docs.filetype = 'folder' 
								AND last.tool = '".TOOL_DOCUMENT."' 
								AND last.to_group_id = ".$to_group_id." 
								AND last.visibility = 1 $condition_session";
				$folder_in_invisible_result = Database::query($folder_in_invisible_sql, __FILE__, __LINE__);
				while ($folders_in_invisible_folder = Database::fetch_array($folder_in_invisible_result,'ASSOC'))
				{
					$invisiblefolders[] = $folders_in_invisible_folder['path'];
					//echo "<br>folders in invisible folders: ".$folders_in_invisible_folder['path']."<br><br><br>";
				}
			}
			//if both results are arrays -> //calculate the difference between the 2 arrays -> only visible folders are left :)
			if (is_array($visiblefolders) && is_array($invisiblefolders))
			{
				$document_folders = array_diff($visiblefolders, $invisiblefolders);

				//sort($document_folders);
				natsort($document_folders);

				return $document_folders;
			}
			//only visible folders found
			elseif (is_array($visiblefolders))
			{
				//sort($visiblefolders);
				natsort($visiblefolders);

				return $visiblefolders;
			}
			//no visible folders found
			else
			{
				return false;
			}
		}
	}
	/**
	 * This check if a document has the readonly property checked, then see if the user
	 * is the owner of this file, if all this is true then return true.
	 *
	 * @param array  $_course
	 * @param int    $user_id id of the current user
	 * @param string $file path stored in the database
	 * @param int    $document_id in case you dont have the file path ,insert the id of the file here and leave $file in blank ''
	 * @return boolean true/false
	 **/
	public static function check_readonly ($_course,$user_id,$file,$document_id='',$to_delete=false) {
		if(!(!empty($document_id) && is_numeric($document_id)))
		{
			$document_id = self::get_document_id($_course, $file);
		}

		$TABLE_PROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY, $_course['dbName']);
		$TABLE_DOCUMENT = Database::get_course_table(TABLE_DOCUMENT, $_course['dbName']);

		if ($to_delete)
		{
			if (self::is_folder($_course, $document_id))
			{
				if (!empty($file))
				{
					$path = Database::escape_string($file);
					$what_to_check_sql = "SELECT td.id, readonly, tp.insert_user_id FROM ".$TABLE_DOCUMENT." td , $TABLE_PROPERTY tp
									WHERE tp.ref= td.id and (path='".$path."' OR path LIKE BINARY '".$path."/%' ) ";
					//get all id's of documents that are deleted
					$what_to_check_result = Database::query($what_to_check_sql, __FILE__, __LINE__);

					if ($what_to_check_result && Database::num_rows($what_to_check_result) != 0)
					{
						// file with readonly set to 1 exist?
						$readonly_set=false;
						while ($row = Database::fetch_array($what_to_check_result))
						{
							//query to delete from item_property table
							//echo $row['id']; echo "<br>";
							if ($row['readonly']==1)
							{
								if (!($row['insert_user_id'] == $user_id))
								{
									$readonly_set=true;
									break;
								}

							}
						}

						if ($readonly_set)
						{
							return true;
						}
					}
				}
				return false;
			}
		}



		if (!empty($document_id))
		{
			$sql= 'SELECT a.insert_user_id, b.readonly FROM '.$TABLE_PROPERTY.' a,'.$TABLE_DOCUMENT.' b
				   WHERE a.ref = b.id and a.ref='.$document_id.' LIMIT 1';
			$resultans   =  Database::query($sql, __FILE__, __LINE__);
			$doc_details =  Database ::fetch_array($resultans,'ASSOC');

			if($doc_details['readonly']==1)
			{
				if ( $doc_details['insert_user_id'] == $user_id || api_is_platform_admin() )
				{
						return false;
				}
				else
				{
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * This check if a document is a folder or not
	 * @param array  $_course
	 * @param int    $document_id of the item
	 * @return boolean true/false
	 **/
	public static function is_folder ($_course, $document_id) {
		$TABLE_DOCUMENT = Database::get_course_table(TABLE_DOCUMENT, $_course['dbName']);
		//if (!empty($document_id))
		$document_id = Database::escape_string($document_id);
		$resultans   =  Database::query('SELECT filetype FROM '.$TABLE_DOCUMENT.' WHERE id='.$document_id.'', __FILE__, __LINE__);
		$result=  Database::fetch_array($resultans,'ASSOC');
		if ($result['filetype']=='folder') {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * This deletes a document by changing visibility to 2, renaming it to filename_DELETED_#id
	 * Files/folders that are inside a deleted folder get visibility 2
	 *
	 * @param array $_course
	 * @param string $path, path stored in the database
	 * @param string ,$base_work_dir, path to the documents folder
	 * @return boolean true/false
	 * @todo now only files/folders in a folder get visibility 2, we should rename them too.
	 */
	public static function delete_document ($_course, $path, $base_work_dir) {
		$TABLE_DOCUMENT = Database :: get_course_table(TABLE_DOCUMENT, $_course['dbName']);
		$TABLE_ITEMPROPERTY = Database :: get_course_table(TABLE_ITEM_PROPERTY, $_course['dbName']);
		//first, delete the actual document...
		$document_id = self :: get_document_id($_course, $path);
		$new_path = $path.'_DELETED_'.$document_id;
		$current_session_id = api_get_session_id();
		if ($document_id)
		{
			if (api_get_setting('permanently_remove_deleted_files') == 'true') //deleted files are *really* deleted
			{
				$what_to_delete_sql = "SELECT id FROM ".$TABLE_DOCUMENT." WHERE path='".$path."' OR path LIKE BINARY '".$path."/%'";
				//get all id's of documents that are deleted
				$what_to_delete_result = Database::query($what_to_delete_sql, __FILE__, __LINE__);

				if ($what_to_delete_result && Database::num_rows($what_to_delete_result) != 0)
				{
					//needed to deleted medadata
					require_once (api_get_path(SYS_CODE_PATH).'metadata/md_funcs.php');
					require_once(api_get_path(LIBRARY_PATH).'fileManage.lib.php');
					$mdStore = new mdstore(TRUE);

					//delete all item_property entries
					while ($row = Database::fetch_array($what_to_delete_result))
					{
						//query to delete from item_property table
						//avoid wrong behavior

						//$remove_from_item_property_sql = "DELETE FROM ".$TABLE_ITEMPROPERTY." WHERE ref = ".$row['id']." AND tool='".TOOL_DOCUMENT."'";
						api_item_property_update($_course, TOOL_DOCUMENT, $row['id'], 'delete', api_get_user_id(),null,null,null,null,$current_session_id);
																								
						//query to delete from document table
						$remove_from_document_sql = "DELETE FROM ".$TABLE_DOCUMENT." WHERE id = ".$row['id']."";
 						self::unset_document_as_template($row['id'],$_course, api_get_user_id());
						//echo($remove_from_item_property_sql.'<br>');
						//Database::query($remove_from_item_property_sql, __FILE__, __LINE__);
						//echo($remove_from_document_sql.'<br>');
						Database::query($remove_from_document_sql, __FILE__, __LINE__);

						//delete metadata
						$eid = 'Document'.'.'.$row['id'];
						$mdStore->mds_delete($eid);
						$mdStore->mds_delete_offspring($eid);

					}
					self::delete_document_from_search_engine(api_get_course_id(), $document_id);
					//delete documents, do it like this so metadata get's deleted too
					//update_db_info('delete', $path);
					//throw it away
					my_delete($base_work_dir.$path);

					return true;
				}
				else
				{
					return false;
				}
			}
			else //set visibility to 2 and rename file/folder to qsdqsd_DELETED_#id
			{
				if (api_item_property_update($_course, TOOL_DOCUMENT, $document_id, 'delete', api_get_user_id(),null,null,null,null,$current_session_id))
				{	
					//echo('item_property_update OK');
					if (is_file($base_work_dir.$path) || is_dir($base_work_dir.$path) )
                    {
                        if(rename($base_work_dir.$path, $base_work_dir.$new_path))
    					{
    						 self::unset_document_as_template($document_id, api_get_course_id(), api_get_user_id());
    						 $sql = "UPDATE $TABLE_DOCUMENT set path='".$new_path."' WHERE id='".$document_id."'";
    						if (Database::query($sql, __FILE__, __LINE__))
    						{
    							//if it is a folder it can contain files
    							$sql = "SELECT id,path FROM ".$TABLE_DOCUMENT." WHERE path LIKE BINARY '".$path."/%'";
    							$result = Database::query($sql, __FILE__, __LINE__);
    							if ($result && Database::num_rows($result) > 0)
    							{
    								while ($deleted_items = Database::fetch_array($result,'ASSOC'))
    								{
    									//echo('to delete also: id '.$deleted_items['id']);
    									api_item_property_update($_course, TOOL_DOCUMENT, $deleted_items['id'], 'delete', api_get_user_id(),null,null,null,null,$current_session_id);
    									//Change path of subfolders and documents in database
    									$old_item_path = $deleted_items['path'];
    									$new_item_path = $new_path.substr($old_item_path, strlen($path));
    									/*/
    									 * trying to fix this bug FS#2681
    									echo $base_work_dir.$old_item_path;
    									echo "<br>";
    									echo $base_work_dir.$new_item_path;
    									echo "<br>";echo "<br>";
										rename($base_work_dir.$old_item_path, $base_work_dir.$new_item_path);
										*/
    									self::unset_document_as_template($deleted_items['id'], api_get_course_id(), api_get_user_id());
    									$sql = "UPDATE $TABLE_DOCUMENT set path = '".$new_item_path."' WHERE id = ".$deleted_items['id'];

    									Database::query($sql, __FILE__, __LINE__);
    								}
    							}

                                self::delete_document_from_search_engine(api_get_course_id(), $document_id);
    							return true;
    						}
    					}
                        else
                        {
                        	//Couldn't rename - file permissions problem?
                            error_log(__FILE__.' '.__LINE__.': Error renaming '.$base_work_dir.$path.' to '.$base_work_dir.$new_path.'. This is probably due to file permissions',0);
                        }
                    }
                    else
                    {	//echo $base_work_dir.$path;
                    	//The file or directory isn't there anymore (on the filesystem)
                        // This means it has been removed externally. To prevent a
                        // blocking error from happening, we drop the related items from the
                        // item_property and the document table.
                        error_log(__FILE__.' '.__LINE__.': System inconsistency detected. The file or directory '.$base_work_dir.$path.' seems to have been removed from the filesystem independently from the web platform. To restore consistency, the elements using the same path will be removed from the database',0);
                        $sql = "SELECT id FROM $TABLE_DOCUMENT WHERE path='".$path."' OR path LIKE BINARY '".$path."/%'";
                        $res = Database::query($sql,__FILE__,__LINE__);

                        self::delete_document_from_search_engine(api_get_course_id(), $document_id);

                        while ( $row = Database::fetch_array($res) )
                        {
                        	$sqlipd = "DELETE FROM $TABLE_ITEMPROPERTY WHERE ref = ".$row['id']." AND tool='".TOOL_DOCUMENT."'";
                            $resipd = Database::query($sqlipd,__FILE__,__LINE__);
                            self::unset_document_as_template($row['id'],api_get_course_id(), api_get_user_id());
                            $sqldd = "DELETE FROM $TABLE_DOCUMENT WHERE id = ".$row['id'];
                            $resdd = Database::query($sqldd,__FILE__,__LINE__);
                        }
                    }
				}
			}

		}

        return false;
	}

	/**
	 * Removes documents from search engine database
	 *
	 * @param string $course_id Course code
	 * @param int $document_id Document id to delete
	 */
	public static function delete_document_from_search_engine ($course_id, $document_id) {
		// remove from search engine if enabled
		if (api_get_setting('search_enabled') == 'true') {
			$tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
			$sql = 'SELECT * FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s LIMIT 1';
			$sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_DOCUMENT, $document_id);
			$res = Database::query($sql, __FILE__, __LINE__);
			if (Database::num_rows($res) > 0) {
				$row2 = Database::fetch_array($res);
				require_once(api_get_path(LIBRARY_PATH) .'search/DokeosIndexer.class.php');
				$di = new DokeosIndexer();
				$di->remove_document((int)$row2['search_did']);
			}
			$sql = 'DELETE FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s LIMIT 1';
			$sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_DOCUMENT, $document_id);
			Database::query($sql, __FILE__, __LINE__);

			// remove terms from db
			require_once(api_get_path(LIBRARY_PATH) .'specific_fields_manager.lib.php');
			delete_all_values_for_item($course_id, TOOL_DOCUMENT, $document_id);
		}
	}

	/**
	 * Gets the id of a document with a given path
	 *
	 * @param array $_course
	 * @param string $path
	 * @return int id of document / false if no doc found
	 */
	public static function get_document_id ($_course, $path) {
		$TABLE_DOCUMENT = Database :: get_course_table(TABLE_DOCUMENT, $_course['dbName']);
		$path = Database::escape_string($path);
		$sql = "SELECT id FROM $TABLE_DOCUMENT WHERE path LIKE BINARY '$path'";
		$result = Database::query($sql, __FILE__, __LINE__);
		if ($result && Database::num_rows($result) == 1) {
			$row = Database::fetch_array($result);
			return $row[0];
		} else {
			return false;
		}
	}


	/**
	 * Allow to set a specific document as a new template for FCKEditor for a particular user in a particular course
	 *
	 * @param string $title
	 * @param string $description
	 * @param int $document_id_for_template the document id
	 * @param string $couse_code
	 * @param int $user_id
	 */
	public static function set_document_as_template ($title, $description, $document_id_for_template, $couse_code, $user_id, $image) {
		// Database table definition
		$table_template = Database::get_main_table(TABLE_MAIN_TEMPLATES);

		// creating the sql statement
		$sql = "INSERT INTO ".$table_template."
					(title, description, course_code, user_id, ref_doc, image)
				VALUES (
					'".Database::escape_string($title)."',
					'".Database::escape_string($description)."',
					'".Database::escape_string($couse_code)."',
					'".Database::escape_string($user_id)."',
					'".Database::escape_string($document_id_for_template)."',
					'".Database::escape_string($image)."')";
		Database::query($sql);

		return true;
	}


	/**
	 * Unset a document as template
	 *
	 * @param int $document_id
	 * @param string $couse_code
	 * @param int $user_id
	 */
	public static function unset_document_as_template ($document_id, $course_code, $user_id) {

		$table_template = Database::get_main_table(TABLE_MAIN_TEMPLATES);
		$course_code = Database::escape_string($course_code);
		$user_id = Database::escape_string($user_id);
		$document_id = Database::escape_string($document_id);

		$sql = 'SELECT id FROM '.$table_template.' WHERE course_code="'.$course_code.'" AND user_id="'.$user_id.'" AND ref_doc="'.$document_id.'"';
		$result = Database::query($sql);
		$template_id = Database::result($result,0,0);

		include_once(api_get_path(LIBRARY_PATH) . 'fileManage.lib.php');
		my_delete(api_get_path(SYS_CODE_PATH).'upload/template_thumbnails/'.$template_id.'.jpg');

		$sql = 'DELETE FROM '.$table_template.' WHERE course_code="'.$course_code.'" AND user_id="'.$user_id.'" AND ref_doc="'.$document_id.'"';

		Database::query($sql);

	}
	/**
	 * return true if the documentpath have visibility=1 as item_property
	 *
	 * @param string $document_path the relative complete path of the document
     * @param array  $course the _course array info of the document's course
	 */
	public static function is_visible ($doc_path, $course) {
       	$docTable  = Database::get_course_table(TABLE_DOCUMENT, $course['dbName']);
		$propTable = Database::get_course_table(TABLE_ITEM_PROPERTY, $course['dbName']);
        //note the extra / at the end of doc_path to match every path in the
        // document table that is part of the document path
        $doc_path = Database::escape_string($doc_path);

        $sql = "SELECT path FROM $docTable d, $propTable ip " .
                "where d.id=ip.ref AND ip.tool='".TOOL_DOCUMENT."' AND d.filetype='file' AND visibility=0 AND ".
                "locate(concat(path,'/'),'".$doc_path."/')=1";
        $result = Database::query($sql,__FILE__,__LINE__);
        if (Database::num_rows($result) > 0){
            $row = Database::fetch_array($result);
            //echo "$row[0] not visible";
            return false;
        }

		//improved protection of documents viewable directly through the url: incorporates the same protections of the course at the url of documents:	access allowed for the whole world Open, access allowed for users registered on the platform Private access, document accessible only to course members (see the Users list), Completely closed; the document is only accessible to the course admin and teaching assistants.
		if ($_SESSION ['is_allowed_in_course'] || api_is_platform_admin())
        {
        	return true; // ok, document is visible
		}
		else
		{
			return false;
		}
    }

	/**
	 * Allow attach certificate to course
	 * @param string The course id
	 * @param int The document id
	 * @return void()
	 */
	 function attach_gradebook_certificate ($course_id,$document_id) {
	 	$tbl_category=Database :: get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
	 	$session_id=api_get_session_id();
	 	if ($session_id==0 || is_null($session_id)) {
	 		$sql_session='AND (session_id='.Database::escape_string($session_id).' OR isnull(session_id)) ';
	 	} elseif ($session_id>0) {
	 		$sql_session='AND session_id='.Database::escape_string($session_id);
	 	} else {
	 		$sql_session='';
	 	}
	 	$sql='UPDATE '.$tbl_category.' SET document_id="'.Database::escape_string($document_id).'" 
	 	WHERE course_code="'.Database::escape_string($course_id).'" '.$sql_session;
	 	$rs=Database::query($sql,__FILE__,__LINE__);
	 }
	 /**
	  * get the document id of default certificate
	  * @param string The course id
	  * @return int The default certificate id
	  */
	 function get_default_certificate_id ($course_id) {
	 	$tbl_category=Database :: get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);	 	
	 	$session_id=api_get_session_id();
	 	if ($session_id==0 || is_null($session_id)) {
	 		$sql_session='AND (session_id='.Database::escape_string($session_id).' OR isnull(session_id)) ';
	 	} elseif ($session_id>0) {
	 		$sql_session='AND session_id='.Database::escape_string($session_id);
	 	} else {
	 		$sql_session='';
	 	}	 	
	 	$sql='SELECT document_id FROM '.$tbl_category.'  
	 	WHERE course_code="'.Database::escape_string($course_id).'" '.$sql_session;
	 	$rs=Database::query($sql,__FILE__,__LINE__);
	 	$row=Database::fetch_array($rs);
	 	return $row['document_id'];
	 }
	 /**
	  * allow replace user info in file html
	  * @param string The course id
	  * @return string The html content of the certificate
	  */
	  function replace_user_info_into_html($course_id) {
	  	global $_course;

	  	$course_info = api_get_course_info($course_id);	  		  	
	  	$tbl_document=Database::get_course_table(TABLE_DOCUMENT,$course_info['dbName']);
	  	$document_id=self::get_default_certificate_id($course_id);	  		  	
	  	
	  	$sql='SELECT path FROM '.$tbl_document.' WHERE id="'.Database::escape_string($document_id).'" ';	  		  	
	  	
	  	$rs=Database::query($sql,__FILE__,__LINE__);
	  	$new_content = '';
	  	if (Database::num_rows($rs)) {
		  	$row=Database::fetch_array($rs);
		  	$filepath = api_get_path('SYS_COURSE_PATH').$course_info['path'].'/document'.$row['path'];
		  	
		  	if (is_file($filepath)) {
		  		$my_content_html=file_get_contents($filepath);
		  	}
			$all_user_info=self::get_all_info_to_certificate();
			$info_to_be_replaced_in_content_html=$all_user_info[0];
			$info_to_replace_in_content_html=$all_user_info[1];
			$new_content=str_replace($info_to_be_replaced_in_content_html,$info_to_replace_in_content_html,$my_content_html);	  	
	  	}
	  	
	  	return $new_content;
	  }
	  
	  /**
	   * return all content to replace and all content to be replace
	   */
	  function get_all_info_to_certificate () {
	  	
		global $charset,$dateFormatLong;
		$info_list	= array();
	  	$user_id	= api_get_user_id();
	  	$course_id	= api_get_course_id();
	  	
		//info portal	
		$organization_name = api_get_setting('Institution');
		$portal_name = api_get_setting('siteName');

		//info extra user data
		$extra_user_info_data=UserManager::get_extra_user_data($user_id,false,false);

		//info student
		$user_info=api_get_user_info($user_id); 		
		$first_name=($user_info['firstName']);
		$last_name=($user_info['lastName']);
		$official_code=($user_info['official_code']);
			
		//info teacher
		$info_teacher_id=UserManager::get_user_id_of_course_admin_or_session_admin($course_id);
		$teacher_info=api_get_user_info($info_teacher_id);
		$teacher_first_name=($teacher_info['firstName']);
		$teacher_last_name=($teacher_info['lastName']);
		
		// info gradebook certificate
		$info_grade_certificate = UserManager::get_info_gradebook_certificate($course_id,$user_id);
		$date_certificate = $info_grade_certificate['date_certificate'];
		$date_long_certificate = '';
		
		if (!empty($date_certificate)) {
			$date_long_certificate = api_ucfirst(format_locale_date($dateFormatLong,convert_mysql_date($date_certificate)));	
		}

		//replace content
		$info_to_replace_in_content_html=array($first_name,$last_name,$organization_name,$portal_name,$teacher_first_name,$teacher_last_name, $official_code, $date_long_certificate);
		$info_to_be_replaced_in_content_html=array('((user_firstname))','((user_lastname))','((gradebook_institution))',
		'((gradebook_sitename))','((teacher_firstname))','((teacher_lastname))','((official_code))','((date_certificate))');
		foreach ($extra_user_info_data as $key_extra=>$value_extra) {
			$info_to_be_replaced_in_content_html[]='(('.strtolower($key_extra).'))';
			$info_to_replace_in_content_html[]=$value_extra;
		}
		$info_list[]=$info_to_be_replaced_in_content_html;
		$info_list[]=$info_to_replace_in_content_html;
		return $info_list;
	  }
	  /**
	   * Remove default certificate
	   * @param string The course id
	   * @param int The document id of the default certificate
	   * @return void()
	   */
	   function remove_attach_certificate ($course_id,$default_certificate_id) {
	   		$default_certificate=self::get_default_certificate_id($course_id);
	   		if ((int)$default_certificate==(int)$default_certificate_id) {
			   	$tbl_category=Database :: get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
			 	$session_id=api_get_session_id();
			 	if ($session_id==0 || is_null($session_id)) {
			 		$sql_session='AND (session_id='.Database::escape_string($session_id).' OR isnull(session_id)) ';
			 	} elseif ($session_id>0) {
			 		$sql_session='AND session_id='.Database::escape_string($session_id);
			 	} else {
			 		$sql_session='';
			 	}
			 	
			 	$sql='UPDATE '.$tbl_category.' SET document_id=null 
			 	WHERE course_code="'.Database::escape_string($course_id).'" AND document_id="'.$default_certificate_id.'" '.$sql_session;
			 	$rs=Database::query($sql,__FILE__,__LINE__);
	   		}
	   }
	   /**
	    * Create specific folders according to folder name
	    * @param string The course id
	    * @return void() 
	    */
	    function create_specifics_folder_in_course ($course_id, $folder_name='certificates') {
	    	
	    	global $_course;
	    	global $_user;
	    	$to_group_id=0;
	    	$to_user_id=null;
	    	$course_dir   = $_course['path']."/document/";
		$sys_course_path = api_get_path(SYS_COURSE_PATH);
	    	$base_work_dir=$sys_course_path.$course_dir;
	    	$base_work_dir_test=$base_work_dir.$folder_name;
	    	if ($folder_name == 'certificates') {
	    	  $dir_name='/'.$folder_name;
	    	} elseif ($folder_name == 'mindmaps') {
	    	  $dir_name=$folder_name;
	    	}
	    	$post_dir_name=$folder_name;
	    	$visibility_command='invisible';
	    	if (!is_dir($base_work_dir_test)) {
				$created_dir = create_unexisting_directory($_course,$_user['user_id'],$to_group_id,$to_user_id,$base_work_dir,$dir_name,$post_dir_name);
	    		$update_id=DocumentManager::get_document_id_of_specifics_folder($folder_name);
	    		api_item_property_update($_course, TOOL_DOCUMENT, $update_id, $visibility_command, $_user['user_id']);
	    	}
	    }
	    /**
	     * Get the document id of a scpefic folder
	     * @param string The course id
	     * @return int The document id of the directory certificate
	     */
	    function get_document_id_of_specifics_folder ($folder_name = 'certificates') {
	    	global $_course;
	  	$tbl_document=Database::get_course_table(TABLE_DOCUMENT);
	    	$sql='SELECT id FROM '.$tbl_document.' WHERE path="/'.$folder_name.'" ';
	    	$rs=Database::query($sql,__FILE__,__LINE__);
	    	$row=Database::fetch_array($rs);
	    	return $row['id'];
	    	
	    }

		 /**
	     * Check if a directory given is for certificate 
	     * @param string path of directory
	     * @return bool  true if is a certificate or false otherwise
	     */	    
	    function is_certificate_mode($dir) {
	    	//I'm in the certification module?  
			$is_certificate_mode = false;
			$is_certificate_array = explode('/',$dir);
			array_shift($is_certificate_array);
			if ($is_certificate_array[0]=='certificates') {
				$is_certificate_mode = true;
			}	
			return $is_certificate_mode;
	    }

  /**
   * Obtains the text inside the file with the right parser
   */
  public static function get_text_content($doc_path, $doc_mime) {
   // TODO: review w$ compatibility
   // use usual exec output lines array to store stdout instead of a temp file
   // because we need to store it at RAM anyway before index on DokeosIndexer object
   $ret_val = NULL;
   switch ($doc_mime) {
    case 'text/plain':
     $handle = fopen($doc_path, "r");
     $output = array(fread($handle, filesize($doc_path)));
     fclose($handle);
     break;
    case 'application/pdf':
     exec("pdftotext $doc_path -", $output, $ret_val);
     break;
    case 'application/postscript':
     $temp_file = tempnam(sys_get_temp_dir(), 'dokeos');
     exec("ps2pdf $doc_path $temp_file", $output, $ret_val);
     if ($ret_val !== 0) { // shell fail, probably 127 (command not found)
      return FALSE;
     }
     exec("pdftotext $temp_file -", $output, $ret_val);
     unlink($temp_file);
     var_dump($output);
     break;
    case 'application/msword':
     exec("catdoc $doc_path", $output, $ret_val);
     var_dump($output);
     break;
    case 'text/html':
     exec("html2text $doc_path", $output, $ret_val);
     break;
    case 'text/rtf':
     // note: correct handling of code pages in unrtf
     // on debian lenny unrtf v0.19.2 can not, but unrtf v0.20.5 can
     exec("unrtf --text $doc_path", $output, $ret_val);
     if ($ret_val == 127) { // command not found
      return FALSE;
     }
     // avoid index unrtf comments
     if (is_array($output) && count($output) > 1) {
      $parsed_output = array();
      foreach ($output as $line) {
       if (!preg_match('/^###/', $line, $matches)) {
        if (!empty($line)) {
         $parsed_output[] = $line;
        }
       }
      }
      $output = $parsed_output;
     }
     break;
    case 'application/vnd.ms-powerpoint':
     exec("catppt $doc_path", $output, $ret_val);
     break;
    case 'application/vnd.ms-excel':
     exec("xls2csv -c\" \" $doc_path", $output, $ret_val);
     break;
   }

   $content = '';
   if (!is_null($ret_val)) {
    if ($ret_val !== 0) { // shell fail, probably 127 (command not found)
     return FALSE;
    }
   }
   if (isset($output)) {
    foreach ($output as $line) {
     $content .= $line . "\n";
    }
    return $content;
   } else {
    return FALSE;
   }
  } 
  
  public static function check_if_folder_exists($path) {
  	global $_course;
	$propTable = Database::get_course_table(TABLE_ITEM_PROPERTY, $_course['dbName']);
	$tbl_documents = Database::get_course_table(TABLE_DOCUMENT, $_course['dbName']);
	$sql_count = "SELECT count(*) AS count FROM $tbl_documents doc,$propTable prop WHERE doc.id = prop.ref AND prop.tool = '".TOOL_DOCUMENT."' AND prop.to_group_id = 0 AND prop.visibility <> 2 AND doc.filetype = 'folder' AND doc.path LIKE '".$path."' AND prop.lastedit_type !='DocumentDeleted'";
	$rs_count = Database::query($sql_count, __FILE__,__LINE__);
	$row_count = Database::fetch_array($rs_count,'ASSOC');
	$count_if_folder_exists = $row_count['count'];

	return $count_if_folder_exists;
  }
  
  public static function search_engine_save($doc_id, $title, $content, $doc_path) {

    $courseid = api_get_course_id();
    isset($_POST['language'])? $lang=Database::escape_string($_POST['language']): $lang = 'english';

   require_once(api_get_path(LIBRARY_PATH) . 'search/DokeosIndexer.class.php');
   require_once(api_get_path(LIBRARY_PATH) . 'search/IndexableChunk.class.php');
   require_once(api_get_path(LIBRARY_PATH) . 'specific_fields_manager.lib.php');

   $specific_fields = get_specific_field_list();
   $ic_slide = new IndexableChunk();

   $all_specific_terms = '';
   foreach ($specific_fields as $specific_field) {
    if (isset($_REQUEST[$specific_field['code']])) {
     $sterms = trim($_REQUEST[$specific_field['code']]);
     if (!empty($sterms)) {
      $all_specific_terms .= ' ' . $sterms;
      $sterms = explode(',', $sterms);
      foreach ($sterms as $sterm) {
       $ic_slide->addTerm(trim($sterm), $specific_field['code']);
       add_specific_field_value($specific_field['id'], $course_id, TOOL_DOCUMENT, $doc_id, $sterm);
      }
     }
    }
   }

    $ic_slide->addValue("title", $title);
    $ic_slide->addCourseId($courseid);
    $ic_slide->addToolId(TOOL_DOCUMENT);
    $xapian_data = array(
      SE_COURSE_ID => $courseid,
      SE_TOOL_ID => TOOL_DOCUMENT,
      SE_DATA => array('doc_id' => (int)$doc_id),
      SE_USER => (int)api_get_user_id(),
    );
    $ic_slide->xapian_data = serialize($xapian_data);

    if (isset($_POST['search_terms'])) {
    $add_extra_terms = Security::remove_XSS($_POST['search_terms']).' ';
    }

    $file_content = $add_extra_terms.$content;
    $ic_slide->addValue("content", $file_content);

    $di = new DokeosIndexer();
    $di->connectDb(NULL, NULL, $lang);
    $di->addChunk($ic_slide);

    //index and return search engine document id
   $did = $di->index();

   if ($did) {
    // save it to db
    $tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
    $sql = 'INSERT INTO %s (id, course_code, tool_id, ref_id_high_level, search_did)
          VALUES (NULL , \'%s\', \'%s\', %s, %s)';
    $sql = sprintf($sql, $tbl_se_ref, $courseid, TOOL_DOCUMENT, $doc_id, $did);
    api_sql_query($sql, __FILE__, __LINE__);

   }
  }
  
}
//end class DocumentManager
?>
