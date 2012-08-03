<?php
/*
Plugin Name: GYS Themed Categories
Plugin URI: http://rumleydesign.com/wordpress/2011/gys-themed-categories/
Description: This plugin allows you to assign themes to each of your Wordpress categories. To assign themes to your categories, just go to Posts->Categories and you'll see a drop-down menu of available themes at the bottom of the form.
Author: Luke Rumley / Mike Lopez
Version: 2.5
Author URI: http://rumleydesign.com/
*/
if(!class_exists('GYSThemedCategories')) {
    class GYSThemedCategories {
	function GYSThemedCategories() {
	    $this->BlogCharset=get_option('blog_charset');
	    $this->OptionName=strtoupper(get_class($this));
	    $this->Options=get_option($this->OptionName);
	}

	// GET CURRENT THEME APPLIED TO TAXONOMY
	function GetOption() {
	    $options=func_get_args();
	    $option=$this->Options;
	    //echo "Arguments: ";
	    //print_r($options);
	    //echo "<br />";
	    //echo "Option: ";
	    //print_r($option);
	    //echo "<br />";
	    foreach($options AS $o) {
		// echo "o: " . $o . "<br />";
		$option=$option[$o];
	    }
	    // echo "Option: " . $option . "<br />";
	    return $option;
	}

	// SET THEME TO SELECTED TAXONOMY
	function SetOptions() {
	    $options=func_get_args();
	    for($i=0;$i<count($options);$i+=2) {
		// echo "Option i: " . $options[$i] . "<br />Option i+1: " . $options[$i+1] . "<br />";
		$this->Options[$options[$i]]=$options[$i+1];
	    }
	    update_option($this->OptionName,$this->Options);
	}

	// CATEGORY FORM PROCESSING
	function EditCategoryForm() {
	    $themes=get_themes();
	    $template=$this->GetOption('CategoryThemes',$_GET['tag_ID']);
	    // echo "DollaTemplate: " . $template . "<br />";
	    //print_r($themes);
	    // echo "Stylesheet: " . $theme['Stylesheet'] . "<br />";
	    $options='<option value="">---</option>';
	    foreach($themes AS $theme) {
		// echo "Temp: ". $theme['Template'] . " | Stylesheet: ". $theme['Stylesheet'] . "<br />";
		$selected=$theme['Stylesheet']==$template?' selected="selected" ':'';
		$options.='<option value="'.$theme['Stylesheet'].'"'.$selected.'>'.__($theme['Name']).' '.$theme['Version'].'</option>';
	    }
	    $form=<<<STRING
			    <div id="GYSThemedCategories">
				    <h3>GYS Themed Categories</h3>
				    <table class="form-table">
				    <tbody>
					<tr class="form-field">
						<th valign="top" scope="row">Category Theme</th>
						<td><select id='GYSThemedCategories' name='GYSThemedCategories'>{$options}</select></td>
					</tr>
				    </tbody>
				    </table>
			    </div>
			    <script type="text/javascript">
				    //<![CDATA[
				    function GYSThemedCategories(){
					    try{
						    var x=document.getElementById('GYSThemedCategories');
						    var p=x.parentNode;
						    var t=p.getElementsByTagName('p')[0];
						    p.insertBefore(x,t);
					    }catch(e){}
				    }
				    GYSThemedCategories();
				    //]]>
			    </script>
STRING;
	    echo $form;
	}

	function SaveCategory($id) {
	    if(isset($_POST['GYSThemedCategories'])) {
		$catthemes=$this->GetOption('CategoryThemes');
		if($_POST['GYSThemedCategories']) {
		    $catthemes[$id]=$_POST['GYSThemedCategories'];
		}else {
		    unset($catthemes[$id]);
		}
		$this->SetOptions('CategoryThemes',$catthemes);
	    }
	}

	// TEMPLATE PROCESSING
	function Template($template) {
	    $pid=$cid=0;
	    $perms=get_option('permalink_structure');
	    if($perms) {
		// get current URL if permalinks are set
		$s=empty($_SERVER['HTTPS'])?'':$_SERVER['HTTPS']=='on'?'s':'';
		$protocol='http'.$s;
		$port=$_SERVER['SERVER_PORT']=='80'?'':':'.$_SERVER['SERVER_PORT'];
		$url=$protocol.'://'.$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI'];
		list($url)=explode('?',$url);
		// get Post ID from URL
		$pid=url_to_postid($url);
		// get Category ID from URL
		list($url)=explode('/page/',$url); // <- added for paging compatibility
		//echo '<h1 style="color: red; text-transform: uppercase;">is not page</h1>';
		$cid=get_category_by_path($url,false);
		$cid=$cid->cat_ID;
		//echo '<strong>Debugging info: is-page ==>' . is_page($pid) . ' | pid==>' . $pid . ' | cid==>' . $cid . " | post type==>". get_post_type($pid) . '</strong>';
	    }
	    else {
		// no permalinks so we simply check GET vars
		$pid=$_GET['p']+0;
		$cid=$_GET['cat']+0;
	    }

	    create_initial_taxonomies();
	    if($cid) {
		// we're in a category page...
		$cat=$cid;
	    }
	    elseif ($pid !=0 && get_post_type($pid) != "page") {
		// we're in a post page... so let's get the first category of this post
		list($cat)=wp_get_post_categories($pid);
	    }
	    else {
		// we are on a page, home page, or custom post type...don't apply new theme
		unset($cat);
	    }

	    if($cat) {
		// we have our category ID now so let's get the theme for it...
		$theme=$this->GetOption('CategoryThemes',$cat);
		// change template if a theme is specified for this category
		if($theme)$template=$theme;
	    }
	    else {
		// grab current theme's stylesheet
		$template = get_option('stylesheet');
	    }

	    // set Theme to current template, then grab full stylesheet location
	    $this->Theme = $template;
	    if (strtolower(substr(ABSPATH, 1, 1)) == ":" ) {
		// we're apparently on a Windows box, so ABSPATH and WP_CONTENT_DIR are messed up as of WP 3.2
		$tempstyleloc = substr(ABSPATH, 0, strlen(ABSPATH)-1) . "\\wp-content\\themes\\" . $this->Theme . "\\style.css";
	    }
	    else {
		// ah, linux/unix
		$tempstyleloc = WP_CONTENT_DIR . "/themes/" . $this->Theme . "/style.css";
	    }
	    // grab template from stylesheet location
	    $themedata = get_theme_data($tempstyleloc);
	    if ($themedata["Template"]) {
		return $themedata["Template"];
	    }
	    else {
		return $template;
	    }

	}

	function Stylesheet() {
	    return $this->Theme;
	}
    }
}
if(class_exists('GYSThemedCategories') && !isset($GYSThemedCategories)) {
    $GYSThemedCategories=new GYSThemedCategories(__FILE__);
}

if(isset($GYSThemedCategories)) {
    add_action('category_edit_form_fields',array(&$GYSThemedCategories,'EditCategoryForm'));
    add_action('category_add_form_fields',array(&$GYSThemedCategories,'EditCategoryForm'));
    add_action('create_category',array(&$GYSThemedCategories,'SaveCategory'));
    add_action('edit_category',array(&$GYSThemedCategories,'SaveCategory'));
    add_filter('template',array(&$GYSThemedCategories,'Template'));
    add_filter('stylesheet',array(&$GYSThemedCategories,'Stylesheet'));
}
?>