<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <title>FOXEL | Demonstration // Point Cloud</title>
    <link rel="shortcut icon" type="image/x-icon" href="http://foxel.ch/favicon.ico" />
    <style>canvas { width: 100%; height: 100% }</style>
</head>
<body style="overflow:hidden;margin: 0; padding: 0">

    <style type="text/css">

    body {
        font-family:arial,helvetica,sans;
        font-size:12px;
    }

    #renderArea{
        width: 100%;
        height: 100%;
    }

    .info{
        color:      white;
        font-weight: bold;
        text-shadow:  1px  1px 1px black,
                      1px -1px 1px black,
                     -1px  1px 1px black,
                     -1px -1px 1px black;
    }

    a:hover, a:visited, a:link, a:active{
        color: #ccccff;
        text-decoration: none;
    }

    </style>

<script id="vs" type="x-shader/x-vertex">
varying vec2 vUv;

void main() {
    vUv = uv;

    gl_Position =   projectionMatrix *
                    modelViewMatrix *
                    vec4(position,1.0);
}
</script>

<script id="fs" type="x-shader/x-vertex">

#extension GL_EXT_frag_depth : enable

uniform sampler2D depthMap;
uniform sampler2D texture;

varying vec2 vUv;

void main() {
    vec4 color = texture2D(texture, vUv);
    float depth = texture2D(depthMap, vUv).g;
    color = color / color.w;
    gl_FragColor = color;

    gl_FragDepthEXT = depth;
}
</script>

    <!--<script src="../libs/plasio/js/laslaz.js"></script>
    <script src="../libs/plasio/vendor/bluebird.js"></script>-->
    <script src="../lib/potree/libs/three.js/build/three.js"></script>
    <script src="../lib/potree/libs/other/OrbitControls.js"></script>
    <script src="../lib/potree/libs/other/stats.min.js"></script>
    <script src="../lib/potree/libs/other/dat.gui.min.js"></script>

    <script src="../lib/potree/build/js/potree.js"></script>
    <!--<script src="../build/js/laslaz.js"></script>-->
    <script src="../lib/potree/examples/js/ProgressBar.js"></script>

    <div id="header" style="position:absolute;top:0;left:0;width:100%;height:auto;padding:15px;background-color:#111;">
        <a href="http://foxel.ch/" target="_blank" style="position:relative;top:1px;outline:none;"><img width="71" height="18" alt="FOXEL" src="http://demo.foxel.ch/lib/freepano/example/img/foxel.png" /></a>
    </div>

    <!--<div id="lblNumVisibleNodes" class="info" style="position: absolute; left: 10px; top: 80px; width: 400px; color:white; text-transform:uppercase;"></div>-->
    <div id="lblNumVisiblePoints" class="info" style="position: absolute; left: 15px; top: 60px; width: 300px; color:white; text-transform:uppercase;text-align:left;"></div>
    <!--<div id="lblMessage" class="info" style="position: absolute; left: 10px; bottom: 0px; width: 400px; color:white"></div>-->

    <div id="tools" style="position: fixed; width: 175px; bottom: 10px; right: 10px">

        <img src="../lib/potree/resources/icons/orbit_controls.png"
            onclick="useOrbitControls()"
            title="Orbit Controls"/>

        <img src="../lib/potree/resources/icons/fps_controls.png"
            onclick="useFPSControls()"
            title="Fly Camera"/>

        <img src="../lib/potree/resources/icons/focus.png"
            onclick="camera.zoomTo(pointcloud)"
            title="Focus on PointCloud"/>

<!--
        <img src="../resources/icons/flip_y_z.png"
            onclick="flipYZ()"
            title="Flip Y and Z coordinates"/>
-->

        <img src="../lib/potree/resources/icons/distance.png"
            onclick="measuringTool.setEnabled(true)"
            title="Distance Measurements"/>

        <img src="../lib/potree/resources/icons/area.png"
            onclick="areaTool.setEnabled(true)"
            title="Area Measurements"/>
<!--
        <img src="../resources/icons/volume.png"
            onclick="volumeTool.startInsertion()"
            title="Volume Measurements"/>

        <img src="../resources/icons/profile.png"
            onclick="profileTool.startInsertion({width: pointcloud.boundingSphere.radius / 100})"
            title="Height Profiles"/>

        <img src="../resources/icons/clip_volume.png"
            onclick="volumeTool.startInsertion({clip: true})"
            title="Clipping Volumes"/>
-->

    </div>

    <div id="renderArea"></div>

    <script>
        var pointSize = 0.1 ;
        var pointCountTarget = 1;
        var opacity = 1;
        var pointSizeType = Potree.PointSizeType.FIXED;
        var pointColorType = Potree.PointColorType.RGB;
        var pointShape = Potree.PointShape.SQUARE;
        var clipMode = Potree.ClipMode.HIGHLIGHT_INSIDE;
        var quality = "Normal";
        var isFlipYZ = false;

        var showStats = true;
        var showBoundingBox = false;

        var fpControls;
        var orbitControls;
        var controls;

        var progressBar = new ProgressBar();

        var pointcloudPath = './ply/';
        <?php if (isset($_GET['ply'])): ?>
            pointcloudPath += '<?php echo $_GET['ply']; ?>';
        <?php endif; ?>
        pointcloudPath += '/cloud.js';

        var elRenderArea = document.getElementById("renderArea");

        var gui;
        var renderer;
        var camera;
        var scene;
        var scenePointCloud;
        var sceneBG, cameraBG;
        var pointcloud;
        var skybox;
        var stats;
        var clock = new THREE.Clock();
        var showSkybox = false;
        var measuringTool;
        var areaTool;
        var volumeTool;
        var transformationTool;
        var referenceFrame;

        function initGUI(){

            // dat.gui
            gui = new dat.GUI({
                height: 5 * 32 - 1,
                width: 350
            });
            gui.close();

            params = {
                "Points (x 1'000'000)": pointCountTarget,
                "Point Size": pointSize,
                "Opacity": opacity,
                "SizeType" : "Fixed",
                "show octree" : false,
                "Materials" : "RGB",
                "Clip Mode": "Highlight Inside",
                "quality": "Normal",
                "skybox": false,
                "stats": showStats,
                "BoundingBox": showBoundingBox
            };

            var pPointSize = gui.add(params, 'Point Size', 0, 5);
            pPointSize.onChange(function(value){
                pointSize = value;
            });

            var pPoints = gui.add(params, 'Points (x 1\'000\'000)', 0.02, 10);
            pPoints.onChange(function(value){
                pointCountTarget = value ;
            });

            var pOpacity = gui.add(params, 'Opacity', 0, 1);
            pOpacity.onChange(function(value){
                opacity = value;
            });

            /*
            var pSizeType = gui.add(params, 'SizeType', [ "Fixed", "Attenuated", "Adaptive"]);
            pSizeType.onChange(function(value){
                if(value === "Fixed"){
                    pointSizeType = Potree.PointSizeType.FIXED;
                }else if(value === "Attenuated"){
                    pointSizeType = Potree.PointSizeType.ATTENUATED;
                }else if(value === "Adaptive"){
                    pointSizeType = Potree.PointSizeType.ADAPTIVE;
                }
            });
            */

            var options = [];
            if(pointcloud.pcoGeometry.pointAttributes === "LAS" || pointcloud.pcoGeometry.pointAttributes === "LAZ"){
                options = [
                "RGB", "Color", "Height", "Intensity", "Intensity Gradient",
                "Classification", "Return Number", "Source",
                "Octree Depth"];
            }else{
                //options = options = [ "RGB", "Color", "Height", "Octree Depth"];
                options = options = [ "RGB", "Height" ];
            }

            pMaterial = gui.add(params, 'Materials',options);
            pMaterial.onChange(function(value){
                if(value === "RGB"){
                    pointColorType = Potree.PointColorType.RGB;
                }else if(value === "Color"){
                    pointColorType = Potree.PointColorType.COLOR;
                }else if(value === "Height"){
                    pointColorType = Potree.PointColorType.HEIGHT;
                }else if(value === "Intensity"){
                    pointColorType = Potree.PointColorType.INTENSITY;
                }else if(value === "Intensity Gradient"){
                    pointColorType = Potree.PointColorType.INTENSITY_GRADIENT;
                }else if(value === "Classification"){
                    pointColorType = Potree.PointColorType.CLASSIFICATION;
                }else if(value === "Return Number"){
                    pointColorType = Potree.PointColorType.RETURN_NUMBER;
                }else if(value === "Source"){
                    pointColorType = Potree.PointColorType.SOURCE;
                }else if(value === "Octree Depth"){
                    pointColorType = Potree.PointColorType.OCTREE_DEPTH;
                }else if(value === "Point Index"){
                    pointColorType = Potree.PointColorType.POINT_INDEX;
                }
            });

            /*
            var pQuality = gui.add(params, 'quality', [ "Normal", "Circles", "Interpolation", "Splats"]);
            pQuality.onChange(function(value){
                quality = value;
            });
            */

            /*
            var pClipMode = gui.add(params, 'Clip Mode', [ "No Clipping", "Clip Outside", "Highlight Inside"]);
            pClipMode.onChange(function(value){
                if(value === "No Clipping"){
                    clipMode = Potree.ClipMode.DISABLED;
                }else if(value === "Clip Outside"){
                    clipMode = Potree.ClipMode.CLIP_OUTSIDE;
                }else if(value === "Highlight Inside"){
                    clipMode = Potree.ClipMode.HIGHLIGHT_INSIDE;
                }
            });
            */

            /*
            var pSykbox = gui.add(params, 'skybox');
            pSykbox.onChange(function(value){
                showSkybox = value;
            });
            */

            /*
            var pStats = gui.add(params, 'stats');
            pStats.onChange(function(value){
                showStats = value;
            });
            */

            /*
            var pBoundingBox = gui.add(params, 'BoundingBox');
            pBoundingBox.onChange(function(value){
                showBoundingBox = value;
            });
            */

            // stats
            stats = new Stats();
            stats.domElement.style.position = 'absolute';
            stats.domElement.style.top = '0px';
            stats.domElement.style.margin = '5px';
            //document.body.appendChild( stats.domElement );
        }

        function initThree(){
            var fov = 75;
            var width = elRenderArea.clientWidth;
            var height = elRenderArea.clientHeight;
            var aspect = width / height;
            var near = 0.1;
            var far = 1000000;

            scene = new THREE.Scene();
            scenePointCloud = new THREE.Scene();
            sceneBG = new THREE.Scene();

            camera = new THREE.PerspectiveCamera(fov, aspect, near, far);
            //camera = new THREE.OrthographicCamera(-50, 50, 50, -50, 1, 100000);
            cameraBG = new THREE.Camera();
            camera.rotation.order = 'ZYX';

            referenceFrame = new THREE.Object3D();
            scenePointCloud.add(referenceFrame);

            renderer = new THREE.WebGLRenderer();
            renderer.setSize(width, height);
            renderer.autoClear = false;
            elRenderArea.appendChild(renderer.domElement);

            //skybox = Potree.utils.loadSkybox("../resources/textures/skybox/");

            // camera and controls
            camera.position.set(-304, 372, 318);
            camera.rotation.y = -Math.PI / 4;
            camera.rotation.x = -Math.PI / 6;
            useOrbitControls();

            // enable frag_depth extension for the interpolation shader, if available
            renderer.context.getExtension("EXT_frag_depth");

            // load pointcloud
            POCLoader.load(pointcloudPath, function(geometry){
                pointcloud = new Potree.PointCloudOctree(geometry);

                pointcloud.material.pointSizeType = Potree.PointSizeType.FIXED;
                pointcloud.material.size = pointSize;
                pointcloud.visiblePointsTarget = pointCountTarget * 1000 * 1000;

                referenceFrame.add(pointcloud);

                referenceFrame.updateMatrixWorld(true);
                var sg = pointcloud.boundingSphere.clone().applyMatrix4(pointcloud.matrixWorld);

                referenceFrame.position.copy(sg.center).multiplyScalar(-1);
                referenceFrame.updateMatrixWorld(true);

                camera.zoomTo(pointcloud, 1);

                flipYZ();

                initGUI();
            });

            //var grid = Potree.utils.createGrid(5, 5, 2);
            //scene.add(grid);

            measuringTool = new Potree.MeasuringTool(scenePointCloud, camera, renderer);
            profileTool = new Potree.ProfileTool(scenePointCloud, camera, renderer);
            areaTool = new Potree.AreaTool(scenePointCloud, camera, renderer);
            volumeTool = new Potree.VolumeTool(scenePointCloud, camera, renderer);
            transformationTool = new Potree.TransformationTool(scenePointCloud, camera, renderer);


            // background
            var texture = THREE.ImageUtils.loadTexture( 'img/black.gif' );
            //var texture = Potree.utils.createBackgroundTexture(512, 512);

            texture.minFilter = texture.magFilter = THREE.NearestFilter;
            texture.minFilter = texture.magFilter = THREE.LinearFilter;

            var bg = new THREE.Mesh(
                new THREE.PlaneBufferGeometry(2, 2, 0),
                new THREE.MeshBasicMaterial({
                    map: texture
                })
            );
            //bg.position.z = -1;
            bg.material.depthTest = false;
            bg.material.depthWrite = false;
            sceneBG.add(bg);

            window.addEventListener( 'keydown', onKeyDown, false );
        }

        function flipYZ(){
            isFlipYZ = !isFlipYZ;

            if(isFlipYZ){
                referenceFrame.matrix.copy(new THREE.Matrix4());
                referenceFrame.applyMatrix(new THREE.Matrix4().set(
                    1,0,0,0,
                    0,0,1,0,
                    0,-1,0,0,
                    0,0,0,1
                ));

            }else{
                referenceFrame.matrix.copy(new THREE.Matrix4());
                referenceFrame.applyMatrix(new THREE.Matrix4().set(
                    1,0,0,0,
                    0,1,0,0,
                    0,0,1,0,
                    0,0,0,1
                ));
            }

            referenceFrame.updateMatrixWorld(true);
            pointcloud.updateMatrixWorld();
            var sg = pointcloud.boundingSphere.clone().applyMatrix4(pointcloud.matrixWorld);
            referenceFrame.position.copy(sg.center).multiplyScalar(-1);
            referenceFrame.updateMatrixWorld(true);
            referenceFrame.position.y -= pointcloud.getWorldPosition().y;
            referenceFrame.updateMatrixWorld(true);
        }

        function onKeyDown(event){
            //console.log(event.keyCode);

            if(event.keyCode === 69){
                // e pressed

                transformationTool.translate();
            }else if(event.keyCode === 82){
                // r pressed

                transformationTool.scale();
            }else if(event.keyCode === 84){
                // r pressed

                transformationTool.rotate();
            }
        };

        function update(){
            if(pointcloud){

                var bbWorld = Potree.utils.computeTransformedBoundingBox(pointcloud.boundingBox, pointcloud.matrixWorld);

                //pointcloud.material.size = pointSize;
                //pointcloud.visiblePointsTarget = pointCountTarget * 1000 * 1000;
                //pointcloud.material.opacity = opacity;
                //pointcloud.material.pointSizeType = pointSizeType;
                ////pointcloud.material.pointColorType = pointColorType;
                //pointcloud.material.pointShape = (quality === "High") ? Potree.PointShape.CIRCLE : pointShape;
                //pointcloud.material.interpolate = (quality === "High");
                //pointcloud.material.heightMin = bbWorld.min.y;
                //pointcloud.material.heightMax = bbWorld.max.y;
                //pointcloud.material.clipMode = clipMode;
                ////pointcloud.material.weighted = false;

                pointcloud.material.clipMode = clipMode;
                pointcloud.material.heightMin = bbWorld.min.y;
                pointcloud.material.heightMax = bbWorld.max.y;
                pointcloud.material.intensityMin = 0;
                pointcloud.material.intensityMax = 200;
                pointcloud.showBoundingBox = showBoundingBox;
                pointcloud.update(camera, renderer);
            }

            if(stats && showStats){
                //document.getElementById("lblNumVisibleNodes").style.display = "";
                document.getElementById("lblNumVisiblePoints").style.display = "";
                stats.domElement.style.display = "";

                stats.update();

                if(pointcloud){
                    //document.getElementById("lblNumVisibleNodes").innerHTML = "visible nodes: " + pointcloud.numVisibleNodes;
                    document.getElementById("lblNumVisiblePoints").innerHTML = "visible points: " + Potree.utils.addCommas(pointcloud.numVisiblePoints);
                }
            }else if(stats){
                //document.getElementById("lblNumVisibleNodes").style.display = "none";
                document.getElementById("lblNumVisiblePoints").style.display = "none";
                stats.domElement.style.display = "none";
            }

            controls.update(clock.getDelta());

            // update progress bar
            if(pointcloud){
                var progress = pointcloud.visibleNodes.length / pointcloud.visibleGeometry.length;

                progressBar.progress = progress;
                progressBar.message = "Loading: " + pointcloud.visibleNodes.length + " / " + pointcloud.visibleGeometry.length;

                if(progress === 1){
                    progressBar.hide();
                }else if(progress < 1){
                    progressBar.show();
                }
            }else{
                progressBar.show();
                progressBar.message = "Loading MetaData";
            }

            volumeTool.update();
            transformationTool.update();
            profileTool.update();


            var clipBoxes = [];

            //var profiles = [];
            //for(var i = 0; i < profileTool.profiles.length; i++){
            //  profiles.push(profileTool.profiles[i]);
            //}
            //if(profileTool.activeProfile){
            //  profiles.push(profileTool.activeProfile);
            //}
            for(var i = 0; i < profileTool.profiles.length; i++){
                var profile = profileTool.profiles[i];

                for(var j = 0; j < profile.boxes.length; j++){
                    var box = profile.boxes[j];
                    box.updateMatrixWorld();
                    var boxInverse = new THREE.Matrix4().getInverse(box.matrixWorld);
                    clipBoxes.push(boxInverse);
                }
            }



            for(var i = 0; i < volumeTool.volumes.length; i++){
                var volume = volumeTool.volumes[i];

                if(volume.clip){
                    volume.updateMatrixWorld();
                    var boxInverse = new THREE.Matrix4().getInverse(volume.matrixWorld);

                    clipBoxes.push(boxInverse);
                }
            }

            if(pointcloud){
                pointcloud.material.setClipBoxes(clipBoxes);
            }

        }

        function useFPSControls(){
            if(controls){
                controls.enabled = false;
            }
            if(!fpControls){
                fpControls = new THREE.FirstPersonControls(camera, renderer.domElement);
            }

            controls = fpControls;
            controls.enabled = true;

            controls.moveSpeed = pointcloud.boundingSphere.radius / 2;
        }

        function useOrbitControls(){
            if(controls){
                controls.enabled = false;
            }
            if(!orbitControls){
                orbitControls = new THREE.OrbitControls(camera, renderer.domElement);
            }

            controls = orbitControls;
            controls.enabled = true;

            if(pointcloud){
                controls.target.copy(pointcloud.boundingSphere.center.clone().applyMatrix4(pointcloud.matrixWorld));
            }
        }

        function render(){
            // resize
            var width = elRenderArea.clientWidth;
            var height = elRenderArea.clientHeight;
            var aspect = width / height;

            camera.aspect = aspect;
            camera.updateProjectionMatrix();

            renderer.setSize(width, height);


            // render skybox
            if(showSkybox){
                skybox.camera.rotation.copy(camera.rotation);
                renderer.render(skybox.scene, skybox.camera);
            }else{
                renderer.render(sceneBG, cameraBG);
            }

            if(pointcloud){
                if(pointcloud.originalMaterial){
                    pointcloud.material = pointcloud.originalMaterial;
                }

                var bbWorld = Potree.utils.computeTransformedBoundingBox(pointcloud.boundingBox, pointcloud.matrixWorld);

                pointcloud.material.size = pointSize;
                pointcloud.visiblePointsTarget = pointCountTarget * 1000 * 1000;
                pointcloud.material.opacity = opacity;
                pointcloud.material.pointColorType = pointColorType;
                pointcloud.material.pointSizeType = pointSizeType;
                pointcloud.material.pointShape = (quality === "Circles") ? Potree.PointShape.CIRCLE : Potree.PointShape.SQUARE;
                pointcloud.material.interpolate = (quality === "Interpolation");
                pointcloud.material.weighted = false;
            }

            // render scene
            renderer.render(scene, camera);
            renderer.render(scenePointCloud, camera);

            profileTool.render();
            volumeTool.render();

            renderer.clearDepth();
            measuringTool.render();
            areaTool.render();
            transformationTool.render();
        }

        // high quality rendering using splats
        //
        var rtDepth = new THREE.WebGLRenderTarget( 1024, 1024, {
            minFilter: THREE.NearestFilter,
            magFilter: THREE.NearestFilter,
            format: THREE.RGBAFormat,
            type: THREE.FloatType
        } );
        var rtNormalize = new THREE.WebGLRenderTarget( 1024, 1024, {
            minFilter: THREE.LinearFilter,
            magFilter: THREE.NearestFilter,
            format: THREE.RGBAFormat,
            type: THREE.FloatType
        } );

        var sceneNormalize;

        var depthMaterial, weightedMaterial;

        // render with splats
        function renderHighQuality(){

            if(!sceneNormalize){
                sceneNormalize = new THREE.Scene();

                var vsNormalize = document.getElementById('vs').innerHTML;
                var fsNormalize = document.getElementById('fs').innerHTML;

                var uniformsNormalize = {
                    depthMap: { type: "t", value: rtDepth },
                    texture: { type: "t", value: rtNormalize }
                };

                var materialNormalize = new THREE.ShaderMaterial({
                    uniforms: uniformsNormalize,
                    vertexShader: vsNormalize,
                    fragmentShader: fsNormalize
                });

                var quad = new THREE.Mesh( new THREE.PlaneBufferGeometry(2, 2, 0), materialNormalize);
                quad.material.depthTest = true;
                quad.material.depthWrite = true;
                quad.material.transparent = true;
                sceneNormalize.add(quad);

            }

            // resize
            var width = elRenderArea.clientWidth;
            var height = elRenderArea.clientHeight;
            var aspect = width / height;

            camera.aspect = aspect;
            camera.updateProjectionMatrix();

            renderer.setSize(width, height);
            rtDepth.setSize(width, height);
            rtNormalize.setSize(width, height);


            renderer.clear();
            //renderer.render(sceneBG, cameraBG);
            // render skybox
            if(showSkybox){
                skybox.camera.rotation.copy(camera.rotation);
                renderer.render(skybox.scene, skybox.camera);
            }else{
                renderer.render(sceneBG, cameraBG);
            }
            renderer.render(scene, camera);

            if(pointcloud){
                if(!weightedMaterial){
                    pointcloud.originalMaterial = pointcloud.material;
                    depthMaterial = new Potree.PointCloudMaterial();
                    weightedMaterial = new Potree.PointCloudMaterial();
                }

                pointcloud.material = depthMaterial;

                var bbWorld = Potree.utils.computeTransformedBoundingBox(pointcloud.boundingBox, pointcloud.matrixWorld);

                // get rid of this
                pointcloud.material.size = pointSize;
                pointcloud.visiblePointsTarget = pointCountTarget * 1000 * 1000;
                pointcloud.material.opacity = opacity;
                pointcloud.material.pointSizeType = pointSizeType;
                pointcloud.material.pointColorType = Potree.PointColorType.DEPTH;
                pointcloud.material.pointShape = Potree.PointShape.CIRCLE;
                pointcloud.material.interpolate = (quality === "Interpolate");
                pointcloud.material.weighted = false;

                pointcloud.material.minSize = 2;
                pointcloud.material.screenWidth = width;
                pointcloud.material.screenHeight = height;

                pointcloud.update(camera, renderer);

                renderer.clearTarget( rtDepth, true, true, true );
                renderer.clearTarget( rtNormalize, true, true, true );

                var origType = pointcloud.material.pointColorType;
                renderer.render(scenePointCloud, camera, rtDepth);

                pointcloud.material = weightedMaterial;



                // get rid of this
                pointcloud.material.size = pointSize;
                pointcloud.visiblePointsTarget = pointCountTarget * 1000 * 1000;
                pointcloud.material.opacity = opacity;
                pointcloud.material.pointSizeType = pointSizeType;
                pointcloud.material.pointColorType = pointColorType;
                pointcloud.material.pointShape = Potree.PointShape.CIRCLE;
                pointcloud.material.interpolate = (quality === "Interpolation");
                pointcloud.material.weighted = true;

                pointcloud.material.depthMap = rtDepth;
                pointcloud.material.blendDepth = Math.min(pointcloud.material.spacing, 20);
                pointcloud.update(camera, renderer);
                renderer.render(scenePointCloud, camera, rtNormalize);


                volumeTool.render();
                profileTool.render();
                renderer.render(sceneNormalize, cameraBG);


                renderer.clearDepth();
                measuringTool.render();
                areaTool.render();
                transformationTool.render();

            }


        }

        function loop() {
            requestAnimationFrame(loop);

            update();

            if(quality === "Splats"){
                renderHighQuality();
            }else{
                render();
            }

        };



        initThree();
        //initGUI();
        loop();

    </script>

</body>
</html>
