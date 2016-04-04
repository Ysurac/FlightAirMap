function getSelect(type,selected) {
$.getJSON("search-ajax.php?ask="+type, function(data) {
    $("#"+type+" option").remove();
    var all_data = '<option></option>';
    $.each(data, function(){
	if (this.id == selected && selected != '') {
		all_data += '<option value="'+ this.id +'" selected>'+ this.value +'</option>';
	} else {
		all_data += '<option value="'+ this.id +'">'+ this.value +'</option>';
        }
    });
    $("#"+type).append(all_data).selectpicker('refresh');
});
}