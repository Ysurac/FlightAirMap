$( document ).ready(function() {
	
	//gets the current date information to be used in the datepicker
	var date = new Date();
	var currentMonth = date.getMonth();
	var currentDate = date.getDate();
	var currentYear = date.getFullYear();

  $( "#start_date" ).datetimepicker({ dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true, minDate: new Date(2014, 04 - 1, 12), maxDate: new Date(currentYear, currentMonth, currentDate, 23, 59) });
  $( "#end_date" ).datetimepicker({ dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true, minDate: new Date(2014, 04 - 1, 12), maxDate: new Date(currentYear, currentMonth, currentDate, 23, 59) });
  $( "#date" ).datepicker({ dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true, minDate: new Date(2014, 04 - 1, 12), maxDate: new Date(currentYear, currentMonth, currentDate) });
  
  //custom select boxes
  if( /Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(navigator.userAgent) ) {
    $('.selectpicker').selectpicker('mobile');
	} else {
	  $('.selectpicker').selectpicker();
	}
  
  //search
  $("body.page-search .sub-menu input[type=text]").click(function(){
	    this.select();
	});
    
  //bootstrap popover
  $('[data-toggle="popover"]').popover({
    trigger: 'hover',
    'placement': 'bottom'
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