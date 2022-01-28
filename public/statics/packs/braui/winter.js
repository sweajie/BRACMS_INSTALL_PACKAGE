require(['pixi' , 'pixi-particles'] , function (PIXI) {
//Create a Pixi Application
    const app = new PIXI.Application({
        resizeTo: window,
        autoDensity: true,
        width: 800, height: 600,
        backgroundColor: 0x000000,
        resolution: window.devicePixelRatio || 1,
        transparent: false,
        backgroundAlpha : 0
    });
    document.getElementById('container').appendChild(app.view);

    const container = new PIXI.Container();

    app.stage.addChild(container);

    const bg = PIXI.Sprite.from('https://img.zcool.cn/community/015a785e15a91aa80120a89502c624.jpg@3000w_1l_2o_100sh.jpg');

    container.addChild(bg);
    // Create a new emitter
// note: if importing library like "import * as particles from 'pixi-particles'"
// or "const particles = require('pixi-particles')", the PIXI namespace will
// not be modified, and may not exist - use "new particles.Emitter()", or whatever
// your imported namespace is

    var emitter = new PIXI.particles.Emitter(

        // The PIXI.Container to put the emitter in
        // if using blend modes, it's important to put this
        // on top of a bitmap, and not use the root stage Container

        container,

        // The collection of particle images to use
        [PIXI.Texture.fromImage('/statics/pixi/snow100.png')],

        // Emitter configuration, edit this to change the look of the emitter
        {
            "alpha": {
                "start": 0.73,
                "end": 0.46
            },
            "scale": {
                "start": 0.15,
                "end": 0.3,
                "minimumScaleMultiplier":0.5
            },
            "color": {
                "start": "ffffff",
                "end": "ffffff"
            },
            "speed": {
                "start": 200,
                "end": 200
            },
            "startRotation": {
                "min": 50,
                "max": 70
            },
            "rotationSpeed": {
                "min": 0,
                "max": 200
            },
            "lifetime": {
                "min": 4,
                "max": 20
            },
            "blendMode": "normal",
            "ease": [
                {
                    "s": 0,
                    "cp": 0.379,
                    "e": 0.548
                },
                {
                    "s": 0.548,
                    "cp": 0.717,
                    "e": 0.676
                },
                {
                    "s": 0.676,
                    "cp": 0.635,
                    "e": 1
                }
            ],
            "frequency": 0.01,
            "emitterLifetime": 0,
            "maxParticles": 800,
            "pos": {
                "x": 0,
                "y": 0
            },
            "addAtBack": false,
            "spawnType": "rect",
            "spawnRect": {
                "x": -1000,
                "y": 0,
                "w": 5000,
                "h": 20
            }
        }
    );

    var elapsed = Date.now();// Calculate the current time

    var update = function(){// Update function every frame
        requestAnimationFrame(update);// Update the next frame
        var now = Date.now();// The emitter requires the elapsed
        emitter.update((now - elapsed) * 0.001);// number of seconds since the last update
        elapsed = now;
        // Should re-render the PIXI Stage
        // renderer.render(stage);
    };
    emitter.emit = true;// Start emitting
    update();// Start the update
});
