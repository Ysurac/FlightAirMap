$( document ).ready(function() {
	
	//gets the current date information to be used in the datepicker
	var date = new Date();
	var currentMonth = date.getMonth();
	var currentDate = date.getDate();
	var currentYear = date.getFullYear();

  $( "#start_date" ).datetimepicker({ dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true, minDate: new Date(2014, 04 - 1, 12), maxDate: new Date(currentYear, currentMonth, currentDate) });
  $( "#end_date" ).datetimepicker({ dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true, minDate: new Date(2014, 04 - 1, 12), maxDate: new Date(currentYear, currentMonth, currentDate) });
  $( "#date" ).datepicker({ dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true, minDate: new Date(2014, 04 - 1, 12), maxDate: new Date(currentYear, currentMonth, currentDate) });
  
  //custom select boxes
  if( /Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(navigator.userAgent) ) {
    $('.selectpicker').selectpicker('mobile');
	} else {
	  $('.selectpicker').selectpicker();
	}
  
  //index slideshow
  $("#slideshow").slidesjs({
    width: 1140,
    height: 500,
    navigation: {
	      active: true,
	        // [boolean] Generates next and previous buttons.
	        // You can set to false and use your own buttons.
	        // User defined buttons must have the following:
	        // previous button: class="slidesjs-previous slidesjs-navigation"
	        // next button: class="slidesjs-next slidesjs-navigation"
	      effect: "slide"
	        // [string] Can be either "slide" or "fade".
	    },
	    pagination: {
	      active: false
	    },
	    play: {
          active: false,
          auto: false,
          effect: "slide",
          interval: 5000,
          swap: false,
          pauseOnHover: true
        }
  });
  $('#slideshow').mouseover(function() {
	  $('#slideshow .slidesjs-previous').show();
	  $('#slideshow .slidesjs-next').show();
  });
  $('#slideshow').mouseout(function() {
	  $('#slideshow .slidesjs-previous').hide();
	  $('#slideshow .slidesjs-next').hide();
  });
  
  //search
  $("body.page-search .sub-menu input[type=text]").click(function(){
	    this.select();
	});
  
  
});

function showSearchContainers(){
	$(".search-explore").hide();
	$(".search-containers").slideDown();
}

function showSubMenu(){
	$(".sub-menu-statistic").hide();
	$(".sub-menu-container").slideDown();
}