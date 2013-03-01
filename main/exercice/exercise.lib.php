<?php

// $Id: exercise.lib.php 22247 2009-07-20 15:57:25Z ivantcholakov $

/*
  ==============================================================================
  Dokeos - elearning and course management software

  Copyright (c) 2004-2008 Dokeos SPRL
  Copyright (c) 2003 Ghent University (UGent)
  Copyright (c) 2001 Universite catholique de Louvain (UCL)
  Copyright (c) various contributors

  For a full list of contributors, see "credits.txt".
  The full license can be read in "license.txt".

  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License
  as published by the Free Software Foundation; either version 2
  of the License, or (at your option) any later version.

  See the GNU General Public License for more details.

  Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
  Mail: info@dokeos.com
  ==============================================================================
 */

/**
 * 	Exercise library
 * 	shows a question and its answers
 * 	@package dokeos.exercise
 * 	@author Olivier Brouckaert <oli.brouckaert@skynet.be>
 * 	@version $Id: exercise.lib.php 22247 2009-07-20 15:57:25Z ivantcholakov $
 */
// The initialization class for the online editor is needed here.
require_once '../inc/lib/fckeditor/fckeditor.php';
require_once '../inc/lib/geometry.lib.php';
include_once('answer.class.php');

/**
 * @param int question id
 * @param boolean only answers
 * @param boolean origin i.e = learnpath
 * @param int current item from the list of questions
 * @param int number of total questions
 * */
function showQuestion($questionId, $onlyAnswers=false, $origin=false, $current_item, $total_item) {
    $_SESSION['ValidateQn'] = 'N';
    $image_match = "N";
    // reads question informations
    if (!$objQuestionTmp = Question::read($questionId)) {
        // question not found
        return false;
    }

    $answerType = $objQuestionTmp->selectType();
    $pictureName = $objQuestionTmp->selectPicture();

    if ($answerType != HOT_SPOT && $answerType != HOT_SPOT_DELINEATION) { // Question is not of type hotspot
        if (!$onlyAnswers) {
            $questionName = $objQuestionTmp->selectTitle();
            $questionDescription = $objQuestionTmp->selectDescription();
            $mediaPosition = $objQuestionTmp->selectMediaPosition();

            if ($mediaPosition == 'top' && !empty($questionDescription)){
                $s="<div align='left'><div class='quiz_content_actions' style=\"width:95%\"><div align='center'><div class='media_scroll'>";
                $questionDescription = api_parse_tex($questionDescription);
                $s.=$questionDescription;
                 $s.="</div></div></div></div><br/>";
                echo $s;
                $s = '';
            }

            $questionName = api_parse_tex($questionName);
            //id="content_with_secondary_actions"

            if ($answerType == MATCHING) {
                $s = "<div id=\"question_title\" class=\"quiz_content_actions\" style=\"width:97%\">";
                echo $s;
                echo $questionName .'</div>';
                if (!empty($questionDescription)) {
                echo '<div style="width:97%" class=\'quiz_content_actions\'><div class=\'media_scroll\'>'.$questionDescription.'</div></div>';
                }
                // Only for preview
                if (isset($_SESSION['is_within_submit']) && $_SESSION['is_within_submit'] == 1) {
                    //   echo '<div class="quiz_content_actions">';
                    echo '<form name="frm_exercise" id="frm_exercise" style="width:95%" class="quiz_content_actions">';
                } else {
                    echo '<form name="frm_exercise" id="frm_exercise" style="width:95%" class="quiz_content_actions">';
                }
            } else {
                if($mediaPosition == 'top' || $mediaPosition == 'nomedia' || empty($questionDescription)){				
                $s = "<div id=\"question_title\" class=\"quiz_content_actions\" style=\"float:left;width:95%\">";
				}
				elseif($mediaPosition == 'right'){				
				$s = "<div id=\"question_title\" class=\"quiz_content_actions\" style=\"float:left;width:50%\">";
				}
                echo $s;
                echo $questionName . '</div>';
            }
            $s = '';
            if (!empty($questionDescription)) {
                if($mediaPosition == 'right'){
				$s.="<div style='float:right;'>";
                $s.="<div style='width:400px;height:auto;min-height:280px;border-radius: 5px;
				background-color:#fff;-moz-border-radius: 5px;-webkit-border-radius: 5px;border:1px solid #b8b8b6;'><div class='media_scroll'>";
                $questionDescription = api_parse_tex($questionDescription);
                $s.=$questionDescription;
                $s.="</div></div></div>";
				}
				if($mediaPosition == 'top' || $mediaPosition == 'nomedia' || empty($questionDescription)){
				$s.="<div class='quiz_content_actions' style='width:95%;float:left'>";	
				}
				elseif($mediaPosition == 'right'){
				$s.="<div class='quiz_content_actions' style='width:50%;float:left'>";	
				}
            } elseif ($answerType != MATCHING) {
                $questionDescription = '';
            }
			if (empty($questionDescription) && $answerType != MATCHING) {
				$s.="<div class='quiz_content_actions' style='width:95%;float:left'>";	
			}

            if (!empty($pictureName)) {
                $s.="<img src='../document/download.php?doc_url=%2Fimages%2F'" . $pictureName . "' border='0'>";
            }
        }

        if ($answerType != MATCHING) {
            $s.="<div style='width:100%;height:auto;min-height:180px;'><table class='exercise_options' style=\"width: 100%;\">";
        }
        // construction of the Answer object
        $objAnswerTmp = new Answer($questionId);

        $nbrAnswers = $objAnswerTmp->selectNbrAnswers();

        // only used for the answer type "Matching"
        if ($answerType == MATCHING) {
            $cpt1 = 'A';
            $cpt2 = 1;
            $cntOption = 1;
            $Select = array();
            $QA = array();
            $s .= '<input type="hidden" name="questionid" value="' . $questionId . '">';
        } elseif ($answerType == FREE_ANSWER) {
            // $oFCKeditor = new FCKeditor("choice[" . $questionId . "]");
            $oFCKeditor = new FCKeditor("newchoice");
            $oFCKeditor->ToolbarSet = 'TestFreeAnswer';
            $oFCKeditor->Width = '100%';
            $oFCKeditor->Height = '200';
            $oFCKeditor->Value = '';

            $s .= "<tr><td colspan='2'>" . $oFCKeditor->CreateHtml() . "</td></tr>";
        }

        for ($answerId = 1; $answerId <= $nbrAnswers; $answerId++) {
            $answer = $objAnswerTmp->selectAnswer($answerId);
            $answerCorrect = $objAnswerTmp->isCorrect($answerId);

            if ($answerType == FILL_IN_BLANKS) {
                // splits text and weightings that are joined with the character '::'
                list($answer) = explode('::', $answer);

                // because [] is parsed here we follow this procedure:
                // 1. find everything between the [tex] and [/tex] tags
                $startlocations = api_strpos($answer, '[tex]');
                $endlocations = api_strpos($answer, '[/tex]');

                if ($startlocations !== false && $endlocations !== false) {
                    $texstring = api_substr($answer, $startlocations, $endlocations - $startlocations + 6);
                    // 2. replace this by {texcode}
                    $answer = str_replace($texstring, '{texcode}', $answer);
                }

                // 3. do the normal matching parsing
                // replaces [blank] by an input field
                //getting the matches
                //$answer = api_ereg_replace('\[[^]]+\]', '<input type="text" name="choice[' . $questionId . '][]" size="10">', ($answer));
                preg_match_all('/\[[^]]+]/', $answer, $matches);

                foreach ($matches[0] as $match) {
                    $answer_len = strlen($match) - 2;
                    if ($answer_len <= 4) {						
                        $size = "2";
                        $temp = str_replace($match, '<input type="text" name="choice[' . $questionId . '][]" size="' . $size . '" >', $answer);
                        $answer = $temp;
                    } 
					else if ($answer_len > 4 && $answer_len <= 20) {
                        $size = "10";
                        $temp = str_replace($match, '<input type="text" name="choice[' . $questionId . '][]" size="' . $size . '">', $answer);
                        $answer = $temp;
                    } 
					else {
                        $size = "18";
                        $temp = str_replace($match, '<input type="text" name="choice[' . $questionId . '][]" size="' . $size . '">', $answer);
                        $answer = $temp;
                    }
                }
                $answer = $temp;

                // Change input size
                /*
                  preg_match_all('/\[[^]]+]/',$answer,$matches);
                  $answer=ereg_replace('\[[^]]+\]','<input type="text" name="choice['.$questionId.'][]" size="@@">',($answer));

                  // 4. resize the input


                  foreach($matches[0] as $match) {
                  $answer_len = strlen($match)-2;
                  //we will only replace 1 item
                  // echo implode("replace term", explode("search term", "input", $limit));
                  if ($answer_len <= 5) {
                  $answer = (implode("5", explode("@@", $answer, 2)));
                  } elseif($answer_len <= 10) {
                  $answer = (implode("10", explode("@@", $answer, 2)));
                  } elseif($answer_len <= 20) {
                  $answer = (implode("20", explode("@@", $answer, 2)));
                  } elseif($answer_len <= 30) {
                  $answer = (implode("30", explode("@@", $answer, 2)));
                  } elseif($answer_len <= 40) {
                  $answer = (implode("45", explode("@@", $answer, 2)));
                  } elseif($answer_len <= 50) {
                  $answer = (implode("60", explode("@@", $answer, 2)));
                  } elseif($answer_len <= 60) {
                  $answer = (implode("70", explode("@@", $answer, 2)));
                  } elseif($answer_len <= 70) {
                  $answer = (implode("80", explode("@@", $answer, 2)));
                  } elseif($answer_len <= 80) {
                  $answer = (implode("90", explode("@@", $answer, 2)));
                  } elseif($answer_len <= 90) {
                  $answer = (implode("100", explode("@@", $answer, 2)));
                  } elseif($answer_len <= 100) {
                  $answer = (implode("110", explode("@@", $answer, 2)));
                  } elseif($answer_len > 100 ) {
                  $answer = (implode("120", explode("@@", $answer, 2)));
                  }
                  }

                 */

                // 5. replace the {texcode by the api_pare_tex parsed code}
                $texstring = api_parse_tex($texstring);
                $answer = str_replace("{texcode}", $texstring, $answer);
            }

            // unique answer
            if ($answerType == UNIQUE_ANSWER) {
                $answer = api_parse_tex($answer);
                    $s .= "<input id='radio-" . $questionId . "-" . $answerId . "' type='radio' name='choice[" . $questionId . "]' value='" . $answerId . "'><input type='hidden' name='choice2[" . $questionId . "]' value='0'>";
                    $s.='<label for="radio-' . $questionId . '-' . $answerId . '">' . strip_tags($answer,'<a><span><img><sub><sup>') . '</label>';
            } elseif ($answerType == MULTIPLE_ANSWER) {
                $answer = api_parse_tex($answer);
                // multiple answers
                $s.="<input id='check-" . $questionId . "-" . $answerId . "' class='checkbox' type='checkbox' name='choice[" . $questionId . "][" . $answerId . "]' value='1' />
			<input type='hidden' name='choice2[" . $questionId . "][0]' value='0' />
                        <label for='check-" . $questionId . "-" . $answerId . "'>".strip_tags($answer,'<a><span><img><sub><sup>')."</label>";

            } elseif ($answerType == REASONING) {
                // reasoning answers
                $answer = api_parse_tex($answer);
                $s.="<input id='check-" . $questionId . "-" . $answerId . "' class='checkbox' type='checkbox' name='choice[" . $questionId . "][" . $answerId . "]' value='1'>
                    <input type='hidden' name='choice2[" . $questionId . "][0]' value='0'>";
                $s.='<label for="check-' . $questionId . '-' . $answerId . '">' . strip_tags($answer,'<a><span><img><sub><sup>') . '</label>';
            } elseif ($answerType == FILL_IN_BLANKS) {
                // fill in blanks
                //$s.="<tr><td colspan='2'>$answer</td></tr>";
                $s .= "<tr><td colspan='2'><div class='scroll'><table><tr><td>$answer</td></tr></table></div></td></tr>";
            }
            // free answer
            // matching
            else {
                if (preg_match("/<img/i", $answer)) {
                    $image_match = "Y";
                }
                
                if (!$answerCorrect) {
                    // options (A, B, C, ...) that will be put into the list-box
                    //	$Select[$answerId]['Lettre']=$cpt1++;
                    // answers that will be shown at the right side
                    $cntOption++;
                    $answer = api_parse_tex($answer);
                    $Select[$answerId]['Reponse'] = $answer;
                    $Select[$answerId]['Lettre'] = $answer;
                    $field_choice_name = "choice[" . $questionId . "][" . $answerId . "]";
                    //$answer = '<p>' . Security::remove_xss($answer) . '</p>';
                    $s .= "<input type='hidden' name='" . $field_choice_name . "' id='" . $field_choice_name . "' value='" . $answer . "' />";
                    //$s .= "<input type='hidden' name='" . $field_choice_name . "' id='" . $field_choice_name . "' value=\"" . $answer . "\" >";
                } else {
                    $s .= "<input type='hidden' name='choice[" . $questionId . "][" . $answerId . "]' id='choice[" . $questionId . "][" . $answerId . "]' value='0'/>";
                    //   if (!empty($answer)) {
                    $QA[] = $answer;
                    $option = array();
                    $option[] = (0);
                    foreach ($Select as $key => $val) {
                        $option[] = $val['Lettre'];
                    }
                    //    }
                    $cpt2++;
                }
            }
        } // end for()
        if (!ereg("MSIE", $_SERVER["HTTP_USER_AGENT"])) {
            if ($answerType != MATCHING) {
                $s .= '</table>';
                $s .= '</div></div></div>';
            }
        } else {
            if ($answerType != MATCHING) {
                $s .= '</table></div></div>';
            }
        }

        // destruction of the Answer object
        unset($objAnswerTmp);

        // destruction of the Question object
        unset($objQuestionTmp);

        if ($origin != 'export') {
            echo $s;
        } else {
            return($s);
        }
    } elseif ($answerType == HOT_SPOT || $answerType == HOT_SPOT_DELINEATION) { // Question is of type HOT_SPOT
        $questionName = $objQuestionTmp->selectTitle();
        $questionDescription = $objQuestionTmp->selectDescription();

        // Get the answers, make a list
        $objAnswerTmp = new Answer($questionId);
        $nbrAnswers = $objAnswerTmp->selectNbrAnswers();

        $answer_list = '<input type="hidden" name="choice[' . $questionId . '][1]" value="0" /><div><b>' . get_lang('HotspotZones') . '</b><dl>';
        for ($answerId = 1; $answerId <= $nbrAnswers; $answerId++) {
            $answer_list .= '<dt>' . $answerId . '.- ' . $objAnswerTmp->selectAnswer($answerId) . '</dt><br />';
        }
        $answer_list .= '</dl></div>';

        if (!$onlyAnswers) {
            $s = "<br /><div id=\"question_title\" class=\"quiz_content_actions\">";
            $s .= $questionName . '</div>';

            $s .="<table class='exercise_questions'>
			<tr>
			  <td valign='top' colspan='2'>
				";
            $questionDescription = api_parse_tex($questionDescription);
            $s.=$questionDescription;
            $s.="
			  </td>
			</tr>";
        }
        
        if($answerType == HOT_SPOT)
        	$swf_file = 'hotspot_user';
        else if($answerType == HOT_SPOT_DELINEATION)
        {
        	$answer_list = '<input type="hidden" name="choice[' . $questionId . '][1]" value="0" />';
        	$swf_file = 'hotspot_delineation_user';
        }

        $canClick = isset($_GET['editQuestion']) ? '0' : (isset($_GET['modifyAnswers']) ? '0' : '1');
        //$tes = isset($_GET['modifyAnswers']) ? '0' : '1';
        //echo $tes;
        $s .= "<script type=\"text/javascript\" src=\"../plugin/hotspot/JavaScriptFlashGateway.js\"></script>
						<script src=\"../plugin/hotspot/hotspot.js\" type=\"text/javascript\"></script>
						<script language=\"JavaScript\" type=\"text/javascript\">
						<!--
						// -----------------------------------------------------------------------------
						// Globals
						// Major version of Flash required
						var requiredMajorVersion = 7;
						// Minor version of Flash required
						var requiredMinorVersion = 0;
						// Minor version of Flash required
						var requiredRevision = 0;
						// the version of javascript supported
						var jsVersion = 1.0;
						// -----------------------------------------------------------------------------
						// -->
						</script>
						<script language=\"VBScript\" type=\"text/vbscript\">
						<!-- // Visual basic helper required to detect Flash Player ActiveX control version information
						Function VBGetSwfVer(i)
						  on error resume next
						  Dim swControl, swVersion
						  swVersion = 0

						  set swControl = CreateObject(\"ShockwaveFlash.ShockwaveFlash.\" + CStr(i))
						  if (IsObject(swControl)) then
						    swVersion = swControl.GetVariable(\"\$version\")
						  end if
						  VBGetSwfVer = swVersion
						End Function
						// -->
						</script>

						<script language=\"JavaScript1.1\" type=\"text/javascript\">
						<!-- // Detect Client Browser type
						var isIE  = (navigator.appVersion.indexOf(\"MSIE\") != -1) ? true : false;
						var isWin = (navigator.appVersion.toLowerCase().indexOf(\"win\") != -1) ? true : false;
						var isOpera = (navigator.userAgent.indexOf(\"Opera\") != -1) ? true : false;
						jsVersion = 1.1;
						// JavaScript helper required to detect Flash Player PlugIn version information
						function JSGetSwfVer(i){
							// NS/Opera version >= 3 check for Flash plugin in plugin array
							if (navigator.plugins != null && navigator.plugins.length > 0) {
								if (navigator.plugins[\"Shockwave Flash 2.0\"] || navigator.plugins[\"Shockwave Flash\"]) {
									var swVer2 = navigator.plugins[\"Shockwave Flash 2.0\"] ? \" 2.0\" : \"\";
						      		var flashDescription = navigator.plugins[\"Shockwave Flash\" + swVer2].description;
									descArray = flashDescription.split(\" \");
									tempArrayMajor = descArray[2].split(\".\");
									versionMajor = tempArrayMajor[0];
									versionMinor = tempArrayMajor[1];
									if ( descArray[3] != \"\" ) {
										tempArrayMinor = descArray[3].split(\"r\");
									} else {
										tempArrayMinor = descArray[4].split(\"r\");
									}
						      		versionRevision = tempArrayMinor[1] > 0 ? tempArrayMinor[1] : 0;
						            flashVer = versionMajor + \".\" + versionMinor + \".\" + versionRevision;
						      	} else {
									flashVer = -1;
								}
							}
							// MSN/WebTV 2.6 supports Flash 4
							else if (navigator.userAgent.toLowerCase().indexOf(\"webtv/2.6\") != -1) flashVer = 4;
							// WebTV 2.5 supports Flash 3
							else if (navigator.userAgent.toLowerCase().indexOf(\"webtv/2.5\") != -1) flashVer = 3;
							// older WebTV supports Flash 2
							else if (navigator.userAgent.toLowerCase().indexOf(\"webtv\") != -1) flashVer = 2;
							// Can't detect in all other cases
							else
							{
								flashVer = -1;
							}
							return flashVer;
						}
						// When called with reqMajorVer, reqMinorVer, reqRevision returns true if that version or greater is available

						function DetectFlashVer(reqMajorVer, reqMinorVer, reqRevision)
						{
						 	reqVer = parseFloat(reqMajorVer + \".\" + reqRevision);
						   	// loop backwards through the versions until we find the newest version
							for (i=25;i>0;i--) {
								if (isIE && isWin && !isOpera) {
									versionStr = VBGetSwfVer(i);
								} else {
									versionStr = JSGetSwfVer(i);
								}
								if (versionStr == -1 ) {
									return false;
								} else if (versionStr != 0) {
									if(isIE && isWin && !isOpera) {
										tempArray         = versionStr.split(\" \");
										tempString        = tempArray[1];
										versionArray      = tempString .split(\",\");
									} else {
										versionArray      = versionStr.split(\".\");
									}
									versionMajor      = versionArray[0];
									versionMinor      = versionArray[1];
									versionRevision   = versionArray[2];

									versionString     = versionMajor + \".\" + versionRevision;   // 7.0r24 == 7.24
									versionNum        = parseFloat(versionString);
						        	// is the major.revision >= requested major.revision AND the minor version >= requested minor
									if ( (versionMajor > reqMajorVer) && (versionNum >= reqVer) ) {
										return true;
									} else {
										return ((versionNum >= reqVer && versionMinor >= reqMinorVer) ? true : false );
									}
								}
							}
						}
						// -->
						</script>";
        $s .= '<tr><td valign="top" colspan="2" width="600"><table><tr><td width="610">' . "
					<script language=\"JavaScript\" type=\"text/javascript\">
						<!--
						// Version check based upon the values entered above in \"Globals\"
						var hasReqestedVersion = DetectFlashVer(requiredMajorVersion, requiredMinorVersion, requiredRevision);


						// Check to see if the version meets the requirements for playback
						if (hasReqestedVersion) {  // if we've detected an acceptable version
						    var oeTags = '<object type=\"application/x-shockwave-flash\" data=\"../plugin/hotspot/".$swf_file.".swf?modifyAnswers=" . $questionId . "&amp;canClick:" . $canClick . "\" width=\"610\" height=\"485\">'
										+ '<param name=\"movie\" value=\"../plugin/hotspot/".$swf_file.".swf?modifyAnswers=" . $questionId . "&amp;canClick:" . $canClick . "\" \/>'
										+ '<\/object>';
						    document.write(oeTags);   // embed the Flash Content SWF when all tests are passed
						} else {  // flash is too old or we can't detect the plugin
							var alternateContent = 'Error<br \/>'
								+ 'Hotspots requires Macromedia Flash 7.<br \/>'
								+ '<a href=http://www.macromedia.com/go/getflash/>Get Flash<\/a>';
							document.write(alternateContent);  // insert non-flash content
						}
						// -->
					</script></td>";
			if($answerType == HOT_SPOT){
				$s .=	"<td valign='top' align='left'><div class='hotspot_answers_frame'><div style='height:280px;overflow:auto;'>$answer_list</div><br/><div><img src='../img/MouseHotspots.png'></div></td></tr></table>";
			}
			if($answerType == HOT_SPOT_DELINEATION){
				$s.= "</td><td valign='top'><div>$answer_list</div><table width='100%' border='0'><tr><td><img src='../img/mousepolygon.png'></td></tr><tr><td>&nbsp;</td></tr><tr><td>&nbsp;</td></tr><tr><td><div class='quiz_content_actions'><div class='quiz_header'>".get_lang('DrawPolygon')."</div><br/><br/><div>".get_lang('DelineationText1')."</div><br/><div>".get_lang('DelineationText2')."</div></div></td></tr><tr><td>&nbsp;</td></tr></table>";
			}

		$s .= "</td></tr></table>";
        echo $s;
        echo '</td></tr></table>';
    }

    if ($answerType == MATCHING) {//question_title
        $Qdiv_css = "Qdiv";
        $destinationbox_css = "destinationBox";
        $answerDiv_css = "answerDiv";
        $drag_answer_css = "drag_answer";
        if ($image_match == 'Y') {
            $Qdiv_css = "Qdiv_img";
            $destinationbox_css = "destinationBox_img";
            $answerDiv_css = "answerDiv_img";
            $drag_answer_css = "drag_answer_img";
        }
        echo '<script type="text/javascript">
$(document).ready(function(){';
        for ($i = 0; $i <= count($QA); $i++) {
            $ans_i = $i + 1;
            echo '
	  $("#a' . $questionId . '-' . $ans_i . '").draggable({
		  revert:true,	
		  revertDuration: 0.5,
                  helper: "clone"
	});
	  $("#q' . $questionId . '-' . $i . '").droppable({
		   //I used this to make only a1 acceptable for q1
			drop: function(event, ui) {
			var cntOption = $("[name=cntOption-' . $questionId . ']").val();				
			var dragid = ui.draggable.attr("id");		
			
			var ansidarr = dragid.split("-");
			var ansid = ansidarr[1];
			var dropid = $(this).attr("id");
			
			var numericIdarr = dropid.split("-");
			var numericId = numericIdarr[1];
			
			var ansOption = (numericId*1) + (cntOption*1);	
			var answer = document.getElementById("choice[' . $questionId . ']["+ansid+"]").value;
			';
            if ($image_match == 'Y') {
                echo '
			$(this).html("<div style=\"border:1px solid #000;background-color:#fff;height:124px;width:272px;overflow:auto;\">"+answer+"</div>");
			';
            } else {
                echo '
			$(this).html("<div style=\"border:1px solid #000;background-color:#fff;height:43px;width:271px;overflow:auto;padding-bottom:1px;\">"+answer+"</div>");
			';
            }
            echo '
			document.getElementById("choice[' . $questionId . ']["+ansOption+"]").value = ansid;
				
	}	
            });  ';
        }

        echo '   }); 
</script>';


        echo '<div id="dragScriptContainer"><div id="' . $Qdiv_css . '"><table width="100%">';
        for ($i = 0; $i < count($QA); $i++) {
            echo '<tr><td><div class="question" >' . $QA[$i] . '</div></td><td><div id="q' . $questionId . '-' . $i . '" class="' . $destinationbox_css . '"></div></td></tr>';
        }
        echo '</table></div><div id="' . $answerDiv_css . '" style="font-face:verdana;"><table width="100%">';
        for ($i = 1; $i < count($option); $i++) {
            if (!empty($option[$i])) {
                echo '<tr><td><div class="' . $drag_answer_css . '" id="a' . $questionId . '-' . $i . '">' . $option[$i] . '</div></td></tr>';
            }
        }
        echo '</table>';
        echo '</div><div id="dragContent"></div>';
        echo '<input type="hidden" name="cntOption-' . $questionId . '" value="' . $cntOption . '">';
        // Only for preview
        if (isset($_SESSION['is_within_submit']) && $_SESSION['is_within_submit'] == 1) {
            echo '</div>';
        } else {
            echo '</form>';
        }
        echo '</div>';
    }

    return $nbrAnswers;
}

function showFeedback($questionId, $onlyAnswers=false, $origin=false, $current_item, $total_item, $exe_id) {
    require_once 'question.class.php';
    require_once 'answer.class.php';
    if (!ereg("MSIE", $_SERVER["HTTP_USER_AGENT"])) {
        if (isset($_REQUEST['quizpopup'])) {
            echo '<script src="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/jquery.js" type="text/javascript"></script>';
        }
    }

    $_SESSION['ValidateQn'] = 'Y';
    $TBL_TRACK_ATTEMPT = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

    $objQuestionTmp = Question::read($questionId);
    $answerType = $objQuestionTmp->type;
    if ($answerType != HOT_SPOT || $answerType != HOT_SPOT_DELINEATION) { // Question is not of type hotspot
        $questionName = $objQuestionTmp->selectTitle();
        $questionName = api_parse_tex($questionName);
		$mediaPosition = $objQuestionTmp->selectMediaPosition();
		$questionDescription = $objQuestionTmp->selectDescription();
    }

	if($mediaPosition == 'right'){
		echo '<div class="quiz_content_actions" style="width:97%;">';
		$s .= '<b>' . $questionName . '</b></div>';
		if(!empty($questionDescription)){
			$s .= '<div style="padding-right:5px;"><div class="quiz_content_actions" style="width:40%;float:right;">'.$questionDescription.'</div></div>';
		}		
	}
	else if($mediaPosition == 'top'){
		if(!empty($questionDescription)){
			echo '<div align="left"><div class="quiz_content_actions" style="width:95%;">'.$questionDescription.'</div></div>';
		}
		$s  = '<div class="quiz_content_actions" style="width:95%;">';
		$s .= '<b>' . $questionName . '</b></div>';
	}
	else {
		echo '<div class="quiz_content_actions" style="width:95%;">';
		$s .= '<b>' . $questionName . '</b></div>';
	}

    unset($objQuestionTmp);
    if ($answerType == UNIQUE_ANSWER) {
        $answerOK = 'N';
        $objAnswerTmp = new Answer($questionId);
        $nbrAnswers = $objAnswerTmp->selectNbrAnswers();
        $questionScore = 0;

		if($mediaPosition == 'top' || $mediaPosition == 'nomedia' || empty($questionDescription)){
		$s .= '<div class="quiz_content_actions" style="width:95%;float:left;">';
		}
		elseif($mediaPosition == 'right'){
		$s .= '<div class="quiz_content_actions" style="width:52%;float:left;">';
		}
		$s .= '<table width="100%" border="0" class="data_table"><tr class="row_odd"><td>'.get_lang("Choice").'</td><td>'.get_lang("ExpectedChoice").'</td><td>'.get_lang("Answer").'</td></tr>';

        for ($answerId = 1; $answerId <= $nbrAnswers; $answerId++) {
            $answerComment = $objAnswerTmp->selectComment($answerId);
            $answerCorrect = $objAnswerTmp->isCorrect($answerId);
            $answer = $objAnswerTmp->selectAnswer($answerId);
            $queryans = "select answer from " . $TBL_TRACK_ATTEMPT . " where exe_id = '" . Database::escape_string($exe_id) . "' and question_id= '" . Database::escape_string($questionId) . "'";
            $resultans = api_sql_query($queryans, __FILE__, __LINE__);
            $choice = Database::result($resultans, 0, "answer");
            $studentChoice = ($choice == $answerId) ? 1 : 0;
            if ($studentChoice && $answerCorrect) {
                $answerOK = 'Y';
                $questionScore+=$answerWeighting;
                $totalScore+=$answerWeighting;
                $feedback_if_true = $objAnswerTmp->selectComment($answerId);
            } else {
                $feedback_if_false = $objAnswerTmp->selectComment($answerId);
            }

			if ($answerId==1) {
				$s .= display_unique_or_multiple_or_reasoning_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,$answerId);
			} else {
				$s .= display_unique_or_multiple_or_reasoning_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,"");
			}
        }

		$s .= '<tr><td colspan="3">&nbsp;</td></tr>';
		if ($answerOK == 'Y') {
            if (empty($feedback_if_true)) {
                $feedback_if_true = get_lang('NoTrainerComment');
            }
            $s .= '<tr><td colspan="3"><b>' . get_lang('Feedback') . '</b></td></tr><tr><td colspan="3">' . $feedback_if_true . '</td></tr>';
        } else {
            if (empty($feedback_if_false)) {
                $feedback_if_false = get_lang('NoTrainerComment');
            }
            $s .= '<tr><td colspan="3"><b>' . get_lang('Feedback') . '</b></td></tr><tr><td colspan="3">' . $feedback_if_false . '</td></tr>';
        }
				
		$s .= '</table></div>';

    /*  if ($answerOK == 'Y') {
            if (empty($feedback_if_true)) {
                $feedback_if_true = 'No Comment Given by the trainer';
            }
            $s .= '<table align="center" width="100%" class="feedback_actions"><tr><td><b>' . get_lang('Feedback') . '</b></td></tr><tr><td><img src="../img/Right32tr.png">&nbsp;' . get_lang('Right') . '</td></tr><tr><td>' . $feedback_if_true . '</td></tr></table>';
        } else {
            if (empty($feedback_if_false)) {
                $feedback_if_false = 'No Comment Given by the trainer';
            }
            $s .= '<table align="center" width="100%" class="feedback_actions"><tr><td><b>' . get_lang('Feedback') . '</b></td></tr><tr><td><img src="../img/Wrong32tr.png">&nbsp;' . get_lang('Wrong') . '</td></tr><tr><td>' . $feedback_if_false . '</td></tr></table>';
        }*/
    }
    if ($answerType == MULTIPLE_ANSWER) {
        $objAnswerTmp = new Answer($questionId);
        $nbrAnswers = $objAnswerTmp->selectNbrAnswers();
        $questionScore = 0;
        $answerWrong = 'N';
   //   $s .= "<table align='center' width='100%' class='feedback_actions'>";
		
		if($mediaPosition == 'top' || $mediaPosition == 'nomedia' || empty($questionDescription)){
		$s .= '<div class="quiz_content_actions" style="width:95%;float:left;">';
		}
		elseif($mediaPosition == 'right'){
		$s .= '<div class="quiz_content_actions" style="width:52%;float:left;">';
		}
		$s .= '<table width="100%" border="0" class="data_table"><tr class="row_odd"><td>'.get_lang("Choice").'</td><td>'.get_lang("ExpectedChoice").'</td><td>'.get_lang("Answer").'</td></tr>';

        for ($answerId = 1; $answerId <= $nbrAnswers; $answerId++) {
            $answerComment = $objAnswerTmp->selectComment($answerId);
            $answer = $objAnswerTmp->selectAnswer($answerId);
            $answerCorrect = $objAnswerTmp->isCorrect($answerId);
            $answerWeighting = $objAnswerTmp->selectWeighting($answerId);
            $queryans = "select * from " . $TBL_TRACK_ATTEMPT . " where exe_id = '" . Database::escape_string($exe_id) . "' and question_id= '" . Database::escape_string($questionId) . "'";
            $resultans = api_sql_query($queryans, __FILE__, __LINE__);
            while ($row = Database::fetch_array($resultans)) {
                $ind = $row['answer'];
                $choice[$ind] = 1;
            }
            $studentChoice = $choice[$answerId];
            if ($studentChoice) {
                if ($studentChoice == $answerCorrect) {
                    $correctChoice = 'Y';
                    $feedback_if_true = $objAnswerTmp->selectComment($answerId);
                } else {
                    $answerWrong = 'Y';
                    $feedback_if_false = $objAnswerTmp->selectComment($answerId);
                }
            }

			if ($answerId==1) {
				$s .= display_unique_or_multiple_or_reasoning_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,$answerId);
			} else {
				$s .= display_unique_or_multiple_or_reasoning_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,"");
			}
        }

		$s .= '<tr><td colspan="3">&nbsp;</td></tr>';
		if ($correctChoice == 'Y' && $answerWrong == 'N') {
            if (empty($feedback_if_true)) {
                $feedback_if_true = get_lang('NoTrainerComment');
            }
            $s .= '<tr><td colspan="3"><b>' . get_lang('Feedback') . '</b></td></tr><tr><td colspan="3">' . $feedback_if_true . '</td></tr>';
        } else {
            if (empty($feedback_if_false)) {
                $feedback_if_false = get_lang('NoTrainerComment');
            }
            $s .= '<tr><td colspan="3"><b>' . get_lang('Feedback') . '</b></td></tr><tr><td colspan="3">' . $feedback_if_false . '</td></tr>';
        }
				
		$s .= '</table></div>';

   /*   if ($correctChoice == 'Y' && $answerWrong == 'N') {
            $s .= '<tr><td><img src="../img/Right32tr.png">&nbsp;' . get_lang('Right') . '</td></tr>';
            $s .= "<tr><td>" . $feedback_if_true . "</td></tr>";
        } else {
            $s .= '<tr><td><img src="../img/Wrong32tr.png">&nbsp;' . get_lang('Wrong') . '</td></tr>';
            $s .= "<tr><td>" . $feedback_if_false . "</td></tr>";
        }
        $s .= "</table>";*/
    }
    if ($answerType == REASONING) {
        $answerOK = 'N';
        $objAnswerTmp = new Answer($questionId);
        $nbrAnswers = $objAnswerTmp->selectNbrAnswers();
        $questionScore = 0;
        $correctChoice = 'Y';
        $noStudentChoice = 'N';
        $answerWrong = 'N';
        $expectedAnswer = '';
        $yourChoice = '';

		if($mediaPosition == 'top' || $mediaPosition == 'nomedia' || empty($questionDescription)){
		$s .= '<div class="quiz_content_actions" style="width:95%;float:left;">';
		}
		elseif($mediaPosition == 'right'){
		$s .= '<div class="quiz_content_actions" style="width:52%;float:left;">';
		}
		$s .= '<table width="100%" border="0" class="data_table"><tr class="row_odd"><td>'.get_lang("Choice").'</td><td>'.get_lang("ExpectedChoice").'</td><td>'.get_lang("Answer").'</td></tr>';

        for ($answerId = 1; $answerId <= $nbrAnswers; $answerId++) {
            $answer = $objAnswerTmp->selectAnswer($answerId);
            $answerComment = $objAnswerTmp->selectComment($answerId);
            $answerCorrect = $objAnswerTmp->isCorrect($answerId);
            $answerWeighting = $objAnswerTmp->selectWeighting($answerId);
            $queryans = "select * from " . $TBL_TRACK_ATTEMPT . " where exe_id = '" . Database::escape_string($exe_id) . "' and question_id= '" . Database::escape_string($questionId) . "'";
            $resultans = api_sql_query($queryans, __FILE__, __LINE__);
            while ($row = Database::fetch_array($resultans)) {
                $ind = $row['answer'];
                $choice[$ind] = 1;
            }
            $studentChoice = $choice[$answerId];

            if ($studentChoice) {
                if (empty($yourChoice)) {
                    $yourChoice = $objAnswerTmp->selectAnswer($answerId);
                } else {
                    $yourChoice = $yourChoice . " " . $objAnswerTmp->selectAnswer($answerId);
                }
            }

            if ($answerCorrect) {
                if (empty($expectedAnswer)) {
                    $expectedAnswer = $objAnswerTmp->selectAnswer($answerId);
                } else {
                    $expectedAnswer = $expectedAnswer . " " . $objAnswerTmp->selectAnswer($answerId);
                }
                $feedback_if_true = $objAnswerTmp->selectComment($answerId);
            } else {
                $feedback_if_false = $objAnswerTmp->selectComment($answerId);
            }

            if ($answerId == '2') {
                $wrongAnswerWeighting = $answerWeighting;
            }
            if ($answerCorrect && $studentChoice == '1' && $correctChoice == 'Y') {
                $correctChoice = 'Y';
                $noStudentChoice = 'Y';
            } elseif ($answerCorrect && !$studentChoice) {
                $correctChoice = 'N';
                $noStudentChoice = 'Y';
                $answerWrong = 'Y';
            } elseif (!$answerCorrect && $studentChoice == '1') {
                $correctChoice = 'N';
                $noStudentChoice = 'Y';
                $answerWrong = 'Y';
            }

			if ($answerId==1) {
					$s .= display_unique_or_multiple_or_reasoning_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,$answerId);
			} else {
					$s .= display_unique_or_multiple_or_reasoning_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect,$id,$questionId,"");
			}	
        }
        if ($noStudentChoice == 'Y') {
            if ($correctChoice == 'Y') {
                $answerOK = 'Y';
            }
        }
		
		$s .= '<tr><td colspan="3">&nbsp;</td></tr>';
		if ($correctChoice == 'Y' && $answerWrong == 'N') {
            if (empty($feedback_if_true)) {
                $feedback_if_true = get_lang('NoTrainerComment');
            }
            $s .= '<tr><td colspan="3"><b>' . get_lang('Feedback') . '</b></td></tr><tr><td colspan="3">' . $feedback_if_true . '</td></tr>';
        } else {
            if (empty($feedback_if_false)) {
                $feedback_if_false = get_lang('NoTrainerComment');
            }
            $s .= '<tr><td colspan="3"><b>' . get_lang('Feedback') . '</b></td></tr><tr><td colspan="3">' . $feedback_if_false . '</td></tr>';
        }
				
		$s .= '</table></div>';

  /*    $s .= '<table align="center" width="100%" class="feedback_actions"><tr style="font-weight:bold;"><td>' . get_lang('Choice') . '</td><td>' . get_lang('ExpectedChoice') . '</td><td>' . get_lang('Correct') . ' - ' . get_lang('SelectReason') . '</td><td>' . get_lang('Feedback') . '</td></tr>
  <tr><td>' . $yourChoice . '</td><td>' . $expectedAnswer . '</td>';
        if ($answerOK == 'Y' && $answerWrong == 'N') {
            if (empty($feedback_if_true)) {
                $feedback_if_true = 'No Comment Given by the trainer';
            }
            $s .= '<td><img src="../img/Right32tr.png">&nbsp;' . get_lang('Right') . '</td><td>' . $feedback_if_true . '</td></tr>';
        } else {
            if (empty($feedback_if_false)) {
                $feedback_if_false = 'No Comment Given by the trainer';
            }
            $s .= '<td><img src="../img/Wrong32tr.png">&nbsp;' . get_lang('Wrong') . '</td><td>' . $feedback_if_false . '</td></tr>';
        }
        $s .= '</table>';*/
    }
    if ($answerType == HOT_SPOT) {
        $objAnswerTmp = new Answer($questionId);
        $nbrAnswers = $objAnswerTmp->selectNbrAnswers();
        $questionScore = 0;
        $correctComment = array();
        $answerOk = 'N';
        $answerWrong = 'N';

        $hotspot_colors = array("", // $i starts from 1 on next loop (ugly fix)
            						"#4271B5",
									"#FE8E16",
									"#3B3B3B",
									"#BCD631",
									"#D63173",
									"#D7D7D7",
									"#90AFDD",
									"#AF8640",
									"#4F9242",
									"#F4EB24",
									"#ED2024",
									"#45C7F0",
									"#F7BDE2");

        $s .= '<table width="100%" border="0"><tr><td><div align="center"><object type="application/x-shockwave-flash" data="../plugin/hotspot/hotspot_solution.swf?modifyAnswers=' . Security::remove_XSS($questionId) . '&exe_id=' . $exe_id . '&from_db=1" width="610" height="410">
            <param name="movie" value="../plugin/hotspot/hotspot_solution.swf?modifyAnswers=' . Security::remove_XSS($questionId) . '&exe_id=' . $exe_id . '&from_db=1" />
          </object></div></td><td width="40%" valign="top"><div class="quiz_content_actions" style="height:380px;"><div class="quiz_header">'.get_lang('Feedback').'</div><div align="center"><img src="../img/MouseHotspots64.png"></div><br/>';
		 
		 $s .= '<div><table width="90%" border="1" class="data_table">';
        for ($answerId = 1; $answerId <= $nbrAnswers; $answerId++) {
            $answer = $objAnswerTmp->selectAnswer($answerId);
            $answerComment = $objAnswerTmp->selectComment($answerId);
            $correctComment[] = $objAnswerTmp->selectComment($answerId);
            $answerCorrect = $objAnswerTmp->isCorrect($answerId);
            if ($nbrAnswers == 1) {
                $correctComment = explode("~", $objAnswerTmp->selectComment($answerId));
            } else {
                if ($answerId == 1) {
                    $correctComment[] = $objAnswerTmp->selectComment(1);
                    $correctComment[] = $objAnswerTmp->selectComment(2);
                } else {
                    $correctComment[] = $objAnswerTmp->selectComment($answerId);
                }
            }

            $TBL_TRACK_HOTSPOT = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_HOTSPOT);
            $query = "select hotspot_correct from " . $TBL_TRACK_HOTSPOT . " where hotspot_exe_id = '" . Database::escape_string($exe_id) . "' and hotspot_question_id= '" . Database::escape_string($questionId) . "' AND hotspot_answer_id='" . Database::escape_string($answerId) . "'";
            $resq = api_sql_query($query);
            $choice = Database::result($resq, 0, "hotspot_correct");

            if ($choice) {
                $answerOk = 'Y';
                $img_choice = get_lang('Right');
            } else {
                $answerOk = 'N';
				$answerWrong = 'Y';
                $img_choice = get_lang('Wrong');
            }

            $s .= '<tr><td><div style="height:11px; width:11px; background-color:'.$hotspot_colors[$answerId].'; display:inline; float:left; margin-top:3px;"></div>&nbsp;'.$answerId.'</td><td>'.$answer.'</td><td>'.$img_choice.'</td></tr>';
		}
         $s .= '</table></div><br/><br/>';
		 if ($answerOk == 'Y' && $answerWrong == 'N') {
			 if ($nbrAnswers == 1){
				 $feedback = $correctComment[0]; 
			 }
			 else {
				 $feedback = $correctComment[1];  
			 }
		 }
		 else
		 {
			 if ($nbrAnswers == 1){
				 $feedback = $correctComment[1]; 
			 }
			 else {
				 $feedback = $correctComment[2];  
			 }	        
		 }
		 if(!empty($feedback)){
		 $s .= '<div align="center" class="quiz_feedback"><b>'.get_lang('Feedback').'</b> : '.$feedback.'</div>';		 
		 }
		 $s .= '</div></td></tr></table>';
    }
	if($answerType == HOT_SPOT_DELINEATION){
		$objAnswerTmp=new Answer($questionId);
		$nbrAnswers=$objAnswerTmp->selectNbrAnswers();
		//$nbrAnswers=1; // based in the code found in exercise_show.php
		$questionScore=0;		
		
		//based on exercise_submit modal
		/*  Hot spot delinetion parameters */		
		$choice=$exerciseResult[$questionid];
		$destination=array();
		$comment='';
		$next=1;
		$_SESSION['hotspot_coord']=array();
		$_SESSION['hotspot_dest']=array();
		$overlap_color=$missing_color=$excess_color=false;
		$organs_at_risk_hit=0;

		$final_answer = 0;
				for($answerId=1;$answerId <= $nbrAnswers;$answerId++) {
					
					$answer			=$objAnswerTmp->selectAnswer($answerId);
					$answerComment	=$objAnswerTmp->selectComment($answerId);
					$answerCorrect	=$objAnswerTmp->isCorrect($answerId);
					$answerWeighting=$objAnswerTmp->selectWeighting($answerId);
					
					//delineation						
					$answer_delineation_destination=$objAnswerTmp->selectDestination(1);
					$delineation_cord=$objAnswerTmp->selectHotspotCoordinates(1);					
					
					if ($answerId===1) {					
						$_SESSION['hotspot_coord'][1]=$delineation_cord;
						$_SESSION['hotspot_dest'][1]=$answer_delineation_destination;
					}	
										
					// getting the user answer 
					$TBL_TRACK_HOTSPOT = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_HOTSPOT);
					$query = "select hotspot_correct, hotspot_coordinate from ".$TBL_TRACK_HOTSPOT." where hotspot_exe_id = '".Database::escape_string($exe_id)."' and hotspot_question_id= '".Database::escape_string($questionId)."' AND hotspot_answer_id='1'"; //by default we take 1 because it's a delineation 
					$resq=api_sql_query($query);
					$row = Database::fetch_array($resq,'ASSOC');
					$choice = $row['hotspot_correct'];
					$user_answer = $row['hotspot_coordinate'];					
							
					// THIS is very important otherwise the poly_compile will throw an error!!
					// round-up the coordinates
					$coords = explode('/',$user_answer);
					$user_array = '';
					foreach ($coords as $coord) {
					    list($x,$y) = explode(';',$coord);
					    $user_array .= round($x).';'.round($y).'/';
					}
					$user_array = substr($user_array,0,-1);									
							
					if ($next) {							                    
						//$tbl_track_e_hotspot = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_HOTSPOT);
								
					// Save into db
					/*	$sql = "INSERT INTO $tbl_track_e_hotspot (hotspot_user_id, hotspot_course_code, hotspot_exe_id, hotspot_question_id, hotspot_answer_id, hotspot_correct, hotspot_coordinate ) 
								VALUES ('".Database::escape_string($_user['user_id'])."', '".Database::escape_string($_course['id'])."', '".Database::escape_string($exeId)."', '".Database::escape_string($questionId)."', '".Database::escape_string($answerId)."', '".Database::escape_string($studentChoice)."', '".Database::escape_string($user_array)."')";							
						$result = api_sql_query($sql,__FILE__,__LINE__);*/						
						$user_answer = $user_array;
					
						// we compare only the delineation not the other points
						$answer_question	= $_SESSION['hotspot_coord'][1];	
						$answerDestination	= $_SESSION['hotspot_dest'][1];
						
						//calculating the area
                        $poly_user 			= convert_coordinates($user_answer,'/'); 
                        $poly_answer		= convert_coordinates($answer_question,'|');
                        $max_coord 			= array('x'=>600,'y'=>400);//poly_get_max($poly_user,$poly_answer);	                   
                        $poly_user_compiled = poly_compile($poly_user,$max_coord);	                             
                        $poly_answer_compiled = poly_compile($poly_answer,$max_coord);
                        $poly_results 		= poly_result($poly_answer_compiled,$poly_user_compiled,$max_coord);
                              
                        $overlap = $poly_results['both'];
                        $poly_answer_area = $poly_results['s1'];
                        $poly_user_area = $poly_results['s2'];
                        $missing = $poly_results['s1Only'];
                        $excess = $poly_results['s2Only'];
                        
                        //$overlap = round(polygons_overlap($poly_answer,$poly_user)); //this is an area in pixels
                        if ($dbg_local>0) { error_log(__LINE__.' - Polygons results are '.print_r($poly_results,1),0);}
                        if ($overlap < 1) {
                            //shortcut to avoid complicated calculations
                        	$final_overlap = 0;
                            $final_missing = 100;
                            $final_excess = 100;
                        } else {
                            // the final overlap is the percentage of the initial polygon that is overlapped by the user's polygon
                        	$final_overlap = round(((float)$overlap / (float)$poly_answer_area)*100);
                            if ($dbg_local>1) { error_log(__LINE__.' - Final overlap is '.$final_overlap,0);}
                            // the final missing area is the percentage of the initial polygon that is not overlapped by the user's polygon
                            $final_missing = 100 - $final_overlap;
                            if ($dbg_local>1) { error_log(__LINE__.' - Final missing is '.$final_missing,0);}
                            // the final excess area is the percentage of the initial polygon's size that is covered by the user's polygon outside of the initial polygon
                            $final_excess = round((((float)$poly_user_area-(float)$overlap)/(float)$poly_answer_area)*100);
                            if ($dbg_local>1) { error_log(__LINE__.' - Final excess is '.$final_excess,0);}
                        }
						
						//checking the destination parameters parsing the "@@"				
						$destination_items= explode('@@', $answerDestination);	                        
				        $threadhold_total = $destination_items[0];			            
				        $threadhold_items=explode(';',$threadhold_total);				        		            
			            $threadhold1 = $threadhold_items[0]; // overlap
			            $threadhold2 = $threadhold_items[1]; // excess
			            $threadhold3 = $threadhold_items[2];	 //missing          
						
						// if is delineation
						if ($answerId===1) {
							//setting colors
							if ($final_overlap>=$threadhold1) {	
								$overlap_color=true; //echo 'a';
							}
							//echo $excess.'-'.$threadhold2;
							if ($final_excess<=$threadhold2) {	
								$excess_color=true; //echo 'b';
							}
							//echo '--------'.$missing.'-'.$threadhold3;
							if ($final_missing<=$threadhold3) {	
								$missing_color=true; //echo 'c';
							}					
							
							// if pass
							if ($final_overlap>=$threadhold1 && $final_missing<=$threadhold3 && $final_excess<=$threadhold2) {																
								$next=1; //go to the oars	
								$result_comment=get_lang('Acceptable');	
								$final_answer = 1;	// do not update with  update_exercise_attempt
								$comment=$answerDestination=$objAnswerTmp->selectComment(1);
							} else {									
								$next=1; //Go to the oars. If $next =  0 we will show this message: "One (or more) area at risk has been hit" instead of the table resume with the results	
								$result_comment=get_lang('Unacceptable');									
								$comment=$answerDestination=$objAnswerTmp->selectComment(2);																
								$answerDestination=$objAnswerTmp->selectDestination(1);
								//checking the destination parameters parsing the "@@"	
								$destination_items= explode('@@', $answerDestination);
								/*
								$try_hotspot=$destination_items[1];
	            				$lp_hotspot=$destination_items[2];
	           					$select_question_hotspot=$destination_items[3];
	            				$url_hotspot=$destination_items[4]; */	 		            											
								 //echo 'show the feedback';
							}
						} elseif($answerId>1) {
                            if ($objAnswerTmp->selectHotspotType($answerId) == 'noerror') {
                                if ($dbg_local>0) { error_log(__LINE__.' - answerId is of type noerror',0);}
                            	//type no error shouldn't be treated
                                $next = 1;
                                continue;
                            }
                            if ($dbg_local>0) { error_log(__LINE__.' - answerId is >1 so we\'re probably in OAR',0);}
							//check the intersection between the oar and the user												
							//echo 'user';	print_r($x_user_list);		print_r($y_user_list);
							//echo 'official';print_r($x_list);print_r($y_list);												
							//$result = get_intersection_data($x_list,$y_list,$x_user_list,$y_user_list);
							$inter= $result['success'];

                            //$delineation_cord=$objAnswerTmp->selectHotspotCoordinates($answerId);
                            $delineation_cord=$objAnswerTmp->selectHotspotCoordinates($answerId);

                            $poly_answer 			= convert_coordinates($delineation_cord,'|');
                            $max_coord 				= poly_get_max($poly_user,$poly_answer);                            
                            $poly_answer_compiled 	= poly_compile($poly_answer,$max_coord); 
                            $overlap 				= poly_touch($poly_user_compiled, $poly_answer_compiled,$max_coord);
                                          				
                            if ($overlap == false) {
                            	//all good, no overlap
                                $next = 1;
                                continue;
                            } else {								
                                if ($dbg_local>0) { error_log(__LINE__.' - Overlap is '.$overlap.': OAR hit',0);}
                                $organs_at_risk_hit++;  
                                //show the feedback
                                $next=0;								
                                $comment=$answerDestination=$objAnswerTmp->selectComment($answerId);                                
                                $answerDestination=$objAnswerTmp->selectDestination($answerId);
                                                    
                                $destination_items= explode('@@', $answerDestination);
                                 /*
                                $try_hotspot=$destination_items[1];
                                $lp_hotspot=$destination_items[2];
                                $select_question_hotspot=$destination_items[3];
                                $url_hotspot=$destination_items[4];*/                                                                                 
                            }
						}
					}
					else
					{	// the first delineation feedback		
                        if ($dbg_local>0) { error_log(__LINE__.' first',0);}								
					}			
				} // end for				
						
		if ($overlap_color) {
			$overlap_color='green';
	    } else {
			$overlap_color='red';
	    }
	    
		if ($missing_color) {
			$missing_color='green';
	    } else {
			$missing_color='red';
	    }
		if ($excess_color) {
			$excess_color='green';
	    } else {
			$excess_color='red';
	    }
	    
	    
	    if (!is_numeric($final_overlap)) {
    	$final_overlap = 0;
	    }
	    
	    if (!is_numeric($final_missing)) {
	    	$final_missing = 0;
	    }
	    if (!is_numeric($final_excess)) {
	    	$final_excess = 0;
	    }
	    
	    if ($final_excess>100) {
	    	$final_excess = 100;
	    }

		if ($answerType!= HOT_SPOT_DELINEATION) {
			$item_list=explode('@@',$destination);
			//print_R($item_list);
			$try = $item_list[0];
			$lp = $item_list[1];
			$destinationid= $item_list[2];
			$url=$item_list[3];
			$table_resume='';
		} else {
			if ($next==0) {
				$try = $try_hotspot;
				$lp = $lp_hotspot;
				$destinationid= $select_question_hotspot;
				$url=$url_hotspot;
			} else {				
				//show if no error
				//echo 'no error';				
			//	$comment=$answerComment=$objAnswerTmp->selectComment($nbrAnswers);	
			//	$comment=$answerComment=$objAnswerTmp->selectComment(2);	
				$answerDestination=$objAnswerTmp->selectDestination($nbrAnswers);
			}
		} 
		
		echo $s;
		$s = '';
		echo '<div><table width="100%" border="0">';	
		echo '<tr><td><object type="application/x-shockwave-flash" data="../plugin/hotspot/hotspot_solution.swf?modifyAnswers='.$questionId.'&exe_id='.$exe_id.'&from_db=1" width="610" height="410">
						<param name="movie" value="../plugin/hotspot/hotspot_solution.swf?modifyAnswers='.$questionId.'&exe_id='.$exe_id.'&from_db=1" />
							
					</object></td>';
		echo '<td width="40%" valign="top"><div class="quiz_content_actions" style="height:380px;"><div class="quiz_header">'.get_lang('Feedback').'</div><p align="center"><img src="../img/mousepolygon64.png"></p><div><table width="100%" border="1" class="data_table"><tr class="row_odd"><td>&nbsp;</td><td>'.get_lang('Requirement').'</td><td>'.get_lang('YourContour').'</td></tr><tr class="row_even"><td align="right">'.get_lang('Overlap').'</td><td align="center">'.get_lang('Min').' '.$threadhold1.' %</td><td align="center"><div style="color:'.$overlap_color.'">'.(($final_overlap < 0)?0:intval($final_overlap)).'</div></td></tr><tr class="row_even"><td align="right">'.get_lang('Excess').'</td><td align="center">'.get_lang('Max').' '.$threadhold2.' %</td><td align="center"><div style="color:'.$excess_color.'">'.(($final_excess < 0)?0:intval($final_excess)).'</div></td></tr><tr class="row_even"><td align="right">'.get_lang('Missing').'</td><td align="center">'.get_lang('Max').' '.$threadhold3.' %</td><td align="center"><div style="color:'.$missing_color.'">'.(($final_missing < 0)?0:intval($final_missing)).'</div></td></tr>';

		if ($answerType == HOT_SPOT_DELINEATION) {			
			if ($organs_at_risk_hit>0) {
				$message= get_lang('ResultIs').' <b>'.$result_comment.'</b>';				
				$message.= '<p style="color:#DC0A0A;"><b>'.get_lang('OARHit').'</b></p>';
			} else {				
				$message = '<p>'.get_lang('ResultIs').' <b>'.$result_comment.'</b></p>';
			}
		
			echo '<tr><td colspan="3" align="center">'.$message.'</td></tr>';
			
			// by default we assume that the answer is ok but if the final answer after calculating the area in hotspot delineation =0 then update  
			if ($final_answer==0) {
				$sql = 'UPDATE '.$TBL_TRACK_ATTEMPT.' SET answer="", marks = 0 WHERE question_id = '.$questionId.' AND exe_id = '.$exe_id;
				Database::query($sql, __FILE__, __LINE__);
			}
			
		} else {
			//echo '<p>'.$comment.'</p>';
			echo '<tr><td colspan="3">'.$comment.'</td></tr>';
		}
		
		echo '</table></div><br/><br/>';
		if(!empty($comment)){
		echo '<div align="center" class="quiz_feedback"><b>'.get_lang('Feedback').'</b> : '.$comment.'</div>';
		}
		echo '</div></td></tr>';
		
		echo '</table>';

	}
    if ($answerType == FILL_IN_BLANKS) {
        $objAnswerTmp = new Answer($questionId);
        $nbrAnswers = $objAnswerTmp->selectNbrAnswers();
        $questionScore = 0;
        $feedback_data = unserialize($objAnswerTmp->comment[1]);
        $feedback_true = $feedback_data['comment[1]'];
        $feedback_false = $feedback_data['comment[2]'];
        for ($answerId = 1; $answerId <= $nbrAnswers; $answerId++) {
            $answer = $objAnswerTmp->selectAnswer($answerId);
            $answerComment = $objAnswerTmp->selectComment($answerId);
            $answerCorrect = $objAnswerTmp->isCorrect($answerId);
            $answerWeighting = $objAnswerTmp->selectWeighting($answerId);

            $pre_array = explode('::', $answer);

            // is switchable fill blank or not
            $is_set_switchable = explode('@', $pre_array[1]);
            $switchable_answer_set = false;
            if ($is_set_switchable[1] == 1) {
                $switchable_answer_set = true;
            }

            $answer = $pre_array[0];

            // splits weightings that are joined with a comma
            $answerWeighting = explode(',', $is_set_switchable[0]);
            //list($answer,$answerWeighting)=explode('::',$multiple[0]);
            //$answerWeighting=explode(',',$answerWeighting);
            // we save the answer because it will be modified
            $temp = $answer;

            // TeX parsing
            // 1. find everything between the [tex] and [/tex] tags
            $startlocations = api_strpos($temp, '[tex]');
            $endlocations = api_strpos($temp, '[/tex]');
            if ($startlocations !== false && $endlocations !== false) {
                $texstring = api_substr($temp, $startlocations, $endlocations - $startlocations + 6);
                // 2. replace this by {texcode}
                $temp = str_replace($texstring, '{texcode}', $temp);
            }
            $j = 0;
            // the loop will stop at the end of the text
            $i = 0;
            $feedback_usertag = array();
            $feedback_correcttag = array();
            $feedback_anscorrect = array();
            //normal fill in blank
            if (!$switchable_answer_set) {
                while (1) {
                    // quits the loop if there are no more blanks
                    if (($pos = api_strpos($temp, '[')) === false) {
                        // adds the end of the text
                        $answer.=$temp;
                        // TeX parsing
                        $texstring = api_parse_tex($texstring);
                        break;
                    }
                    $temp = api_substr($temp, $pos + 1);
                    // quits the loop if there are no more blanks
                    if (($pos = api_strpos($temp, ']')) === false) {
                        break;
                    }

                    $queryfill = "select answer from " . $TBL_TRACK_ATTEMPT . " where exe_id = '" . Database::escape_string($exe_id) . "' and question_id= '" . Database::escape_string($questionId) . "'";
                    $resfill = api_sql_query($queryfill, __FILE__, __LINE__);
                    $str = Database::result($resfill, 0, "answer");
                    $str = str_replace("<br />", "", $str);

                    preg_match_all('#\[([^[]*)\]#', $str, $arr);
                    $choice = $arr[1];
                    $tmp = strrpos($choice[$j], ' / ');
                    $choice[$j] = substr($choice[$j], 0, $tmp);
                    $choice[$j] = trim($choice[$j]);
                    $choice[$j] = stripslashes($choice[$j]);
                    $feedback_usertag[] = $choice[$j];
                    $feedback_correcttag[] = api_strtolower(api_substr($temp, 0, $pos));

                    // if the word entered by the student IS the same as the one defined by the professor
                    if (api_strtolower(api_substr($temp, 0, $pos)) == api_strtolower($choice[$j])) {
                        $feedback_anscorrect[] = "Y";
                        // gives the related weighting to the student
                        $questionScore+=$answerWeighting[$j];
                        // increments total score
                        $totalScore+=$answerWeighting[$j];
                    } else {
                        $feedback_anscorrect[] = "N";
                    }
                    // else if the word entered by the student IS NOT the same as the one defined by the professor
                    $j++;
                    $temp = api_substr($temp, $pos + 1);
                    $i = $i + 1;
                }
                $answer = stripslashes($str);
            } else {
                //multiple fill in blank
                while (1) {
                    // quits the loop if there are no more blanks
                    if (($pos = api_strpos($temp, '[')) === false) {
                        // adds the end of the text
                        $answer.=$temp;
                        // TeX parsing
                        $texstring = api_parse_tex($texstring);
                        //$answer=str_replace("{texcode}",$texstring,$answer);
                        break;
                    }
                    // adds the piece of text that is before the blank and ended by [
                    $real_text[] = api_substr($temp, 0, $pos + 1);
                    $answer.=api_substr($temp, 0, $pos + 1);
                    $temp = api_substr($temp, $pos + 1);

                    // quits the loop if there are no more blanks
                    if (($pos = api_strpos($temp, ']')) === false) {
                        // adds the end of the text
                        //$answer.=$temp;
                        break;
                    }

                    $queryfill = "SELECT answer FROM " . $TBL_TRACK_ATTEMPT . " WHERE exe_id = '" . Database::escape_string($id) . "' and question_id= '" . Database::escape_string($questionId) . "'";
                    $resfill = api_sql_query($queryfill, __FILE__, __LINE__);
                    $str = Database::result($resfill, 0, "answer");
                    $str = str_replace("<br />", "", $str);

                    preg_match_all('#\[([^[/]*)/#', $str, $arr);
                    $choice = $arr[1];

                    $choice[$j] = trim($choice[$j]);
                    $user_tags[] = api_strtolower($choice[$j]);
                    $correct_tags[] = api_strtolower(api_substr($temp, 0, $pos));

                    $j++;
                    $temp = api_substr($temp, $pos + 1);
                    $i = $i + 1;
                }
                $answer = '';
                for ($i = 0; $i < count($correct_tags); $i++) {
                    if (in_array($user_tags[$i], $correct_tags)) {
                        // gives the related weighting to the student
                        $questionScore+=$answerWeighting[$i];
                        // increments total score
                        $totalScore+=$answerWeighting[$i];
                    }
                }
                $answer = stripslashes($str);
                $answer = str_replace('rn', '', $answer);
            }
            //echo $questionScore."-".$totalScore;

            $i++;
        }

		if($mediaPosition == 'top' || $mediaPosition == 'nomedia' || empty($questionDescription)){
		$s .= '<div class="quiz_content_actions" style="width:95%;float:left;">';
		$s .= '<div class="scroll_feedback"><b>' . $answer . '</b></div>';
		}
		elseif($mediaPosition == 'right'){
		$s .= '<div class="quiz_content_actions" style="width:52%;float:left;height:auto;min-height:300px;">';
		$s .= '<div class="scroll_feedback" style="width:450px;"><b>' . $answer . '</b></div>';
		}		
		$s .= '<table width="100%" border="0"><tr><td colspan="3"><b>'.get_lang('Feedaback').'</b></td></tr>';
		for ($k = 0; $k < sizeof($feedback_anscorrect); $k++) {
            $s .= '<tr><td>' . $feedback_usertag[$k] . ' / ' . $feedback_correcttag[$k] . '</td>';
            if ($feedback_anscorrect[$k] == "Y") {
                $s .= '<td><img src="../img/Right32tr.png">&nbsp;' . get_lang('Right') . '</td><td>' . $feedback_true . '</td></tr>';
            } else {
                $s .= '<td><img src="../img/Wrong32tr.png">&nbsp;' . get_lang('Wrong') . '</td><td>' . $feedback_false . '</td></tr>';
            }
        }
		$s .= '</table></div>';

   /*   $s .= '<table align="center" width="100%" class="feedback_actions"><tr><td colspan="2"><b>' . $answer . '</b></td></tr>';
        $s .= '<div class="scroll_feedback"><b>' . $answer . '</b></div>';
        $s .= '<table align="center" width="100%" class="feedback_actions">';
        $s .= '<table align="center" width="100%" class="feedback_actions"><tr><td colspan="2"><div class="scroll_feedback"><b>' . $answer . '</b></div></td></tr>';
        for ($k = 0; $k < sizeof($feedback_anscorrect); $k++) {
            $s .= '<tr><td>' . $feedback_usertag[$k] . ' / ' . $feedback_correcttag[$k] . '</td>';
            if ($feedback_anscorrect[$k] == "Y") {
                $s .= '<td><img src="../img/Right32tr.png">&nbsp;' . get_lang('Right') . '</td><td>' . $feedback_true . '</td></tr>';
            } else {
                $s .= '<td><img src="../img/Wrong32tr.png">&nbsp;' . get_lang('Wrong') . '</td><td>' . $feedback_false . '</td></tr>';
            }
        }
        $s .= '</table>';*/
    }
    if ($answerType == MATCHING) {
        $objQuestionTmp = Question::read($questionId);
        $questionWeighting = $objQuestionTmp->selectWeighting();

        $objAnswerTmp = new Answer($questionId);
        $nbrAnswers = $objAnswerTmp->selectNbrAnswers();
        $answerComment_true = $objAnswerTmp->selectComment(1);
        $answerComment_false = $objAnswerTmp->selectComment(2);
        $questionScore = 0;
        $answer_ok = 'N';
        $answer_wrong = 'N';
        $table_ans = Database :: get_course_table(TABLE_QUIZ_ANSWER);
        $TBL_TRACK_ATTEMPT = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

        $sql_select_answer = 'SELECT id, answer, correct, position FROM ' . $table_ans . ' WHERE question_id="' . Database::escape_string($questionId) . '" AND correct<>0';
        $sql_answer = 'SELECT position, answer FROM ' . $table_ans . ' WHERE question_id="' . Database::escape_string($questionId) . '" AND correct=0';
        $res_answer = api_sql_query($sql_answer, __FILE__, __LINE__);
        // getting the real answer
        $real_list = array();
        while ($real_answer = Database::fetch_array($res_answer)) {
            $real_list[$real_answer['position']] = $real_answer['answer'];
        }

        $res_answers = api_sql_query($sql_select_answer, __FILE__, __LINE__);

        echo '<table cellspacing="3" cellpadding="3" align="center" class="feedback_actions">';
        echo '<tr><td colspan="4">&nbsp;</td></tr>';
        echo '<tr>
					<td align="center" width="30%"><span style="font-style: italic;color:#4171B5;font-weight:bold;">' . get_lang("ElementList") . '</span> </td>
					<td align="center" width="35%"><span style="font-style: italic;color:#4171B5;font-weight:bold;">' . get_lang("YourAnswers") . '</span></td>
					<td align="center" width="35%"><span style="font-style: italic;color:#4171B5;font-weight:bold;">' . get_lang("Correct") . '</span></td>
				  </tr>';
        echo '<tr><td colspan="3">&nbsp;</td></tr>';

        while ($a_answers = Database::fetch_array($res_answers)) {
            $i_answer_id = $a_answers['id']; //3
            $s_answer_label = $a_answers['answer'];  // your dady - you mother
            $i_answer_correct_answer = $a_answers['correct']; //1 - 2
            $i_answer_position = $a_answers['position']; // 3 - 4

            $sql_user_answer =
                    'SELECT answers.answer
						FROM ' . $TBL_TRACK_ATTEMPT . ' as track_e_attempt
						INNER JOIN ' . $table_ans . ' as answers
							ON answers.position = track_e_attempt.answer
							AND track_e_attempt.question_id=answers.question_id
						WHERE answers.correct = 0
						AND track_e_attempt.exe_id = "' . Database::escape_string($exe_id) . '"
						AND track_e_attempt.question_id = "' . Database::escape_string($questionId) . '"
						AND track_e_attempt.position="' . Database::escape_string($i_answer_position) . '"';

            $res_user_answer = api_sql_query($sql_user_answer, __FILE__, __LINE__);
            if (Database::num_rows($res_user_answer) > 0) {
                $s_user_answer = Database::result($res_user_answer, 0, 0); //  rich - good looking
            } else {
                $s_user_answer = '';
            }

            //$s_correct_answer = $s_answer_label; // your ddady - your mother
            $s_correct_answer = $real_list[$i_answer_correct_answer];

            $i_answerWeighting = $objAnswerTmp->selectWeighting($i_answer_id);

            //if($s_user_answer == $s_correct_answer) // rich == your ddady?? wrong
            //echo $s_user_answer.' - '.$real_list[$i_answer_correct_answer];
            if ($s_user_answer == $real_list[$i_answer_correct_answer]) { // rich == your ddady?? wrong
                $questionScore+=$i_answerWeighting;
                $totalScore+=$i_answerWeighting;
                if ($answer_wrong == 'N') {
                    $answer_ok = 'Y';
                }
            } else {
                $s_user_answer = '<span style="color: #FF0000; text-decoration: line-through;">' . $s_user_answer . '</span>';
                $answer_wrong = 'Y';
            }
            echo '<tr>';
            echo '<td align="center"><div id="matchresult">' . $s_answer_label . '</div></td><td align="center" width="25%"><div id="matchresult">' . $s_user_answer . '</div></td><td align="center"><div id="matchresult"><b><span>' . $real_list[$i_answer_correct_answer] . '</span></b></div></td>';
            echo '</tr>';
        }

        /*  $s .= '<tr><td colspan="5" style="color:#ED9438;font-size:14px;font-weight:bold;">'.get_lang('Feedback').'</td></tr>';
          if ($questionScore == $questionWeighting) {
          if (empty($answerComment_true)) {
          $answerComment_true = 'No Comment Given by the trainer';
          }
          $s .= '<tr><td colspan="5" style="color:green;font-size:12px;font-weight:bold;">' . $answerComment_true . '</td></tr>';
          } else {
          if (empty($answerComment_false)) {
          $answerComment_false = 'No Comment Given by the trainer';
          }
          $s .= '<tr><td colspan="5" style="color:red;font-size:12px;font-weight:bold;">' . $answerComment_false . '</td></tr>';
          } */
        echo '<tr><td><b>' . get_lang('Feedback') . '</b></td></tr><tr>';
        if ($answer_ok == 'Y' && $answer_wrong == 'N') {
            echo '<td>' . $answerComment_true . '</td>';
        } else {
            echo '<td>' . $answerComment_false . '</td>';
        }
        $s .= '</tr></table>';
    }
    if ($answerType == FREE_ANSWER) {
        $objQuestionTmp = Question::read($questionId);
        $questionWeighting = $objQuestionTmp->selectWeighting();

        $objAnswerTmp = new Answer($questionId);
        $nbrAnswers = $objAnswerTmp->selectNbrAnswers();
        $answerComment_true = $objAnswerTmp->selectComment(1);
        $answerComment_false = $objAnswerTmp->selectComment(2);
        $questionScore = 0;

		if($mediaPosition == 'top' || $mediaPosition == 'nomedia' || empty($questionDescription)){
		$s .= '<div class="quiz_content_actions" style="width:95%;float:left;">';
		}
		elseif($mediaPosition == 'right'){
		$s .= '<div class="quiz_content_actions" style="width:52%;float:left;height:auto;min-height:300px;">';
		}

        if (api_is_allowed_to_edit()) {
            $s .= '<table align="center" width="70%"><tr><td valign="top">' . get_lang("EditCommentsAndMarks") . '</td><td><textarea name="freeans_comment" rows="4" cols="40"></textarea><input type="hidden" name="freeaction" value="freeanswer"><input type="hidden" name="freeqnid" value="' . $questionId . '"><input type="hidden" name="freeexeid" value="' . $exe_id . '"></td></tr><tr><td colspan="2">';
            $s .= get_lang("AssignMarks") . '<select name="marks" id="marks">';
            for ($i = 0; $i <= $questionWeighting; $i++) {
                $s .= '<option ' . (($i == $questionScore) ? "selected='selected'" : '') . '>' . $i . '</option>';
            }
            $s .= '</select></td></tr></table>';
        } else {
            $s .= '<table align="center" class="feedback_actions" width="70%"><tr><td>&nbsp;</td></tr><tr><td valign="top">' . get_lang("MarksAfterCorrection") . '</td></tr><tr><td>&nbsp;</td></tr></table>';
        }

     /* if (api_is_allowed_to_edit()) {
            $s .= '<table align="center" class="feedback_actions" width="70%"><tr><td valign="top">' . get_lang("EditCommentsAndMarks") . '</td><td><textarea name="freeans_comment" rows="4" cols="40"></textarea><input type="hidden" name="freeaction" value="freeanswer"><input type="hidden" name="freeqnid" value="' . $questionId . '"><input type="hidden" name="freeexeid" value="' . $exe_id . '"></td></tr><tr><td colspan="2">';
            $s .= get_lang("AssignMarks") . '<select name="marks" id="marks">';
            for ($i = 0; $i <= $questionWeighting; $i++) {
                $s .= '<option ' . (($i == $questionScore) ? "selected='selected'" : '') . '>' . $i . '</option>';
            }
            $s .= '</select></td></tr></table>';
        } else {
            $s .= '<table align="center" class="feedback_actions" width="70%"><tr><td>&nbsp;</td></tr><tr><td valign="top">' . get_lang("MarksAfterCorrection") . '</td></tr><tr><td>&nbsp;</td></tr></table>';
        }*/
    }

    echo $s;
    echo '</div>';
}

function display_unique_or_multiple_or_reasoning_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect, $id, $questionId, $ans)
{
	if($answerType == UNIQUE_ANSWER){
		$img = 'radio';
	}
	else {
		$img = 'checkbox';
	}
	if($studentChoice){
		$your_choice = $img.'_on'.'.gif';
	}
	else {
		$your_choice = $img.'_off'.'.gif';
	}

	if($answerCorrect){
		$expected_choice = $img.'_on'.'.gif';
	}
	else {
		$expected_choice = $img.'_off'.'.gif';
	}

	$s .= '
	<tr>
	<td width="5%" align="center">
		<img src="../img/'.$your_choice.'"
		border="0" alt="" />
	</td>
	<td width="5%" align="center">
		<img src="../img/'.$expected_choice.'"
		border="0" alt=" " />
	</td>
	<td width="40%" style="border-bottom: 1px solid #4171B5;">'.api_parse_tex($answer).'	
	</td>		
	</tr>';
	return $s;
}
?>
