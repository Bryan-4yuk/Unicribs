
    // Initialize Three.js scene
    function init3DModel() {
        // Get the container
        const container = document.getElementById('3d-model-container');
        
        // Create scene
        const scene = new THREE.Scene();
        scene.background = new THREE.Color(0x000000); // Transparent background
        
        // Create camera
        const camera = new THREE.PerspectiveCamera(75, container.clientWidth / container.clientHeight, 0.1, 1000);
        camera.position.z = 5;
        
        // Create renderer
        const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
        renderer.setSize(container.clientWidth, container.clientHeight);
        container.appendChild(renderer.domElement);
        
        // Add lights
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
        scene.add(ambientLight);
        
        const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
        directionalLight.position.set(0, 1, 1);
        scene.add(directionalLight);
        
        // Add orbit controls
        const controls = new THREE.OrbitControls(camera, renderer.domElement);
        controls.enableDamping = true;
        controls.dampingFactor = 0.25;
        
        // Load GLTF model
        const loader = new THREE.GLTFLoader();
        let model;
        
        loader.load(
            'model.gltf', // path to your model
            function (gltf) {
                model = gltf.scene;
                scene.add(model);
                
                // Scale and position the model if needed
                model.scale.set(1, 1, 1);
                model.position.set(0, 0, 0);
                
                // Start animation if the model has animations
                if (gltf.animations && gltf.animations.length) {
                    const mixer = new THREE.AnimationMixer(model);
                    const action = mixer.clipAction(gltf.animations[0]);
                    action.play();
                    
                    // Animation loop
                    const clock = new THREE.Clock();
                    
                    function animate() {
                        requestAnimationFrame(animate);
                        const delta = clock.getDelta();
                        mixer.update(delta);
                        controls.update();
                        renderer.render(scene, camera);
                    }
                    
                    animate();
                } else {
                    // Simple rotation if no animations
                    function animate() {
                        requestAnimationFrame(animate);
                        model.rotation.y += 0.01;
                        controls.update();
                        renderer.render(scene, camera);
                    }
                    
                    animate();
                }
            },
            undefined,
            function (error) {
                console.error('An error happened loading the GLTF model:', error);
            }
        );
        
        // Handle window resize
        window.addEventListener('resize', function() {
            camera.aspect = container.clientWidth / container.clientHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(container.clientWidth, container.clientHeight);
        });
    }
    
    // Initialize when the page loads
    window.addEventListener('load', init3DModel);
