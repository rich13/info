// info

$(function(){
	info.go();
});

var info = function(){

var r = {};

// =================================================================
//
r.go = function(){
	console.log("info");

	if($("body").hasClass("remote")){
		info.update();
	}

	r.hoverlinks();
	r.linktypes();
};


// =================================================================
//
r.hoverlinks = function(){
	this.links = $("a");

	for (var i = 0; i < this.links.length; i++) {
		if(this.links[i].title){

			$(this.links[i]).addClass("t");
			$(this.links[i]).append("<span>"+this.links[i].title+"</span>");				
			this.links[i].title = "";
			
			$(this.links[i]).hover(
				function () {
					$(this).addClass("hover");
					},
					function () {
					$(this).removeClass("hover");
					}
			);
		};
	};	
}


// =================================================================
//
r.linktypes = function(){
	// http://stackoverflow.com/questions/2910946/test-if-links-are-external-with-jquery-javascript
 	
 	hostname = new RegExp(location.host);
    //console.log(hostname);
	
	$('a').each(function(){

        var url = $(this).attr("href");

        //console.log(url);

	 	// Test if current host (domain) is in it
        if( hostname.test(url) ||
        	url.slice(0, 1) == "/" ||
        	url.slice(0, 4) != "http"
        	){
        
           // If it's local...
           $(this).addClass('local');

        } else if(url.slice(0, 1) == "#"){
        
            // It's an anchor link
            $(this).addClass('anchor'); 
        
        } else {
        
           // a link that does not contain the current host
           $(this).addClass('external');
           $(this).append("<sup>+</sup>");                       
        
        }
	});
}


// =================================================================
//
r.update = function() {

$("body").append('<p id="update"></p>');

console.log("updating...");

$("#update").html("&hellip;");

	r.check = $.get(
    			".sync.lock"
    		);
	
			r.check.fail(function(){
				console.log("check failed")	
			});
	
			r.check.complete(function(response){
				
				if(response.responseText == "locked"){
				
					console.log("locked!");
				
				} else{

					//console.log("unlocked!");

					r.request = $.get(
		    			"sync.php"
		    		);
			
					r.request.fail(function(){
						console.log("update failed")	
					});
			
					r.request.complete(function(response){
						
						var update = response.responseText;

						$("#update").html(update);

						if(update == "-"){
							console.log("no update");
						} else {
							console.log("updated");	
						}
						
						if(update == "*"){
							window.location.reload();
						}
						

					});

				}
			});
}

// =================================================================
return r;

}();
