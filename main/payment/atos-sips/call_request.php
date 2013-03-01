<?php

// resetting the course id
$cidReset = true;

// name of the language file that needs to be included
$language_file = array ('registration','admin');

require_once '../../inc/global.inc.php';
require_once 'load_pathfile.inc.php';
$libpath = api_get_path(LIBRARY_PATH);
require_once  $libpath.'sessionmanager.lib.php';
require_once  $libpath.'usermanager.lib.php';

if (isset($_GET['cat_id'])) {
    $_SESSION['cat_id'] = intval($_GET['cat_id']);
}

$cat_id = intval($_SESSION['cat_id']);
$cat = SessionManager::get_session_category($cat_id);

// get destination back
if (isset($_GET['from'])) {
    $_SESSION['from'] = $_GET['from'];
}

if (isset($_GET['pay_type'])) {
    $pay_type = intval($_GET['pay_type']);            
    // get cost by payment type
    $_SESSION['pay_type'] = $pay_type;
    $country_code = isset($_SESSION['payer_info'])?$_SESSION['payer_info']['country']:$_SESSION['user_info']['country'];  
    if (!isset($_SESSION['user_info']['country'])) {
        $user_id      = api_get_user_id();
        $extra_field  = UserManager::get_extra_user_data($row_users['user_id']);
        $country_code = $extra_field['country'];
    }
    if (isset($cat['cost'])) {	
        // TVA
        if (!empty($country_code)) {
            $cost = SessionManager::get_user_amount_pay_atos($cat['cost'], $country_code);
            if ($pay_type == 3) {
                $next_quota     = SessionManager::get_next_quota_install_to_pay($user_id, $cat_id);                
                $install_cost   = SessionManager::get_cost_installment_quota($cat_id, $next_quota);                
                $cost = SessionManager::get_user_amount_pay_atos($install_cost, $country_code);
            }            
        }	
    }    
    $cost = !empty($cost)?($cost*100):'000';    
}

//display the header
Display::display_header(get_lang('TrainingCategory'));

$from = 'register';    
$href = api_get_path(WEB_CODE_PATH).'admin/payment_options.php?iden='.$_SESSION['iden'].'&wish='.$_SESSION['wish'].'&id='.$_SESSION['cat_id'].'&prev=4';    

if (!isset($_SESSION['cat_id'])) {    
    echo '<div class="actions">';
    echo '<center>'.get_lang('YourSessionOrderIsOver').'<br /><a href="'.api_get_path(WEB_PATH).'">'.get_lang('GoToCatalogue').'</a></center>';
    echo '</div>';
    // display the footer
    Display::display_footer();
    exit;
}

if (api_get_user_id()) {
    echo '<div class="actions">';
    echo '<a href="'.$href.'">'.Display::return_icon('pixel.gif', get_lang("Previous"), array('class' => 'toolactionplaceholdericon toolactionback')).get_lang('Back').'</a>';
    echo '<a href="'.api_get_path(WEB_CODE_PATH).'payment/session_category_payments"">'.Display::return_icon('pixel.gif', get_lang("Catalogue"), array('class' => 'toolactionplaceholdericon toolactioncatalogue')).get_lang('Catalogue').'</a>';
    echo '<a href="'.api_get_path(WEB_CODE_PATH).'payment/installment_payment.php">'.Display::return_icon('pixel.gif', get_lang("InstallmentPaymentInfo"), array('class' => 'toolactionplaceholdericon toolactionother')).get_lang('InstallmentPaymentInfo').'</a>&nbsp;';
    echo '</div>';
} else {
    echo '<div class="actions">';    
    echo '<a href="'.$href.'">'.Display::return_icon('pixel.gif', get_lang("Previous"), array('class' => 'toolactionplaceholdericon toolactionback')).get_lang('Back').'</a>';
    echo '</div>';
}

print '<div id="content">';
print '<center><h3>'.get_lang('ChooseCreditCard').'</h3></center>';

$topic     = SessionManager::get_topic_info($cat['topic']);
$catalogue = SessionManager::get_catalogue_info($topic['catalogue_id']);
if ($pay_type == 1) {
    if (!empty($catalogue['cc_payment_message'])) {
        echo '<div class="messages-payment" style="margin-bottom:10px;padding:4px;">'.$catalogue['cc_payment_message'].'</div>';
    }
} else if ($pay_type == 3) {
    if(!empty($catalogue['installment_payment_message'])) {
        echo '<div class="messages-payment" style="margin-bottom:10px;padding:4px;">'.$catalogue['installment_payment_message'].'</div>';
    }
}

// Affectation des paramètres obligatoires
$parm="merchant_id=011223344551111";
$parm="$parm merchant_country=fr";
$parm="$parm amount=$cost";
$parm="$parm currency_code={$cat['currency']}";

$parm="$parm payment_means=CB,2,VISA,2,MASTERCARD,2";
$parm="$parm header_flag=yes"; // (yes/no)

$data = array();
if (isset($_SESSION['user_info'])) {
	$data['user_info'] = $_SESSION['user_info'];
}
if (isset($_SESSION['payer_info'])) {
	$data['payer_info'] = $_SESSION['payer_info'];
}
if (isset($_SESSION['cat_id'])) {
	$data['cat_id'] = $_SESSION['cat_id'];
}
if (isset($_SESSION['pay_type'])) {
	$data['pay_type'] = $_SESSION['pay_type'];
}
if (isset($_SESSION['selected_sessions'])) {
	$data['selected_sessions'] = $_SESSION['selected_sessions'];
}
if (isset($_SESSION['cours_rel_session'])) {
	$data['cours_rel_session'] = $_SESSION['cours_rel_session'];
}
$data = base64_encode(serialize($data));
$parm="$parm caddie=$data";

//$parm="$parm return_context=";
// Initialisation du chemin du fichier pathfile (à modifier)
$parm ="$parm pathfile=".api_get_path(SYS_CODE_PATH)."payment/atos-sips/param/pathfile";

// URL de retour suite a paiement accepte
$parm ="$parm normal_return_url=".api_get_path(WEB_CODE_PATH)."payment/atos-sips/call_response.php";
//$parm="$parm normal_return_url=".api_get_path(WEB_CODE_PATH)."payment/atos-sips/call_autoresponse.php";
// URL de traitement d'un paiement refuse
$parm ="$parm cancel_return_url=".$href;

/* Extra params */
// $parm="$parm language=fr";   
// $parm="$parm capture_day=";
// $parm="$parm capture_mode=";
// $parm="$parm bgcolor=";
// $parm="$parm block_align=";
// $parm="$parm block_order=";
// $parm="$parm textcolor=";
// $parm="$parm receipt_complement=";
// $parm="$parm caddie=";
// $parm="$parm customer_id=";
// $parm="$parm customer_email=";
// $parm="$parm customer_ip_address=";
//$parm="$parm data=1!2!3";      
// $parm="$parm target=";
//$parm="$parm order_id=123";
// Les valeurs suivantes ne sont utilisables qu'en pré-production
// Elles nécessitent l'installation de vos fichiers sur le serveur de paiement
// $parm="$parm normal_return_logo=";
// $parm="$parm cancel_return_logo=";
// $parm="$parm submit_logo=";
// $parm="$parm logo_id=";
// $parm="$parm logo_id2=";
// $parm="$parm advert=";
// $parm="$parm background_id=";
// $parm="$parm templatefile=";

// Initialisation du chemin de l'executable response (à modifier)
$path_bin = api_get_path(SYS_CODE_PATH)."payment/atos-sips/bin/request";

// Appel du binaire request
$result=exec("$path_bin $parm");

// On separe les differents champs et on les met dans une variable tableau
$tableau = explode ("!", "$result");

// récupération des paramètres
$code = $tableau[1];
$error = $tableau[2];
$message = $tableau[3];

// analyse du code retour
if (( $code == "" ) && ( $error == "" )) {
    print ("<BR><CENTER>Erreur appel request</CENTER><BR>");
    print ("executable request non trouve $path_bin");
    print ("<br><br><br>");
    //print "Votre demande n'a pas été exécuté, s'il vous plaît Give it a try en quelques minutes. Merci.<br>";
}
// Erreur, affiche le message d'erreur
else if ($code != 0) {
    print ("<center><b><h2>Erreur appel API de paiement.</h2></center></b>");
    print ("<br><br><br>");
    print (" Message erreur : $error <br>");
    //print "Votre demande n'a pas été exécuté, s'il vous plaît Give it a try en quelques minutes. Merci.<br>";
}
// OK, affiche le formulaire HTML
else {
    print ("<br><br>");		
    # OK, affichage du mode DEBUG si activé
    print (" $error <br>");		
    print ("  $message <br>");
}

print '</div>';

// display the footer
Display::display_footer();
?>
