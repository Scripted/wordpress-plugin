<?php
function scripted_create_finished_jobs_callback()
{
    
    wp_enqueue_style('thickbox');
    wp_enqueue_script('thickbox');    

    $apiKey                 = get_option( '_scripted_api_key' );
    $_scripted_business_id  = get_option( '_scripted_business_id' );
    $paged                  = (isset($_GET['paged']) and $_GET['paged'] !='') ? $_GET['paged'] : 1;
    $sorting                = (isset($_GET['sort']) and $_GET['sort'] !='') ? '&sort='.$_GET['sort'] : '&sort=topic';
    $per_page               = 20;
    
    $validate = validateApiKey($apiKey,$_scripted_business_id);
    
    $out = '<div class="wrap">
            <div class="icon32" style="width:100px;padding-top:5px;" id="icon-scripted"><img src="'.SCRIPTED_LOGO.'"></div><h2>Finished Jobs <a class="add-new-h2" href="admin.php?page=scripted_create_a_job">Create a Job</a></h2>';
    
    if($validate) {
        $_finishedJobs = @file_get_contents('https://app.scripted.com/finished_jobs?key='.$apiKey.'&business_id='.$_scripted_business_id.'&page='.$paged.'&per_page='.$per_page.$sorting);            
        $_finishedJobs = json_decode($_finishedJobs);        
        
        $totalProjects  = $_finishedJobs->total;
        $totalPages     = ceil($totalProjects/$per_page);
        
        
        // paggination
         $pageOne = '';         
         if($totalPages < 2)
             $pageOne = ' one-page';     
         
         $out .= '<div class="tablenav top">';
         $out .= '<div class="alignleft actions">
                    <select name="actions" id="actions" style="width:150px;">
                      <option value="topic" '.  selected('sort', (isset($_GET['sort']) and $_GET['sort'] !='') ? $_GET['sort'] : '',false).'>Topic</option>
                      <option value="state"'.  selected('state', (isset($_GET['sort']) and $_GET['sort'] !='') ? $_GET['sort'] : '',false).'>State</option>
                    </select>
                    <input type="button" value="Apply" class="button-secondary action" id="doaction" name="doaction" onclick="doSorting()">
                  </div>';
         $out .= '<div class="tablenav-pages'.$pageOne.'">';
         
         $pagedLink = 'admin.php?page=scripted_create_finished_jobs'.$sorting;
         
                $out .='<span class="displaying-num">'.$totalProjects.' items</span>';
                $prePage = '';
                if($paged < 2) 
                    $prePage = 'disabled';
                $nextPage = '';
                if($totalPages == $paged) 
                    $nextPage = 'disabled';
                
                $preLink = 1;
                if($paged > 1) 
                    $preLink = $paged-1;
                $nextLink = $totalPages;
                if($paged < $totalPages) 
                    $nextLink = $paged+1;
                
                $out .='<span class="pagination-links"><a href="'.$pagedLink.'&paged=1" title="Go to the first page" class="first-page  '.$prePage.'">&laquo;</a>
                        <a href="'.$pagedLink.'&paged='.$preLink.'" title="Go to the previous page" class="prev-page '.$prePage.'">&lsaquo;</a> 
                            <span class="paging-input">'.$paged.' of <span class="total-pages">'.$totalPages.'</span></span>
                            <a href="'.$pagedLink.'&paged='.$nextLink.'" title="Go to the next page" class="next-page '.$nextPage.'">&rsaquo;</a>
                        <a href="'.$pagedLink.'&paged='.$totalPages.'" title="Go to the last page" class="last-page '.$nextPage.'">&raquo;</a>';
         
                   $out .='</span>
             </div>
            <br class="clear">
            </div>';
        // paggination end
        
        
        $out .='<table cellspacing="0" class="wp-list-table widefat sTable">
                    <thead>
                        <tr>
                        <th scope="col" width="60%"><span>Topic</span></th>
                        <th scope="col" width="20%"><span>State</span></th>
                        <th scope="col" width="20%"></th>
                        </tr>
                    </thead>
                      <tbody id="the-list">
                    ';
        
        if($_finishedJobs->total) {
            $finishedJobs = $_finishedJobs->jobs;
            $i = 1;
            foreach($finishedJobs as $job) {                
                $out .='<tr valign="top" class="scripted type-page status-publish hentry alternate">
                    <input type="hidden" id="project_'.$i.'" value="'.$job->id.'">
                    <td>'.$job->topic.'</td>
                    <td>'.$job->state.'</td>
                    <td>';
                    if(!in_array($job->state,ifStatusInNoFunction())) {


                        if($job->state == 'Needs Review') {
                                $out .= '<a id="accept_'.$job->id.'"  href="javascript:void(0)" onclick="finishedProjectActions(\''.$job->id.'\',\'Accept\')">Accept</a> | ';
                                $out .= '<a id="request_'.$job->id.'"  href="'.admin_url('admin-ajax.php').'?action=scripted_poject_finished&do=request_edit&project_id='.$job->id.'&secure='.wp_create_nonce('request_edit').'&amp;type=page&amp;TB_iframe=1&amp;width=600&amp;height=400" class="thickbox" title="'.strip_tags(substr($job->topic,0,50)).'">Request Edits</a> | ';
                        }elseif($job->state == 'Needs Final Review') {
                                $out .= '<a id="accept_'.$job->id.'"  href="javascript:void(0)" onclick="finishedProjectActions(\''.$job->id.'\',\'Accept\')">Accept</a> | ';
                                $out .= '<a id="reject_'.$job->id.'"  href="javascript:void(0)" onclick="finishedProjectActions(\''.$job->id.'\',\'Reject\')">Reject</a> | ';
                        }elseif(in_array($job->state, ifStatusInCreateDraftFunction()))
                                $out .= '<a id="create_'.$job->id.'" href="javascript:void(0)"  onclick="finishedProjectActions(\''.$job->id.'\',\'Create\')">Create Draft</a> | ';

                        $out .= '<a href="'.admin_url('admin-ajax.php').'?action=scripted_poject_finished&do=view_project&project_id='.$job->id.'&secure='.wp_create_nonce('view_project').'&amp;type=page&amp;TB_iframe=1&amp;width=850&amp;height=500" class="thickbox" title="'.strip_tags(substr($job->topic,0,50)).'">View</a>';
                    }
             
                   $out .='</td></tr>';
                $i++;
            }
            
        } else {
            $out .='<tr valign="top">
                    <th colspan="3" style="text-align:center;"><strong>Your Scripted account has no Finished Jobs. <a href="admin.php?page=scripted_create_a_job">Create a Job</a></strong></td>
                    </tr>';
        }
         $out .= '</tbody>
                </table>
                '; // end table
         
         // paggination
         
         $out .='<div class="tablenav bottom">
            <div class="tablenav-pages'.$pageOne.'">';
         
                $out .='<span class="displaying-num">'.$totalProjects.' items</span>';                
                
                $out .='<span class="pagination-links"><a href="'.$pagedLink.'&paged=1" title="Go to the first page" class="first-page  '.$prePage.'">&laquo;</a>
                        <a href="'.$pagedLink.'&paged='.$preLink.'" title="Go to the previous page" class="prev-page '.$prePage.'">&lsaquo;</a> 
                            <span class="paging-input">'.$paged.' of <span class="total-pages">'.$totalPages.'</span></span>
                            <a href="'.$pagedLink.'&paged='.$nextLink.'" title="Go to the next page" class="next-page '.$nextPage.'">&rsaquo;</a>
                        <a href="'.$pagedLink.'&paged='.$totalPages.'" title="Go to the last page" class="last-page '.$nextPage.'">&raquo;</a>';
         
                   $out .='</span>
             </div>
            <br class="clear">
            </div>';
        // paggination end
    }
    
    $out .='</div>';// end of wrap div
    
    echo $out;
}
function createScriptedProject($proId,$apiKey,$_scripted_business_id)
{
    global $current_user;
    $userID = $current_user->ID;
    
    $_projectContent = @file_get_contents('https://app.scripted.com/finished_jobs/show/'.$proId.'?content_format=html&business_id='.$_scripted_business_id.'&key='.$apiKey);             
    $_projectContent = json_decode($_projectContent);
    if($_projectContent->id == $proId) {
        $content = $_projectContent->content;
        if(is_array($content)) {
            $content = $content[0];
        }
        $post['post_title']     = wp_strip_all_tags($_projectContent->topic);
        $post['post_status']    = 'draft';
        $post['post_author']    = $userID;
        $post['post_type']      = 'post';
        $post['post_content']   = $content;
        $post['post_content']  .= '<p style="font-style:italic; font-size: 10px;">Powered by <a href="https://app.scripted.com" alt="Scripted.com content marketing automation">Scripted.com</a></p>';
        $post_id                = wp_insert_post($post ,true); // draft created
        echo 'Draft Created!';
        $track_url = 'http://toofr.com/api/track?url='.urlencode(get_permalink($post_id)).'&title='.urlencode($post['post_title']);
        @file_get_contents($track_url);
    } else {
        echo 'Failed';
    }
        
   
}
function createProjectAjax()
{
    ?>
    <script>
       function finishedProjectActions(proId,actions) {
           if(actions == 'Accept')
                jQuery("#accept_"+proId).html('Accepting...'); 
           else if(actions == 'Reject')
                jQuery("#reject_"+proId).html('Rejecting...'); 
           else if(actions == 'Create')
                jQuery("#create_"+proId).html('Creating...'); 
                
            jQuery.ajax({
                    type: 'POST',
                    url: '<?php echo wp_nonce_url( admin_url('admin-ajax.php'), 'create_reject_accept' );?>&do='+actions+'&project_id='+proId+'&action=scripted_poject_finished',
                    data: '',
                    success: function(data) {                            
                        if(actions == 'Accept')
                            jQuery("#accept_"+proId).html(data); 
                       else if(actions == 'Reject')
                            jQuery("#reject_"+proId).html(data); 
                       else if(actions == 'Create')
                            jQuery("#create_"+proId).html(data); 
                    }
                });
       }
       function doSorting() {
           var sortDo =  jQuery("#actions").val(); 
           if(sortDo == '')
                document.location.href='<?php echo admin_url();?>/admin.php?page=scripted_create_finished_jobs<?php echo (isset($_GET['paged']) and $_GET['paged'] !='') ? '&paged'.$_GET['paged'] : ''?>';
           else
               document.location.href='<?php echo admin_url();?>/admin.php?page=scripted_create_finished_jobs<?php echo (isset($_GET['paged']) and $_GET['paged'] !='') ? '&paged='.$_GET['paged'] : ''?>&sort='+sortDo;
       }
       function completeActionRefresh() {
           window.location.reload();
       }
    </script>
        <?php
}

function ifStatusInNoFunction() {
    return array(
        'Needs Plagiarism Check',
        'Checking for Plagiarism',
        'Plagiarism',
        'Fixing Plagiarism',
        'Needs Initial Review',
        'In Initial Review',
        'Fixing Initial Review',
        'Needs Edits',
        'Rejected'
        );
}
function ifStatusInCreateDraftFunction() {
    return array(
        'Accepted First Time',
        'Accepted Final Time',
        'Auto Accepted First Time',
        'Auto Accepted Final Time'
        );
}
//
add_action('wp_ajax_scripted_poject_finished', 'scriptedPojectFinished');
function scriptedPojectFinished() {
    $do             = (isset($_GET['do']) and $_GET['do'] !='') ? sanitize_text_field($_GET['do']) : '';
    $project_id     = (isset($_GET['project_id']) and $_GET['project_id'] !='') ? sanitize_text_field($_GET['project_id']) : '';
    
    $apiKey                 = get_option( '_scripted_api_key' );
    $_scripted_business_id  = get_option( '_scripted_business_id' );    
    $validate               = validateApiKey($apiKey,$_scripted_business_id);
    
    $scriptedBaseUrl        = 'https://app.scripted.com/';
    
    if(!$validate or $project_id == '' or $do == '') 
        die('Failed');
    
    if(wp_verify_nonce($_GET['secure'],'view_project') and $do == 'view_project') {
        $_projectContent = @file_get_contents($scriptedBaseUrl.'finished_jobs/show/'.$project_id.'?content_format=html&business_id='.$_scripted_business_id.'&key='.$apiKey);             
        $_projectContent = json_decode($_projectContent);
        
        if($_projectContent->id == $project_id) {
            $content = $_projectContent->content;
            if(is_array($content)) {
                $content = $content[0];
            }
            
            echo $content;
        }
    }elseif(wp_verify_nonce($_GET['_wpnonce'],'create_reject_accept') and $do == 'Accept') {
        $_projectAction = @file_get_contents($scriptedBaseUrl.'finished_jobs/update/'.$project_id.'?accepted=true&business_id='.$_scripted_business_id.'&key='.$apiKey);      
        if($_projectAction)
            echo 'Accepted';
        else
            echo 'Failed';
    }elseif(wp_verify_nonce($_GET['_wpnonce'],'create_reject_accept') and $do == 'Reject') {
        $_projectAction = @file_get_contents($scriptedBaseUrl.'finished_jobs/update/'.$project_id.'?rejected=true&business_id='.$_scripted_business_id.'&key='.$apiKey);      
        if($_projectAction)
            echo 'Accepted';
        else
            echo 'Failed';
    }elseif(wp_verify_nonce($_GET['_wpnonce'],'create_reject_accept') and $do == 'Create') {
        createScriptedProject($project_id,$apiKey,$_scripted_business_id);
    }elseif(wp_verify_nonce($_GET['secure'],'request_edit') and $do == 'request_edit') {
        
        if(empty($_POST))
            getFormRequestEditProject($project_id);
        else {
            $chief_complaint = $_POST['chief_complaint'];
            $url = $scriptedBaseUrl.'finished_jobs/update/'.$project_id.'?&business_id='.$_scripted_business_id.'&key='.$apiKey;
            
            if(isset($_POST['chief_complaint']) and $_POST['chief_complaint'] != '') 
                $url .='&chief_complaint='.urlencode($_POST['chief_complaint']);
            if(isset($_POST['accuracy']) and $_POST['accuracy'] != '') 
                $url .='&accuracy='.sanitize_text_field($_POST['accuracy']);
            if(isset($_POST['quality']) and $_POST['quality'] != '') 
                $url .='&quality='.sanitize_text_field($_POST['quality']);
            $_projectAction = @file_get_contents($url); 
           
            if($_projectAction)
                echo 'Accepted';
            else
                echo 'Failed';
            
            echo '<script type="text/javascript">';
            echo 'window.top.completeActionRefresh();';
            echo '</script>';
        }
    }
    die();
}
function getFormRequestEditProject() {
    
    $out ='<form action="" method="post" name="frmEditRequests" id="frmEditRequests" onsubmit="return sendEditRequest();">'.wp_nonce_field( 'edit_requests', '_wpnonce' );
    $out .= '<label for="chief_complaint">Chief Complaint</label></br></br>';
    $out .= '<textarea id="chief_complaint" name="chief_complaint" style="width:400px; height:200px;"></textarea></br>';
    $out .= '<label for="accuracy">Accuracy</label></br>';
    $out .= '<select name="accuracy" id="accuracy" style="width:400px;">';
        $out .= '<option value="">~ Select ~</option>';
        $out .= '<option value="5">Yup! This writer nailed it!</option>';
        $out .= '<option value="4">Yes, with a couple minor exceptions.</option>';
        $out .= '<option value="3">It\'s close, but there are a few sections that don\'t quite fit.</option>';
        $out .= '<option value="2">Somewhat, but it\'s not the direction I wanted.</option>';
        $out .= '<option value="1">No! I see no clear correlation to the assignment.</option>';
    $out .= '</select></br>';
    $out .= '<label for="quality">Quality</label></br>';
    $out .= '<select name="quality" id="quality" style="width:400px;">';
        $out .= '<option value="">~ Select ~</option>';
        $out .= '<option value="5">Wow! Billy Shakespeare, is that you!?</option>';
        $out .= '<option value="4">Yes, with a couple minor exceptions.</option>';
        $out .= '<option value="3">It\'s okay. There are a few sections I\'d like to change, though.</option>';
        $out .= '<option value="2">No. It is rife with grammatical errors.</option>';
        $out .= '<option value="1">No! It is totally unintelligible.</option>';
    $out .= '</select></br></br>';
    $out .='<input type="submit" value="Request Edits" class="button-primary" name="submit">';
    $out .='</form>';
    $out .='<script>';
    $out .='function sendEditRequest() {
                var chief_complaint = document.getElementById("chief_complaint").value;
                if(chief_complaint == "") {
                    document.getElementById("chief_complaint").style.border="1px solid red";
                    return false;
                }                
                return true;
            }
        ';
    $out .='</script>';
    echo $out;
}