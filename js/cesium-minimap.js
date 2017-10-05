/*!
 * Cesium-minimap
 * Initial version from : https://github.com/knreise/cesium-minimap
 * Modified by Ycarus for Zugaina
 * Licensed under: BSD-3-Clause
 */
/*global Cesium:false*/

function CesiumMiniMap(parentViewer, options) {
    'use strict';

    options = options || {};
    var expanded = options.expanded || true;
    var _osm = options.osm || true;
    var _viewer, _container, _toggleButton;

    var CESIUM_OPTS = {
        animation: false,
        baseLayerPicker: false,
        fullscreenButton: false,
        geocoder: false,
        homeButton: false,
        infoBox: false,
        sceneModePicker: false,
        selectionIndicator: false,
        timeline: false,
        navigationHelpButton: false,
        navigationInstructionsInitiallyVisible: false,
        orderIndependentTranslucency: false,
        sceneMode: Cesium.SceneMode.SCENE2D,
        mapProjection: new Cesium.WebMercatorProjection()
    };

    function _getContainer() {
        var parentDiv = document.createElement('div');
        parentDiv.className = 'cesium-minimap';
        parentViewer.bottomContainer.appendChild(parentDiv);
        return parentDiv;
    }

    function _addLayer(layer) {
        _viewer.imageryLayers.addImageryProvider(layer.imageryProvider);
    }

    function _setupMap(div) {

        CESIUM_OPTS.creditContainer = document.createElement('div');

        var miniviewer = new Cesium.Viewer(div, CESIUM_OPTS);
        miniviewer.scene.imageryLayers.removeAll();

        var miniscene = miniviewer.scene;
        miniscene.screenSpaceCameraController.enableRotate = false;
        miniscene.screenSpaceCameraController.enableTranslate = false;
        miniscene.screenSpaceCameraController.enableZoom = false;
        miniscene.screenSpaceCameraController.enableTilt = false;
        miniscene.screenSpaceCameraController.enableLook = false;

        if (!_osm) {
	    parentViewer.scene.imageryLayers.layerAdded.addEventListener(_addLayer);
	} else {
	    var imProvOSM = Cesium.createOpenStreetMapImageryProvider({
		url : 'https://a.tile.openstreetmap.org/'
	    });
	    miniviewer.scene.imageryLayers.addImageryProvider(imProvOSM);
        }

        var pos = parentViewer.scene.camera.positionCartographic;
	//pos.height = 2200000.0;
	pos.height = Math.max(Math.min(pos.height,2200000) * 2, 2000);
        miniviewer.scene.camera.setView({
            destination: Cesium.Ellipsoid.WGS84.cartographicToCartesian(pos),
        });

        _viewer = miniviewer;
    }

    function _setupListener() {
        var minicamera = _viewer.scene.camera;
        var parentCamera = parentViewer.scene.camera;
        parentCamera.percentageChanged = 0.001;
        parentCamera.changed.addEventListener(function () {
            var pos = parentCamera.positionCartographic;
	    pos.height = Math.max(Math.min(pos.height,2200000) * 2, 2000);
            minicamera.setView({
                destination: Cesium.Ellipsoid.WGS84.cartographicToCartesian(pos),
                orientation: {
            	    heading : parentCamera.heading,
            	    pitch : parentCamera.pitch
            	}
            });
        });
    }

    function _toggle() {
        expanded = !expanded;

        if (expanded) {
            _container.style.width = '150px';
            _container.style.height = '150px';
            _toggleButton.className = _toggleButton.className.replace(
                ' minimized',
                ''
            );
        } else {
            //close
            _container.style.width = '19px';
            _container.style.height = '19px';
            _toggleButton.className += ' minimized';
        }
    }

    function _createToggleButton() {
        var btn = document.createElement('a');
        btn.className = 'minimap-toggle-display';
        btn.onclick = function (e) {
            e.preventDefault();
            _toggle();
            return false;
        };
        return btn;
    }


    function init() {
        var div = document.createElement('div');
        div.className = 'minimap-container';

        _container = _getContainer();
        _container.appendChild(div);
        _toggleButton = _createToggleButton();
        _container.appendChild(_toggleButton);
        _setupMap(div);
        _setupListener();
        if (!_osm && parentViewer.imageryLayers.length) {
            _addLayer(parentViewer.imageryLayers.get(0));
        }
    }

    init();
}