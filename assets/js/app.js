jQuery( document ).ready(function($) {
	//generate_w3vx_vote_buttons needs to be a global variable (and "var = generate_w3vx_vote_buttons" doesn't cut it for some reason).
	//https://stackoverflow.com/a/2223341
	window.generate_w3vx_vote_buttons = function($ids, $type){
	
		// if $type is not defined, end function
		if(typeof $type == "undefined") return;
	
		
		if($ids.constructor === Array){
			//
		} else {		// if it's not an array, make it one.
			$ids = Number($ids);//convert string to integer
			$ids = [$ids];
		}
	
		$ids = JSON.stringify($ids, null, ' ');
	
		$.ajax({
			url: w3vx_ajax.ajaxurl,
			type: "POST",
			data: {
				action: 'w3vx_get_vote_buttons_html',
				ids: $ids,
				type: $type,
			},
			dataType: "json",			
			success: function ( $results ) {
				$.each($results, function($id, $value){	
					//REMINDER: the id (e.g. voteButton-1 should be something more context-specific like "commentVoteButton-1")
					// REMEMBER: We want no space between the id and the class because they belong to the same DOM element.
					$("#voteButton-" + $id + "." + $value.type + "-vote").html($value.html);
				});
			}
		});		
	}
	
	// UPDATE VOTE
	$(document).on( 'click', '.vote-button', function() {
	
		if(!$(this).hasClass("disable")) {
			type = $(this).data("type");

			if($(this).hasClass("active")) {
				vote = "";
				
				$(this).removeClass("active");
			} else {
				vote = $(this).data("vote");
				
				voteButtons = $("#voteButton-" + $(this).data("pid")).find(".vote-button");
				
				// remove other active button if present		
				voteButtons.each(function(index){
					voteButtons.eq(index).removeClass("active");
				});
			
				// make current button active
				$(this).addClass("active");
			}
					console.log(vote);
					console.log(type);
					console.log($(this).data("pid"));
			
			$.ajax({
				url: MyAjax.ajaxurl,
				type: "POST",
				data: {
					action: 'w3vx_voting_handler',
					vote: vote,
					type: type,
					pid: $(this).data("pid")
				},
				dataType: "json",			
				success: function ( result ) {
					console.log("returned");
					$("#voteButton-" + result.pid + " .vote-count").html(result.counter);
				}
			});
		}
	});
});
