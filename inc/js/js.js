// info

$(function(){
	info.go();
});

var info = function(){

var r = {};

r.infopath = $("html").attr("id");

r.syncpath = r.infopath+"sync.php";
r.synclockpath = r.infopath+".sync.lock";

// =================================================================
//
r.go = function(){
	console.log("info");

	if($("body").hasClass("remote")){
		info.check();
	}

	r.hoverlinks();
	r.linktypes();
	r.interhr();
	//r.captions();
};


// =================================================================
//
r.captions = function(){

this.imgs = $("img");
	for (var i = 0; i < this.imgs.length; i++) {
		$(this.imgs[i]).addClass("caption");
		alt = $(this.imgs[i]).attr("alt");
		console.log(alt);
		$(this.imgs[i]).after("<p class=\"caption\">"+alt+"</p>");
	};
}

// =================================================================
//
r.interhr = function(){
	n = 1;
	if($("#q").length != 0){ n = 2; }

	this.hrs = $("hr");
	if(this.hrs.length > 2){
	
		for (var i = n; i < (this.hrs.length)-1; i++) {
			$(this.hrs[i]).addClass("inter");
		};

	}
}

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
r.check = function() {

$("body").append('<p id="update"></p>');

console.log("checking...");

$("#update").html("&hellip;");

	r.check = $.get(r.synclockpath);
	
			r.check.fail(function(){
				console.log("check failed")	
			});
	
			r.check.complete(function(response){
				if(response.responseText == "locked"){
					console.log("locked!");
				} else {
					console.log("checked");
					r.update();
				}
			});
}

r.update = function(){
	r.request = $.get(r.syncpath);
			
					r.request.fail(function(){
						console.log("update failed")	
					});
			
					r.request.complete(function(response){
						
						var update = response.responseText;

						$("#update").html(update);

						if(update == "-"){
							console.log("not updating");
						} else if(update == "=") {
							console.log("no update needed");	
						}
						
						if(update == "*"){
							console.log("updated");	
							
							//r.reload(window.location.href);
							window.location.reload();
						}
						

					});
}


r.reload = function(page){
	console.log("reload "+page);

	r.page = $.get(page+".json");
	
			r.page.fail(function(){
				console.log("failed")	
			});
	
			r.page.complete(function(response){

				var newpage = jQuery.parseJSON(response.responseText);

				if(newpage.path == $(location).attr("pathname")){
					$("html").html(newpage.content);
				} else {
					console.log("another page was updated...");
				}

			});
}

// =================================================================
return r;

}();
