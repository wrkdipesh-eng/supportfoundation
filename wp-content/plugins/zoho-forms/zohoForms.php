<?php

/*
Plugin Name:Zoho Forms 
Plugin URI: http://wordpress.org/extend/plugins/zohoforms
Description: Try Zoho Forms, the best WordPress contact form plugin! Create contact, payment & custom forms with a drag and drop builder. Get started for free!
Version: 4.0.4
Author: Zoho Forms
Author URI: https://forms.zoho.com
*/

//Shortcode for embeding the form

add_shortcode('zohoForms', 'zohoForms');


function zohoForms($atts, $content = null) {  
	extract(shortcode_atts(array(
		'src' => '',
		'width' => '',
		'height' => '',
        'autoheight' => '',
        'type' => '',
        'urlparams' =>'',
        'formtitle' => ''
	), $atts));

    $src=$atts['src'];
    $height = "600px";
    $width = "100%";
    $urlParams = '';
    $formTitle = '';
    $ariaLabelStr = '';
    if(!empty($atts['formtitle'])){
        $formTitle = $atts['formtitle'];
        $ariaLabelStr = ' aria-label="'.esc_attr($formTitle).'"';
    }
    if(!empty($atts['height'])){
        $height = $atts['height'];
    }
    if(!empty($atts['width'])){
        $width = $atts['width'];
    }
    if(!empty($atts['urlparams'])){
        $urlParams = $atts['urlparams'];
    }
    if(!empty($atts['type']) &&$atts['type'] == "js"){
        $idx= strpos($src, "/formperma/");
        $perma = substr($src, $idx+11);
        if(!empty($atts['autoheight']) && $atts['autoheight'] === 'true'){
            $src.='?zf_rszfm=1';
            if(!empty($urlParams)){
                $src.='&'.$urlParams;
            }
        }else {
            if(!empty($urlParams)){
                $src.='?'.$urlParams;
            }
        }
        $iframeJsCode = 'var f = document.createElement("iframe");
            f.src = "'.esc_url_raw($src).'";
            f.style.border="none";
            f.style.height="'.esc_attr($height).'";
            f.style.width="'.esc_attr($width).'";
            f.style.transition="all 0.5s ease";
            f.setAttribute("allow", "geolocation;microphone;camera");';
        if(!empty($formTitle)) {
            $iframeJsCode .= 'f.setAttribute("aria-label", "' . esc_attr($formTitle) . '");';
        }
        $iframeJsCode .= 'var d = document.getElementById("zf_div_'.esc_attr($perma).'");
            d.appendChild(f);';
        if(!empty($atts['autoheight']) && $atts['autoheight'] === 'true') {
            $iframeJsCode.= 'window.addEventListener("message", function () {
                var evntData = event.data;
                if(evntData && evntData.constructor == String) {
                    var zf_ifrm_data = evntData.split("|");
                    if(zf_ifrm_data.length == 2 || zf_ifrm_data.length == 3) {
                        var zf_perma = zf_ifrm_data[0];
                        var zf_ifrm_ht_nw = (parseInt(zf_ifrm_data[1], 10) + 15) + "px";
                        var iframe = document.getElementById("zf_div_'.esc_attr($perma).'").getElementsByTagName("iframe")[0];
                        if((iframe.src).indexOf("formperma") > 0 && (iframe.src).indexOf(zf_perma) > 0) {
                            var prevIframeHeight = iframe.style.height;
                            var zf_tout = false;
                            if(zf_ifrm_data.length == 3) {
                                iframe.scrollIntoView();
                                zf_tout = true;
                            }
                            if(prevIframeHeight != zf_ifrm_ht_nw) {
                                if(zf_tout) {
                                    setTimeout(function() {
                                        iframe.style.height = zf_ifrm_ht_nw;
                                    }, 500);
                                } else {
                                    iframe.style.height = zf_ifrm_ht_nw;
                                }
                            }
                        }
                    }
                }
            }, false);';
        }
        $jsCodeToEmbed = '<div id="zf_div_'.esc_attr($perma).'"></div>
            <script type="text/javascript">
                (function() {
                    try{'.$iframeJsCode.'}catch(e){}
                })();
            </script>';
        return str_replace('&amp;','&',$jsCodeToEmbed);
    }//Js code check ends
    if(!empty($urlParams)){
        $src.='?'.$urlParams;
    }
    return '<iframe height="'.esc_attr($height).'" width="'.esc_attr($width).'" frameborder="0" allow="geolocation;microphone;camera" src="'.esc_url_raw($src).'"' . $ariaLabelStr . '> </iframe>';  
}  


// Creation of TinyMCE button

add_action('init', 'add_zohoforms_button');

// Adding filters for the external plugins.

function add_zohoforms_button() {  
   
     add_filter('mce_external_plugins', 'add_zohoForms_plugin');  
     add_filter('mce_buttons', 'register_zohoForms_button');  
     
}  

// Registering the TinyMCE button.

function register_zohoForms_button($buttons) {  
   array_push($buttons, "zohoForms");  
   return $buttons;  
}  

// Returns the plugin_array which contains the values for the shortcode. 

function add_zohoForms_plugin($plugin_array) {  
   $plugin_array['zohoForms'] = plugin_dir_url( __FILE__ ) . 'tinymce/zforms_editor_plugin.js'; 
   return $plugin_array;  
} 

//Including block files
function loadZohoFormsBlockFiles() {
  wp_enqueue_script(
    'zoho-forms-block-js',
    plugin_dir_url(__FILE__) . 'zohoforms-block.js',
    array('wp-blocks', 'wp-i18n', 'wp-editor'),
    true
  );
  wp_localize_script( 'zoho-forms-block-js', 'zohoFormsBlock', array(
      'blockCSS' => plugin_dir_url(__FILE__) . 'zohoforms-block.css',
      'favIconPath' => plugin_dir_url(__FILE__) . 'tinymce/zFormsIcon.png'
    ) );
}



add_action('enqueue_block_editor_assets', 'loadZohoFormsBlockFiles'); 

?>
