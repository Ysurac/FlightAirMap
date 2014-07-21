$( document ).ready(function() {

  //create the map
  var map = L.map('map').setView([44.413333,-79.68], 9);

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
    fillOpacity: 0.2,
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
                  iconUrl: '/images/map-icon.png',
                  iconRetinaUrl: '/images/map-icon@2x.png',
                  iconSize: [40, 40],
                  iconAnchor: [20, 40]
                })
              })
            },
            onEachFeature: function (feature, layer) {
              var output = '';
              if (feature.properties.type == "aircraft"){

                output += '<div class="image"><a href="/ident/'+feature.properties.callsign+'" target="_blank"><img src="'+feature.properties.image+'" alt="'+feature.properties.registration+' '+feature.properties.aircraft_name+'" title="'+feature.properties.registration+' '+feature.properties.aircraft_name+'" /></a></div>';
                output += '<div class="callsign-details">';
                  output += '<div class="callsign"><a href="/ident/'+feature.properties.callsign+'" target="_blank">'+feature.properties.callsign+'</a></div>';
                  output += '<div class="airline">'+feature.properties.airline_name+'</div>';
                output += '</div>';

                output += '<div class="details">';

                  output += '<div class="airports">';
                    output += '<div class="airport">';
                      output += '<span class="code"><a href="/airport/'+feature.properties.departure_airport_code+'" target="_blank">'+feature.properties.departure_airport_code+'</a></span><br />'+feature.properties.departure_airport;
                    output += '</div>';
                    output += '<i class="fa fa-long-arrow-right"></i>';
                    output += '<div class="airport">';
                      output += '<span class="code"><a href="/airport/'+feature.properties.arrival_airport_code+'" target="_blank">'+feature.properties.arrival_airport_code+'</a></span><br />'+feature.properties.arrival_airport;
                    output += '</div>';
                  output += '</div>';

                  output += '<div class="little-details">';
                    output += '<div>';
                      output += '<span>Aircraft</span>';
                      output += feature.properties.aircraft_name;
                    output += '</div>';
                    if (feature.properties.registration != "")
                    {
                      output += '<div>';
                        output += '<span>Registration</span>';
                        output += feature.properties.registration;
                      output += '</div>';
                    }
                      output += '<div class="beside">';
                      output += '<div>';
                        output += '<span>Altitude</span>';
                        output += 'FL'+feature.properties.altitude;
                      output += '</div>';
                      output += '<div>';
                        output += '<span>Speed</span>';
                        output += feature.properties.ground_speed+' knots';
                      output += '</div>';
                      output += '<div>';
                        output += '<span>Heading</span>';
                        output += feature.properties.heading;
                      output += '</div>';
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
  setInterval(function(){getLiveData()},30000);
});
