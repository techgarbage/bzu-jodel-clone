<?php
	session_start();
	//Set default values for head & load it
	$title = "Create Post | SocialDomayn";
	$stylesheet = "jodel.css";
	include 'functions/header.php';
	//Load all requred functions & config
	require 'functions/apicalls.php';
	$config = require('config.php');
	require 'functions/jodelmeta.php';
	require 'functions/class.upload.php';
	$apiroot = $config->apiUrl;
	$uploaddir = $config->image_upload_dir;
	//get session info & post to show comments from
	$userid = $_SESSION['userid'];
	$post = $_GET['comment'];
	//get color of post
	$colorOfPost = getColorOfPost($post);
	$colid = $colorOfPost->colid;
	$colorname = $colorOfPost->name;
	$colorhex = $colorOfPost->hex;

	//user is not logged in
	if(!isset($_SESSION['userid'])) {
		die('You need to <a href="login.php">login</a> first');
	}
	//no comment is selected
	if(!isset($_GET['comment'])){
		die('You need to select a post');
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

	//user wants to post a comment
	if(isset($_GET['post'])){
				//get ID of post to post comment to
		$jodel = $_GET['comment'];
		//encode special chars to avoid injection
		$comment = htmlspecialchars($_POST['comment'], ENT_QUOTES);
		$comment = trim(preg_replace('/\s\s+/', ' ', $comment));
		//set color as local value
		$color = $_POST['color'];
		$colorhex = $_POST['colhex'];
		


		if(isset($_FILES["imageFile"]) && $_FILES['imageFile']['name'] != ""){
			$epoch = time();
			$filename = $epoch . "-" . $_FILES['imageFile']['name'];
			$withoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $filename);
			//encode is done by upload class
			$comment = $_POST['comment'];
			$handle = new upload($_FILES['imageFile']);
			if ($handle->uploaded) {
				$handle->file_new_name_body   = $withoutExt;
  				$handle->image_resize         = true;
				$handle->image_y              = 300;
				$handle->file_safe_name = true;
				$handle->allowed = array('image/*');
				$handle->image_text = $comment;
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
			$callurl = $apiroot . "images";
			$postfields = "{\n \"path\": \"$filename\" \n}";
			$imageID = postCall($callurl, $postfields);
		
		} 

		//get data from original post
		$callurl = $apiroot . "jodels?transform=1&filter=jodelID,eq," . $jodel;
		$orgpostjson = getCall($callurl);
		$orgpost = json_decode($orgpostjson, true);
		foreach($orgpost['jodels'] as $theop){
			//get number of comments of original post
			$comments_cnt = $theop['comments_cnt'];
			$author = $theop['jodlerIDFK'];
			$score = $theop['score'];
		}
		//incerase number of comments of OP
		$comments_cnt++;
		$score = $score + $config->postmeta['get_comment'];
		//insert new comment in DB, $postfields as JSON with all data

		if($imageID !== null){
			$postfields = "{\n\t\"jodlerIDFK\": \"$userid\",\n\t\"colorIDFK\": \"$color\",\n\t\"jodelIDFK\": \"$jodel\",\n\t\"imageIDFK\": \"$imageID\",\n\t\"comment\": \"$comment\"\n\n}";
		} else {
			$postfields = "{\n\t\"jodlerIDFK\": \"$userid\",\n\t\"colorIDFK\": \"$color\",\n\t\"jodelIDFK\": \"$jodel\",\n\t\"comment\": \"$comment\"\n\n}";
		}
		$callurl = $apiroot . "comments";
		$posted = postCall($callurl, $postfields);

		//update comment count of OP in DB
		$callurl = $apiroot . "jodels/" . $jodel;
		$postfields = "{\n\t\"comments_cnt\": \"$comments_cnt\",\n\t\"score\": \"$score\"\n\n}";
		$cmntupdated = putCall($callurl, $postfields);

		//update the authors karma for creating a comment
		$karma = $karma + $config->karma_calc['post_comment'];
		$postfields = "{\n  \n  \"karma\": \"$karma\"\n\n}";
		$callurl = $apiroot . "jodlers/" . $userid;
		$karmaupdated = putCall($callurl, $postfields);

		//get info about the author of the OP
		$callurl = $apiroot . "jodlers?transform=1&filter=jodlerID,eq," . $author;
		$authorjson = getCall($callurl);
		$authorarray = json_decode($authorjson, true);
		foreach($authorarray['jodlers'] as $user){
			$karmaOfUser = $user['karma'];
		}
		//incerase karma of author
		$karmaOfUser = $karmaOfUser + $config->karma_calc['get_comment'];
		$postfields = "{\n  \n  \"karma\": \"$karmaOfUser\"\n\n}";
		$callurl =  $apiroot . "jodlers/" . $author;
		$authorkarmaupdated = putCall($callurl, $postfields);
		
		
		//redirect to post overview
		header('Location: ' . $config->baseUrl . 'comments.php?showcomment=' . $jodel . '#' . $posted);
	}
	

?>

<div id="top"></div>

<ul class="nav justify-content-center">
	<li class="nav-item">
		<a class="nav-link" href="comments.php?showcomment=<?php echo $post;?>"><i class="fa fa-chevron-left" aria-hidden="true"></i></a>
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

<form action="?post=1&comment=<?php echo $post;?>" method="POST" enctype="multipart/form-data">
	<div class="form-group">
		<label for="comment">Enter your message</label>
		<textarea class="form-control" rows="10" name="comment" placeholder="Your post" style="color:white;background-color:#<?php echo $colorhex;?>"></textarea>
	</div>
	<!-- save the color in a hidden field -->
	<input type="hidden" name="color" value="<?php echo $colid;?>">
	<input type="hidden" name="colhex" value="<?php echo $colorhex;?>">
		<input type="file" name="imageFile" id="imageFile">
	<button type="submit" class="btn btn-warning">Submit</button>
</form>
<!-- end post form -->
<?php
include 'functions/footer.php';