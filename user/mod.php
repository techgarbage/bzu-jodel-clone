<?php
	session_start();
	//Include functions & meta data
	require '../functions/apicalls.php';
	require '../functions/jodelmeta.php';
	$config = require('../config.php');
	$title = "Moderation | SocialDomayn";
	$stylesheet = "jodel.css";
	include '../functions/header.php';

	//check if user is logged in & has required caps
	if(!isset($_SESSION['userid']) || !isset($_SESSION['caps_add_color'])) {
		header('Location: ' . $config->baseUrl . '/login.php');
	}

	//set up working variables
	$userid = $_SESSION['userid'];
	$apiroot = $config->apiUrl;

?>
<div id="top"></div>
<!-- main menu -->
<ul class="nav justify-content-center">
	<li class="nav-item">
		<a class="nav-link" href="../user.php"><i class="fa fa-chevron-left" aria-hidden="true"></i></a>
	</li>
	<li class="nav-item">
    <a class="nav-link" href="javascript:window.location.reload();"><i class="fa fa-refresh" aria-hidden="true"></i></a>
  </li>
  <li class="nav-item">
    <a class="nav-link" href="../jodels.php"><i class="fa fa-comments-o" aria-hidden="true"></i></a>
  </li>
  
</ul>
<!-- end main menu -->
<div class="test"></div>
<?php
	if(isset($_SESSION['errorMsg'])) {
		//show error msg
 		?>
		<div class="alert alert-danger" role="alert">
  		<strong>Holy guacamole!</strong> <?php echo $_SESSION['errorMsg'];?>
		</div>
		<?php
	}
	?>
	<div class="container">
		<h1>
			<?php echo "Hello " . $_SESSION['username'];?>
		</h1>
	</div>
	<?php
		//get JSON of all colors, save it in PHP array
		$reporturl = $apiroot . "reports?transform=1";
		$reportjson = getCall($reporturl);
		$reports = json_decode($reportjson, true);

		foreach($reports['reports'] as $report){
			if($report['jodelDFK'] != null){
				$type = "post";
				$callurl = $apiroot . "jodeldata?transform=1&filter=jodelID,eq," . $report['jodelDFK'];
			} elseif($report['commentIDFK'] != null){	
				$type = "comment";	
				$callurl = $apiroot . "comments?transform=1&filter=commentID,eq," . $report['commentIDFK'];	
			}
			
			$contentjson = getCall($callurl);
			$contentarray = json_decode($contentjson, true);

			if($type == "post"){

				foreach($contentarray['jodeldata'] as $post){
				?>
				<div class="card card-inverse mb-3 text-center" id="<?php echo $post['jodelID'];?>" style="background-color: #<?php echo $post['colorhex'];?>;">
  				<div class="card-block">
    				<blockquote class="card-blockquote">
					<?php
							//post isn't downvoted
		 						echo $post['jodel'];?>
		 						<!-- voting and number of votes -->
								<div class="jodelvotes">
									<a href="#"<i class="fa fa-angle-up" aria-hidden="true"></i></a><br>
									<?php echo $post['votes_cnt'] . "<br>";?>
									<a href="#"<i class="fa fa-angle-down" aria-hidden="true"></i></a>
								</div>
								<div class="clear"></div>
								<!-- end voting and number of votes -->
								<!-- post metadata -->
								<div class="jodelmeta">
									<?php
										$timeago = jodelage($post['createdate']);
									?>
									<?php echo " ";?><i class="fa fa-clock-o" aria-hidden="true"></i><span id="<?php echo 'time-' . $post['jodelID'];?>"><?php echo $timeago;?></span>
									<?php echo " " ;?><a href="comments.php?showcomment=<?php echo $post['jodelID'];?>"><i class="fa fa-comment" aria-hidden="true"></i><?php echo $post['comments_cnt'];?></a>
									<?php if ($post['account_state'] == 4){echo '<i class="adminmark fa fa-check-square" aria-hidden="true"></i>';}?>
								<!-- end post metadata -->
					</blockquote>
  				</div> <!-- end post card somewhere here -->
			</div>
			<?php
			}}


		}
	?>

	