<?php
	session_start();
	//Set default values for head & load it
	$title = "Create Post | SocialDomayn";
	$stylesheet = "jodel.css";
	include 'functions/header.php';
	//Load API functions
	require 'functions/apicalls.php';
	require 'functions/jodelmeta.php';
	require 'functions/class.upload.php';
	require 'functions/admintools.php';
	$config = require('config.php');
	$apiroot = $config->apiUrl;
	$uploaddir = $config->image_upload_dir;
	$userid = $_SESSION['userid'];

	if(!isset($_SESSION['userid'])) {
		die('You need to <a href="login.php">login</a> first');
	}

	//Get userdata
	$callurl = $apiroot . "jodlers?transform=1&filter=jodlerID,eq," . $userid;
	$userjson = getCall($callurl);
	$user = json_decode($userjson, true);
	foreach($user['jodlers'] as $jodler){
		//get karma and account state
		$karma = $jodler['karma'];
		$accstate = $jodler['account_state'];
	}
	//set user data in session values
	$_SESSION['karma'] = $karma;
	$_SESSION['acctype'] = $accstate;

	if(isset($_GET['post'])){

		//new post created
		//encode special chars to avoid injection
		$jodel = htmlspecialchars($_POST['jodel'], ENT_QUOTES);
		$jodel = trim(preg_replace('/\s\s+/', ' ', $jodel));
		//set color as local value
		$color = $_POST['color'];
		$colorhex = $_POST['colhex'];


		if(isset($_FILES["imageFile"]) && $_FILES['imageFile']['name'] != ""){
			$epoch = time();
			$filename = $epoch . "-" . $_FILES['imageFile']['name'];
			$withoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $filename);
			//direct text input, upload functions does the rest
			$jodel = $_POST['jodel'];
			$handle = new upload($_FILES['imageFile']);
			if ($handle->uploaded) {
				$handle->file_new_name_body   = $withoutExt;
  				$handle->image_resize         = true;
				$handle->image_y              = 300;
				$handle->file_safe_name = true;
				$handle->allowed = array('image/*');
				$handle->image_text = $jodel;
				$handle->image_text_background = $colorhex;
				$handle->image_text_x = 1;
				$handle->image_text_y = rand(1, 299);
				$handle->image_ratio_x        = true;
				//$handle->file_auto_rename = true;
				$handle->process($uploaddir);
				if ($handle->processed) {
					echo 'image resized';
    				$handle->clean();
  				} else {
    				echo 'error : ' . $handle->error;
  				}
			}
			//save image location to DB

			//split filname and extension to lowercase extension
			$filearray = explode(".", $filename);
			$ext = $filearray[1];
			$extlow = strtolower($ext);
			$filename = $filearray[0] . "." . $extlow;

			$callurl = $apiroot . "images";
			$postfields = "{\n \"path\": \"$filename\" \n}";
			$imageID = postCall($callurl, $postfields);
		
		} 
		
		//insert new post in DB, $postfields as JSON with all data
		if($imageID !== null){
			$postfields = "{\n  \"jodlerIDFK\": \"$userid\",\n  \"colorIDFK\": \"$color\",\n \"imageIDFK\": \"$imageID\",\n  \"jodel\": \"$jodel\"\n}";
		} else {
		$postfields = "{\n  \"jodlerIDFK\": \"$userid\",\n  \"colorIDFK\": \"$color\",\n  \"jodel\": \"$jodel\"\n}";
		}
		$callurl = $apiroot . "jodels";
		$posted = postCall($callurl, $postfields);
		//update the authors karma for creating a post
		$karma = $karma + $config->karma_calc['create_jodel'];
		$postfields = "{\n  \n  \"karma\": $karma\n}";
		$callurl = $apiroot . "jodlers/" . $userid;
		$karmaupdated = putCall($callurl, $postfields);
		//redirect to post overview

		//scan for blacklistet words
		/*$blacklistjson = getCall($apiroot . "blacklist?transform=1");
		$blacklist = json_decode($blacklistjson, true);
		$keywords = array();
		foreach($blacklist['blacklist'] as $item){
			array_push($keywords,$item['itemName']);
		}
		foreach($keywords as $keyword){
			if(strpos($jodel, $keyword) !== false){
				$reported = reportContent( "post", $posted, $config->postmeta['system_mod_id']);
			}
		}*/

		header('Location: ' . $config->baseUrl . 'jodels.php');
	}

?>

<!-- main menu -->
<div id="top"></div>

<ul class="nav justify-content-center">
	<li class="nav-item">
		<a class="nav-link" href="jodels.php"><i class="fa fa-chevron-left" aria-hidden="true"></i></a>
	</li>
	<li class="nav-item">
    	<a class="nav-link" href="javascript:window.location.reload();"><i class="fa fa-refresh" aria-hidden="true"></i></a>
  	</li>
  	<li class="nav-item">
    	<a class="nav-link" href="user.php"><i class="fa fa-user" aria-hidden="true"></i><?php echo $_SESSION['karma'];?></a>
	</li>
</ul>
<div class="test"></div>
<!-- end main menu -->
<?php
	$colorarray = getRandomColor();
	$colorhex = $colorarray['colorhex'];
	$colornmb = $colorarray['colorID'];
?>

<!-- post form -->
<form action="?post=1" method="POST" enctype="multipart/form-data">
	<div class="form-group">
		<label for="jodel">Enter your message</label>
		<textarea class="form-control" rows="10" name="jodel" placeholder="Your post" style="color:white;background-color:#<?php echo $colorhex;?>" required="true"></textarea>
		<input type="file" name="imageFile" id="imageFile">
	</div>
	<!-- save the color in a hidden field -->
	<input type="hidden" name="color" value="<?php echo $colornmb;?>">
	<input type="hidden" name="colhex" value="<?php echo $colorhex;?>">
	<button type="submit" class="btn btn-warning">Submit</button>
</form>
<!-- end post form -->

<?php include 'functions/footer.php';

