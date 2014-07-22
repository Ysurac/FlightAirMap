var map;
var user = new L.FeatureGroup();
$( document ).ready(function() {

  //create the map
  map = L.map('map', { zoomControl:false }).setView([44.413333,-79.68], 9);

  //initialize the layer group for the aircrft markers
  var layer_data = L.layerGroup();

  //a few title layers
  L.tileLayer('https://{s}.tiles.mapbox.com/v3/{id}/{z}/{x}/{y}.png', {
    maxZoom: 18,
    attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, ' +
      '<a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, ' +
      'Imagery © <a href="http://mapbox.com">Mapbox</a>',
    id: 'examples.map-i86knfo3'
  }).addTo(map);

  //create the bounding box to show the coverage area
  var polygon = L.polygon(
    [ [[90, -180],
    [90, 180],
    [-90, 180],
    [-90, -180]], // outer ring
    [[44.067853669357596, -80.22216796875],
    [44.067853669357596, -79.06036376953125],
    [44.734052347483086, -79.06036376953125],
    [44.734052347483086, -80.22216796875]] // actual cutout polygon
    ],{
    color: '#000',
    fillColor: '#000',
    fillOpacity: 0.1,
    stroke: false
    }).addTo(map);

  function getLiveData()
  {
    map.removeLayer(layer_data);
    layer_data = L.layerGroup();

    $.ajax({
      dataType: "json",
      url: "live/geojson?"+Math.random(),
      success: function(data) {

          var live_data = L.geoJson(data, {
            pointToLayer: function (feature, latLng) {
              return new L.Marker(latLng, {
                iconAngle: feature.properties.heading,
                title: feature.properties.callsign,
                alt: feature.properties.callsign,
                icon: L.icon({
                  iconUrl: '/images/map-icon-shadow.png',
                  iconRetinaUrl: '/images/map-icon-shadow@2x.png',
                  iconSize: [40, 40],
                  iconAnchor: [20, 40]
                })
              })
            },
            onEachFeature: function (feature, layer) {
              var output = '';
              if (feature.properties.type == "aircraft"){

                output += '<div class="top">';
                  output += '<div class="left"><a href="/ident/'+feature.properties.callsign+'" target="_blank"><img src="'+feature.properties.image+'" alt="'+feature.properties.registration+' '+feature.properties.aircraft_name+'" title="'+feature.properties.registration+' '+feature.properties.aircraft_name+'" /></a></div>';
                  output += '<div class="right">';
                    output += '<div class="callsign-details">';
                      output += '<div class="callsign"><a href="/ident/'+feature.properties.callsign+'" target="_blank">'+feature.properties.callsign+'</a></div>';
                      output += '<div class="airline">'+feature.properties.airline_name+'</div>';
                    output += '</div>';
                    output += '<div class="airports">';
                      output += '<div class="airport">';
                        output += '<span class="code"><a href="/airport/'+feature.properties.departure_airport_code+'" target="_blank">'+feature.properties.departure_airport_code+'</a></span><br />'+feature.properties.departure_airport;
                      output += '</div>';
                      output += '<i class="fa fa-long-arrow-right"></i>';
                      output += '<div class="airport">';
                        output += '<span class="code"><a href="/airport/'+feature.properties.arrival_airport_code+'" target="_blank">'+feature.properties.arrival_airport_code+'</a></span><br />'+feature.properties.arrival_airport;
                      output += '</div>';
                    output += '</div>';
                  output += '</div>';
                output += '</div>';

                output += '<div class="details">';
                    output += '<div>';
                      output += '<span>Aircraft</span>';
                      output += feature.properties.aircraft_name;
                    output += '</div>';
                    if (feature.properties.altitude != "" || feature.properties.altitude != 0)
                    {
                      output += '<div>';
                        output += '<span>Altitude</span>';
                        output += feature.properties.altitude+'00 feet (FL'+feature.properties.altitude+')';
                      output += '</div>';
                    }
                    if (feature.properties.registration != "")
                    {
                      output += '<div>';
                        output += '<span>Registration</span>';
                        output += feature.properties.registration;
                      output += '</div>';
                    }
                    output += '<div>';
                      output += '<span>Speed</span>';
                      output += feature.properties.ground_speed+' knots';
                    output += '</div>';
                    output += '<div class="bottom">';
                      output += '<span>Coordinates</span>';
                      output += feature.properties.latitude+", "+feature.properties.longitude;
                    output += '</div>';
                    output += '<div class="bottom">';
                      output += '<span>Heading</span>';
                      output += feature.properties.heading;
                    output += '</div>';
                output += '</div>';

                output += '</div>';

                layer.bindPopup(output);

                layer_data.addLayer(layer);
               }

             }
          });
          layer_data.addTo(map);
        }
    }).error(function() {});
  }

  //load the function on startup
  getLiveData();

  //then load it again every 30 seconds
  setInterval(function(){getLiveData()},60000);
});


//zooms in the map
function zoomInMap(){
  var zoom = map.getZoom();
  map.setZoom(zoom + 1);
}

//zooms in the map
function zoomOutMap(){
  var zoom = map.getZoom();
  map.setZoom(zoom - 1);
}

//figures out the user's location
function getUserLocation(){
  //if the geocode is currently active then disable it, otherwise enable it
  if (!$(".geocode").hasClass("active"))
  {
    //add the active class
    $(".geocode").addClass("active");
    //check to see if geolocation is possible in the browser
    if (navigator.geolocation) {
        //gets the current position and calls a function to make use of it
        navigator.geolocation.getCurrentPosition(showPosition);
    } else {
        //if the geolocation is not supported by the browser let the user know
        alert("Geolocation is not supported by this browser.");
        //remove the active class
        $(".geocode").removeClass("active");
    }
  } else {
    //remove the user location marker
    removeUserPosition();
  }
}

//plots the users location on the map
function showPosition(position) {
    //creates a leaflet marker based on the coordinates we got from the browser and add it to the map
    var markerUser = L.marker([position.coords.latitude, position.coords.longitude], {
        title: "Your location",
        alt: "Your location",
        icon: L.icon({
          iconUrl: '/images/map-user.png',
          iconRetinaUrl: '/images/map-user@2x.png',
          iconSize: [40, 40],
          iconAnchor: [20, 40]
        })
    });
    user.addLayer(markerUser);
    map.addLayer(user);
    //pan the map to the users location
    map.panTo([position.coords.latitude, position.coords.longitude]);
}

//removes the user postion off the map
function removeUserPosition(){
  //remove the marker off the map
  map.removeLayer(user);
  //remove the active class
  $(".geocode").removeClass("active");
}

//determines the users heading based on the iphone
function getCompassDirection(){

  //if the compass is currently active then disable it, otherwise enable it
  if (!$(".compass").hasClass("active"))
  {
    //add the active class
    $(".compass").addClass("active");
    //check to see if the device orietntation event is possible on the browser
    if (window.DeviceOrientationEvent) {
      //first lets get the user location to mak it more user friendly
      getUserLocation();
      //disable dragging the map
      map.dragging.disable();
      //disable double click zoom
      map.doubleClickZoom.disable();
      //disable touch zoom
      map.touchZoom.disable();
      //add event listener for device orientation and call the function to actually get the values
      window.addEventListener('deviceorientation', capture_orientation, false);
    } else {
      //if the browser is not capable for device orientation let the user know
      alert("Compass is not supported by this browser.");
      //remove the active class
      $(".compass").removeClass("active");
    }
  } else {
    //remove the event listener to disable the device orientation
    window.removeEventListener('deviceorientation', capture_orientation, false);
    //reset the orientation to be again north to south
    $("#map").css({ WebkitTransform: 'rotate(360deg)'});
    $("#map").css({'-moz-transform': 'rotate(360deg)'});
    $("#map").css({'-ms-transform': 'rotate(360deg)'});
    //remove the active class
    $(".compass").removeClass("active");
    //remove the user location marker
    removeUserPosition();
    //enable dragging the map
    map.dragging.enable();
    //enable double click zoom
    map.doubleClickZoom.enable();
    //enable touch zoom
    map.touchZoom.enable();
  }

}

//gets the users heading information
function capture_orientation (event) {
 //store the values of each of the recorded elements in a variable
 //to learn more about the below variables see this blog: http://dev.opera.com/articles/w3c-device-orientation-api/
 var alpha = event.alpha;
 var beta = event.beta;
 var gamma = event.gamma;

  //we use the "alpha" variable for the rotation effect
  $("#map").css({ WebkitTransform: 'rotate(' + alpha + 'deg)'});
  $("#map").css({'-moz-transform': 'rotate(' + alpha + 'deg)'});
  $("#map").css({'-ms-transform': 'rotate(' + alpha + 'deg)'});
}
