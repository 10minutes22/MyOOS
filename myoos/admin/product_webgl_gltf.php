<?php
/* ----------------------------------------------------------------------

   MyOOS [Shopsystem]
   https://www.oos-shop.de

   Copyright (c) 2003 - 2019 by the MyOOS Development Team.
   ----------------------------------------------------------------------
   Based on:

   File: categories.php,v 1.146 2003/07/11 14:40:27 hpdl
         categories.php,v 1.138 2002/11/18 21:38:22 dgw_
   ----------------------------------------------------------------------
   osCommerce, Open Source E-Commerce Solutions
   http://www.oscommerce.com

   Copyright (c) 2003 osCommerce
   ----------------------------------------------------------------------
   Released under the GNU General Public License
   ---------------------------------------------------------------------- */

define('OOS_VALID_MOD', 'yes');
require 'includes/main.php';

require 'includes/functions/function_categories.php';

require_once MYOOS_INCLUDE_PATH . '/includes/lib/htmlpurifier/library/HTMLPurifier.auto.php';


$action = (isset($_GET['action']) ? $_GET['action'] : '');
$cPath = (isset($_GET['cPath']) ? oos_prepare_input($_GET['cPath']) : $current_category_id);
$pID = (isset($_GET['pID']) ? intval($_GET['pID']) : 0);

if (!empty($action)) {
	switch ($action) {
		case 'insert_model':
		case 'update_model':
		
			if (isset($_SESSION['formid']) && ($_SESSION['formid'] == $_POST['formid'])) {		
				$products_id = intval($_POST['products_id']);		

				if (isset($_FILES['files'])) {
					foreach ($_FILES['files']['name'] as $key => $name) {
						if (empty($name)) {
							// purge empty slots
							unset($_FILES['files']['name'][$key]);
							unset($_FILES['files']['type'][$key]);
							unset($_FILES['files']['tmp_name'][$key]);
							unset($_FILES['files']['error'][$key]);
							unset($_FILES['files']['size'][$key]);
						}
					}
				}

				$nModelCounter = (!isset($_POST['model_counter']) || !is_numeric($_POST['model_counter'])) ? 0 : intval($_POST['model_counter']);
				
				for ($i = 0, $n = $nModelCounter; $i < $n; $i++) {
								
					$action = (!isset($_POST['models_id'][$i]) || !is_numeric($_POST['models_id'][$i])) ? 'insert_model' : 'update_model';
			
					$sql_data_array = array('products_id' => intval($products_id),
											'models_author' => oos_db_prepare_input($_POST['models_author'][$i]),
											'models_author_url' => oos_db_prepare_input($_POST['models_author_url'][$i]),
											'models_camera_pos' => oos_db_prepare_input($_POST['models_camera_pos'][$i]),
											'models_object_rotation' => oos_db_prepare_input($_POST['models_object_rotation'][$i]),
											'models_hdr' => oos_db_prepare_input($_POST['models_hdr'][$i]),
											'models_add_lights' => oos_db_prepare_input($_POST['models_add_lights'][$i]),
											'models_add_ground' => oos_db_prepare_input($_POST['models_add_ground'][$i]),
											'models_shadows' => oos_db_prepare_input($_POST['models_shadows'][$i]),
											'models_add_env_map' => oos_db_prepare_input($_POST['models_add_env_map'][$i]),
											'models_extensions' => oos_db_prepare_input($_POST['models_extensions'][$i])
											);
										

					if ($action == 'insert_model') {
						$insert_sql_data = array('models_date_added' => 'now()');

						$sql_data_array = array_merge($sql_data_array, $insert_sql_data);

						oos_db_perform($oostable['products_models'], $sql_data_array);
						$models_id = $dbconn->Insert_ID();

					} elseif ($action == 'update_model') {
						$update_sql_data = array('models_last_modified' => 'now()');
						$models_id = intval($_POST['models_id'][$i]);
						
						$sql_data_array = array_merge($sql_data_array, $update_sql_data);

						oos_db_perform($oostable['products_models'], $sql_data_array, 'UPDATE', 'models_id = \'' . intval($models_id) . '\'');

					}

					$aLanguages = oos_get_languages();
					$nLanguages = count($aLanguages);

					for ($li = 0, $l = $nLanguages; $li < $l; $li++) {
						$language_id = $aLanguages[$li]['id'];

						$sql_data_array = array('models_name' => oos_db_prepare_input($_POST['models_name'][$i][$language_id]),
												'models_title' => oos_db_prepare_input($_POST['models_title'][$i][$language_id]),
												'models_description_meta' => oos_db_prepare_input($_POST['models_description_meta_'. $i . '_'  . $aLanguages[$li]['id']]));
	
						if ($action == 'insert_model') {
							$insert_sql_data = array('models_id' => $models_id,
													'models_languages_id' => $language_id);

							$sql_data_array = array_merge($sql_data_array, $insert_sql_data);

							oos_db_perform($oostable['products_models_description'], $sql_data_array);
						} elseif ($action == 'update_model') {
							oos_db_perform($oostable['products_models_description'], $sql_data_array, 'UPDATE', 'models_id = \'' . intval($models_id) . '\' AND models_languages_id = \'' . intval($language_id) . '\'');
						}
					}


					if ( ($_POST['remove_products_model'][$i] == 'yes') && (isset($_POST['models_webgl_gltf'][$i])) ) {
						$models_webgl_gltf = oos_db_prepare_input($_POST['models_webgl_gltf'][$i]);

						$dbconn->Execute("DELETE FROM " . $oostable['products_models'] . " WHERE models_id = '" . intval($_POST['models_id'][$i]) . "'");	

						oos_remove_products_model($models_webgl_gltf);
					}


					if (isset($_FILES['zip_file'])) {
						if ($_FILES["zip_file"]["error"] == UPLOAD_ERR_OK) {

							$filename = oos_db_prepare_input($_FILES['zip_file']['name']);
							$source = $_FILES['zip_file']['tmp_name'];
							$type = oos_db_prepare_input($_FILES['zip_file']['type']);


							if (is_zip($filename)) {
								$name = oos_strip_suffix($filename);
								$models_extensions = oos_db_prepare_input($_POST['models_extensions'][$i]);

								$check =  OOS_ABSOLUTE_PATH . OOS_MEDIA . 'models/gltf/' . oos_var_prep_for_os($name);
								if (is_dir($check)) oos_remove($check);

								$path = OOS_ABSOLUTE_PATH . OOS_MEDIA . 'models/gltf/' . oos_var_prep_for_os($name) . '/' . oos_var_prep_for_os($models_extensions) . '/';
								$targetdir = $path;  // target directory
								$targetzip = $path . $filename; // target zip file
													
								mkdir($check, 0755);
								mkdir($targetdir, 0755);

								if (move_uploaded_file($source, $targetzip)) {
									$zip = new ZipArchive();
									$x = $zip->open($targetzip);  // open the zip file to extract
									if ($x === true) {
										$zip->extractTo($targetdir); // place in the directory with same name  
										$zip->close();

										unlink($targetzip);
									}
									$messageStack->add_session(TEXT_SUCCESSFULLY_UPLOADED, 'success');

									$folder = opendir($targetdir);
									while($file = readdir($folder)) {
										if ($file == '.' || $file == '..' || $file == 'CVS') continue;

										$ext = oos_get_suffix($file);
										switch ($ext) {
											case 'glb':
											case 'gltf':
												$models_webgl_gltf = $file;
												break;
										}
									}

									$sql_data_array = array('models_webgl_gltf' => oos_db_prepare_input($models_webgl_gltf));

									oos_db_perform($oostable['products_models'], $sql_data_array, 'UPDATE', 'models_id = \'' . intval($models_id) . '\'');
								
								} else {  
									$messageStack->add_session(ERROR_PROBLEM_WITH_ZIP_FILE, 'error');							
								}
							} else {
								$messageStack->add_session(ERROR_NO_ZIP_FILE, 'error');					
						
							}
						}
					}
				}
				oos_redirect_admin(oos_href_link_admin($aContents['categories'], 'cPath=' . $cPath . '&pID=' . $products_id));
			}
			break;

	}
}

require 'includes/header.php';
?>
<!-- body //-->
<div class="wrapper">
	<!-- Header //-->
	<header class="topnavbar-wrapper">
		<!-- Top Navbar //-->
		<?php require 'includes/menue.php'; ?>
	</header>
	<!-- END Header //-->
	<aside class="aside">
		<!-- Sidebar //-->
		<div class="aside-inner">
			<?php require 'includes/blocks.php'; ?>
		</div>
		<!-- END Sidebar (left) //-->
	</aside>

	<!-- Main section //-->
	<section>
		<!-- Page content //-->
		<div class="content-wrapper">

				<div class="row">
					<div class="col-lg-12">
<?php
if ($action == 'edit_3d') {
		
    $parameters = array('products_id' => '',
						'products_name' => '',
						'products_image' => '',
                        'products_models' => array());

    $pInfo = new objectInfo($parameters);	  
	  
	if (isset($_GET['pID']) && empty($_POST)) {	  
		$productstable = $oostable['products'];

		$products_descriptiontable = $oostable['products_description'];
		$product_result = $dbconn->Execute("SELECT p.products_id, p.products_image, pd.products_name
                                            FROM $productstable p,
                                                 $products_descriptiontable pd
                                           WHERE p.products_id = '" . intval($pID) . "' AND
                                                 p.products_id = pd.products_id AND
                                                 pd.products_languages_id = '" . intval($_SESSION['language_id']) . "'");									 
		$product = $product_result->fields;

		$pInfo = new objectInfo($product);

		$products_modelstable = $oostable['products_models'];
		$products_models_descriptiontable = $oostable['products_models_description'];
		$products_models_result = $dbconn->Execute("SELECT m.models_id, m.products_id, md.models_name, md.models_title,	md.models_description_meta, 
													md.models_keywords, m.models_webgl_gltf, m.models_author, m.models_author_url, 
													m.models_camera_pos, m.models_object_rotation, m.models_add_lights, models_add_ground, m.models_shadows, 
													m.models_add_env_map, m.models_extensions, m.models_hdr 
                                            FROM $products_modelstable m,
                                                 $products_models_descriptiontable md
                                           WHERE m.products_id = '" . intval($product['products_id']) . "' AND
                                                 m.models_id = md.models_id AND
                                                 md.models_languages_id = '" . intval($_SESSION['language_id']) . "'");

		if (!$products_models_result->RecordCount()) {
			$pInfo->products_models[] = array('products_id' => $product['products_id'],
											'models_webgl_gltf' => '',
											'models_author' => '',
											'models_author_url' => 'https://',
											'models_camera_pos' => '0.02, 0.01, 0.03',
											'models_object_rotation' => '0, Math.PI, 0',
											'models_add_lights' => 'false',
											'models_add_ground' => 'false',
											'models_shadows' => 'false',
											'models_add_env_map' => 'true',
											'models_extensions' => 'glTF',
											'models_hdr' => 'venice_sunset_2k.hdr');

		} else {			
			while ($products_models = $products_models_result->fields) {
				$pInfo->products_models[] = array('models_id' => $products_models['models_id'],
											'products_id' => $products_models['products_id'],
											'models_webgl_gltf' => $products_models['models_webgl_gltf'],
											'models_author' => $products_models['models_author'],
											'models_author_url' => $products_models['models_author_url'],
											'models_camera_pos' => $products_models['models_camera_pos'],
											'models_object_rotation' => $products_models['models_object_rotation'],
											'models_add_lights' => $products_models['models_add_lights'],
											'models_add_ground' => $products_models['models_add_ground'],
											'models_shadows' => $products_models['models_shadows'],
											'models_add_env_map' => $products_models['models_add_env_map'],
											'models_extensions' => $products_models['models_extensions'],
											'models_hdr' => $products_models['models_hdr']);
				// Move that ADOdb pointer!
				$products_models_result->MoveNext();
			} 	
		}
	} 

	$aExtensions = array();
	$aExtensions = array('glTF', 'glTF-Embedded', 'glTF-pbrSpecularGlossiness', 'glTF-Binary', 'glTF-Draco');

    $aLanguages = oos_get_languages();
	$nLanguages = count($aLanguages);
	
    $form_action = (isset($_GET['pID'])) ? 'update_model' : 'insert_model';

    $back_url = $aContents['categories'];
    $back_url_params = 'cPath=' . $cPath;
    if (oos_is_not_null($pInfo->products_id)) {
		$back_url_params .= '&pID=' . $pInfo->products_id;
	}	

?>	
	<!-- Breadcrumbs //-->
	<div class="content-heading">
		<div class="col-lg-12">
			<h2><?php echo sprintf(TEXT_NEW_PRODUCT, oos_output_generated_category_path($current_category_id)); ?></h2>
			<ol class="breadcrumb">
				<li class="breadcrumb-item">
					<?php echo '<a href="' . oos_href_link_admin($aContents['default']) . '">' . HEADER_TITLE_TOP . '</a>'; ?>
				</li>
				<li class="breadcrumb-item">
					<?php echo '<a href="' . oos_href_link_admin($aContents['categories'], 'selected_box=catalog') . '">' . BOX_HEADING_CATALOG . '</a>'; ?>
				</li>
				<li class="breadcrumb-item active">
					<strong><?php echo sprintf(TEXT_NEW_PRODUCT, oos_output_generated_category_path($current_category_id)); ?></strong>
				</li>
			</ol>
		</div>
	</div>
	<!-- END Breadcrumbs //-->

	<?php echo oos_draw_form('id', 'new_product', $aContents['product_webgl_gltf'], 'cPath=' . $cPath . (!empty($pID) ? '&pID=' . intval($pID) : '') . '&action=' . $form_action, 'post', FALSE, 'enctype="multipart/form-data"'); ?>
		<?php  	
		
				$sFormid = md5(uniqid(rand(), true));
				$_SESSION['formid'] = $sFormid;
				echo oos_draw_hidden_field('formid', $sFormid);
				echo oos_draw_hidden_field('products_id', $pInfo->products_id);
				echo oos_hide_session_id();
		?>
               <div role="tabpanel">
                  <ul class="nav nav-tabs nav-justified" id="myTab">
                     <li class="nav-item" role="presentation">
                        <a class="nav-link active" href="#product" aria-controls="product" role="tab" data-toggle="tab"><?php echo TEXT_PRODUCTS; ?></a>
                     </li>
                     <li class="nav-item" role="presentation">
                        <a class="nav-link" href="#model" aria-controls="model" role="tab" data-toggle="tab"><?php echo TEXT_MODELS_MODEL; ?></a>
                     </li>
                     <li class="nav-item" role="presentation">
                        <a class="nav-link" href="#uplaod" aria-controls="picture" role="tab" data-toggle="tab"><?php echo TEXT_UPLOAD_MODELS; ?></a>
                     </li>
                  </ul>
                  <div class="tab-content">
					<div class="text-right mt-3 mb-3">
						<?php echo '<a class="btn btn-sm btn-warning mb-20" href="' . oos_href_link_admin($back_url, $back_url_params) . '" role="button"><strong><i class="fa fa-chevron-left"></i> ' . IMAGE_BACK . '</strong></a>'; ?>
						<?php echo oos_submit_button(IMAGE_SAVE); ?>
						<?php echo oos_reset_button(BUTTON_RESET); ?>			   
					</div>			  
                     <div class="tab-pane active" id="product" role="tabpanel">


                           <div class="col-9">
                              <h2><?php echo $pInfo->products_name; ?></h2>
                           </div>	
					 
                           <div class="col-9">
                              <?php echo product_info_image($pInfo->products_image, $pInfo->products_name); ?>
                           </div>					 
					 
                     </div>
			 
                     <div class="tab-pane" id="model" role="tabpanel">
						<div class="col-9">
<?php
	if (is_array($pInfo->products_models) || is_object($pInfo->products_models)) {
		$nCounter = 0;
	
		foreach ($pInfo->products_models as $models) {

			if (isset($models['models_id'])) {
				echo oos_draw_hidden_field('models_id['. $nCounter . ']', $models['models_id']);
?>
						<fieldset>
                           <div class="form-group row">
                              <label class="col-lg-2 col-form-label"><?php echo TEXT_MODELS_MODEL; ?></label>
                              <div class="col-lg-10">
								<?php echo oos_draw_input_field('models_webgl_gltf['. $nCounter . ']', $models['models_webgl_gltf'], '', FALSE, 'text', TRUE, TRUE); ?>
								<?php echo oos_draw_hidden_field('models_webgl_gltf['. $nCounter . ']', $models['models_webgl_gltf']); ?>
                              </div>
                           </div>
                        </fieldset>	
						<fieldset>
                           <div class="form-group row">
                              <label class="col-lg-2 col-form-label"></label>
                              <div class="col-lg-10">
								<?php echo oos_draw_checkbox_field('remove_products_model['. $nCounter . ']', 'yes') . ' ' . TEXT_MODEL_REMOVE; ?>
                              </div>
                           </div>
                        </fieldset>	
<?php						
			}

    for ($i = 0, $n = $nLanguages; $i < $n; $i++) {
?>

                        <fieldset>
                           <div class="form-group row">
                              <label class="col-lg-2 col-form-label"><?php if ($i == 0) echo TEXT_MODELS_NAME; ?></label>
							  <?php if ($nLanguages > 1) echo '<div class="col-lg-1">' .  oos_flag_icon($aLanguages[$i]) . '</div>'; ?>
                              <div class="col-lg-9">
								<?php echo oos_draw_input_field('models_name['. $nCounter . '][' . $aLanguages[$i]['id'] . ']', (($models_name[$aLanguages[$i]['id']]) ? stripslashes($models_name[$aLanguages[$i]['id']]) : oos_get_models_name($models['models_id'], $aLanguages[$i]['id']))); ?>
                              </div>
                           </div>
                        </fieldset>						
<?php
    }
    for ($i = 0, $n = $nLanguages; $i < $n; $i++) {
?>
                        <fieldset>
                           <div class="form-group row">
                              <label class="col-lg-2 col-form-label"><?php if ($i == 0) echo TEXT_MODELS_TITLE; ?></label>
							  <?php if ($nLanguages > 1) echo '<div class="col-lg-1">' .  oos_flag_icon($aLanguages[$i]) . '</div>'; ?>
                              <div class="col-lg-9">
								<?php echo oos_draw_input_field('models_title['. $nCounter . '][' . $aLanguages[$i]['id'] . ']', (($models_title[$aLanguages[$i]['id']]) ? stripslashes($models_title[$aLanguages[$i]['id']]) : oos_get_models_title($models['models_id'], $aLanguages[$i]['id']))); ?>
                              </div>
                           </div>
                        </fieldset>						
<?php
    }	
	for ($i = 0, $n = $nLanguages; $i < $n; $i++) {
?>
					<fieldset>
						<div class="form-group row">
							<label class="col-lg-2 col-form-label"><?php if ($i == 0) echo TEXT_MODELS_DESCRIPTION_META; ?></label>
							<?php if ($nLanguages > 1) echo '<div class="col-lg-1">' .  oos_flag_icon($aLanguages[$i]) . '</div>'; ?>
							<div class="col-lg-9">
								<?php echo oos_draw_textarea_field('models_description_meta_'. $nCounter . '_' . $aLanguages[$i]['id'], 'soft', '70', '4', ($_POST['models_description_meta_'. $nCounter . '_' . $aLanguages[$i]['id']] ? stripslashes($_POST['models_description_meta_'. $nCounter . '_' .$aLanguages[$i]['id']]) : oos_get_models_description_meta($models['models_id'], $aLanguages[$i]['id']))); ?>
							</div>
						</div>
					</fieldset>
<?php
	}
?>


						
                       <fieldset>
                           <div class="form-group row">
                              <label class="col-lg-2 col-form-label"><?php echo TEXT_MODELS_AUTHOR; ?></label>
                              <div class="col-lg-10">
								<?php echo oos_draw_input_field('models_author['. $nCounter . ']',  $models['models_author']); ?>
                              </div>
                           </div>
                        </fieldset>
                       <fieldset>
                           <div class="form-group row">
                              <label class="col-lg-2 col-form-label"><?php echo TEXT_MODELS_AUTHOR_URL; ?></label>
                              <div class="col-lg-10">
								<?php echo oos_draw_input_field('models_author_url['. $nCounter . ']',  $models['models_author_url']); ?>
                              </div>
                           </div>
                        </fieldset>
                       <fieldset>
                           <div class="form-group row">
                              <label class="col-lg-2 col-form-label"><?php echo TEXT_MODELS_CAMERA_POS; ?></label>
                              <div class="col-lg-10">
								<?php echo oos_draw_input_field('models_camera_pos['. $nCounter . ']',  $models['models_camera_pos']); ?>
                              </div>
                           </div>
                        </fieldset>
                       <fieldset>
                           <div class="form-group row">
                              <label class="col-lg-2 col-form-label"><?php echo TEXT_MODELS_OBJECT_ROTATION; ?></label>
                              <div class="col-lg-10">
								<?php echo oos_draw_input_field('models_object_rotation['. $nCounter . ']',  $models['models_object_rotation']); ?>
                              </div>
                           </div>
                        </fieldset>


                       <fieldset>
                           <div class="form-group row mt-5">
								<label class="col-sm col-form-label"><?php echo TEXT_MODELS_HDR; ?></label>
								<div class="col-sm">
									<div class="c-radio c-radio-nofont">
										<label>
										<?php
											echo '<input type="radio" name="models_hdr['. $nCounter . ']" value="venetian_crossroads_2k.hdr"'; 
											if ($models['models_hdr'] == 'venetian_crossroads_2k.hdr') echo ' checked="checked"';
											echo  '>&nbsp;venetian_crossroads_2k.hdr';
											echo oos_image(OOS_IMAGES . 'background/venetian_crossroads.jpg', 'venetian_crossroads_2k.hdr');
										?>
										</label>
									</div>
								</div>
								<div class="col-sm">
									<div class="c-radio c-radio-nofont">
										<label>
										<?php
											echo '<input type="radio" name="models_hdr['. $nCounter . ']" value="vignaioli_2k.hdr"'; 
											if ($models['models_hdr'] == 'vignaioli_2k.hdr') echo ' checked="checked"';
											echo  '>&nbsp;vignaioli_2k.hdr';
											echo oos_image(OOS_IMAGES . 'background/vignaioli.jpg', 'vignaioli_2k.hdr');
										?>
										</label>
									</div>
								</div>
                           </div>
						   
						   
	                           <div class="form-group row mt-5">
								<label class="col-sm col-form-label"></label>
								<div class="col-sm">
									<div class="c-radio c-radio-nofont">
										<label>
										<?php
											echo '<input type="radio" name="models_hdr['. $nCounter . ']" value="canary_wharf_2k.hdr"'; 
											if ($models['models_hdr'] == 'canary_wharf_2k.hdr') echo ' checked="checked"';
											echo  '>&nbsp;canary_wharf_2k.hdr';
											echo oos_image(OOS_IMAGES . 'background/canary_wharf.jpg', 'canary_wharf_2k.hdr');
										?>
										</label>
									</div>
								</div>
								<div class="col-sm">
									<div class="c-radio c-radio-nofont">
										<label>
										<?php
											echo '<input type="radio" name="models_hdr['. $nCounter . ']" value="venice_sunset_2k.hdr"'; 
											if ($models['models_hdr'] == 'venice_sunset_2k.hdr') echo ' checked="checked"';
											echo  '>&nbsp;venice_sunset_2k.hdr';
											echo oos_image(OOS_IMAGES . 'background/venice_sunset.jpg', 'venice_sunset_2k.hdr');
										?>
										</label>
									</div>
								</div>
                           </div>					   
                        </fieldset>
                       <fieldset>
                           <div class="form-group row">
                              <label class="col-lg-2 col-form-label"><?php echo TEXT_MODELS_ADD_LIGHTS; ?></label>

								<div class="col-lg-10">
									<div class="c-radio c-radio-nofont">
										<label>
										<?php
											echo '<input type="radio" name="models_add_lights['. $nCounter . ']" value="true"'; 
											if ($models['models_add_lights'] == 'true') echo ' checked="checked"';
											echo  '>&nbsp;';
									   ?>
											<span class="badge badge-success float-right"><?php echo ENTRY_YES; ?></span>
										</label>
									</div>
								<div class="c-radio c-radio-nofont">
									<label>
										<?php
											echo '<input type="radio" name="models_add_lights['. $nCounter . ']" value="false"'; 
											if ($models['models_add_lights'] == 'false') echo ' checked="checked"';
											echo  '>&nbsp;';
									   ?>
										<span class="badge badge-danger float-right"><?php echo ENTRY_NO; ?></span>
									</label>
								</div>
							</div>
						</div>							  
                        </fieldset>	
                       <fieldset>
                           <div class="form-group row">
                              <label class="col-lg-2 col-form-label"><?php echo TEXT_MODELS_ADD_GROUND; ?></label>

								<div class="col-lg-10">
									<div class="c-radio c-radio-nofont">
										<label>
										<?php
											echo '<input type="radio" name="models_add_ground['. $nCounter . ']" value="true"'; 
											if ($models['models_add_ground'] == 'true') echo ' checked="checked"';
											echo  '>&nbsp;';
									   ?>
											<span class="badge badge-success float-right"><?php echo ENTRY_YES; ?></span>
										</label>
									</div>
								<div class="c-radio c-radio-nofont">
									<label>
										<?php
											echo '<input type="radio" name="models_add_ground['. $nCounter . ']" value="false"'; 
											if ($models['models_add_ground'] == 'false') echo ' checked="checked"';
											echo  '>&nbsp;';
									   ?>
										<span class="badge badge-danger float-right"><?php echo ENTRY_NO; ?></span>
									</label>
								</div>
							</div>
						</div>							  
                        </fieldset>	
						
                       <fieldset>
                           <div class="form-group row">
                              <label class="col-lg-2 col-form-label"><?php echo TEXT_MODELS_SHADOWS; ?></label>

								<div class="col-lg-10">
									<div class="c-radio c-radio-nofont">
										<label>
										<?php
											echo '<input type="radio" name="models_shadows['. $nCounter . ']" value="true"'; 
											if ($models['models_shadows'] == 'true') echo ' checked="checked"';
											echo  '>&nbsp;';
									   ?>
											<span class="badge badge-success float-right"><?php echo ENTRY_YES; ?></span>
										</label>
									</div>
								<div class="c-radio c-radio-nofont">
									<label>
										<?php
											echo '<input type="radio" name="models_shadows['. $nCounter . ']" value="false"'; 
											if ($models['models_shadows'] == 'false') echo ' checked="checked"';
											echo  '>&nbsp;';
									   ?>
										<span class="badge badge-danger float-right"><?php echo ENTRY_NO; ?></span>
									</label>
								</div>
							</div>
						</div>							  
                        </fieldset>	
                       <fieldset>
                           <div class="form-group row">
                              <label class="col-lg-2 col-form-label"><?php echo TEXT_MODELS_ENV_MAP; ?></label>

								<div class="col-lg-10">
									<div class="c-radio c-radio-nofont">
										<label>
										<?php
											echo '<input type="radio" name="models_add_env_map['. $nCounter . ']" value="true"'; 
											if ($models['models_add_env_map'] == 'true') echo ' checked="checked"';
											echo  '>&nbsp;';
									   ?>
											<span class="badge badge-success float-right"><?php echo ENTRY_YES; ?></span>
										</label>
									</div>
								<div class="c-radio c-radio-nofont">
									<label>
										<?php
											echo '<input type="radio" name="models_add_env_map['. $nCounter . ']" value="false"'; 
											if ($models['models_add_env_map'] == 'false') echo ' checked="checked"';
											echo  '>&nbsp;';
									   ?>
										<span class="badge badge-danger float-right"><?php echo ENTRY_NO; ?></span>
									</label>
								</div>
							</div>
						</div>							  
                        </fieldset>						
					
					  	<fieldset>
                           <div class="form-group row">
                              <label class="col-lg-2 col-form-label"><?php echo TEXT_MODELS_EXTENSIONS; ?></label>
                              <div class="col-lg-10"><?php echo oos_draw_extensions_menu('models_extensions['. $nCounter . ']', $aExtensions, $models['models_extensions']); ?></div>
                           </div>
                        </fieldset>
							  
							 
				</div>

<?php
					$nCounter++;
					echo oos_draw_hidden_field('model_counter', $nCounter);
		}
	} 
?>

			</div>
            <div class="tab-pane" id="uplaod" role="tabpanel">


				<div class="row mb-3">
					<div class="col-3">
						<strong><?php echo TEXT_CHOOSE_A_ZIP_FILE; ?></strong>
					</div>
					<div class="col-9">
			
						<fieldset>
							<div class="form-group">
							<div class="col-sm-10">
									<input type="file" name="zip_file" />
							</div>
							</div>
						</fieldset>
					</div>
				</div>
			</div>



                  </div>
               </div>
            <div class="text-right mt-3">
				<?php echo '<a class="btn btn-sm btn-warning mb-20" href="' . oos_href_link_admin($back_url, $back_url_params) . '" role="button"><strong><i class="fa fa-chevron-left"></i> ' . IMAGE_BACK . '</strong></a>'; ?>
				<?php echo oos_submit_button(IMAGE_SAVE); ?>
				<?php echo oos_reset_button(BUTTON_RESET); ?>			   
			</div>				
			
            </form>
<!-- body_text_eof //-->
<?php
}
?>

				</div>
			</div>

		</div>
	</section>
	<!-- Page footer //-->
	<footer>
		<span>&copy; 2019 - <a href="https://www.oos-shop.de" target="_blank" rel="noopener">MyOOS [Shopsystem]</a></span>
	</footer>
</div>

<?php
	require 'includes/bottom.php';
	require 'includes/nice_exit.php';
?>
