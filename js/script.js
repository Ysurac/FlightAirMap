/** global: navigator */

$( document ).ready(function() {
/*	
	//gets the current date information to be used in the datepicker
	var date = new Date();
	var currentMonth = date.getMonth();
	var currentDate = date.getDate();
	var currentYear = date.getFullYear();

  $( "#start_date" ).datetimepicker({ dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true, minDate: new Date(2014, 04 - 1, 12), maxDate: new Date(currentYear, currentMonth, currentDate, 23, 59) });
  $( "#end_date" ).datetimepicker({ dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true, minDate: new Date(2014, 04 - 1, 12), maxDate: new Date(currentYear, currentMonth, currentDate, 23, 59) });
  $( "#date" ).datepicker({ dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true, minDate: new Date(2014, 04 - 1, 12), maxDate: new Date(currentYear, currentMonth, currentDate) });
*/  
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
function language(selectObj) {
    var idx = selectObj.selectedIndex;
    var lang = selectObj.options[idx].value;
    document.cookie =  'language='+lang+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
    window.location.reload();
}
function populate(obj,str,selected) {
	//console.log('populate');
	$.ajax({
		url:'search-ajax.php',
		type:'GET',
		data: 'ask=' + str,
		dataType: 'json',
		success: function( json ) {
			var options = "";
			$.each(json, function(i, item){
				if ($.inArray(item.id,selected) != -1) {
					options += "<option value="+item.id+" selected>"+item.value+"</option>";
				} else {
					options += "<option value="+item.id+">"+item.value+"</option>";
				}
			});
			obj.append(options);
		}
	});
}
function statsdatechange(e) {
	var form = document.getElementById('changedate');
	var yearmonth = form.date.value.split("-");
	var pagename = location.pathname;
	pagename = pagename.split('/');
	var i = 0;
	var page = '';
	for (i = 0; i < pagename.length; i++) {
		if (pagename[i] != '') {
			if (isNaN(pagename[i])) page = page +'/'+ pagename[i];
		}
	}
	if (typeof yearmonth[1] != 'undefined') {
		form.action = page+'/'+yearmonth[0]+'/'+yearmonth[1];
	} else {
		form.action = page;
	}
	form.submit();
}
function statsairlinechange(e) {
	var form = document.getElementById('changeairline');
	var airline = form.airline.value;
	var pagename = location.pathname;
	pagename = pagename.split('/');
	var i = 0;
	var page = '';
	var add = false;
	for (i = 0; i < pagename.length; i++) {
		if (pagename[i] != '') {
			if (pagename[i].length != 3 && pagename[i].substr(0,9) != 'alliance_') page = page+'/'+pagename[i];
			else {
				add = true;
				if (airline != 'all') page = page+'/'+airline;
			}
		}
	}
	if (add === false) page = page+'/'+airline;
	form.action = page;
	form.submit();
}
