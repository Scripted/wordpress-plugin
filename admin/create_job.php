<?php
function scripted_create_a_job_callback()
{
    ?>
       <style>
           .form-table select {
               width: 300px;
           }
           label.left_label {
                float: left;
                width: 218px;
           }
           p.help_text{
                  font-size: 10px;
                  margin-left: 218px;
           }
       </style>
        <?php
  
   $ID               = get_option( '_scripted_ID' );
   $accessToken      = get_option( '_scripted_auccess_tokent' );
   $success = false;
   
    if(isset($_POST) && wp_verify_nonce($_POST['_wpnonce'],'createProject')) {  
         
       $error = validateCreateProject($_POST);       
       if($error == '') {
           $topic           = urlencode(sanitize_text_field($_POST['topic']));
           $quantity_order  = sanitize_text_field($_POST['quantity_order']);
           
           $format_id       = sanitize_text_field($_POST['format_id']);
           $industry_ids    = sanitize_text_field($_POST['industry_ids']);
           $guideline_ids   = sanitize_text_field($_POST['guideline_ids']);
           $delivery        = sanitize_text_field($_POST['delivery']);
           $formFields      = $_POST['form_fields'];
           $fields          ='topic='.$topic.'&quantity='.$quantity_order;
           
           if($format_id!= '')
               $fields .= '&job_template[id]='.$format_id;
           
           if(is_array($formFields)) {
                foreach($formFields as $key => $value) {
                    $value   = $value;

                    if(is_array($value)) {
                        foreach ($value as $sub) {    
                            $fields  .= '&job_template[prompts][][id]='.$key;
                            $fields  .= '&job_template[prompts][][value][]='.urlencode($sub);
                        }                            
                    } else {
                        $fields  .= '&job_template[prompts][][id]='.$key;
                        $fields  .= '&job_template[prompts][][value]='.urlencode($value);
                    }
                }
            }  
           
           if($industry_ids!= '')
               $fields .= '&industries[][id]='.$industry_ids;
           
           if($guideline_ids!= '')
               $fields .= '&guidelines[][id]='.$guideline_ids;
           
           if($delivery!= '')
               $fields .= '&delivery='.$delivery;
           
            $fieldslength = strlen($fields);
           
            $ch = curl_init(); 
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('authorization: Token token='.$accessToken));    
            curl_setopt($ch, CURLOPT_HEADER, false);    
            curl_setopt($ch, CURLOPT_URL, SCRIPTED_END_POINT.'/'.$ID.'/v1/jobs');     
            curl_setopt($ch,CURLOPT_POST,$fieldslength);
            curl_setopt($ch,CURLOPT_POSTFIELDS,$fields);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
            $result = curl_exec($ch);   
            curl_close($ch);
            
            $response = json_decode($result);
            
            if ($result === false) {        
                echo '<div class="updated" id="message"><p>Sorry, we found an error and your Scripted job was not created! Please confirm your ID and Access Token are correct and try again.</p></div>';
            } else {  
                
                    if($response != '' and isset($response->data)) {
                        $success = true;
                        $response   = $response->data;
                        $deadlineAt = strtotime($response->deadline_at);
                        $deadlineAt = '<p>Delivery Time : '.date('M d, Y',$deadlineAt).'</p>';
                        $projectId  = '<p>Project id : '.$response->id.'</p>';

                        echo '<div class="updated" id="message"><p>Congratulation! Your project has been created.</p>'.$projectId.$deadlineAt.'</div>';      
                        
                    }
            }
           
       } else {
            echo '<div class="updated" id="message">'.$error.'</div>';
       }
    }
  
    
   $out = '<div class="wrap">
            <div class="icon32" style="width:100px;padding-top:5px;" id="icon-scripted"><img src="'.SCRIPTED_LOGO.'"></div><h2>Create a Job</h2>';
            
   $out .='<form action="" method="post" name="scripted_settings">'.wp_nonce_field( 'createProject', '_wpnonce' );
   $fields = '';
   $validate = validateApiKey($ID,$accessToken);
   if($validate) {
       if(!$success and isset($_POST['format_id']) and $_POST['format_id'] !='0') {
           $fields = getFormFieldsCallback($_POST['format_id']);
       }
   $out .='<table class="form-table">
            <tbody>
                <tr valign="top">
        <th scope="row"><label for="topic">Topic </label></th>
        <td><input type="text" class="regular-text" value="'.((!$success) ? $_POST['topic'] : '').'" id="topic" name="topic"></td>
        </tr>
        <tr valign="top">
        <th scope="row"><label for="api_key">Template </label></th>
        <td>'.getStandardBlogPost((!$success) ? $_POST['format_id'] : '').'</td>
        </tr>
        <tr valign="top">
        <th colspan="2" scope="row" id="formfieldsplace">'.@$fields.'</th>
        </tr>
        <tr valign="top">
        <th scope="row"><label for="api_key">Industries </label></th>
        <td>'.getListIndustryIds((!$success) ? $_POST['industry_ids'] : '').'</td>
        </tr>
        <tr valign="top">
        <th scope="row"><label for="api_key">Guidelines </label></th>
        <td>'.getListGuidelineIds((!$success) ? $_POST['guideline_ids'] : '').'</td>
        </tr>
        <tr valign="top">
        <th scope="row"><label for="api_key">Delivery </label></th>
        <td>'.delivery((!$success) ? $_POST['delivery'] : '').'</td>
        </tr>
            </tbody>
            </table>
            <p class="submit">
            <input type="submit" value="Create this Job" class="button-primary" id="submit" name="submit">
            </p>';
   }
   
   
   $out .='</form>';
   
   $out .='</div>';// end of wrap div
   echo $out;
}
function getStandardBlogPost($selected ='')
{
    $jobTemplates = curlRequest('job_templates/');    
    if($jobTemplates) {

        $out .= '<select name="format_id" onchange="getFormFields(this.value);">';
        $out .='<option value="0">Select</option>';
        foreach($jobTemplates as $jobT) { 
            $class = '';
            if($selected !='' and $selected == $jobT->id) 
                $class = 'selected="selected"';
            $out .='<option value="'.$jobT->id.'" '.$class.'>'.$jobT->name.' for $'.($jobT->pricing->base/100).'</option>';
        }
        $out .='</select>';
        return $out;
    }
}
function getListIndustryIds($selected ='')
{
    $industuries = curlRequest('industries/');
    if($industuries) {        
        $out .= '<select name="industry_ids">';
        $out .='<option value="">Select one at a time</option>';
        foreach($industuries as $indust) {

            $class = '';
            if($selected !='' and $selected == $indust->id) 
                $class = 'selected="selected"';

            $out .='<option value="'.$indust->id.'" '.$class.'>'.$indust->name.'</option>';
        }
        $out .='</select>';
        return $out;
    }
}
function getListGuidelineIds($selected ='')
{
    $guideLines = curlRequest('guidelines/');
    if($guideLines) {

        $out .= '<select name="guideline_ids">';
        $out .='<option value="">Select one at a time</option>';
        foreach($guideLines as $guide) {
            $class = '';
            if($selected !='' and $selected == $guide->id) 
                $class = 'selected="selected"';

            $out .='<option value="'.$guide->id.'" '.$class.'>'.$guide->name.'</option>';
        }
        $out .='</select>';
        return $out;
    }
}
function delivery($selected ='')
{
    $standard = ($selected != '' and $selected=='standard')?'selected="selected"':'';
    $rush = ($selected != '' and $selected=='rush')?'selected="selected"':'';
    
    $out ='<select name="delivery" id="delivery" class="span3">
        <option value="standard" '.$standard.'>Delivered in 5 business days</option>
            <option value="rush" '.$rush.'>Delivered in 3 business days (+$10)</option>
            </select>';
    
    return $out;
}
function validateCreateProject($posted) {
    $error = '';
    if(isset($posted['topic']) and $posted['topic'] =='') {
        $error .= '<p>Topic field can not be empty.</p>';
    }
    if(isset($posted['quantity_order']) and $posted['quantity_order'] =='') {
        $error .= '<p>Quantity field can not be empty.</p>';
    } else {
        $format_id       = sanitize_text_field($_POST['format_id']);
        $quantity_option = array();
        if($format_id !='') {
            $dataFields = curlRequest('job_templates/'.$format_id);
            $quantity_option = $dataFields->content_format->quantity_options;
        }        
        if(!in_array($posted['quantity_order'], $dataFields->content_format->quantity_options)) {
            $error .= '<p>Quantity field is not correct.</p>';
        }
    }
  
    return $error;
}
function getFormFields()
{
    ?>
    <script>
        function getFormFields(id) {
                    jQuery.ajax({
                            type: 'POST',
                            url: '<?php echo wp_nonce_url( admin_url('admin-ajax.php'), 'formfields_project' );?>',
                            data: 'form_id='+id+'&action=get_form_fields',
                            success: function(data) {
                                jQuery('#formfieldsplace').html(data);

                        }
                        });
       }
    </script>
        <?php
}
add_action('wp_ajax_get_form_fields', 'getFormFieldsCallback');
function getFormFieldsCallback($postformField = '')
{
    $formField =  $_POST['form_id'];
    if($postformField !='')
        $formField =  $postformField;
    
    $out = '';
    if((isset($_POST) && wp_verify_nonce($_GET['_wpnonce'],'formfields_project') and $formField !='0') or $postformField!='') {
        $dataFields = curlRequest('job_templates/'.$formField); 
        
        if($dataFields) {           
            $out .= '<ul>';
            
            $out .='<li><label class="left_label">Quantity</label><select name="quantity_order" class="span3">';
            foreach($dataFields->content_format->quantity_options as $key => $value) {
                    $out .='<option value="'.$value.'">'.$value.'</option>';
                }
            $out .='</select></li>';
            //$out .='<li><label style="width:220px; float:left;">Quantity</label><input style="width:50px;" class="regular-text" type="text" name="quantity_order" value="'.((isset($_POST['quantity_order']) and $_POST['quantity_order'] !='') ? $_POST['quantity_order'] : $dataFields->content_format->min_quantity).'" /><p style="margin-left:220px; font-size:10px;">Minimum Quantity: '.$dataFields->content_format->min_quantity.'</p></li>';
            
            $fields = $dataFields->prompts;
            foreach($fields as $field) {
                
                    $required = (isset($field->answer_required) and $field->answer_required == 1) ? '*':'';
                
                    if($field->kind == 'checkbox') {    
                        $oldValue = (isset($_POST['form_fields'][$field->id])) ? $_POST['form_fields'][$field->id] : array();   
                        $out .='<li><label class="left_label">'.$field->label.$required.'</label><div style="float:left">';
                        foreach ($field->value_options as $optionValue) {
                                $class = '';
                                if(in_array($optionValue, $oldValue))
                                    $class = 'checked';
                            $out .='<label><input '.$class.' name="form_fields['.$field->id.'][]" type="checkbox" value="'.$optionValue.'">'.$optionValue.'</label><br />';
                        }
                        
                        $out .='</div><div style="clear:both"></div><p class="help_text">'.$field->description.'</p></li>';
                        
                    } else if($field->kind == 'radio') {
                        $oldValue = (isset($_POST['form_fields'][$field->id])) ? $_POST['form_fields'][$field->id] : '';   
                        $out .='<li><label class="left_label">'.$field->label.$required.'</label><div style="float:left">';
                        foreach ($field->value_options as $optionValue) {
                            $class = '';
                                if($optionValue == $oldValue)
                                    $class = 'checked';
                            $out .='<label><input '.$class.' name="form_fields['.$field->id.']" type="radio" value="'.$optionValue.'">'.$optionValue.'</label><br />';
                        }
                        
                        $out .='</div><div style="clear:both"></div><p class="help_text">'.$field->description.'</p></li>';
                        
                    } else if(strpos($field->kind, 'string[255]') !== false) {
                        $out .='<li><label class="left_label">'.$field->label.$required.'</label>'
                           . '<input name="form_fields['.$field->id.']" type="text" class="span3" value="'.@$_POST['form_fields'][$field->id].'">'
                           . '<p class="help_text">'.$field->description.'</p></li>';
                        
                    } else if(strpos($field->kind, 'string[1024]') !== false) {
                        $out .='<li><label class="left_label">'.$field->label.$required.'</label>'
                           . '<textarea name="form_fields['.$field->id.']" cols="48" rows="5" class="span3">'.@$_POST['form_fields'][$field->id].'</textarea>'
                           . '<p class="help_text">'.$field->description.'</p></li>';             
                    } else if(strpos($field->kind, 'array') !== false) {
                        $out .='<li><label class="left_label">'.$field->label.$required.'</label>'
                           . '<textarea name="form_fields['.$field->id.']" cols="48" rows="5" class="span3">'.@$_POST['form_fields'][$field->id].'</textarea>'
                           . '<p class="help_text">'.$field->description.'</p></li>';             
                    }
                    
                       
            }
            
            $out .= '<li>';
            
        }
        
    }
    if($postformField !='')
        return $out;
    else
        echo $out;
    die();
}


function curlRequest($type,$post = false,$fields = '') {
    
    $ID               = get_option( '_scripted_ID' );
    $accessToken      = get_option( '_scripted_auccess_tokent' );
    
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('authorization: Token token='.$accessToken));    
    curl_setopt($ch, CURLOPT_HEADER, 1);    
    curl_setopt($ch, CURLOPT_URL, SCRIPTED_END_POINT.'/'.$ID.'/v1/'.$type);     
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    if($post) {
         curl_setopt($ch,CURLOPT_POST,1);
            curl_setopt($ch,CURLOPT_POSTFIELDS,$fields);
    } else {
        curl_setopt($ch, CURLOPT_POST, 0);
    }
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    $result = curl_exec($ch);   
    curl_close($ch);
        
    if ($result === false) {        
        return false;
    }
    
    list( $header, $contents ) = preg_split( '/([\r\n][\r\n])\\1/', $result, 2 ); // extracting
    if($contents != '') {
        $contents = json_decode($contents);    
        if(isset($contents->data) and count($contents->data) > 0) {
            if(isset($contents->total_count))
                return $contents;
            return $contents->data;
        }
    }
    
    return false;
}