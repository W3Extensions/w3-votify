<?php
function w3vx_update_vote($id, $vote, $type = "post") {
	global $wpdb, $current_user;
	
	$userID = intval($current_user->ID);
	
	// no $id provided, end function.
	if(!isset($id) || empty($id) || $id == 0) return;	
	
	//if user submitted post, end function.
	if($type == "post" && intval(get_post($id)->post_author) == $userID) return;

	//if user submitted comment, end function.
	if($type == "comment" && get_comment($id, OBJECT)->user_id == $userID) return;
	
	
	$previousVote = w3vx_get_user_vote($id, $userID, $type);
	$vote = in_array($vote, array("up", "down")) ? $vote : null; // we don't want to blindly trust the input.

	
	// upvotes
	if($type == "post"){
		$upvotes = get_post_meta($id, 'w3vx_upvotes', true );
		$count =  get_post_meta($id, 'w3vx_count', true );		
	} else if ($type == "comment"){
		$upvotes = get_comment_meta($id, 'w3vx_upvotes', true );		
		$count = get_comment($id)->comment_karma;
	}
	
	$upvotes = intval($upvotes); //force null value to become 0 
	$count = intval($count); //force null value to become 0 
	
	// if the $vote is null that means we need to undo an existing vote
	// if the $vote equals current value, we need to undo the vote
	// for example, undoing an upvote means subtracting it from the counter and (trend value) which means it's effectively a downvote from the value they previously increased by voting. 
	if(empty($vote) || ($vote == $previousVote && !empty($previousVote))){ // nullify vote
		if($previousVote == "up"){
			$upvotes =  $upvotes - 1;
			$count =  $count - 1;		

			w3px_add_user_points($userID, 0);
		} elseif($previousVote == "down"){
			$upvotes =  $upvotes + 1;
			$count =  $count + 1;	
			
			w3px_add_user_points($userID, 0);				
		}
	} else { 
		if(empty($previousVote)){
			if($vote == "up"){
				$upvotes =  $upvotes + 1;
				$count =  $count + 1;
				w3px_add_user_points($userID, 1);				
			} elseif($vote == "down") {
				$upvotes =  $upvotes -  1;
				$count =  $count - 1;

				w3px_add_user_points($userID, -1);				
			}
		} elseif ($vote !== $previousVote) {// remember: if vote is not empty and it doesn't match the prev vote, we must be reversing a vote. when reversing a vote we need to move the counter 2 points in either direction since neutral would move the counter 1 point.
			if($vote == "up"){
				$upvotes =  $upvotes + 2;
				$count =  $count + 2;
				
				w3px_add_user_points($userID, 1);				
			} elseif($vote == "down") {
				$upvotes =  $upvotes -  2;
				$count =  $count - 2;
				
				w3px_add_user_points($userID, -1);
			}
		}
	}

	$rating = intval(round(($upvotes / $count) * 100));

	if($type == "post"){
		update_post_meta( $id, 'w3vx_count', $count );
		update_post_meta( $id, 'w3vx_upvotes', $upvotes );
		update_post_meta( $id, 'w3vx_rating', $rating );
		
	} else {

		wp_update_comment(array("comment_ID" => $id, "comment_karma" => $count));	

		update_comment_meta( $id, 'w3vx_upvotes', $upvotes );
		update_comment_meta( $id, 'w3vx_rating', $rating );
	}

	w3vx_rankify($id, $type);
	
	
	# DATABASE UPDATE
	
	$table_name = $wpdb->prefix . "w3vx_user_votes";

	$values = array(
		"pid" => $id,
		"userid" => $userID,
		"vote" => $vote,
		"type" => $type,
		"timestamp" => time(),
	);

	if(empty($vote) || ($vote == $previousVote)){
		$wpdb->delete($table_name, array( 'pid' => $id, 'userid' => $userID, "type" => $type ), array( '%d', '%d', '%s' ));
	} else if(!empty($vote) && empty($previousVote)){
		$wpdb->insert($table_name, $values, array("%d", "%d", "%s", "%s"));	
	} else {
		// this should be an update query, but I can't get the damn thing to work.
		$wpdb->delete($table_name, array( 'pid' => $id, 'userid' => $userID, 'type' => $type ), array( '%d', '%d', '%s' ) );
		$wpdb->insert($table_name, $values, array("%d", "%d", "%s", "%s"));	
	}
	
	$result = array();
	$result["pid"] = $id;// JS will need the $id to update counter DOM.
	
	// we need to get the number since it may have been updated since we cast our vote.
	if($type == "post"){
		$result["counter"] = get_post_meta($id, 'w3vx_count', true );
	} elseif($type == "comment"){
		$result["counter"] = get_comment($id)->comment_karma;
	}
	
	return $result;
}


// Generate rank based on w3vx_count and w3vx_upvotes value
// Reddit inspired ranking algorithm

function w3vx_rankify($id, $type = "post") {
	if($type == "post"){
		$x = get_post_meta($id, 'w3vx_count', true );
	} else if ($type == "comment"){
		$x = get_comment($id)->comment_karma;
	}
	
	if($x == "") {
		$x = 0;
	}
	
	$ts = get_the_time("U",$id);
	
	if($x > 0){
		$y = 1;
	} elseif($x<0) {
		$y = -1;
	} else {
		$y = 0;
	}
	
	$absx = abs($x);
	if($absx >= 1) {
		$z = $absx;
	} else {
		$z = 1;
	}
	
	
	$rating = log10($z) + (($y * $ts)/45000);
	
	if($type == "post"){
		update_post_meta($id,'w3vx_rank',$rating);
	} else if ($type == "comment"){
		update_comment_meta($id,'w3vx_rank',$rating);	
	}
	
	return $rating;
	
}

# VOTE BUTTONS
function w3vx_get_vote_buttons($id, $type = "post"){
	$userID = intval(get_current_user_id());

	//$id = intval($id);

	// no $id provided, end function.
	if(!isset($id) || empty($id) || $id == 0) return;	
	

	$html = ""; //default pid entered
	
	// no $id provided, end function.
	if(!isset($id) || empty($id) || $id == 0) return;	
	
	if ($type == "post") {
		$count =  get_post_meta($id, 'w3vx_count', true );
	} else if ($type == "comment") {
		$count =  get_comment($id)->comment_karma;
	}
	
	$count = intval($count); //intval will force count to be 0 if no value found.
	
	$vote = w3vx_get_user_vote($id, $userID, $type);

	
	//if user submitted post or comment, disbale vote functionality
	if( ($type == "post" && intval(get_post($id)->post_author) == $userID) || ($type == "comment" && get_comment($id, OBJECT)->user_id == $userID) ) {
		$disable = " disable";

		$active1 = "active";
		$active0 = null;		
	} else {
		$disable = null;

		$active1 = ($vote == "up") ? " active" : null;
		$active0 = ($vote == "down") ? " active" : null;
	}


	if($type == "post"){
		$html = 
			'<div class="vote-button vote-up '.$disable.' '.$active1.'" data-vote="up" data-pid="'.$id.'"  data-type="'.$type.'"></div>
			<div class="vote-count">'.$count.'</div>			
			<div class="vote-button vote-down '.$disable.' '.$active0.'" data-vote="down" data-pid="'.$id.'"  data-type="'.$type.'"></div>
			
			';
	} elseif($type == "comment"){
		$html = 
			'<span class="vote-count">'.$count.'</span>
			<span class="vote-button vote-down '.$active0.'" data-vote="down" data-pid="'.$id.'"  data-type="'.$type.'">-</span> 
			<span class="vote-button vote-up '.$active1.'" data-vote="up" data-pid="'.$id.'"  data-type="'.$type.'">+</span>';
	}
	

	return $html;
}


# VOTE BUTTONS WRAPPER (FOR AJAX)
function w3vx_vote_buttons_wrapper($id, $type = "post"){
	if($type == "post"){
		$html = '<div class="btn-group vote-buttons-wrapper '.$type.'-vote" role="group" aria-label="Vote Buttons" id="voteButton-'.$id.'" data-id="'.$id.'" data-type="'.$type.'"></div>';
	} elseif($type == "comment") {
		$html = '<div class="vote-buttons-wrapper '.$type.'-vote" aria-label="Vote Buttons" id="voteButton-'.$id.'" data-id="'.$id.'" data-type="'.$type.'"></div>';
	}
	
	return $html;
}

# GET VOTE BUTTONS

function w3vx_get_vote_buttons_html(){
	if($_POST["type"] == null || !isset($_POST["ids"])){
		exit();
	}

	$ids = array_map("intval", json_decode($_POST["ids"], true));
	$type = in_array($_POST["type"], array("post", "comment")) ? $_POST["type"]: null;
	
	
	$buttons = array();
	
	foreach($ids as $id){
		$buttons[$id]["type"] = $type;
		$buttons[$id]["html"] = w3vx_get_vote_buttons($id, $type);
	}
	
	echo json_encode($buttons);
	exit();
}

add_action( 'wp_ajax_nopriv_w3vx_get_vote_buttons_html', 'w3vx_get_vote_buttons_html' );
add_action( 'wp_ajax_w3vx_get_vote_buttons_html', 'w3vx_get_vote_buttons_html' );



# AJAX VOTE HANDLER

function w3vx_voting_handler(){
	$id = intval($_POST["pid"]);
	$type = sanitize_text_field($_POST["type"]);
	
	$vote = in_array($_POST["vote"], array("up", "down")) ? $_POST["vote"] : null;
		
	$result = w3vx_update_vote($id, $vote, $type);
	
	echo json_encode($result);
	exit;
}
add_action( 'wp_ajax_w3vx_voting_handler', 'w3vx_voting_handler' );


# GET USER VOTES RECORD
function w3vx_get_user_vote($id, $userID, $type = "post") {
	global $wpdb;
	
	$table_name = $wpdb->prefix . "w3vx_user_votes";
	$row = $wpdb->get_results("SELECT vote FROM $table_name WHERE pid = '$id' AND userid = '$userID' AND type = '$type'");
	if(count($row) > 0 ){
		$result = $row[0]->vote;
	} else {
		$result = null;
	}

	return $result;
}

# CREATE USER VOTES TABLE

function w3vx_create_user_votes_table(){
	global $wpdb;
	
	$table_name = $wpdb->prefix . "w3vx_user_votes";
	
	$charset_collate = $wpdb->get_charset_collate();

	// TROUBLESHOOTING: Don't include trailing comma for array of columns.
	$sql = "
      CREATE TABLE IF NOT EXISTS $table_name (
	  userid int(9) NOT NULL,
	  pid int(9) NOT NULL,
	  vote tinytext NOT NULL,
	  type varchar(9) NOT NULL,	  
	  timestamp int(12) NOT NULL	  
	) $charset_collate;";
	
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql ); 	
}